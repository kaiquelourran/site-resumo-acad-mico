<?php
/**
 * Configuração do Modo de Manutenção
 * Este arquivo controla se o site está em modo de manutenção
 */

// Define se o modo de manutenção está ativo (true = ativo, false = inativo)
$maintenance_mode = false;

// Mensagem personalizada para exibir durante a manutenção
$maintenance_message = "Estamos realizando uma manutenção programada. Voltaremos em breve!";

// Data/hora estimada para o fim da manutenção (opcional)
$maintenance_end_time = "2024-01-15 14:00:00";

// IPs que podem acessar o site mesmo durante a manutenção (administradores)
$allowed_ips = [
    '127.0.0.1',
    '::1',
    // Adicione outros IPs de administradores aqui
];

/**
 * Função para verificar se o modo de manutenção está ativo
 */
function is_maintenance_mode() {
    global $maintenance_mode;
    return $maintenance_mode;
}

/**
 * Função para verificar se o IP atual tem permissão para acessar durante a manutenção
 */
function is_allowed_ip() {
    global $allowed_ips;
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($user_ip, $allowed_ips);
}

/**
 * Função para ativar/desativar o modo de manutenção
 */
function toggle_maintenance_mode($status) {
    $config_file = __FILE__;
    $content = file_get_contents($config_file);
    
    if ($status) {
        $content = preg_replace('/\$maintenance_mode = false;/', '$maintenance_mode = true;', $content);
    } else {
        $content = preg_replace('/\$maintenance_mode = true;/', '$maintenance_mode = false;', $content);
    }
    
    return file_put_contents($config_file, $content);
}
?>