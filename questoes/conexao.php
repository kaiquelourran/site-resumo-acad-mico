<?php
$host = "localhost";
$db   = "resumo_quiz"; // Nome do seu banco de dados local
$user = "root";   // Usuário padrão do XAMPP
$pass = "";       // Senha padrão do XAMPP (em branco)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// Helpers de segurança e sessão
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (!isset($_SESSION)) { session_start(); }
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
        if (!isset($_SESSION)) { session_start(); }
        return isset($_POST['csrf_token'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
}
?>