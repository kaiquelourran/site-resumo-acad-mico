<?php
// Endpoint para processar respostas via AJAX
require_once __DIR__ . '/conexao.php';

// Headers CORS para permitir requisições cross-origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Responde a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Inicia a sessão
session_start();

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

// Verifica se os dados necessários foram enviados
if (!isset($_POST['resposta']) || !isset($_POST['id_questao'])) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

try {
    $id_questao = (int)$_POST['id_questao'];
    $id_alternativa = (int)$_POST['resposta'];
    
    // Verifica se a resposta está correta
    $stmt_verifica = $pdo->prepare("SELECT correta FROM alternativas WHERE id_alternativa = ?");
    $stmt_verifica->execute([$id_alternativa]);
    $correta = $stmt_verifica->fetchColumn();
    
    if ($correta === false) {
        echo json_encode(['success' => false, 'error' => 'Alternativa não encontrada']);
        exit;
    }
    
    // Busca todas as alternativas da questão para mostrar o feedback
    $stmt_todas_alternativas = $pdo->prepare("SELECT id_alternativa, texto, correta FROM alternativas WHERE id_questao = ?");
    $stmt_todas_alternativas->execute([$id_questao]);
    $todas_alternativas = $stmt_todas_alternativas->fetchAll(PDO::FETCH_ASSOC);
    
    // Adiciona a questão às respondidas
    if (!isset($_SESSION['quiz_progress'])) {
        $_SESSION['quiz_progress'] = [
            'acertos' => 0,
            'respondidas' => [],
            'id_assunto' => 0,
        ];
    }
    
    $_SESSION['quiz_progress']['respondidas'][] = $id_questao;
    
    if ($correta) {
        $_SESSION['quiz_progress']['acertos']++;
    }
    
    // Prepara o feedback para retornar
    $feedback = [
        'id_questao' => $id_questao,
        'id_alternativa_escolhida' => $id_alternativa,
        'alternativas' => $todas_alternativas,
        'acertou' => (bool)$correta
    ];
    
    // Armazena o feedback na sessão para manter consistência
    $_SESSION['feedback'] = $feedback;
    
    // Retorna sucesso com os dados do feedback
    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
}
?>