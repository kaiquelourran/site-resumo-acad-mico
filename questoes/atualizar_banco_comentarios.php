<?php
require_once 'conexao.php';

try {
    // Adicionar colunas para curtidas e respostas
    $sql = "ALTER TABLE comentarios_questoes 
            ADD COLUMN IF NOT EXISTS curtidas INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS id_comentario_pai INT NULL,
            ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT TRUE,
            ADD INDEX idx_curtidas (curtidas),
            ADD INDEX idx_pai (id_comentario_pai),
            ADD FOREIGN KEY (id_comentario_pai) REFERENCES comentarios_questoes(id_comentario) ON DELETE CASCADE";
    
    $pdo->exec($sql);
    echo "✅ Colunas adicionadas com sucesso!<br>";
    
    // Criar tabela de curtidas de usuários
    $sql = "CREATE TABLE IF NOT EXISTS curtidas_comentarios (
        id_curtida INT AUTO_INCREMENT PRIMARY KEY,
        id_comentario INT NOT NULL,
        ip_usuario VARCHAR(45) NOT NULL,
        data_curtida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_curtida (id_comentario, ip_usuario),
        FOREIGN KEY (id_comentario) REFERENCES comentarios_questoes(id_comentario) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✅ Tabela de curtidas criada!<br>";
    
    // Verificar estrutura atualizada
    $stmt = $pdo->query("DESCRIBE comentarios_questoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estrutura atualizada da tabela comentarios_questoes:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
