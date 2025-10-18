<?php
/**
 * Arquivo de configuração para alternar entre ambiente local e Hostinger
 * 
 * Como usar:
 * 1. Para forçar ambiente local: ?ambiente=local
 * 2. Para forçar ambiente online: ?ambiente=online
 * 3. Para usar detecção automática: não use parâmetro ou use ?ambiente=auto
 * 
 * Exemplo:
 * http://localhost:8080/questoes/login.php?ambiente=local
 * http://localhost:8080/questoes/login.php?ambiente=online
 */

// Iniciar sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se foi solicitada alteração de ambiente
if (isset($_GET['ambiente'])) {
    $ambiente = $_GET['ambiente'];
    
    // Salvar configuração na sessão
    if ($ambiente === 'local') {
        $_SESSION['ambiente'] = 'local';
    } elseif ($ambiente === 'online') {
        $_SESSION['ambiente'] = 'online';
    } else {
        // Auto = detecção automática
        unset($_SESSION['ambiente']);
    }
    
    // Redirecionar para a mesma página sem o parâmetro
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirect_url");
    exit;
}

// Aplicar configuração de ambiente
if (isset($_SESSION['ambiente'])) {
    if ($_SESSION['ambiente'] === 'local') {
        // Forçar ambiente local
        $_SERVER['HTTP_HOST'] = 'localhost';
        error_log("Usando ambiente LOCAL (forçado por sessão)");
    } elseif ($_SESSION['ambiente'] === 'online') {
        // Forçar ambiente online
        $_SERVER['HTTP_HOST'] = 'resumoacademico.com.br';
        error_log("Usando ambiente ONLINE (forçado por sessão)");
    }
}

// Função para obter o ambiente atual
function get_ambiente() {
    $is_local = (
        $_SERVER['HTTP_HOST'] === 'localhost' || 
        $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
        strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0 ||
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') === 0
    );
    
    return $is_local ? 'local' : 'online';
}

// Função para criar links de alternância de ambiente
function ambiente_links() {
    $current_url = strtok($_SERVER['REQUEST_URI'], '?');
    $ambiente_atual = get_ambiente();
    
    $html = '<div class="ambiente-switch" style="position:fixed; bottom:10px; right:10px; background:rgba(0,0,0,0.7); padding:10px; border-radius:5px; color:white; z-index:9999;">';
    $html .= '<p style="margin:0 0 5px 0;"><strong>Ambiente:</strong> ' . strtoupper($ambiente_atual) . '</p>';
    $html .= '<a href="' . $current_url . '?ambiente=local" style="color:' . ($ambiente_atual == 'local' ? 'yellow' : 'white') . '; margin-right:10px;">Local</a> | ';
    $html .= '<a href="' . $current_url . '?ambiente=online" style="color:' . ($ambiente_atual == 'online' ? 'yellow' : 'white') . '; margin-right:10px;">Online</a> | ';
    $html .= '<a href="' . $current_url . '?ambiente=auto" style="color:white;">Auto</a>';
    $html .= '</div>';
    
    return $html;
}
?>