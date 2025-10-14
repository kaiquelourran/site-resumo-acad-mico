<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

// Verificar CSRF token
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

$id_relatorio = intval($_POST['id_relatorio'] ?? 0);

if ($id_relatorio <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do relatório inválido']);
    exit;
}

try {
    // Marcar notificação como lida
    $stmt = $pdo->prepare("
        UPDATE relatorios_bugs 
        SET usuario_viu_resposta = TRUE 
        WHERE id_relatorio = ? 
        AND id_usuario = ?
        AND resposta_admin IS NOT NULL 
        AND resposta_admin != ''
    ");
    
    $result = $stmt->execute([$id_relatorio, $_SESSION['user_id']]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notificação marcada como lida']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Relatório não encontrado ou sem resposta']);
    }
    
} catch (Exception $e) {
    error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>
