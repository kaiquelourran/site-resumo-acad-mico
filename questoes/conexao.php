
<?php
// Configurar para não mostrar erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// =======================================================
// Configuração do banco de dados
// =======================================================

// FORÇAR APENAS AMBIENTE LOCAL (XAMPP)
// Configurações para desenvolvimento local (XAMPP)
$host = "localhost";
$db = "resumo_quiz"; // CORREÇÃO 1: Nome do banco de dados que existe no phpMyAdmin
$user = "root";
$pass = ""; // XAMPP padrão não tem senha para root

// Definir que sempre está em ambiente local
$is_local = true;

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
    
    // CORREÇÃO 2: Forçar o uso de 'id_usuario' pois é o nome correto da coluna
    // no seu banco de dados local e será o nome na Hostinger.
    return 'id_usuario';
}

// Função auxiliar para inicializar sessão de forma segura
if (!function_exists('init_secure_session')) {
 function init_secure_session(): void {
     if (session_status() === PHP_SESSION_NONE) {
         ini_set('session.cookie_httponly', 1);
         ini_set('session.use_only_cookies', 1);
         ini_set('session.cookie_secure', 0); // Alterar para 1 com HTTPS
         ini_set('session.cookie_samesite', 'Lax');
         session_start();
         
         // Regenerar ID periodicamente
         if (!isset($_SESSION['last_regeneration'])) {
             $_SESSION['last_regeneration'] = time();
         } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
             session_regenerate_id(true);
             $_SESSION['last_regeneration'] = time();
         }
     }
 }
}

// Helpers de segurança e sessão
if (!function_exists('csrf_token')) {
 function csrf_token(): string {
     init_secure_session();
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
     init_secure_session();
     return isset($_POST['csrf_token'], $_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
 }
}

// Função auxiliar para ler JSON do php://input com segurança
if (!function_exists('get_json_input')) {
 function get_json_input($max_size = 1048576) { // 1MB default
     $input = file_get_contents('php://input', false, null, 0, $max_size);
     
     if ($input === false || empty($input)) {
         return null;
     }
     
     $data = json_decode($input, true);
     
     if (json_last_error() !== JSON_ERROR_NONE) {
         error_log('Erro ao decodificar JSON: ' . json_last_error_msg());
         return null;
     }
     
     return $data;
 }
}

// Função auxiliar para sanitizar inputs
if (!function_exists('sanitize_input')) {
 function sanitize_input($data, $type = 'string') {
     if (is_array($data)) {
         return array_map(function($item) use ($type) {
             return sanitize_input($item, $type);
         }, $data);
     }
     
     $data = trim($data);
     $data = stripslashes($data);
     
     switch ($type) {
         case 'int':
             return filter_var($data, FILTER_VALIDATE_INT) !== false ? (int)$data : 0;
         case 'float':
             return filter_var($data, FILTER_VALIDATE_FLOAT) !== false ? (float)$data : 0.0;
         case 'email':
             return filter_var($data, FILTER_SANITIZE_EMAIL);
         case 'url':
             return filter_var($data, FILTER_SANITIZE_URL);
         case 'html':
             return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
         default: // string
             return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
     }
 }
}
?> 