<?php
// Arquivo de teste para verificar o sistema de comentários
require_once 'conexao.php';

echo "<h1>🧪 Teste do Sistema de Comentários</h1>";

try {
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'comentarios_questoes'");
    $tabelaExiste = $stmt->rowCount() > 0;
    
    if (!$tabelaExiste) {
        echo "<h2>❌ Tabela não existe. Criando...</h2>";
        
        // Criar tabela
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
    } else {
        echo "<h2>✅ Tabela 'comentarios_questoes' já existe!</h2>";
    }
    
    // Verificar estrutura da tabela
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
    
    // Testar inserção de comentário de teste
    $stmt = $pdo->prepare("SELECT id_questao FROM questoes LIMIT 1");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<h3>Testando inserção de comentário...</h3>";
        
        // Inserir comentário de teste
        $stmt = $pdo->prepare("
            INSERT INTO comentarios_questoes (id_questao, nome_usuario, email_usuario, comentario) 
            VALUES (?, ?, ?, ?)
        ");
        
        $resultado = $stmt->execute([
            $questao['id_questao'],
            'Usuário Teste',
            'teste@exemplo.com',
            'Este é um comentário de teste para verificar se o sistema está funcionando corretamente.'
        ]);
        
        if ($resultado) {
            echo "✅ Comentário de teste inserido com sucesso!<br>";
            
            // Buscar comentários
            $stmt = $pdo->prepare("
                SELECT *, 
                       DATE_FORMAT(data_comentario, '%d/%m/%Y às %H:%i') as data_formatada
                FROM comentarios_questoes 
                WHERE id_questao = ? 
                ORDER BY data_comentario DESC
            ");
            $stmt->execute([$questao['id_questao']]);
            $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Comentários encontrados:</h3>";
            foreach ($comentarios as $comentario) {
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                echo "<strong>Autor:</strong> " . htmlspecialchars($comentario['nome_usuario']) . "<br>";
                echo "<strong>Data:</strong> " . $comentario['data_formatada'] . "<br>";
                echo "<strong>Comentário:</strong> " . htmlspecialchars($comentario['comentario']) . "<br>";
                echo "</div>";
            }
        } else {
            echo "❌ Erro ao inserir comentário de teste!<br>";
        }
    } else {
        echo "<h3>⚠️ Nenhuma questão encontrada no banco de dados</h3>";
    }
    
    // Testar API
    echo "<h3>Testando API de comentários...</h3>";
    echo "<p><a href='api_comentarios.php?id_questao=" . ($questao['id_questao'] ?? 1) . "' target='_blank'>Testar API GET</a></p>";
    
} catch (PDOException $e) {
    echo "❌ Erro no banco de dados: " . $e->getMessage();
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}

echo "<h3>Links de Teste:</h3>";
echo "<p><a href='quiz_vertical_filtros.php?id=1&filtro=todas' target='_blank'>Testar Quiz com Comentários</a></p>";
echo "<p><a href='index.php'>Voltar ao Início</a></p>";
?>
