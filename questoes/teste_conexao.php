<?php
require_once __DIR__ . '/conexao.php';

try {
    $pdo->query("SELECT 1");
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>