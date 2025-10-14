<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    // Contar notificações não lidas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM relatorios_bugs 
        WHERE id_usuario = ? 
        AND resposta_admin IS NOT NULL 
        AND resposta_admin != '' 
        AND usuario_viu_resposta = FALSE
    ");
    
    $stmt->execute([$_SESSION['id_usuario']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['count' => intval($result['total'])]);
    
} catch (Exception $e) {
    error_log("Erro ao verificar notificações: " . $e->getMessage());
    echo json_encode(['count' => 0]);
}
?>
