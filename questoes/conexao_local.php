<?php
// Configurar para não mostrar erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// =======================================================
// Configuração do banco de dados LOCAL (XAMPP)
// =======================================================

// Configurações para desenvolvimento local
$host = "localhost";
$db = "resumo_quiz_local";
$user = "root";
$pass = ""; // XAMPP padrão não tem senha para root

// =======================================================
// FIM DAS CONFIGURAÇÕES
// =======================================================

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Garantir nomes de meses/datas em PT-BR nas funções DATE_FORMAT
    $pdo->exec("SET lc_time_names = 'pt_BR'");
    
    // Log de sucesso (opcional para debug)
    error_log("Conexão com banco local estabelecida com sucesso");
    
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro de conexão com banco: " . $e->getMessage());
    
    // Em desenvolvimento, você pode mostrar o erro
    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
        die("Erro de conexão: " . $e->getMessage());
    }
    
    // Em produção, redirecionar para página de erro
    die("Erro interno do servidor. Tente novamente mais tarde.");
}
?>