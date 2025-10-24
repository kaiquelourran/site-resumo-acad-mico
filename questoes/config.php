<?php
/**
 * Arquivo de Configuração Global
 * Define constantes e configurações do sistema
 */

// Modo de desenvolvimento (alterar para false em produção)
define('DEV_MODE', true); // Alterar para false quando subir para produção

// Modo de debug (apenas se DEV_MODE estiver ativo)
define('DEBUG_MODE', DEV_MODE && true);

// Configurações de erro
if (DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Função auxiliar para debug (apenas em DEV_MODE)
if (!function_exists('debug_log')) {
    function debug_log($message, $data = null) {
        if (DEBUG_MODE) {
            $log_message = is_string($message) ? $message : print_r($message, true);
            if ($data !== null) {
                $log_message .= ': ' . print_r($data, true);
            }
            error_log('[DEBUG] ' . $log_message);
        }
    }
}

// Constantes de path (para evitar ../../../)
define('BASE_PATH', dirname(__DIR__));
define('QUESTOES_PATH', __DIR__);
define('ADMIN_PATH', __DIR__ . '/admin');

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>

