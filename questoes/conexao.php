<?php
// Configurar para não mostrar erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// =======================================================
// Configuração do banco de dados
// =======================================================

// Detectar se estamos em ambiente local ou produção
$is_local = (
    $_SERVER['HTTP_HOST'] === 'localhost' || 
    $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
    strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0 ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1:') === 0
);

if ($is_local) {
    // Configurações para desenvolvimento local (XAMPP)
    $host = "localhost";
    $db = "resumo_quiz_local";
    $user = "root";
    $pass = ""; // XAMPP padrão não tem senha para root
} else {
    // Configurações para produção (Hostinger)
    $host = "localhost";
    $db = "u775269467_questoes";
    $user = "u775269467_kaique";
    $pass = "Kaique1976@24";
}

// =======================================================
// FIM DAS CONFIGURAÇÕES
// =======================================================

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Garantir nomes de meses/datas em PT-BR nas funções DATE_FORMAT
    $pdo->exec("SET lc_time_names = 'pt_BR'");
    
    // Log de sucesso (opcional para debug)
    $env = $is_local ? 'local' : 'produção';
    error_log("Conexão com banco ($env) estabelecida com sucesso");
    
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro de conexão com banco: " . $e->getMessage());
    
    // Em desenvolvimento, você pode mostrar o erro
    if ($is_local && isset($_GET['debug']) && $_GET['debug'] == '1') {
        die("Erro de conexão: " . $e->getMessage());
    }
    
    // Em produção, redirecionar para página de erro
    die("Erro interno do servidor. Tente novamente mais tarde.");
}

// Helpers de segurança e sessão
if (!function_exists('csrf_token')) {
 function csrf_token(): string {
     if (session_status() === PHP_SESSION_NONE) { session_start(); }
     if (empty($_SESSION['csrf_token'])) {
         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
     }
     return $_SESSION['csrf_token'];
 }
}

if (!function_exists('csrf_field')) {
 function csrf_field(): string {
     return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
 }
}

if (!function_exists('validate_csrf')) {
 function validate_csrf(): bool {
     if (session_status() === PHP_SESSION_NONE) { session_start(); }
     return isset($_POST['csrf_token'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
 }
}
?>