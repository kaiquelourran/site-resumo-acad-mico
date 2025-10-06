<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>CORRIGINDO TABELA respostas_usuario</h1>";

try {
    // Verificar estrutura atual da tabela
    echo "<h2>1. Estrutura atual da tabela respostas_usuario:</h2>";
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($colunas)) {
        echo "<p style='color: red;'>❌ Tabela não existe, criando...</p>";
        
        // Criar tabela com estrutura correta
        $sql_create = "CREATE TABLE respostas_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            id_questao INT NOT NULL,
            id_alternativa INT NOT NULL,
            acertou TINYINT(1) NOT NULL DEFAULT 0,
            data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_questao (user_id, id_questao)
        )";
        
        $pdo->exec($sql_create);
        echo "<p style='color: green;'>✅ Tabela criada com sucesso!</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabela existe</p>";
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
        
        // Verificar se tem a coluna user_id
        $tem_user_id = false;
        foreach ($colunas as $coluna) {
            if ($coluna['Field'] === 'user_id') {
                $tem_user_id = true;
                break;
            }
        }
        
        if (!$tem_user_id) {
            echo "<h2>2. Adicionando coluna user_id:</h2>";
            $sql_add_user_id = "ALTER TABLE respostas_usuario ADD COLUMN user_id INT NOT NULL AFTER id";
            $pdo->exec($sql_add_user_id);
            echo "<p style='color: green;'>✅ Coluna user_id adicionada!</p>";
            
            // Adicionar índice único
            $sql_add_index = "ALTER TABLE respostas_usuario ADD UNIQUE KEY unique_user_questao (user_id, id_questao)";
            $pdo->exec($sql_add_index);
            echo "<p style='color: green;'>✅ Índice único adicionado!</p>";
        } else {
            echo "<p style='color: green;'>✅ Coluna user_id já existe</p>";
        }
    }
    
    // Verificar estrutura final
    echo "<h2>3. Estrutura final da tabela:</h2>";
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas_finais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas_finais as $coluna) {
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
        echo "<h3>Últimas respostas inseridas:</h3>";
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Próximos passos:</h2>";
echo "<p>1. Acesse este arquivo para corrigir a tabela</p>";
echo "<p>2. Teste novamente o quiz_vertical_filtros.php</p>";
echo "<p>3. Verifique se o feedback visual está funcionando</p>";
?>