<?php
session_start();

// Verifica se o usuário está logado E se ele tem o tipo 'admin'
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Incluir o arquivo de configuração de manutenção
require_once __DIR__ . '/../maintenance_config.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verifica CSRF token
if (!validate_csrf()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
    exit;
}

// Pega o status atual e inverte
$current_status = is_maintenance_mode();
$new_status = !$current_status;

// Tenta alterar o status
$result = toggle_maintenance_mode($new_status);

if ($result !== false) {
    $status_text = $new_status ? 'ativado' : 'desativado';
    echo json_encode([
        'success' => true, 
        'message' => "Modo de manutenção $status_text com sucesso!",
        'maintenance_mode' => $new_status
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao alterar o modo de manutenção'
    ]);
}
?>