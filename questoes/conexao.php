
<?php
// Configurar para não mostrar erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// =======================================================
// Configuração do banco de dados
// =======================================================

// CONFIGURAÇÕES DE PRODUÇÃO (HOSTINGER) - **USE ESTE CÓDIGO NA HOSTINGER**
$host = "localhost"; // O host do MySQL da Hostinger é frequentemente 'localhost'
$db = "u775269467_questoes";
$user = "u775269467_kaique";
$pass = "deixar de ser curioso";

// Definir que NÃO está em ambiente local (MUDANÇA CRUCIAL PARA PRODUÇÃO)
$is_local = false; 

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

// Função auxiliar para determinar a coluna ID correta
function get_id_column($pdo) {
    global $is_local;
    
    // CORREÇÃO MANTIDA: Usa 'id_usuario' que é o nome correto da coluna
    return 'id_usuario';
}

// Helpers de segurança e sessão
if (!function_exists('csrf_token')) {
 function csrf_token(): string {
     if (session_status() === PHP_SESSION_NONE) { session_start(); }
     if (!isset($_SESSION['csrf_token'])) {
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
