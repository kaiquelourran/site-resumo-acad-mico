<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>RECRIANDO TABELA respostas_usuario</h1>";

try {
    // Dropar a tabela existente se existir
    echo "<h2>1. Removendo tabela existente (se houver):</h2>";
    $pdo->exec("DROP TABLE IF EXISTS respostas_usuario");
    echo "<p style='color: green;'>✅ Tabela removida (se existia)</p>";
    
    // Criar nova tabela com estrutura correta
    echo "<h2>2. Criando nova tabela com estrutura correta:</h2>";
    $sql_create = "CREATE TABLE respostas_usuario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL DEFAULT 1,
        id_questao INT NOT NULL,
        id_alternativa INT NOT NULL,
        acertou TINYINT(1) NOT NULL DEFAULT 0,
        data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_questao (user_id, id_questao)
    )";
    
    $pdo->exec($sql_create);
    echo "<p style='color: green;'>✅ Tabela criada com sucesso!</p>";
    
    // Verificar estrutura
    echo "<h2>3. Verificando estrutura da nova tabela:</h2>";
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Testar inserção
    echo "<h2>4. Testando inserção:</h2>";
    $user_id = 1;
    $id_questao = 1;
    $id_alternativa = 1;
    $acertou = 1;
    
    $stmt_resposta = $pdo->prepare("
        INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
        VALUES (?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        id_alternativa = VALUES(id_alternativa), 
        acertou = VALUES(acertou), 
        data_resposta = VALUES(data_resposta)
    ");
    
    $resultado = $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
    
    if ($resultado) {
        echo "<p style='color: green;'>✅ Inserção de teste bem-sucedida!</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha na inserção de teste</p>";
    }
    
    // Mostrar dados inseridos
    $stmt = $pdo->query("SELECT * FROM respostas_usuario ORDER BY id DESC LIMIT 5");
    $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($respostas)) {
        echo "<h3>Dados inseridos:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Questão</th><th>Alternativa</th><th>Acertou</th><th>Data</th></tr>";
        foreach ($respostas as $resp) {
            echo "<tr>";
            echo "<td>" . $resp['id'] . "</td>";
            echo "<td>" . $resp['user_id'] . "</td>";
            echo "<td>" . $resp['id_questao'] . "</td>";
            echo "<td>" . $resp['id_alternativa'] . "</td>";
            echo "<td>" . ($resp['acertou'] ? 'SIM' : 'NÃO') . "</td>";
            echo "<td>" . $resp['data_resposta'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>5. ✅ Tabela recriada com sucesso!</h2>";
    echo "<p>Agora você pode testar o quiz_vertical_filtros.php novamente.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
