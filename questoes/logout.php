<?php
session_start();
+// Evitar bloqueio de postMessage/revoke pelo COOP
+header('Cross-Origin-Opener-Policy: unsafe-none');

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se desejar destruir a sessão completamente, apague também o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir a sessão
session_destroy();

// Renderizar uma pequena página para revogar o Google e depois redirecionar
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Saindo...</title>
  <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
  <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
  <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
<script>
  // Desabilitar auto seleção e revogar o consentimento do Google
  function doGoogleLogout() {
    try {
      if (window.google && google.accounts && google.accounts.id) {
        google.accounts.id.disableAutoSelect();
        const email = localStorage.getItem('google_email');
        if (email) {
          google.accounts.id.revoke(email, () => {
            // Limpar armazenamento local
            try { localStorage.removeItem('google_email'); } catch(e) {}
            // Redirecionar para login
            window.location.href = 'login.php?message=logout_success';
          });
          return;
        }
      }
    } catch (e) { console.warn('Falha ao revogar login Google:', e); }
    // Fallback de redirecionamento
    window.location.href = 'login.php?message=logout_success';
  }
  // Aguarda carregar o script do Google e executa
  if (document.readyState === 'complete') {
    doGoogleLogout();
  } else {
    window.addEventListener('load', doGoogleLogout);
  }
</script>
</body>
</html>