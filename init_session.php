<?php
/**
 * Inicialização Segura de Sessão
 * Arquivo centralizado para iniciar sessões de forma segura
 */

// Verificar se a sessão já foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    // Configurações de segurança da sessão
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Alterar para 1 quando tiver HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    
    // Iniciar a sessão
    session_start();
    
    // Regenerar ID da sessão periodicamente (a cada 30 minutos)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
?>

