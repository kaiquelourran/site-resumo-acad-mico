<?php
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se o cookie de sessão existir, destrói o cookie também
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir a sessão
session_destroy();

// Redirecionar para a página de login com parâmetro para permitir troca de conta imediata
header("Location: login.php?trocar=1");
exit;
?>