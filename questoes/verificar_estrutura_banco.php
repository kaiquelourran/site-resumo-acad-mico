<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>VERIFICAÇÃO DA ESTRUTURA DO BANCO</h1>";

// Verificar se a tabela respostas_usuario existe e tem a estrutura correta
echo "<h2>1. Verificando tabela respostas_usuario:</h2>";

try {
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($colunas)) {
        echo "<p style='color: red;'>❌ Tabela respostas_usuario não existe!</p>";
        
        // Criar a tabela
        echo "<p>Criando tabela respostas_usuario...</p>";
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
        echo "<p style='color: green;'>✅ Tabela respostas_usuario existe</p>";
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
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

// Verificar se há dados na tabela
echo "<h2>2. Verificando dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas: $total</p>";
    
    if ($total > 0) {
        $stmt = $pdo->query("SELECT * FROM respostas_usuario LIMIT 5");
        $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimas 5 respostas:</h3>";
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
    echo "<p style='color: red;'>❌ Erro ao verificar dados: " . $e->getMessage() . "</p>";
}

// Verificar se há questões e alternativas
echo "<h2>3. Verificando questões e alternativas:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
    $total_questoes = $stmt->fetchColumn();
    echo "<p>Total de questões: $total_questoes</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas");
    $total_alternativas = $stmt->fetchColumn();
    echo "<p>Total de alternativas: $total_alternativas</p>";
    
    if ($total_questoes > 0) {
        $stmt = $pdo->query("SELECT q.id_questao, q.enunciado, COUNT(a.id_alternativa) as num_alternativas FROM questoes q LEFT JOIN alternativas a ON q.id_questao = a.id_questao GROUP BY q.id_questao LIMIT 3");
        $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Primeiras 3 questões:</h3>";
        foreach ($questoes as $q) {
            echo "<p><strong>Questão " . $q['id_questao'] . ":</strong> " . htmlspecialchars(substr($q['enunciado'], 0, 100)) . "... (Alternativas: " . $q['num_alternativas'] . ")</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar questões: " . $e->getMessage() . "</p>";
}

// Testar inserção de uma resposta de teste
echo "<h2>4. Testando inserção de resposta:</h2>";
try {
    // Simular dados de teste
    $user_id = 1;
    $id_questao = 1;
    $id_alternativa = 1;
    $acertou = 1;
    
    $stmt = $pdo->prepare("
        INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
        VALUES (?, ?, ?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE 
        id_alternativa = VALUES(id_alternativa), 
        acertou = VALUES(acertou), 
        data_resposta = VALUES(data_resposta)
    ");
    
    $resultado = $stmt->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
    
    if ($resultado) {
        echo "<p style='color: green;'>✅ Inserção de teste bem-sucedida!</p>";
    } else {
        echo "<p style='color: red;'>❌ Falha na inserção de teste</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na inserção de teste: " . $e->getMessage() . "</p>";
}
?>
