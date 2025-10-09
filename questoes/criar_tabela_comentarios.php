<?php
require_once 'conexao.php';

try {
    // Criar tabela de comentários
    $sql = "CREATE TABLE IF NOT EXISTS comentarios_questoes (
        id_comentario INT AUTO_INCREMENT PRIMARY KEY,
        id_questao INT NOT NULL,
        nome_usuario VARCHAR(100) NOT NULL,
        email_usuario VARCHAR(100),
        comentario TEXT NOT NULL,
        data_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        aprovado BOOLEAN DEFAULT TRUE,
        INDEX idx_questao (id_questao),
        INDEX idx_data (data_comentario),
        FOREIGN KEY (id_questao) REFERENCES questoes(id_questao) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    echo "✅ Tabela 'comentarios_questoes' criada com sucesso!<br>";
    
    // Verificar se a tabela foi criada
    $stmt = $pdo->query("DESCRIBE comentarios_questoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estrutura da tabela:</h3>";
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
    echo "❌ Erro ao criar tabela: " . $e->getMessage();
}
?>
