<?php
// Configurar para não mostrar erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// =======================================================
// Configuração do banco de dados - CREDENCIAIS HOSTRINGER
// =======================================================

// Hostinger DB Host (Confirmado: srv2023.hstgr.io)
$host = "srv2023.hstgr.io";

// Hostinger DB Name (Confirmado: u775269467_questoes)
$db  = "u775269467_questoes";

// Hostinger DB User (Confirmado: u775269467_kaique)
$user = "u775269467_kaique";

// Hostinger DB Password: *** COLOQUE A SUA SENHA REAL AQUI! ***
$pass = "Mel976@24"; 

// =======================================================
// FIM DAS CONFIGURAÇÕES
// =======================================================

try {
 $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 // Garantir nomes de meses/datas em PT-BR nas funções DATE_FORMAT
 $pdo->exec("SET lc_time_names = 'pt_BR'");
} catch (PDOException $e) {
 // Log do erro em vez de exibir
 error_log("Erro ao conectar ao banco: " . $e->getMessage());
 
 // Se for uma requisição AJAX, retornar JSON
 if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
     header('Content-Type: application/json');
     echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
     exit;
 }
 
 die("Erro ao conectar com o banco de dados");}

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