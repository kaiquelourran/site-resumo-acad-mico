<?php
require_once 'conexao.php';

echo "<h2>Criando Tabela de Usuários</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        google_id VARCHAR(255) NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";

    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Tabela 'usuarios' criada ou já existente!</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao criar tabela 'usuarios': " . $e->getMessage() . "</p>";
}
?>