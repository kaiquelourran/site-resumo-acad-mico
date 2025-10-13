<?php
require_once 'conexao.php';

echo "<h2>Corrigindo Tabela de Usuários</h2>";

try {
    // 1. Verificar se a tabela existe
    $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
    
    if (empty($tables)) {
        // Criar tabela do zero
        $sql = "
        CREATE TABLE usuarios (
            id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            senha VARCHAR(255) NULL,
            google_id VARCHAR(255) NULL UNIQUE,
            avatar_url VARCHAR(512) NULL,
            tipo ENUM('usuario', 'admin') DEFAULT 'usuario',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Tabela 'usuarios' criada com estrutura completa!</p>";
    } else {
        // Tabela existe, vamos corrigir
        echo "<p style='color: blue;'>📋 Tabela 'usuarios' já existe. Verificando estrutura...</p>";
        
        // Verificar colunas existentes
        $columns = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
        
        // Adicionar colunas que faltam
        if (!in_array('senha', $columns)) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN senha VARCHAR(255) NULL");
            echo "<p style='color: green;'>✅ Coluna 'senha' adicionada!</p>";
        }
        
        if (!in_array('tipo', $columns)) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN tipo ENUM('usuario', 'admin') DEFAULT 'usuario'");
            echo "<p style='color: green;'>✅ Coluna 'tipo' adicionada!</p>";
        }
        
        if (!in_array('google_id', $columns)) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) NULL UNIQUE");
            echo "<p style='color: green;'>✅ Coluna 'google_id' adicionada!</p>";
        }
        
        if (!in_array('avatar_url', $columns)) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(512) NULL");
            echo "<p style='color: green;'>✅ Coluna 'avatar_url' adicionada!</p>";
        }
        
        if (!in_array('updated_at', $columns)) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "<p style='color: green;'>✅ Coluna 'updated_at' adicionada!</p>";
        }
        
        // Renomear coluna id para id_usuario se necessário
        if (in_array('id', $columns) && !in_array('id_usuario', $columns)) {
            $pdo->exec("ALTER TABLE usuarios CHANGE id id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY");
            echo "<p style='color: green;'>✅ Coluna 'id' renomeada para 'id_usuario'!</p>";
        }
    }
    
    // Verificar estrutura final
    echo "<h3>Estrutura final da tabela:</h3>";
    $final_columns = $pdo->query("DESCRIBE usuarios")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach ($final_columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Tabela 'usuarios' corrigida com sucesso!</p>";
    echo "<p style='color: blue;'>Agora o cadastro manual deve funcionar perfeitamente!</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao corrigir tabela 'usuarios': " . $e->getMessage() . "</p>";
}
?>
