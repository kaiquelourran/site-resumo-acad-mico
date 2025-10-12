<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Funo de log para diagnstico - comentada para produo
/*
function debug_log($message, $data = null) {
    echo "<div style='background:#f8f9fa;border:1px solid #ddd;margin:10px;padding:10px;'>";
    echo "<strong>DEBUG:</strong> " . htmlspecialchars($message);
    if ($data !== null) {
        echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
    }
    echo "</div>";
}
*/

session_start();
require_once __DIR__ . '/conexao.php';

// Captura parmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sem embaralhamento - usar ordem original do banco
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';
$questao_inicial = isset($_GET['questao_inicial']) ? (int)$_GET['questao_inicial'] : 0;

// Busca informaes do assunto
$assunto_nome = 'Todas as Questes';
if ($id_assunto > 0) {
    $stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
    $stmt_assunto->execute([$id_assunto]);
    $assunto_nome = $stmt_assunto->fetchColumn() ?: 'Assunto nao encontrado';
}

// Processar resposta se enviada via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_questao']) && isset($_POST['alternativa_selecionada'])) {
    try {
    $id_questao = (int)$_POST['id_questao'];
    $alternativa_selecionada = $_POST['alternativa_selecionada'];
    
        // Buscar as alternativas da questao para mapear a letra correta
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$id_questao]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        // NO EMBARALHAR - usar ordem original do banco
        
        // Mapear a letra selecionada para o ID da alternativa (ordem original do banco)
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $id_alternativa = null;
        foreach ($alternativas_questao as $index => $alternativa) {
            $letra = $letras[$index] ?? ($index + 1);
            if (strtoupper($letra) === strtoupper($alternativa_selecionada)) {
                $id_alternativa = $alternativa['id_alternativa'];
                break;
            }
        }
        
        // Buscar a alternativa correta para esta questao (ordem original do banco)
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        if ($alternativa_correta && $id_alternativa) {
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        // Inserir ou atualizar resposta
            $user_id = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0); // Usar 0 para anonimo se nao houver usuario
            
            // Verificar se a tabela tem a coluna user_id
            try {
                $stmt_check = $pdo->query("DESCRIBE respostas_usuario");
                $colunas = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                $tem_user_id = in_array('user_id', $colunas);
                
                if ($tem_user_id) {
                    // Usar estrutura com user_id (permitir mltiplas respostas)
                    $stmt_resposta = $pdo->prepare("\n                        INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) \n                        VALUES (?, ?, ?, ?, NOW())\n                        ON DUPLICATE KEY UPDATE\n                            id_alternativa = VALUES(id_alternativa),\n                            acertou = VALUES(acertou),\n                            data_resposta = NOW()\n                    ");
                    $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);

                    // Registrar tentativa individual em respostas_usuarios para ranking semanal (somente usurios logados)
                    if ($user_id > 0) {
                        try {
                            $stmt_salvar = $pdo->prepare("INSERT INTO respostas_usuarios (id_usuario, id_questao, acertou, data_resposta) VALUES (?, ?, ?, NOW())");
                            $stmt_salvar->execute([$user_id, $id_questao, $acertou]);
                        } catch (Exception $e) {
                            error_log("ERRO inserindo em respostas_usuarios (quiz_vertical_filtros): " . $e->getMessage());
                        }
                    }
                    
                    // Log para diagnstico - comentado para produo
                    /*
                    debug_log("Resposta inserida com user_id", [
                        'user_id' => $user_id,
                        'id_questao' => $id_questao,
                        'id_alternativa' => $id_alternativa,
                        'acertou' => $acertou,
                        'filtro_atual' => $filtro_ativo
                    ]);
                    */
                } else {
                    // Usar estrutura sem user_id (permitir mltiplas respostas)
        $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
                    
                    // Log para diagnstico - comentado para produo
                    /*
                    debug_log("Resposta inserida sem user_id", [
                        'id_questao' => $id_questao,
                        'id_alternativa' => $id_alternativa,
                        'acertou' => $acertou,
                        'filtro_atual' => $filtro_ativo
                    ]);
                    */
                }
            } catch (Exception $e) {
                error_log("ERRO ao verificar estrutura da tabela: " . $e->getMessage());
                // Fallback: tentar estrutura simples (permitir mltiplas respostas)
                $stmt_resposta = $pdo->prepare("
                    INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
            }
        
        // Se for uma requisio AJAX, retornar JSON
        if (isset($_POST['ajax_request'])) {
                // Encontrar a letra da alternativa correta (ordem original do banco)
            $letra_correta = '';
            $letras = ['A', 'B', 'C', 'D', 'E'];
            foreach ($alternativas_questao as $index => $alt) {
                if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                    $letra_correta = $letras[$index] ?? ($index + 1);
                    break;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'acertou' => (bool)$acertou,
                'alternativa_correta' => $letra_correta, // Retornar a LETRA, no o ID
                'explicacao' => '', // Explicacao no disponvel na tabela alternativas
                'message' => $acertou ? 'Parabns! Voc acertou!' : 'No foi dessa vez, mas continue tentando!'
                    // 'debug_info' => [
                    //     'filtro_atual' => $filtro_ativo,
                    //     'id_questao' => $id_questao,
                    //     'acertou' => $acertou,
                    //     'user_id' => $user_id
                    // ]
            ]);
            exit;
        }
    } else {
        // Se for uma requisio AJAX, retornar erro
        if (isset($_POST['ajax_request'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar resposta: alternativa no encontrada'
            ]);
            exit;
        }
        }
    } catch (Exception $e) {
        // Se for uma requisio AJAX, retornar erro
        if (isset($_POST['ajax_request'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

// Construir query SQL baseada no filtro
// Detectar suporte a user_id na tabela de respostas e obter user_id atual
$tem_user_id = false;
try {
    $stmt_check = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
    $tem_user_id = in_array('user_id', $colunas);
} catch (Exception $e) {
    // Mantm $tem_user_id = false se no conseguir descrever a tabela
}
$user_id = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0);

if ($filtro_ativo === 'nao-respondidas') {
    // Para "no-respondidas", selecionar apenas questes sem resposta (por usurio quando houver)
    if ($tem_user_id && $user_id !== null) {
    $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                   q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                   a.nome,
                   'nao-respondida' as status_resposta,
                   NULL as id_alternativa
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                WHERE NOT EXISTS (
                    SELECT 1 FROM respostas_usuario ru
                    WHERE ru.id_questao = q.id_questao AND ru.user_id = ?
                )";
        $params = [$user_id];
    } else {
        // Sem coluna user_id (ou sem usurio em sesso): considerar questes sem qualquer resposta
        $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                       q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                       a.nome,
                       'nao-respondida' as status_resposta,
                       NULL as id_alternativa
                FROM questoes q 
                LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                WHERE NOT EXISTS (
                    SELECT 1 FROM respostas_usuario ru
                    WHERE ru.id_questao = q.id_questao
                )";
        $params = [];
    }
} else {
    // Para todos os outros filtros (incluindo "todas"), carregar dados de resposta considerando a ltima resposta por questo
    if ($tem_user_id && $user_id !== null) {
        // Com coluna user_id: considerar a ltima resposta do usurio atual por questo
        $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                       q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                       a.nome,
                       CASE 
                           WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                           WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                           WHEN r.id_questao IS NOT NULL THEN 'respondida'
                           ELSE 'nao-respondida'
                       END as status_resposta,
                       r.id_alternativa
                FROM questoes q 
                LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                LEFT JOIN (
                    SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                    FROM respostas_usuario ru1
                    INNER JOIN (
                        SELECT id_questao, MAX(data_resposta) AS max_data
                        FROM respostas_usuario
                        WHERE user_id = ?
                        GROUP BY id_questao
                    ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                    WHERE ru1.user_id = ?
                ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
        $params = [$user_id, $user_id];
} else {
        // Sem coluna user_id: considerar a ltima resposta geral por questo
    $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                   q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                   a.nome,
                   CASE 
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta,
                   r.id_alternativa
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                LEFT JOIN (
                    SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                    FROM respostas_usuario ru1
                    INNER JOIN (
                        SELECT id_questao, MAX(data_resposta) AS max_data
                        FROM respostas_usuario
                        GROUP BY id_questao
                    ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
$params = [];
    }
}

if ($id_assunto > 0) {
    $sql .= " AND q.id_assunto = ?";
    $params[] = $id_assunto;
}

// Aplicar filtro especfico
switch($filtro_ativo) {
    case 'respondidas':
        $sql .= " AND r.id_questao IS NOT NULL";
        break;
    case 'nao-respondidas':
        // Para no-respondidas, no aplicar filtro adicional pois ja no carregamos respostas
        break;
    case 'certas':
        $sql .= " AND r.acertou = 1";
        break;
    case 'erradas':
        $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
        break;
    case 'todas':
        // Para todas, no aplicar filtro adicional
        break;
}

$sql .= " ORDER BY q.id_questao";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log para diagnstico - comentado para produo
/*
debug_log("Consulta SQL para filtro: " . $filtro_ativo, [
    'tem_user_id' => $tem_user_id,
    'user_id' => $user_id,
    'total_questoes' => count($questoes),
    'sql' => $sql,
    'params' => $params
]);
*/

// Se uma questo inicial foi especificada, reorganizar array
if ($questao_inicial > 0) {
    $questao_inicial_index = -1;
    foreach ($questoes as $index => $questao) {
        if ($questao['id_questao'] == $questao_inicial) {
            $questao_inicial_index = $index;
            break;
        }
    }
    
    if ($questao_inicial_index >= 0) {
        $questoes_reorganizadas = array_slice($questoes, $questao_inicial_index);
        $questoes_reorganizadas = array_merge($questoes_reorganizadas, array_slice($questoes, 0, $questao_inicial_index));
        $questoes = $questoes_reorganizadas;
    }
}

// Funo para obter nome do filtro
function getNomeFiltro($filtro) {
    switch($filtro) {
        case 'todas': return 'Todas as Questes';
        case 'respondidas': return 'Questes Respondidas';
        case 'nao-respondidas': return 'Questes No Respondidas';
        case 'certas': return 'Questes Certas';
        case 'erradas': return 'Questes Erradas';
        default: return 'Questes';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questes - <?php echo htmlspecialchars($assunto_nome); ?> - Resumo Acadmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <link rel="stylesheet" href="alternative-fix.css">
    <style>
        /* Background gradiente azul igual ao da listar_questoes.php */
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
        }

        /* Header da subjects-page idntico ao da listar_questoes.php */
        .subjects-page .header .breadcrumb .header-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 10px 24px rgba(0,114,255,0.12);
            border-radius: 16px;
            padding: 14px 20px 16px 44px;
            position: relative;
        }
        .subjects-page .header .breadcrumb .header-container::before {
            content: "";
            position: absolute;
            left: 16px;
            top: 12px;
            bottom: 12px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .subjects-page .header .breadcrumb-link,
        .subjects-page .header .breadcrumb-current {
            font-size: 1.08rem;
            font-weight: 800;
            color: #111827;
            padding: 10px 14px;
            border-radius: 10px;
            background-color: #FFFFFF;
            border: 1px solid #CFE8FF;
            box-shadow: 0 1px 3px rgba(0,114,255,0.10);
        }
        .subjects-page .header .breadcrumb-current { color: #0057D9; }
        .subjects-page .header .breadcrumb-link:hover {
            background-color: #F0F7FF;
            color: #0057D9;
            border-color: #BBDDFF;
        }
        .subjects-page .header .breadcrumb-separator { color: #6B7280; font-size: 1rem; }

        .subjects-page .header .user-info {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
            margin-bottom: 0 !important;
            animation: none !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .subjects-page .header .user-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: #111827;
            font-weight: 700;
        }
        .subjects-page .header .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 0 3px 8px rgba(0,114,255,0.25);
        }
        .subjects-page .header .user-name {
            font-size: 0.92rem;
            color: #111827;
            margin: 0;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .subjects-page .header .user-name { max-width: 120px; }
        }
        @media (max-width: 480px) {
            .subjects-page .header .user-name { display: none; }
            .subjects-page .header .user-avatar { width: 26px; height: 26px; font-size: 0.85rem; }
        }

        /* Ocultar o boto Entrar na subjects-page */
        .subjects-page .header .header-btn.primary { display: none !important; }

        /* Estilo destacado para o boto Sair */
        .subjects-page .header a.header-btn[href="logout.php"] {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(180deg, #ff4b5a 0%, #dc3545 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(220,53,69,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            letter-spacing: 0;
            font-size: 0.95rem;
        }
        .subjects-page .header a.header-btn[href="logout.php"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(220,53,69,0.40);
            filter: brightness(1.02);
        }
        .subjects-page .header a.header-btn[href="logout.php"]:focus {
            outline: 3px solid rgba(220,53,69,0.45);
            outline-offset: 2px;
        }
        .subjects-page .header a.header-btn[href="logout.php"]::before { content: none; }

        /* Boto 'Ir para o Site' */
        .subjects-page .header a.header-btn.site-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,114,255,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            font-size: 0.95rem;
        }
        .subjects-page .header a.header-btn.site-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,114,255,0.40);
            filter: brightness(1.02);
        }
        .subjects-page .header a.header-btn.site-link:focus {
            outline: 3px solid rgba(0,114,255,0.35);
            outline-offset: 2px;
        }
        
        /* Garantir que as alternativas sejam clicveis */
        .alternative {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 1;
        }
        
        /* Garantir que as alternativas sejam clicveis */
        .alternative {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative !important;
            z-index: 10 !important;
        }
        
        .alternative::before {
            pointer-events: none !important;
            z-index: 0 !important;
        }
        
        .alternative * {
            pointer-events: none;
        }

        /* Destaque para o ttulo e subttulo */
        .subjects-page .page-header .header-container {
            max-width: 1100px;
            margin: 16px auto 24px;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 12px 28px rgba(0,114,255,0.14);
            border-radius: 16px;
            padding: 16px 24px 18px 48px;
            position: relative;
        }
        .subjects-page .page-header .header-container::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 14px;
            bottom: 14px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .subjects-page .page-title {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: 0.3px;
        }
        .subjects-page .page-subtitle {
            margin-top: 8px;
            color: #6B7280;
            font-size: 1.05rem;
        }
        @media (max-width: 768px) {
            .subjects-page .page-title { font-size: 1.45rem; }
            .subjects-page .page-subtitle { font-size: 0.95rem; }
        }

        .questoes-info {
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.08) 0%, rgba(0, 114, 255, 0.08) 100%);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            border: 2px solid rgba(0, 114, 255, 0.15);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 114, 255, 0.1);
        }

        .questoes-info h3 {
            color: #0072FF;
            margin-bottom: 8px;
            font-size: 1.4em;
            font-weight: 700;
        }

        .questoes-info p {
            color: #555;
            margin: 0;
            font-size: 1.05em;
            font-weight: 600;
        }

        .questions-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .question-card {
            background: white;
            border-radius: 14px;
            padding: 0;
            box-shadow: 0 8px 16px rgba(0,0,0,0.06);
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .question-card::before {
            display: none !important;
            content: none !important;
        }

        .question-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0,114,255,0.2);
            border-color: #00C6FF;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
            padding: 10px 18px;
            background: linear-gradient(to top, #00C6FF, #0072FF);
            border-radius: 14px 14px 0 0;
        }

        .question-number {
            font-weight: 700;
            color: #FFFFFF;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .question-number::before {
            content: '';
            font-size: 1em;
        }

        .question-status {
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .status-nao-respondida {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #6c757d;
            border: 2px solid #dee2e6;
        }

        .status-acertada {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-errada {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .question-text {
            font-size: 1.05em;
            line-height: 1.5;
            color: #333;
            margin-bottom: 0;
            padding: 15px 18px;
            background: #FFFFFF;
            font-weight: 500;
        }

        .alternatives-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0 18px 18px 18px;
            background: #FFFFFF;
            margin-bottom: 0;
        }

        .alternative {
            background: #FFFFFF;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            gap: 12px;
        }

        .alternative::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to top, #00C6FF, #0072FF);
            transform: scaleY(0);
            transition: transform 0.3s ease;
            border-radius: 10px 0 0 10px;
        }

        .alternative:hover::before {
            transform: scaleY(1);
        }

        .alternative:hover {
            border-color: #00C6FF;
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(0, 114, 255, 0.15);
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.03) 0%, rgba(0, 114, 255, 0.03) 100%);
        }

        .alternative-letter {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            flex-shrink: 0;
            font-size: 1em;
            box-shadow: 0 3px 10px rgba(0, 114, 255, 0.3);
        }

        .alternative-text {
            flex: 1;
            font-size: 1em;
            line-height: 1.5;
            color: #333;
            font-weight: 500;
        }

        /* Estilos para feedback visual - usando as classes corretas */
        .alternative-correct {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
            border-color: #28a745 !important;
            color: #155724 !important;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3) !important;
        }

        .alternative-correct::before {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4) !important;
        }

        .alternative-incorrect-chosen {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
            border-color: #dc3545 !important;
            color: #721c24 !important;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3) !important;
        }

        .alternative-incorrect-chosen::before {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4) !important;
        }

        /* Estilos para painel de estatsticas */
        .stats-toggle-container {
            padding: 15px 18px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
            position: relative;
            z-index: 1;
        }

        .stats-toggle-btn {
            width: 100%;
            padding: 10px 15px;
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            font-weight: 600;
            color: #0072FF;
        }

        .stats-toggle-btn:hover {
            background: #f0f7ff;
            border-color: #0072FF;
        }

        .stats-toggle-btn.active .fa-chevron-down {
            transform: rotate(180deg);
        }

        .stats-panel {
            padding: 20px 18px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
            position: relative;
            z-index: 1;
        }

        .stats-loading {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-size: 1em;
        }

        .stats-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stats-chart-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stats-chart-item h4 {
            font-size: 0.9em;
            margin: 0 0 15px 0;
            color: #333;
            text-align: center;
        }

        .stats-chart-item canvas {
            max-height: 200px;
        }

        .stats-history {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stats-history h4 {
            font-size: 0.9em;
            margin: 0 0 15px 0;
            color: #333;
        }

        .stats-history-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stats-history-item {
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
        }

        .stats-history-item.correct {
            background: #d4edda;
            color: #155724;
        }

        .stats-history-item.incorrect {
            background: #f8d7da;
            color: #721c24;
        }

        .stats-history-date {
            font-weight: 500;
        }

        .stats-history-result {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .stats-charts {
                grid-template-columns: 1fr;
            }
        }

        @keyframes pulse-green {
            0% { transform: scale(1) translateX(8px); }
            50% { transform: scale(1.03) translateX(8px); }
            100% { transform: scale(1) translateX(8px); }
        }

        @keyframes pulse-red {
            0% { transform: scale(1) translateX(8px); }
            50% { transform: scale(1.03) translateX(8px); }
            100% { transform: scale(1) translateX(8px); }
        }

        .explicacao-container {
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.08) 0%, rgba(0, 114, 255, 0.08) 100%);
            border-left: 4px solid #0072FF;
            padding: 16px 20px;
            margin: 16px 20px 20px 20px;
            border-radius: 0 10px 10px 0;
            opacity: 0;
            animation: fadeIn 0.5s ease-in-out forwards;
            box-shadow: 0 4px 12px rgba(0, 114, 255, 0.1);
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(-15px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .explicacao-title {
            color: #0072FF;
            margin-bottom: 8px;
            font-size: 1em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .explicacao-title::before {
            content: '';
            font-size: 1em;
        }

        .explicacao-text {
            color: #333;
            line-height: 1.6;
            margin: 0;
            font-size: 0.95em;
            font-weight: 500;
        }

        .navigation-section {
            text-align: center;
            padding: 25px 0;
            background: transparent;
            border-radius: 0;
            border: none;
            margin-top: 30px;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
        }

        .progress-info {
            text-align: center;
            color: #667eea;
            font-weight: 600;
            font-size: 1.1em;
            background: white;
            padding: 15px 25px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid #f0f0f0;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .empty-state-icon {
            font-size: 5em;
            margin-bottom: 25px;
            opacity: 0.7;
        }

        .empty-state-title {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .empty-state-text {
            font-size: 1.2em;
            line-height: 1.6;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                border-radius: 20px;
            }
            
            .content-wrapper {
                padding: 25px;
            }
            
            .page-header {
                 padding: 25px;
             }
            
            .page-title {
                 font-size: 2.2em;
             }
            
            .question-card {
                padding: 20px;
            }
            
            .nav-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-btn {
                width: 100%;
                justify-content: center;
            }
            
            .alternative {
                padding: 12px 16px;
            }
            
            .alternative:hover {
                transform: translateX(5px);
            }
        }

        /* Estilos para Comentrios - Baseado nas imagens */
        .comments-section {
            margin-top: 15px;
        }

        .comments-toggle-btn {
            width: 100%;
            padding: 10px 15px;
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            font-weight: 600;
            color: #28a745;
        }

        .comments-toggle-btn:hover {
            background: #f0fff4;
            border-color: #28a745;
        }

        .comments-toggle-btn.active .fa-chevron-down {
            transform: rotate(180deg);
        }

        .comments-panel {
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            position: relative;
            z-index: 1;
            max-height: 600px;
            overflow-y: auto;
        }

        .comments-loading {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-size: 1em;
        }

        .comments-content {
            padding: 20px;
        }

        /* Cabealho com abas */
        .comments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .comments-tabs {
            display: flex;
            gap: 0;
        }

        .tab-btn {
            background: none;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            color: #007bff;
            border-bottom-color: #007bff;
            font-weight: 600;
        }

        .tab-btn:hover {
            color: #007bff;
        }

        .follow-comments {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .follow-comments:hover {
            text-decoration: underline;
        }

        /* Lista de comentrios */
        .comments-list {
            margin-bottom: 20px;
        }

        .comment-item {
            background: white;
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            position: relative;
        }

        .comment-avatar i {
            color: #666;
            font-size: 18px;
        }
        .comment-avatar img, .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .user-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            background: #ffc107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-status i {
            color: white;
            font-size: 8px;
        }

        .comment-author {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .comment-date {
            color: #666;
            font-size: 12px;
            margin-left: auto;
        }

        .comment-text {
            color: #333;
            line-height: 1.5;
            margin: 10px 0;
            font-size: 14px;
        }

        .comment-actions {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 0;
        }

        .action-btn:hover {
            text-decoration: underline;
        }

        .action-btn i {
            font-size: 12px;
        }

        .report-abuse {
            color: #dc3545;
            text-decoration: none;
            font-size: 13px;
        }

        .report-abuse:hover {
            text-decoration: underline;
        }

        /* Boto carregar mais */
        .load-more-section {
            text-align: center;
            margin: 20px 0;
        }

        .load-more-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 10px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .load-more-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        /* Formulrio de comentrio */
        .add-comment-form {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .comment-user-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            position: relative;
        }

        .user-avatar i {
            color: #666;
            font-size: 18px;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .user-status {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 16px;
            height: 16px;
            background: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-status i {
            color: white;
            font-size: 8px;
        }

        .user-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .comment-input-section {
            margin-left: 52px;
        }

        .comment-toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            padding: 8px 0;
        }

        .toolbar-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 4px;
            color: #666;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .toolbar-btn:hover {
            background: #e9ecef;
            color: #333;
        }

        .toolbar-btn.active {
            background: #007bff;
            color: white;
        }

        .color-underline {
            display: inline-block;
            width: 100%;
            height: 2px;
            background: #007bff;
            margin-top: 2px;
        }

        .comment-textarea-container {
            position: relative;
            margin-bottom: 15px;
        }

        .comment-textarea {
            width: 100%;
            min-height: 80px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
        }

        .comment-textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.1);
        }

        .textarea-resize-handle {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 8px;
            height: 8px;
            background: #ccc;
            border-radius: 50%;
            cursor: se-resize;
        }

        .btn-responder {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-responder:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-responder:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Boto fechar */
        .close-comments {
            position: absolute;
            top: 15px;
            right: 15px;
        }

        .close-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .no-comments {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px 20px;
        }

        /* Respostas de comentrios */
        .comment-replies {
            margin-left: 52px;
            margin-top: 15px;
            padding-left: 15px;
            border-left: 2px solid #f0f0f0;
        }
        .comment-replies.collapsed {
            display: none;
        }

        .reply-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body class="subjects-page">
<?php
$breadcrumb_items = [
    ['icon' => '', 'text' => 'Incio', 'link' => 'index.php', 'current' => false],
    ['icon' => '', 'text' => 'Assuntos', 'link' => 'escolher_assunto.php', 'current' => false],
    ['icon' => '', 'text' => 'Lista de Questes', 'link' => 'listar_questoes.php?id=' . $id_assunto . '&filtro=' . $filtro_ativo, 'current' => false],
    ['icon' => '', 'text' => 'Questes', 'link' => '', 'current' => true]
];
$page_title = ' Questes';
$page_subtitle = htmlspecialchars($assunto_nome) . ' - ' . getNomeFiltro($filtro_ativo);
include 'header.php';
?>
    <script>
    // Funo para ajustes de header
    function ajustarHeader() {
        if (!document.body.classList.contains('subjects-page')) return;
        const header = document.querySelector('.header');
        if (!header) return;
        const userInfo = header.querySelector('.user-info');
        if (!userInfo) return;
        // garantir boto Sair
        let logoutBtn = header.querySelector('a.header-btn[href="logout.php"]');
        if (!logoutBtn) {
            const a = document.createElement('a');
            a.href = 'logout.php';
            a.className = 'header-btn';
            a.setAttribute('aria-label', 'Sair da sesso');
            a.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Sair</span>';
            userInfo.appendChild(a);
        }
        // perfil do usurio
        let profile = userInfo.querySelector('.user-profile');
        <?php
        $displayNameSubjects = '';
        foreach ([
            'usuario_nome','usuario','nome','user_name','username','login','nome_usuario','nomeCompleto'
        ] as $k) {
            if (isset($_SESSION[$k]) && trim($_SESSION[$k]) !== '') { $displayNameSubjects = $_SESSION[$k]; break; }
        }
        ?>
        const userName = "<?php echo htmlspecialchars($displayNameSubjects, ENT_QUOTES, 'UTF-8'); ?>";
        if (userName) {
            if (!profile) {
                const p = document.createElement('div');
                p.className = 'user-profile';
                const avatar = document.createElement('div');
                avatar.className = 'user-avatar';
                avatar.textContent = userName.trim().charAt(0).toUpperCase() || '?';
                const nameEl = document.createElement('span');
                nameEl.className = 'user-name';
                nameEl.textContent = userName;
                p.appendChild(avatar);
                p.appendChild(nameEl);
                userInfo.insertBefore(p, userInfo.firstChild);
            }
        }
        const loginBtn = header.querySelector('a.header-btn.primary[href="login.php"]');
        if (loginBtn) loginBtn.style.display = 'none';
        let siteBtn = header.querySelector('a.header-btn.site-link');
        if (!siteBtn) {
            const s = document.createElement('a');
            s.href = '../index.html';
            s.className = 'header-btn site-link';
            s.target = '_blank';
            s.rel = 'noopener';
            s.innerHTML = '<i class="fas fa-globe"></i><span>Ir para o Site</span>';
            userInfo.appendChild(s);
        }
    }
    </script>

            <!-- Informaes das Questes -->
            <div class="questoes-info">
                <h3> <?php echo getNomeFiltro($filtro_ativo); ?></h3>
                <p><?php echo count($questoes); ?> questo(es) disponvel(eis)</p>
            </div>

            <!-- Container das Questes -->
            <?php if (empty($questoes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"></div>
                    <h3 class="empty-state-title">Nenhuma questo encontrada</h3>
                    <p class="empty-state-text">
                        No h questes disponiveis para o filtro selecionado.<br>
                        Volte  lista de questes para selecionar outro filtro.
                    </p>
                </div>
            <?php else: ?>
                <div class="questions-container">
                    <?php 
                     foreach ($questoes as $index => $questao): ?>
                        <div class="question-card" id="questao-<?php echo $questao['id_questao']; ?>">
                            <div class="question-header">
                                <div class="question-number">
                                    Questo #<?php echo $questao['id_questao']; ?>
                                </div>
                                <div class="question-status status-<?php echo $questao['status_resposta']; ?>">
                                    <?php
                                    switch($questao['status_resposta']) {
                                        case 'nao-respondida':
                                            echo ' No Respondida';
                                            break;
                                            case 'certa':
                                                echo ' Acertou';
                                            break;
                                        case 'errada':
                                                echo ' Errou';
                                            break;
                                        default:
                                            echo ' Respondida';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="question-text">
                                <?php echo htmlspecialchars($questao['enunciado']); ?>
                            </div>
                            
                            <form class="questoes-form" data-questao-id="<?php echo $questao['id_questao']; ?>">
                                <input type="hidden" name="id_questao" value="<?php echo $questao['id_questao']; ?>">
                                
                                <div class="alternatives-container">
                                    <?php
                                    // Buscar alternativas da tabela 'alternativas'
                                    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
                                    $stmt_alt->execute([$questao['id_questao']]);
                                    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // NO EMBARALHAR - usar ordem original do banco
                                    
                                    // Mapear as letras corretas (ordem original do banco)
                                    $letras = ['A', 'B', 'C', 'D', 'E'];
                                    $letra_correta = '';
                                    foreach ($alternativas_questao as $index => $alternativa) {
                                        $letra = $letras[$index] ?? ($index + 1);
                                        
                                        // Identificar qual letra corresponde  resposta correta aps embaralhamento
                                        if ($alternativa['eh_correta'] == 1) {
                                            $letra_correta = $letra;
                                        }
                                        
                                        // Identificar alternativa correta
                                        $is_correct = ($alternativa['eh_correta'] == 1);
                                        
                                        // Verificar se esta alternativa foi selecionada pelo usurio
                                        $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
                                        
                                        // Verificar se a questo foi respondida (apenas para filtros que mostram respostas)
                                        $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
                                        
                                        $class = '';
                                        // NO aplicar classes visuais automaticamente - deixar para o JavaScript
                                        // Isso permite que o usurio clique e responda novamente
                                        ?>
                                        <div class="alternative <?php echo $class; ?>" 
                                             data-alternativa="<?php echo $letra; ?>"
                                             data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
                                             data-questao-id="<?php echo $questao['id_questao']; ?>"
                                             data-correta="<?php echo $is_correct ? 'true' : 'false'; ?>">
                                            <div class="alternative-letter"><?php echo $letra; ?></div>
                                            <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </form>
                            
                            <?php if (!empty($questao['explicacao']) && !empty($questao['id_alternativa'])): ?>
                                <div class="explicacao-container">
                                    <div class="explicacao-title"> Explicacao:</div>
                                    <div class="explicacao-text"><?php echo htmlspecialchars($questao['explicacao']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Boto de Estatsticas -->
                            <div class="stats-toggle-container">
                                <button class="stats-toggle-btn" data-questao-id="<?php echo $questao['id_questao']; ?>">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Ver Estatsticas</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>

                            <!-- Painel de Estatsticas -->
                            <div class="stats-panel" id="stats-<?php echo $questao['id_questao']; ?>" style="display: none;">
                                <div class="stats-loading">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando estatsticas...
                                </div>
                                <div class="stats-content" style="display: none;">
                                    <div class="stats-charts">
                                        <div class="stats-chart-item">
                                            <h4>Percentual de Rendimento</h4>
                                            <canvas id="chart-pie-<?php echo $questao['id_questao']; ?>"></canvas>
                                        </div>
                                        <div class="stats-chart-item">
                                            <h4>Alternativas mais respondidas</h4>
                                            <canvas id="chart-bar-<?php echo $questao['id_questao']; ?>"></canvas>
                                        </div>
                                    </div>
                                    <div class="stats-history">
                                        <h4>Histrico de Respostas</h4>
                                        <div class="stats-history-list"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seo de Comentrios -->
                            <div class="comments-section">
                                <button class="comments-toggle-btn" data-questao-id="<?php echo $questao['id_questao']; ?>">
                                    <i class="fas fa-comments"></i>
                                    <span>Comentrios</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>

                            <!-- Painel de Comentrios -->
                            <div class="comments-panel" id="comments-<?php echo $questao['id_questao']; ?>" style="display: none;">
                                <div class="comments-loading">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando comentrios...
                                </div>
                                <div class="comments-content" style="display: none;">
                                    <!-- Cabealho com abas de ordenao -->
                                    <div class="comments-header">
                                        <div class="comments-tabs">
                                            <button class="tab-btn active" data-ordenacao="data">Ordenando por Data</button>
                                            <button class="tab-btn" data-ordenacao="curtidas">Mais curtidos</button>
                                        </div>
                                        <a href="#" class="follow-comments">Acompanhar comentrios</a>
                                    </div>
                                    
                                    <!-- Lista de comentrios -->
                                    <div class="comments-list"></div>
                                    
                                    <!-- Boto carregar mais -->
                                    <div class="load-more-section">
                                        <button class="load-more-btn">Carregar mais</button>
                                    </div>
                                    
                                    <!-- Formulrio para adicionar comentrio -->
                                    <div class="add-comment-form">
                                        <div class="comment-user-info">
                                            <div class="user-avatar">
                                                <?php $avatar_url = $_SESSION['user_avatar'] ?? $_SESSION['user_picture'] ?? $_SESSION['foto_usuario'] ?? null; ?>
                                                <?php if (!empty($avatar_url)): ?>
                                                    <img src="<?= htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" />
                                                <?php else: ?>
                                                    <i class="fas fa-user"></i>
                                                <?php endif; ?>

                                            </div>
                                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? $_SESSION['nome_usuario'] ?? $_SESSION['user_name'] ?? 'Usuário Anônimo', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                        
                                        <div class="comment-input-section">
                                            <div class="comment-toolbar">
                                                <button type="button" class="toolbar-btn" data-format="bold" title="Negrito">
                                                    <strong>B</strong>
                                                </button>
                                                <button type="button" class="toolbar-btn" data-format="italic" title="Itálico">
                                                    <em>I</em>
                                                </button>
                                            </div>
                                            
                                            <form id="comment-form-<?php echo $questao['id_questao']; ?>" class="comment-form">

                                                <div class="comment-textarea-container">
                                                    <textarea name="comentario" placeholder="Escreva o seu comentrio" required minlength="10" maxlength="500" class="comment-textarea"></textarea>
                                                    <div class="textarea-resize-handle"></div>
                                                </div>
                                                <button type="submit" class="btn-responder">
                                                    Responder
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- Boto fechar -->
                                    <div class="close-comments">
                                        <button class="close-btn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Navegao -->
            <div class="navigation-section">
                <div class="nav-buttons">
                    <a href="listar_questoes.php?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>" 
                       class="nav-btn nav-btn-primary">
                         Voltar  Lista
                    </a>
                    <a href="index.php" class="nav-btn nav-btn-outline">
                         Incio
                    </a>
                    <a href="escolher_assunto.php" class="nav-btn nav-btn-outline">
                         Escolher Assunto
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funcao para mostrar feedback visual
        function mostrarFeedbackVisual(questaoId, alternativaSelecionada, alternativaCorreta, explicacao) {
            console.log('mostrarFeedbackVisual chamada com:', {
                questaoId, alternativaSelecionada, alternativaCorreta, explicacao
            });
            
            const questaoCard = document.querySelector(`#questao-${questaoId}`);
            
            if (!questaoCard) {
                console.error('Questao nao encontrada:', questaoId);
                console.log('Tentando buscar por:', `#questao-${questaoId}`);
                console.log('Elementos disponiveis:', document.querySelectorAll('[id^="questao-"]'));
                return;
            }
            
            console.log(' Questao encontrada:', questaoCard);
            
            const alternativas = questaoCard.querySelectorAll('.alternative');
            console.log(' Alternativas encontradas:', alternativas.length);
            
            // Limpar feedback anterior
            alternativas.forEach(alt => {
                alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                alt.style.background = '';
                alt.style.borderColor = '';
            });
            
            // Marcar alternativa correta
            const alternativaCorretaEl = questaoCard.querySelector(`[data-alternativa="${alternativaCorreta}"]`);
            console.log(' Buscando alternativa correta:', alternativaCorreta);
            console.log(' Alternativa correta encontrada:', alternativaCorretaEl);
            if (alternativaCorretaEl) {
                alternativaCorretaEl.classList.add('alternative-correct');
            } else {
                console.error(' Alternativa correta nao encontrada!');
            }
            
            // Marcar alternativa selecionada
            const alternativaSelecionadaEl = questaoCard.querySelector(`[data-alternativa="${alternativaSelecionada}"]`);
            if (alternativaSelecionadaEl) {
                // Se a alternativa selecionada for a correta, ela ja foi marcada como verde acima
                // Se for incorreta, marcar como vermelha
                if (alternativaSelecionada !== alternativaCorreta) {
                    alternativaSelecionadaEl.classList.add('alternative-incorrect-chosen');
                } else {
                    console.log(' Alternativa selecionada e a correta, ja marcada em verde');
                }
            } else {
                console.error(' Alternativa selecionada nao encontrada!');
            }
            
            // Mostrar explicacao aps um delay se disponvel
            if (explicacao && explicacao.trim() !== '') {
                setTimeout(() => {
                    let explicacaoContainer = questaoCard.querySelector('.explicacao-container');
                    if (!explicacaoContainer) {
                        explicacaoContainer = document.createElement('div');
                        explicacaoContainer.className = 'explicacao-container';
                        explicacaoContainer.innerHTML = `
                            <div class="explicacao-title"> Explicacao:</div>
                            <div class="explicacao-text">${explicacao}</div>
                        `;
                        questaoCard.appendChild(explicacaoContainer);
                    }
                }, 1000);
            }
        }

        // Event listeners para as alternativas - VERSO FINAL CORRIGIDA
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, configurando alternativas...');
            const filtroAtual = '<?php echo addslashes($filtro_ativo); ?>';
            
            // Verificar se ja foi configurado para evitar duplicao
            if (window.alternativasConfiguradas) {
                console.log('Alternativas ja configuradas, pulando...');
                return;
            }
            window.alternativasConfiguradas = true;
            
            // Verificar e remover duplicatas iniciais
            function removerDuplicatasIniciais() {
                const questoesExistentes = document.querySelectorAll('.question-card');
                const idsVistos = new Set();
                const duplicatas = [];
                
                questoesExistentes.forEach(questao => {
                    const id = questao.id;
                    if (idsVistos.has(id)) {
                        duplicatas.push(questao);
                    } else {
                        idsVistos.add(id);
                    }
                });
                
                if (duplicatas.length > 0) {
                    console.log('Removendo', duplicatas.length, 'duplicatas iniciais...');
                    duplicatas.forEach(questao => questao.remove());
                    return true; // Houve duplicatas
                }
                return false; // No houve duplicatas
            }
            
            // Executar remoo de duplicatas iniciais
            removerDuplicatasIniciais();
            
            // Limpar flag quando a pgina for recarregada
            window.addEventListener('beforeunload', function() {
                window.alternativasConfiguradas = false;
            });
            
            // Configurar TODAS as alternativas de uma vez (SEM clonagem para evitar duplicao)
            const todasAlternativas = document.querySelectorAll('.alternative');
            console.log('Total de alternativas encontradas:', todasAlternativas.length);
            
            todasAlternativas.forEach((alternativa, index) => {
                console.log('Configurando alternativa', index + 1);
                
                // Garantir que seja clicvel
                alternativa.style.pointerEvents = 'auto';
                alternativa.style.cursor = 'pointer';
                alternativa.style.position = 'relative';
                alternativa.style.zIndex = '10';
                
                // Remover classes de feedback
                alternativa.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                
                // Verificar se ja tem event listener para evitar duplicao
                if (alternativa.dataset.listenerAdded === 'true') {
                    console.log('Event listener ja adicionado, pulando...');
                    return;
                }
                alternativa.dataset.listenerAdded = 'true';
                
                // Adicionar event listener diretamente
                alternativa.addEventListener('click', function(e) {
                    console.log(' CLIQUE DETECTADO!', this);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest('.question-card');
                    
                    console.log('Questao ID:', questaoId);
                    console.log('Alternativa selecionada:', alternativaSelecionada);
                    console.log('Questao card:', questaoCard);
                    
                    // Verificar se ja foi respondida
                    if (questaoCard.dataset.respondida === 'true') {
                        console.log('Questao ja respondida, ignorando...');
                        return;
                    }
                    
                    // Verificar se esta alternativa ja foi clicada
                    if (this.dataset.clicked === 'true') {
                        console.log('Alternativa ja foi clicada, ignorando...');
                        return;
                    }
                    
                    // Verificar se ja existe uma questo duplicada no DOM ANTES de processar
                    const questoesExistentes = document.querySelectorAll('.question-card');
                    const questoesIds = Array.from(questoesExistentes).map(q => q.id);
                    const questaoAtualId = questaoCard.id;
                    
                    if (questoesIds.filter(id => id === questaoAtualId).length > 1) {
                        console.log('Questao duplicada detectada, removendo e cancelando clique...');
                        const questoesDuplicadas = document.querySelectorAll(`#${questaoAtualId}`);
                        for (let i = 1; i < questoesDuplicadas.length; i++) {
                            questoesDuplicadas[i].remove();
                        }
                        // Executar verificao geral de duplicatas
                        verificarDuplicatas();
                        return;
                    }
                    
                    // Marcar como clicada ANTES de processar
                    this.dataset.clicked = 'true';
                    
                    // Destacar a alternativa clicada imediatamente
                    const todasAlternativas = questaoCard.querySelectorAll('.alternative');
                    todasAlternativas.forEach(alt => {
                        alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                        alt.style.background = '';
                        alt.style.borderColor = '';
                    });
                    
                    // Marcar como selecionada
                    this.style.background = '#e3f2fd';
                    this.style.borderColor = '#2196f3';
                    
                    // Marcar questo como respondida
                    questaoCard.dataset.respondida = 'true';
                    
                    // Desabilitar todas as alternativas desta questo para evitar cliques duplos
                    todasAlternativas.forEach(alt => {
                        alt.style.pointerEvents = 'none';
                        alt.style.cursor = 'default';
                    });
                    
                    // Enviar resposta via AJAX
                    const formData = new FormData();
                    formData.append('id_questao', questaoId);
                    formData.append('alternativa_selecionada', alternativaSelecionada);
                    formData.append('ajax_request', '1');
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Resposta recebida:', response);
                        console.log('Status:', response.status);
                        return response.text();
                    })
                    .then(data => {
                        console.log('Dados recebidos:', data);
                        try {
                            const jsonData = JSON.parse(data);
                            console.log('JSON parseado:', jsonData);
                            
                            // Log de diagnstico - comentado para produo
                            /*
                            if (jsonData.debug_info) {
                                console.log('DEBUG INFO:', jsonData.debug_info);
                                // Adicionar div de debug na pgina
                                const debugDiv = document.createElement('div');
                                debugDiv.className = 'debug-info';
                                debugDiv.style.position = 'fixed';
                                debugDiv.style.bottom = '10px';
                                debugDiv.style.right = '10px';
                                debugDiv.style.background = '#f8f9fa';
                                debugDiv.style.border = '1px solid #ddd';
                                debugDiv.style.padding = '10px';
                                debugDiv.style.zIndex = '9999';
                                debugDiv.style.maxWidth = '300px';
                                debugDiv.style.fontSize = '12px';
                                debugDiv.innerHTML = `
                                    <strong>Debug Info:</strong>
                                    <pre>${JSON.stringify(jsonData.debug_info, null, 2)}</pre>
                                    <button id="close-debug">Fechar</button>
                                `;
                                document.body.appendChild(debugDiv);
                                document.getElementById('close-debug').addEventListener('click', () => {
                                    debugDiv.remove();
                                });
                            }
                            */
                            
                            if (jsonData.success) {
                                console.log('Sucesso! Mostrando feedback...');
                                console.log('Dados para feedback:', {
                                    questaoId: questaoId,
                                    alternativaSelecionada: alternativaSelecionada,
                                    alternativaCorreta: jsonData.alternativa_correta,
                                    explicacao: jsonData.explicacao
                                });
                                
                                // Usar a funo de feedback visual
                                mostrarFeedbackVisual(questaoId, alternativaSelecionada, jsonData.alternativa_correta, jsonData.explicacao);
                                
                                console.log('Feedback aplicado:', { 
                                    alternativaSelecionada, 
                                    alternativaCorreta: jsonData.alternativa_correta,
                                    acertou: alternativaSelecionada === jsonData.alternativa_correta,
                                    message: jsonData.message 
                                });
                                
                                // Verificar duplicatas aps processar resposta
                                setTimeout(() => {
                                    const questoesExistentes = document.querySelectorAll('.question-card');
                                    const questoesIds = Array.from(questoesExistentes).map(q => q.id);
                                    const questaoAtualId = questaoCard.id;
                                    
                                    if (questoesIds.filter(id => id === questaoAtualId).length > 1) {
                                        console.log('Duplicata detectada aps processamento, removendo...');
                                        const questoesDuplicadas = document.querySelectorAll(`#${questaoAtualId}`);
                                        for (let i = 1; i < questoesDuplicadas.length; i++) {
                                            questoesDuplicadas[i].remove();
                                        }
                                    }
                                    
                                    // Feedback visual de toast removido
                                }, 100);
                            
                        } else {
                                console.log('Erro na resposta:', jsonData.message);
                            // Reabilitar cliques em caso de erro
                            questaoCard.dataset.respondida = 'false';
                                todasAlternativas.forEach(alt => {
                                    alt.style.pointerEvents = 'auto';
                                    alt.style.cursor = 'pointer';
                                });
                            }
                        } catch (e) {
                            console.error('Erro ao fazer parse do JSON:', e);
                            console.log('Dados brutos:', data);
                            // Reabilitar cliques em caso de erro
                            questaoCard.dataset.respondida = 'false';
                            todasAlternativas.forEach(alt => {
                                alt.style.pointerEvents = 'auto';
                                alt.style.cursor = 'pointer';
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisio:', error);
                        // Reabilitar cliques em caso de erro
                        questaoCard.dataset.respondida = 'false';
                        todasAlternativas.forEach(alt => {
                            alt.style.pointerEvents = 'auto';
                            alt.style.cursor = 'pointer';
                        });
                    });
                });
            });

            // Funo para mostrar mensagem quando filtro fica vazio
            function mostrarMensagemFiltroVazio() {
                const container = document.querySelector('.questions-container');
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon"></div>
                        <div class="empty-state-title">Parabns!</div>
                        <div class="empty-state-text">
                            Voc respondeu todas as questes deste filtro!<br>
                            <a href="?id=<?php echo $id_assunto; ?>&filtro=todas" class="nav-btn" style="margin-top: 20px; display: inline-block;">
                                 Ver Todas as Questes
                            </a>
                        </div>
                    </div>
                `;
            }

            // Animaes de entrada
            const cards = document.querySelectorAll('.question-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });

// Inicializar estatsticas apenas uma vez
if (!window.statsInitialized) {
    initStats();
}
            
            // Ajustar header
            ajustarHeader();
            
            // Funo para verificar e remover duplicatas
            function verificarDuplicatas() {
                const questoesExistentes = document.querySelectorAll('.question-card');
                const idsVistos = new Set();
                const duplicatas = [];
                
                questoesExistentes.forEach(questao => {
                    const id = questao.id;
                    if (idsVistos.has(id)) {
                        duplicatas.push(questao);
                    } else {
                        idsVistos.add(id);
                    }
                });
                
                if (duplicatas.length > 0) {
                    console.log('Removendo', duplicatas.length, 'questes duplicadas...');
                    duplicatas.forEach(questao => questao.remove());
                    return true; // Houve duplicatas removidas
                }
                return false; // No houve duplicatas
            }
            
            // Verificar duplicatas a cada 2 segundos (menos frequente para melhor performance)
            setInterval(verificarDuplicatas, 2000);
        });

        // Load Chart.js library
        const chartScript = document.createElement('script');
        chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        document.head.appendChild(chartScript);

        // Statistics toggle functionality
        function initStats() {
            // Verificar se ja foi inicializada para evitar duplicao
            if (window.statsInitialized) {
                console.log('Estatsticas ja inicializadas, pulando...');
                return;
            }
            window.statsInitialized = true;
            
            const statsButtons = document.querySelectorAll('.stats-toggle-btn');
            
            statsButtons.forEach(button => {
                // Remover event listeners existentes
                button.removeEventListener('click', handleStatsClick);
                // Adicionar novo event listener
                button.addEventListener('click', handleStatsClick);
            });
        }

        function handleStatsClick() {
            const questaoId = this.dataset.questaoId;
            const statsPanel = document.getElementById('stats-' + questaoId);
            const isOpen = statsPanel.style.display !== 'none';
            
            if (isOpen) {
                // Close panel
                statsPanel.style.display = 'none';
                this.classList.remove('active');
            } else {
                // Open panel and load stats
                statsPanel.style.display = 'block';
                this.classList.add('active');
                loadStatistics(questaoId);
            }
        }

        function loadStatistics(questaoId) {
            const statsPanel = document.getElementById('stats-' + questaoId);
            const loadingDiv = statsPanel.querySelector('.stats-loading');
            const contentDiv = statsPanel.querySelector('.stats-content');
            
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            // Fetch statistics from API
            fetch('api_estatisticas.php?id_questao=' + questaoId)
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    contentDiv.style.display = 'block';
                    
                    // Wait for Chart.js to load
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => renderCharts(questaoId, data), 500);
                    } else {
                        renderCharts(questaoId, data);
                    }
                })
                .catch(error => {
                    loadingDiv.innerHTML = '<p style="color: #dc3545;">Erro ao carregar estatsticas</p>';
                });
        }

        function renderCharts(questaoId, data) {
            // Pie Chart - Correct vs Incorrect
            const pieCtx = document.getElementById('chart-pie-' + questaoId).getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Acertos', 'Erros'],
                    datasets: [{
                        data: [data.acertos, data.erros],
                        backgroundColor: ['#5FD08D', '#E06B7D'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { font: { size: 11 } }
                        }
                    }
                }
            });
            
            // Bar Chart - Alternatives distribution
            const barCtx = document.getElementById('chart-bar-' + questaoId).getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['A', 'B', 'C', 'D', 'E'],
                    datasets: [{
                        label: 'Respostas',
                        data: [
                            data.alternativas.A || 0,
                            data.alternativas.B || 0,
                            data.alternativas.C || 0,
                            data.alternativas.D || 0,
                            data.alternativas.E || 0
                        ],
                        backgroundColor: ['#FFB84D', '#5DADE2', '#F4D03F', '#A3CB7F', '#EC7063']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
            
            // Render history list
            const historyList = document.querySelector('#stats-' + questaoId + ' .stats-history-list');
            if (data.historico && data.historico.length > 0) {
                historyList.innerHTML = data.historico.map(item => `
                    <div class="stats-history-item ${item.acertou ? 'correct' : 'incorrect'}">
                        <span class="stats-history-date">Em ${item.data}, voc respondeu a opo ${item.alternativa}.</span>
                        <span class="stats-history-result">
                            ${item.acertou ? 
                                '<i class="fas fa-check-circle"></i> Voc acertou!' : 
                                '<i class="fas fa-times-circle"></i> Voc errou!'}
                        </span>
                    </div>
                `).join('');
            } else {
                historyList.innerHTML = '<p style="text-align: center; color: #6c757d;">Voc ainda no respondeu esta questo.</p>';
            }
        }

        // Funo auxiliar para verificar elementos
        function safeGetElement(container, selector, errorMsg) {
            const element = container.querySelector(selector);
            if (!element) {
                console.error(errorMsg, 'Seletor:', selector, 'Container:', container);
                return null;
            }
            return element;
        }

        
        // Funo auxiliar robusta para elementos
        function safeSetTextContent(element, text, fallback = "") {
            try {
                if (!element) {
                    console.error("Elemento nao encontrado para textContent");
                    return false;
                }
                element.textContent = typeof text === 'string' ? text : fallback;
                return true;
            } catch (err) {
                console.error("Falha ao definir textContent:", err);
                if (element) element.textContent = fallback;
                return false;
            }
        }
        // Funes para gerenciar comentrios
        function initComments() {
            const commentsButtons = document.querySelectorAll('.comments-toggle-btn');
            
            commentsButtons.forEach(button => {
                button.addEventListener('click', handleCommentsClick);
            });

            // Inicializar contadores de caracteres
            const textareas = document.querySelectorAll('textarea[name="comentario"]');
            textareas.forEach(textarea => {
                const container = textarea.closest('.comment-textarea-container') || textarea.parentElement || textarea;
                let charCount = container ? container.querySelector('.char-count') : null;
                if (!charCount && container) {
                    charCount = document.createElement('div');
                    charCount.className = 'char-count';
                    charCount.style.cssText = 'margin-top: 6px; font-size: 12px; color: #6c757d;';
                    container.appendChild(charCount);
                }
                const updateCharCount = () => {
                    const count = textarea.value.length;
                    if (charCount) {
                        safeSetTextContent(charCount, `${count}/500 caracteres`, `${count}/500 caracteres`);
                        if (count > 450) {
                            charCount.style.color = '#dc3545';
                        } else if (count > 400) {
                            charCount.style.color = '#ffc107';
                        } else {
                            charCount.style.color = '#6c757d';
                        }
                    }
                };
                updateCharCount();
                textarea.removeEventListener('input', updateCharCount);
                textarea.addEventListener('input', updateCharCount);
            });

            // Inicializar formulrios de comentrios
            const commentForms = document.querySelectorAll('[id^="comment-form-"]');
            commentForms.forEach(form => {
                form.addEventListener('submit', handleCommentSubmit);
            });
        }

        function handleCommentsClick() {
            const questaoId = this.dataset.questaoId;
            const commentsPanel = document.getElementById('comments-' + questaoId);
            const isOpen = commentsPanel.style.display !== 'none';
            
            if (isOpen) {
                // Fechar painel
                commentsPanel.style.display = 'none';
                this.classList.remove('active');
            } else {
                // Abrir painel e carregar comentrios
                commentsPanel.style.display = 'block';
                this.classList.add('active');
                loadComments(questaoId);
            }
        }

        function loadComments(questaoId) {
            const commentsPanel = document.getElementById('comments-' + questaoId);
            const loadingDiv = commentsPanel.querySelector('.comments-loading');
            const contentDiv = commentsPanel.querySelector('.comments-content');
            
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            // Buscar comentrios da API
            fetch('api_comentarios.php?id_questao=' + questaoId)
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    contentDiv.style.display = 'block';
                    
                    if (data.success) {
                        const all = Array.isArray(data.data) ? data.data : [];
                        // Preservar avatar_url vindo do backend; não substituir pelo avatar da sessão do visualizador
                        all.forEach(c => {
                            if (!c.avatar_url && c.email_usuario) {
                                // sem ação: render usará ícone fallback; backend já tenta enriquecer
                            }
                            if (Array.isArray(c.respostas)) {
                                c.respostas.forEach(r => {
                                    if (!r.avatar_url && r.email_usuario) {
                                        // idem
                                    }
                                });
                            }
                        });
                        window.COMMENTS_STATE = window.COMMENTS_STATE || {};
                        window.COMMENTS_STATE[questaoId] = { all: all, visibleCount: Math.min(5, all.length) };
                        const subset = all.slice(0, Math.min(5, all.length));
                        renderComments(questaoId, subset);
                    } else {
                        showCommentsError(questaoId, data.message);
                    }
                })
                .catch(error => {
                    loadingDiv.innerHTML = '<p style="color: #dc3545;">Erro ao carregar comentrios</p>';
                    console.error('Erro ao carregar comentrios:', error);
                });
        }

        function renderComments(questaoId, comentarios) {
            const commentsList = document.querySelector(`#comments-${questaoId} .comments-list`);
            
            if (comentarios.length === 0) {
                commentsList.innerHTML = '<div class="no-comments">Nenhum comentrio ainda. Seja o primeiro a comentar!</div>';
                return;
            }
            
            commentsList.innerHTML = comentarios.map(comentario => `
                <div class="comment-item" data-comentario-id="${comentario.id_comentario}">
                    <div class="comment-header">
                        <div class="comment-avatar">
                            ${comentario.avatar_url ? `<img src="${escapeAttr(comentario.avatar_url)}" alt="Avatar" />` : `<i class="fas fa-user"></i>`}

                        </div>
                        <div class="comment-author">${escapeHtml(comentario.nome_usuario)}</div>
                        <div class="comment-date">${comentario.data_formatada}</div>
                    </div>
                    <div class="comment-text">${escapeHtml(comentario.comentario)}</div>
                    <div class="comment-actions">
                        <button class="action-btn curtir-btn${comentario.curtido_pelo_usuario ? ' curtido' : ''}" data-comentario-id="${comentario.id_comentario}" style="${comentario.curtido_pelo_usuario ? 'color: #28a745;' : 'color: #007bff;'}">
                            <i class="fas fa-thumbs-up"></i>
                            Gostei (${comentario.total_curtidas || 0})
                        </button>
                        <span class="voce-curtiu" style="${comentario.curtido_pelo_usuario ? 'margin-left:8px;color:#28a745;font-weight:600;' : 'display:none;margin-left:8px;color:#28a745;font-weight:600;'}">Você curtiu</span>
                        <button class="action-btn responder-btn" data-comentario-id="${comentario.id_comentario}">
                            <i class="fas fa-reply"></i>
                            Respostas (${comentario.total_respostas || 0})
                        </button>
                        <a href="#" class="report-abuse" data-comentario-id="${comentario.id_comentario}">Reportar abuso</a>
                        ${"<?php echo (($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'admin') ? '1' : ''; ?>" ? `<button class="action-btn apagar-btn" data-comentario-id="${comentario.id_comentario}" title="Apagar comentário" style="color:#dc3545;"><i class="fas fa-trash"></i> Apagar</button>` : ''}
                    </div>
                    ${comentario.respostas && comentario.respostas.length > 0 ? `
                        <div class="comment-replies collapsed">
                            ${comentario.respostas.map(resposta => `
                                <div class="reply-item">
                                    <div class="comment-header">
                                        <div class="comment-avatar">
                                            ${resposta.avatar_url ? `<img src="${escapeAttr(resposta.avatar_url)}" alt="Avatar" />` : `<i class="fas fa-user"></i>`}
                                        </div>
                                        <div class="comment-author">${escapeHtml(resposta.nome_usuario)}</div>
                                        <div class="comment-date">${resposta.data_formatada}</div>
                                    </div>
                                    <div class="comment-text">${escapeHtml(resposta.comentario)}</div>
                                    <div class="comment-actions">
                                        <button class="action-btn curtir-btn${resposta.curtido_pelo_usuario ? ' curtido' : ''}" data-comentario-id="${resposta.id_comentario}" style="${resposta.curtido_pelo_usuario ? 'color: #28a745;' : 'color: #007bff;'}">
                                            <i class="fas fa-thumbs-up"></i>
                                            Gostei (${resposta.total_curtidas || 0})
                                        </button>
                                        <span class="voce-curtiu" style="${resposta.curtido_pelo_usuario ? 'margin-left:8px;color:#28a745;font-weight:600;' : 'display:none;margin-left:8px;color:#28a745;font-weight:600;'}">Você curtiu</span>
                                        <a href="#" class="report-abuse" data-comentario-id="${resposta.id_comentario}">Reportar abuso</a>
                                        ${"<?php echo (($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'admin') ? '1' : ''; ?>" ? `<button class="action-btn apagar-btn" data-comentario-id="${resposta.id_comentario}" title="Apagar resposta" style="color:#dc3545;"><i class="fas fa-trash"></i> Apagar</button>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    ` : ''}
                </div>
            `).join('');
            
            // Adicionar event listeners para os botes
            addCommentEventListeners(questaoId);
        }

        function showCommentsError(questaoId, message) {
            const commentsList = document.querySelector(`#comments-${questaoId} .comments-list`);
            commentsList.innerHTML = `<div class="no-comments" style="color: #dc3545;">Erro: ${escapeHtml(message)}</div>`;
        }

        function handleCommentSubmit(e) {
            e.preventDefault();
            
            const form = e.target;
            
            // Verificao adicional de segurana
            if (!form || !form.id) {
                console.error('Formulrio invlido', form);
                showMessage('Erro: Formulrio invlido', 'error');
                return;
            }
            const questaoId = form.id.replace('comment-form-', '');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.id_questao = questaoId;
            
            // Valida o no cliente
            const comentarioTexto = (data.comentario || '').trim();
            if (comentarioTexto.length < 10 || comentarioTexto.length > 500) {
                showMessage('Coment rio deve ter entre 10 e 500 caracteres', 'error');
                return;
            }
            
            // Debug: log dos dados
            console.log('Enviando comentrio:', data);
            
            const submitBtn = form.querySelector('.btn-responder');
            if (!submitBtn) {
                console.error('Botao de envio nao encontrado');
                showMessage('Erro: Botao de envio nao encontrado', 'error');
                return;
            }
            const originalText = submitBtn.textContent;
            
            // Desabilitar boto e mostrar loading
            submitBtn.disabled = true;
            safeSetTextContent(submitBtn, "Enviando...");
            
            // Enviar comentrio
            fetch('api_comentarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Limpar formulrio
                    form.reset();
                    const container = form.querySelector('.comment-textarea-container');
                    const charCount = container ? container.querySelector('.char-count') : null;
                    if (charCount) {
                        charCount.textContent = '0/500 caracteres';
                        charCount.style.color = '#6c757d';
                    }
                    
                    // Recarregar comentrios
                    loadComments(questaoId);
                    
                    // Mostrar mensagem de sucesso
                    showMessage('Comentrio enviado com sucesso!', 'success');
                } else {
                    showMessage('Erro: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao enviar comentrio:', error);
                showMessage('Erro ao enviar comentrio. Tente novamente.', 'error');
            })
                .finally(() => {
                    // Reabilitar boto
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        safeSetTextContent(submitBtn, originalText);
                    }
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeAttr(text) {
            const div = document.createElement('div');
            div.innerText = text ?? '';
            return div.innerHTML.replace(/\"/g, '&quot;');
        }

        function showMessage(message, type) {
            // Criar elemento de mensagem
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 600;
                z-index: 10000;
                max-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            if (type === 'success') {
                messageDiv.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            } else {
                messageDiv.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
            }
            
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);
            
            // Remover aps 3 segundos
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 3000);
        }

        // Funo para adicionar event listeners aos comentrios
        function addCommentEventListeners(questaoId) {
            // Botes de curtir
            document.querySelectorAll(`#comments-${questaoId} .curtir-btn`).forEach(btn => {
                btn.addEventListener('click', function() {
                    const comentarioId = this.dataset.comentarioId;
                    curtirComentario(comentarioId, this);
                });
            });

            // Botões de respostas: toggling da seção de respostas + abrir formulário quando expandir
            document.querySelectorAll(`#comments-${questaoId} .responder-btn`).forEach(btn => {
                btn.addEventListener('click', function() {
                    const comentarioId = this.dataset.comentarioId;
                    const comentarioItem = document.querySelector(`[data-comentario-id="${comentarioId}"]`);
                    if (!comentarioItem) return;
                    let repliesSection = comentarioItem.querySelector('.comment-replies');
                    if (!repliesSection) {
                        // cria seção vazia inicialmente oculta
                        repliesSection = document.createElement('div');
                        repliesSection.className = 'comment-replies collapsed';
                        const actions = comentarioItem.querySelector('.comment-actions');
                        if (actions && actions.parentNode) {
                            actions.parentNode.insertBefore(repliesSection, actions.nextSibling);
                        } else {
                            comentarioItem.appendChild(repliesSection);
                        }
                    }
                    const isCollapsed = repliesSection.classList.contains('collapsed');
                    if (isCollapsed) {
                        // expandir: remover classe e abrir formulário de resposta
                        // Não recarregar comentários aqui para evitar perder o formulário e o texto digitado
                        repliesSection.classList.remove('collapsed');
                        mostrarFormularioResposta(questaoId, comentarioId);
                    } else {
                        // recolher: adicionar classe e remover formulário de resposta se existir
                        repliesSection.classList.add('collapsed');
                        const form = repliesSection.querySelector('.form-resposta');
                        if (form) form.remove();
                    }
                });
            });

            // Links de reportar abuso
            document.querySelectorAll(`#comments-${questaoId} .report-abuse`).forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const comentarioId = this.dataset.comentarioId;
                    reportarAbuso(comentarioId);
                });
            });

            // Botões de apagar (admin)
            document.querySelectorAll(`#comments-${questaoId} .apagar-btn`).forEach(btn => {
                btn.addEventListener('click', function() {
                    const comentarioId = this.dataset.comentarioId;
                    apagarComentario(comentarioId, questaoId, this);
                });
            });
        }

        // Funo para curtir/descurtir comentrio
        function curtirComentario(comentarioId, botao) {
            const acao = botao.classList.contains('curtido') ? 'descurtir' : 'curtir';
            
            fetch('api_comentarios.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_comentario: comentarioId,
                    acao: acao
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Atualizar contador visual usando valor do backend quando disponível
                    const texto = botao.textContent;
                    const match = texto.match(/Gostei \((\d+)\)/);
                    let newCount = null;
                    if (typeof result.total_curtidas === 'number') {
                        newCount = result.total_curtidas;
                    } else if (match) {
                        const count = parseInt(match[1]);
                        newCount = acao === 'curtir' ? count + 1 : Math.max(count - 1, 0);
                    }
                    if (newCount !== null) {
                        botao.innerHTML = `<i class=\"fas fa-thumbs-up\"></i> Gostei (${newCount})`;
                    }
                    // Estilo visual de curtido baseado no backend
                    if (result.curtido_pelo_usuario === true) {
                        botao.classList.add('curtido');
                        botao.style.color = '#28a745';
                    } else {
                        botao.classList.remove('curtido');
                        botao.style.color = '#007bff';
                    }
                    // Alternar indicador “Você curtiu” com base no backend
                    const indicator = botao.parentElement.querySelector('.voce-curtiu');
                    if (result.curtido_pelo_usuario === true) {
                        if (indicator) {
                            indicator.style.display = '';
                        } else {
                            const span = document.createElement('span');
                            span.className = 'voce-curtiu';
                            span.style.cssText = 'margin-left:8px;color:#28a745;font-weight:600;';
                            span.textContent = 'Você curtiu';
                            botao.insertAdjacentElement('afterend', span);
                        }
                    } else {
                        if (indicator) {
                            indicator.style.display = 'none';
                        }
                    }
                } else {
                    showMessage('Erro: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao curtir comentrio:', error);
                showMessage('Erro ao curtir comentrio', 'error');
            });
        }

        // Função para apagar comentário (admin)
        function apagarComentario(comentarioId, questaoId, botao) {
            if (!confirm('Tem certeza que deseja apagar este comentário? Esta ação não pode ser desfeita.')) return;
            fetch('api_comentarios.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_comentario: comentarioId,
                    acao: 'apagar'
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const item = document.querySelector(`[data-comentario-id="${comentarioId}"]`);
                    if (item) {
                        item.style.opacity = '0.6';
                        item.style.pointerEvents = 'none';
                        item.style.filter = 'grayscale(0.7)';
                        item.querySelectorAll('.action-btn, .report-abuse').forEach(el => el.remove());
                        const removed = document.createElement('div');
                        removed.style.cssText = 'margin-top:8px;color:#dc3545;font-weight:600;';
                        removed.textContent = 'Comentário apagado pelo administrador';
                        item.appendChild(removed);
                    }
                    showMessage('Comentário apagado com sucesso.', 'success');
                } else {
                    showMessage('Erro ao apagar: ' + (result.message || 'tente novamente.'), 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao apagar comentário:', error);
                showMessage('Erro ao apagar comentário.', 'error');
            });
        }

        // Funo para mostrar formulrio de resposta
        function mostrarFormularioResposta(questaoId, comentarioPaiId) {
            const comentarioItem = document.querySelector(`[data-comentario-id="${comentarioPaiId}"]`);
            if (!comentarioItem) {
                console.error('Comentario pai nao encontrado para id:', comentarioPaiId);
                showMessage('Erro: Comentário não encontrado para responder', 'error');
                return;
            }

            // Garantir a seção de respostas
            let repliesSection = comentarioItem.querySelector('.comment-replies');
            if (!repliesSection) {
                repliesSection = document.createElement('div');
                repliesSection.className = 'comment-replies collapsed';
                const actions = comentarioItem.querySelector('.comment-actions');
                if (actions && actions.parentNode) {
                    actions.parentNode.insertBefore(repliesSection, actions.nextSibling);
                } else {
                    comentarioItem.appendChild(repliesSection);
                }
            }

            // Evitar múltiplos formulários
            let formResposta = repliesSection.querySelector('.form-resposta');
            if (formResposta) {
                const textareaExistente = formResposta.querySelector('textarea[name="comentario"]');
                if (textareaExistente) textareaExistente.focus();
                return;
            }

            formResposta = document.createElement('div');
            formResposta.className = 'form-resposta';
            const sessAvatar = "<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? $_SESSION['user_picture'] ?? $_SESSION['foto_usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
            const sessNome = "<?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? $_SESSION['nome_usuario'] ?? $_SESSION['user_name'] ?? 'Usuário Anônimo', ENT_QUOTES, 'UTF-8'); ?>";
            const isAdmin = "<?php echo (($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'admin') ? '1' : ''; ?>";
            formResposta.innerHTML = `
                <div class="comment-user-info">
                    <div class="user-avatar">${sessAvatar ? `<img src="${sessAvatar}" alt="Avatar" />` : `<i class="fas fa-user"></i>`}</div>
                    <div class="user-name">${sessNome}</div>
                </div>
                <div class="comment-input-section">
                    <form class="reply-form" data-comentario-pai="${comentarioPaiId}">
                        <div class="comment-textarea-container">
                            <textarea name="comentario" placeholder="Escreva sua resposta..." required minlength="10" maxlength="500" class="comment-textarea"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 10px;">
                            <button type="submit" class="btn-responder">Responder</button>
                            <button type="button" class="btn-cancelar">Cancelar</button>
                        </div>
                    </form>
                </div>
            `;
            repliesSection.appendChild(formResposta);

            // Cancelar
            const btnCancelar = formResposta.querySelector('.btn-cancelar');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function() {
                    const container = this.closest('.form-resposta');
                    if (container && container.parentNode) container.parentNode.removeChild(container);
                });
            }

            // Contador de caracteres
            const replyTextarea = formResposta.querySelector('textarea[name="comentario"]');
            if (replyTextarea) {
                const container = replyTextarea.closest('.comment-textarea-container') || replyTextarea.parentElement || replyTextarea;
                let charCount = container ? container.querySelector('.char-count') : null;
                if (!charCount && container) {
                    charCount = document.createElement('div');
                    charCount.className = 'char-count';
                    charCount.style.cssText = 'margin-top: 6px; font-size: 12px; color: #6c757d;';
                    container.appendChild(charCount);
                }
                const updateCharCount = () => {
                    const count = replyTextarea.value.length;
                    if (charCount) {
                        safeSetTextContent(charCount, `${count}/500 caracteres`, `${count}/500 caracteres`);
                        charCount.style.color = count > 450 ? '#dc3545' : (count > 400 ? '#ffc107' : '#6c757d');
                    }
                };
                updateCharCount();
                replyTextarea.addEventListener('input', updateCharCount);
            }

            const replyForm = formResposta.querySelector('.reply-form');
            if (replyForm) {
                replyForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    enviarResposta(questaoId, comentarioPaiId, this);
                });
            }
        }

        // Funcao para enviar resposta
        function enviarResposta(questaoId, comentarioPaiId, form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.id_questao = questaoId;
            data.id_comentario_pai = comentarioPaiId;
            // nome_usuario será definido pelo backend com base na sessão do usuário
            
            // Validao no cliente
            const comentarioTexto = (data.comentario || '').trim();
            if (comentarioTexto.length < 10 || comentarioTexto.length > 500) {
                showMessage('Resposta deve ter entre 10 e 500 caracteres', 'error');
                return;
            }
            
            const submitBtn = form.querySelector('.btn-responder');
            if (!submitBtn) {
                console.error('Botao de envio nao encontrado');
                showMessage('Erro: Botao de envio nao encontrado', 'error');
                return;
            }
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            safeSetTextContent(submitBtn, "Enviando...");
            
            fetch('api_comentarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    form.remove();
                    loadComments(questaoId); // Recarregar comentarios
                    showMessage('Resposta enviada com sucesso!', 'success');
                } else {
                    showMessage('Erro: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao enviar resposta:', error);
                showMessage('Erro ao enviar resposta', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    safeSetTextContent(submitBtn, originalText);
                }
            });
        }

        // Funo para reportar abuso
        function reportarAbuso(comentarioId) {
            if (confirm('Tem certeza que deseja reportar este comentrio por abuso?')) {
                fetch('api_comentarios.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id_comentario: comentarioId
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showMessage('Comentrio reportado com sucesso!', 'success');
                        // Remover comentrio da interface com segurança
                        const el = document.querySelector(`[data-comentario-id="${comentarioId}"]`);
                        if (el) { el.remove(); }
                    } else {
                        showMessage('Erro: ' + result.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro ao reportar abuso:', error);
                    showMessage('Erro ao reportar abuso', 'error');
                });
            }
        }

        // Funo para gerenciar abas de ordenao
        function initTabs(questaoId) {
            const tabs = document.querySelectorAll(`#comments-${questaoId} .tab-btn`);
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remover classe active de todas as abas
                    tabs.forEach(t => t.classList.remove('active'));
                    // Adicionar classe active na aba clicada
                    this.classList.add('active');
                    
                    // Recarregar comentrios com nova ordenao
                    const ordenacao = this.dataset.ordenacao;
                    loadComments(questaoId, ordenacao);
                });
            });
        }

        // Funo para carregar mais comentrios
        function initLoadMore(questaoId) {
            const loadMoreBtn = document.querySelector(`#comments-${questaoId} .load-more-btn`);
            const state = (window.COMMENTS_STATE = window.COMMENTS_STATE || {});
            const st = state[questaoId];
            if (loadMoreBtn) {
                // Atualizar visibilidade inicialmente
                if (st && Array.isArray(st.all)) {
                    loadMoreBtn.style.display = st.visibleCount < st.all.length ? 'inline-block' : 'none';
                } else {
                    loadMoreBtn.style.display = 'none';
                }
                if (loadMoreBtn.dataset.hasListener !== '1') {
                    loadMoreBtn.dataset.hasListener = '1';
                    loadMoreBtn.addEventListener('click', function() {
                        const s = (window.COMMENTS_STATE || {})[questaoId];
                        if (!s || !Array.isArray(s.all)) return;
                        s.visibleCount = Math.min(s.visibleCount + 5, s.all.length);
                        const subset = s.all.slice(0, s.visibleCount);
                        renderComments(questaoId, subset);
                        // atualizar visibilidade do botão
                        this.style.display = s.visibleCount < s.all.length ? 'inline-block' : 'none';
                    });
                }
            }
        }

        // Funo para inicializar toolbar de formatao
        function initToolbar(questaoId) {
            const toolbar = document.querySelector(`#comments-${questaoId} .comment-toolbar`);
            if (toolbar) {
                toolbar.addEventListener('click', function(e) {
                    if (e.target.classList.contains('toolbar-btn')) {
                        e.preventDefault();
                        const format = e.target.dataset.format;
                        applyFormat(format, questaoId);
                    }
                });
            }
        }

        // Funo para aplicar formatao
        function applyFormat(format, questaoId) {
            const textarea = document.querySelector(`#comments-${questaoId} .comment-textarea`);
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            
            let formattedText = '';
            switch (format) {
                case 'bold':
                    formattedText = `**${selectedText}**`;
                    break;
                case 'italic':
                    formattedText = `*${selectedText}*`;
                    break;
                default:
                    // Formatos removidos: ignorar ações não suportadas
                    return;
            }
            
            textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
            textarea.focus();
        }

        // Atualizar funo loadComments para suportar ordenao
        function loadComments(questaoId, ordenacao = 'data') {
            const commentsPanel = document.getElementById('comments-' + questaoId);
            const loadingDiv = commentsPanel.querySelector('.comments-loading');
            const contentDiv = commentsPanel.querySelector('.comments-content');
            
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            fetch(`api_comentarios.php?id_questao=${questaoId}&ordenacao=${ordenacao}`)
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    contentDiv.style.display = 'block';
                    
                    if (data.success) {
                        const all = Array.isArray(data.data) ? data.data : [];
                        window.COMMENTS_STATE = window.COMMENTS_STATE || {};
                        window.COMMENTS_STATE[questaoId] = { all: all, visibleCount: Math.min(5, all.length) };
                        const subset = all.slice(0, Math.min(5, all.length));
                        renderComments(questaoId, subset);
                        initTabs(questaoId);
                        initLoadMore(questaoId);
                        initToolbar(questaoId);
                    } else {
                        showCommentsError(questaoId, data.message);
                    }
                })
                .catch(error => {
                    loadingDiv.innerHTML = '<p style="color: #dc3545;">Erro ao carregar comentrios</p>';
                    console.error('Erro ao carregar comentrios:', error);
                });
        }

        // Inicializar comentrios quando a pgina carregar
        document.addEventListener('DOMContentLoaded', function() {
            initComments();
        });
    </script>

</main>
</div>
    
<?php include 'footer.php'; ?>
</body>
</html>