<?php
session_start();

// Limpa qualquer buffer/saída pendente antes de começar
if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
}

require_once __DIR__ . '/conexao.php';

// Evitar que avisos/notices quebrem o JSON
@ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

// Sanitiza entrada
$idQuestao = isset($_GET['id_questao']) ? (int)$_GET['id_questao'] : 0;
if ($idQuestao <= 0) {
    $payload = [
        'success' => false,
        'message' => 'Parâmetro id_questao inválido',
        'acertos' => 0,
        'erros' => 0,
        'alternativas' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0],
        'historico' => []
    ];
    $json = json_encode($payload);
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $json;
    exit;
}

// Identifica usuário (0 para anônimo)
$idUsuario = 0;
if (isset($_SESSION['id_usuario'])) {
    $idUsuario = (int)$_SESSION['id_usuario'];
} elseif (isset($_SESSION['user_id'])) {
    $idUsuario = (int)$_SESSION['user_id'];
}

try {
    // Descobrir colunas da tabela respostas_usuarios
    $temAlternativa = false;
    try {
        $desc = $pdo->query("DESCRIBE respostas_usuarios");
        $cols = $desc ? $desc->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        $temAlternativa = in_array('alternativa', $cols, true);
    } catch (Exception $e) {
        // ignore
    }

    // Totais de acertos/erros (somente respostas reais em respostas_usuario)
    $stmtTotais = $pdo->prepare(
        "SELECT 
            SUM(CASE WHEN acertou = 1 THEN 1 ELSE 0 END) AS acertos,
            SUM(CASE WHEN acertou = 0 THEN 1 ELSE 0 END) AS erros
         FROM respostas_usuario
         WHERE id_questao = ? AND id_alternativa IS NOT NULL"
    );
    $stmtTotais->execute([$idQuestao]);
    $totais = $stmtTotais->fetch(PDO::FETCH_ASSOC) ?: ['acertos' => 0, 'erros' => 0];

    // Distribuição por alternativas (geral para a questão) baseada em respostas_usuario (id_alternativa)
    $alternativas = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0];
    try {
        // Buscar alternativas da questão para mapear letras
        $stmt_alternativas = $pdo->prepare(
            "SELECT id_alternativa, texto, eh_correta
        FROM alternativas 
        WHERE id_questao = ? 
             ORDER BY id_alternativa"
        );
        $stmt_alternativas->execute([$idQuestao]);
        $alternativas_questao = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // Mapear IDs das alternativas para letras
        $letras = ['A','B','C','D','E','F','G','H','I','J'];
    $mapa_alternativas = [];
    foreach ($alternativas_questao as $index => $alt) {
            $letra = $letras[$index] ?? (string)($index + 1);
        $mapa_alternativas[$alt['id_alternativa']] = $letra;
    }
    
        // Contagem geral por id_alternativa
        $stmtAlt = $pdo->prepare(
            "SELECT id_alternativa, COUNT(*) AS total
             FROM respostas_usuario
             WHERE id_questao = ? AND id_alternativa IS NOT NULL
             GROUP BY id_alternativa"
        );
        $stmtAlt->execute([$idQuestao]);
        while ($row = $stmtAlt->fetch(PDO::FETCH_ASSOC)) {
            $idAlt = (int)$row['id_alternativa'];
            $letra = $mapa_alternativas[$idAlt] ?? null;
            if ($letra && isset($alternativas[$letra])) {
                $alternativas[$letra] = (int)$row['total'];
            }
        }
    } catch (Exception $e) {
        // mantém zeros
    }

    // Histórico do usuário (se logado) baseado em respostas_usuario (id_alternativa)
    $historico = [];
    if ($idUsuario > 0) {
        try {
            // Garantir mapa de alternativas disponível (pode vir do bloco acima)
            if (!isset($mapa_alternativas)) {
                $stmt_alternativas = $pdo->prepare(
                    "SELECT id_alternativa, texto, eh_correta
                     FROM alternativas 
                     WHERE id_questao = ? 
                     ORDER BY id_alternativa"
                );
                $stmt_alternativas->execute([$idQuestao]);
                $alternativas_questao = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $letras = ['A','B','C','D','E','F','G','H','I','J'];
                $mapa_alternativas = [];
                foreach ($alternativas_questao as $index => $alt) {
                    $letra = $letras[$index] ?? (string)($index + 1);
                    $mapa_alternativas[$alt['id_alternativa']] = $letra;
                }
            }

            $stmtHist = $pdo->prepare(
                "SELECT id_alternativa, acertou, data_resposta
                 FROM respostas_usuario
                 WHERE user_id = ? AND id_questao = ?
                 ORDER BY data_resposta DESC
                 LIMIT 100"
            );
            $stmtHist->execute([$idUsuario, $idQuestao]);
            $rows = $stmtHist->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                $data = '—';
                if (!empty($r['data_resposta'])) {
                    try {
                        $dt = new DateTime($r['data_resposta']);
                        $data = $dt->format('d/m/Y');
                    } catch (Exception $e) {
                        $data = '—';
                    }
                }
                $letra = isset($r['id_alternativa']) ? ($mapa_alternativas[$r['id_alternativa']] ?? '?') : '?';
                // Fallback: tentar coluna 'alternativa' em respostas_usuarios se letra nao encontrada
                if ($letra === '?') {
                    try {
                        $stmtAltLetra = $pdo->prepare(
                            "SELECT alternativa FROM respostas_usuarios 
                             WHERE id_usuario = ? AND id_questao = ? 
                             ORDER BY data_resposta DESC LIMIT 1"
                        );
                        $stmtAltLetra->execute([$idUsuario, $idQuestao]);
                        $rowLetra = $stmtAltLetra->fetch(PDO::FETCH_ASSOC);
                        if ($rowLetra && !empty($rowLetra['alternativa'])) {
                            $cand = strtoupper($rowLetra['alternativa']);
                            if (in_array($cand, ['A','B','C','D','E','F','G','H','I','J'], true)) {
                                $letra = $cand;
                            }
                        }
                    } catch (Exception $e) {
                        // ignora
                    }
                }
                $historico[] = [
                    'alternativa' => $letra,
                    'acertou' => (int)$r['acertou'] === 1,
                    'data' => $data,
                ];
            }
        } catch (Exception $e) {
            // histórico vazio
        }
    }

    $payload = [
        'success' => true,
        'acertos' => (int)($totais['acertos'] ?? 0),
        'erros' => (int)($totais['erros'] ?? 0),
        'alternativas' => $alternativas,
        'historico' => $historico,
    ];
    $json = json_encode($payload);
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $json;
    exit;
} catch (Exception $e) {
    $payload = [
        'success' => false,
        'message' => 'Falha ao carregar estatísticas',
        'error' => $e->getMessage(),
        'acertos' => 0,
        'erros' => 0,
        'alternativas' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0, 'I' => 0, 'J' => 0],
        'historico' => []
    ];
    $json = json_encode($payload);
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo $json;
    exit;
}
?>
