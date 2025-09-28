<?php
require_once __DIR__ . '/conexao.php';

echo "<h2>Correção da Tabela respostas_usuario</h2>";

try {
    // 1. Verificar constraints existentes
    echo "<h3>1. Verificando constraints existentes:</h3>";
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME, 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = 'resumo_quiz' 
        AND TABLE_NAME = 'respostas_usuario' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($constraints)) {
        echo "<p>✅ Nenhuma constraint de foreign key encontrada.</p>";
    } else {
        foreach ($constraints as $constraint) {
            echo "<p>🔍 Constraint: {$constraint['CONSTRAINT_NAME']} - {$constraint['COLUMN_NAME']} → {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</p>";
        }
    }
    
    // 2. Remover constraints se existirem
    echo "<h3>2. Removendo constraints problemáticas:</h3>";
    foreach ($constraints as $constraint) {
        try {
            $sql = "ALTER TABLE respostas_usuario DROP FOREIGN KEY {$constraint['CONSTRAINT_NAME']}";
            $pdo->exec($sql);
            echo "<p>✅ Constraint {$constraint['CONSTRAINT_NAME']} removida com sucesso.</p>";
        } catch (Exception $e) {
            echo "<p>❌ Erro ao remover constraint {$constraint['CONSTRAINT_NAME']}: " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. Recriar a tabela com estrutura mais flexível
    echo "<h3>3. Recriando tabela com estrutura otimizada:</h3>";
    
    // Backup dos dados existentes
    $stmt = $pdo->query("SELECT * FROM respostas_usuario");
    $dados_backup = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>📦 Backup de " . count($dados_backup) . " registros criado.</p>";
    
    // Drop e recriar tabela
    $pdo->exec("DROP TABLE IF EXISTS respostas_usuario");
    
    $sql_create = "CREATE TABLE respostas_usuario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_questao INT NOT NULL,
        id_alternativa INT NOT NULL,
        acertou TINYINT(1) NOT NULL DEFAULT 0,
        data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_questao (id_questao),
        INDEX idx_questao (id_questao),
        INDEX idx_alternativa (id_alternativa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_create);
    echo "<p>✅ Tabela recriada com sucesso (sem foreign keys rígidas).</p>";
    
    // 4. Restaurar dados
    echo "<h3>4. Restaurando dados:</h3>";
    if (!empty($dados_backup)) {
        $stmt = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) VALUES (?, ?, ?, ?)");
        
        foreach ($dados_backup as $registro) {
            $stmt->execute([
                $registro['id_questao'],
                $registro['id_alternativa'],
                $registro['acertou'],
                $registro['data_resposta']
            ]);
        }
        echo "<p>✅ " . count($dados_backup) . " registros restaurados com sucesso.</p>";
    }
    
    // 5. Testar inserção
    echo "<h3>5. Testando inserção:</h3>";
    try {
        $stmt = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id_alternativa = VALUES(id_alternativa), acertou = VALUES(acertou)");
        $resultado = $stmt->execute([999, 999, 1]);
        
        if ($resultado) {
            echo "<p>✅ Teste de inserção bem-sucedido.</p>";
            // Remove o registro de teste
            $pdo->exec("DELETE FROM respostas_usuario WHERE id_questao = 999");
            echo "<p>🧹 Registro de teste removido.</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Erro no teste de inserção: " . $e->getMessage() . "</p>";
    }
    
    // 6. Verificar estrutura final
    echo "<h3>6. Estrutura final da tabela:</h3>";
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $estrutura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($estrutura as $campo) {
        echo "<tr>";
        echo "<td>{$campo['Field']}</td>";
        echo "<td>{$campo['Type']}</td>";
        echo "<td>{$campo['Null']}</td>";
        echo "<td>{$campo['Key']}</td>";
        echo "<td>{$campo['Default']}</td>";
        echo "<td>{$campo['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Correção concluída com sucesso!</h3>";
    echo "<p><strong>A tabela respostas_usuario agora pode receber dados sem restrições de foreign key rígidas.</strong></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Erro durante a correção:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>