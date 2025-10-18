<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once __DIR__ . '/conexao.php';

try {
    // Verificar se o parâmetro id_questao foi fornecido
    if (!isset($_GET['id_questao']) || empty($_GET['id_questao'])) {
        throw new Exception('ID da questão não fornecido');
    }
    
    $id_questao = (int)$_GET['id_questao'];
    
    // Buscar todas as respostas para esta questão
    $stmt_respostas = $pdo->prepare("
        SELECT 
            r.id_alternativa,
            r.acertou,
            r.data_resposta,
            a.texto as alternativa_texto
        FROM respostas_usuario r
        LEFT JOIN alternativas a ON r.id_alternativa = a.id_alternativa
        WHERE r.id_questao = ?
        ORDER BY r.data_resposta DESC
    ");
    $stmt_respostas->execute([$id_questao]);
    $respostas = $stmt_respostas->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar as alternativas da questão para mapear letras
    $stmt_alternativas = $pdo->prepare("
        SELECT id_alternativa, texto, eh_correta
        FROM alternativas 
        WHERE id_questao = ? 
        ORDER BY id_alternativa
    ");
    $stmt_alternativas->execute([$id_questao]);
    $alternativas_questao = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
    
    // Mapear IDs das alternativas para letras
    $letras = ['A', 'B', 'C', 'D', 'E'];
    $mapa_alternativas = [];
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $mapa_alternativas[$alt['id_alternativa']] = $letra;
    }
    
    // Calcular estatísticas
    $total = count($respostas);
    $acertos = 0;
    $erros = 0;
    $contador_alternativas = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
    $historico = [];
    
    foreach ($respostas as $resposta) {
        // Contar acertos e erros
        if ($resposta['acertou'] == 1) {
            $acertos++;
        } else {
            $erros++;
        }
        
        // Mapear alternativa para letra
        $letra_alternativa = $mapa_alternativas[$resposta['id_alternativa']] ?? '?';
        
        // Contar alternativas escolhidas
        if (isset($contador_alternativas[$letra_alternativa])) {
            $contador_alternativas[$letra_alternativa]++;
        }
        
        // Adicionar ao histórico
        $data_formatada = date('d/m/Y H:i:s', strtotime($resposta['data_resposta']));
        $historico[] = [
            'data' => $data_formatada,
            'alternativa' => $letra_alternativa,
            'acertou' => (bool)$resposta['acertou']
        ];
    }
    
    // Calcular percentual de acerto
    $percentual_acerto = $total > 0 ? round(($acertos / $total) * 100, 1) : 0;
    
    // Preparar resposta
    $response = [
        'success' => true,
        'total' => $total,
        'acertos' => $acertos,
        'erros' => $erros,
        'percentual_acerto' => $percentual_acerto,
        'alternativas' => $contador_alternativas,
        'historico' => $historico
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'total' => 0,
        'acertos' => 0,
        'erros' => 0,
        'percentual_acerto' => 0,
        'alternativas' => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0],
        'historico' => []
    ];
    
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}
?>
