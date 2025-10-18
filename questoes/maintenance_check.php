<?php
/**
 * Verificação de Modo de Manutenção
 * Este arquivo deve ser incluído no início de todas as páginas públicas
 */

// Inclui a configuração de manutenção
require_once __DIR__ . '/maintenance_config.php';

// Verifica se o modo de manutenção está ativo
if (is_maintenance_mode()) {
    // Verifica se o usuário atual é um administrador ou tem IP permitido
    $is_admin = false;
    $is_allowed_ip = is_allowed_ip();
    
    // Verifica se há uma sessão ativa e se o usuário é admin
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] === 'admin') {
        $is_admin = true;
    }
    
    // Se não é admin e não tem IP permitido, redireciona para a página de manutenção
    if (!$is_admin && !$is_allowed_ip) {
        // Verifica se já não está na página de manutenção para evitar loop
        $current_page = basename($_SERVER['PHP_SELF']);
        if ($current_page !== 'maintenance.php') {
            header('Location: maintenance.php');
            exit;
        }
    }
}
?>