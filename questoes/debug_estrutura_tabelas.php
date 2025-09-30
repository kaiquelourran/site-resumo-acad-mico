<?php
require_once 'conexao.php';

echo "<h2>Debug - Estrutura das Tabelas</h2>";

try {
    // Verificar estrutura da tabela questoes
    echo "<h3>Estrutura da tabela 'questoes':</h3>";
    $sql_desc_questoes = "DESCRIBE questoes";
    $stmt_desc = $pdo->query($sql_desc_questoes);
    $colunas_questoes = $stmt_desc->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas_questoes as $coluna) {
        $destaque = (strpos($coluna['Field'], 'alternativa_') === 0) ? 'style="background-color: #d4edda;"' : '';
        echo "<tr $destaque>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar se existe tabela alternativas
    echo "<h3>Verificando se existe tabela 'alternativas':</h3>";
    $sql_check_alternativas = "SHOW TABLES LIKE 'alternativas'";
    $stmt_check = $pdo->query($sql_check_alternativas);
    $tabela_alternativas_existe = $stmt_check->rowCount() > 0;
    
    if ($tabela_alternativas_existe) {
        echo "<p>✅ Tabela 'alternativas' existe!</p>";
        
        // Mostrar estrutura da tabela alternativas
        echo "<h4>Estrutura da tabela 'alternativas':</h4>";
        $sql_desc_alternativas = "DESCRIBE alternativas";
        $stmt_desc_alt = $pdo->query($sql_desc_alternativas);
        $colunas_alternativas = $stmt_desc_alt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($colunas_alternativas as $coluna) {
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
        
        // Mostrar dados da tabela alternativas
        echo "<h4>Dados na tabela 'alternativas':</h4>";
        $sql_dados_alt = "SELECT * FROM alternativas ORDER BY id_questao, id_alternativa LIMIT 20";
        $stmt_dados = $pdo->query($sql_dados_alt);
        $dados_alternativas = $stmt_dados->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($dados_alternativas)) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID Alt</th><th>ID Questão</th><th>Texto</th><th>É Correta</th></tr>";
            foreach ($dados_alternativas as $alt) {
                $destaque = $alt['eh_correta'] ? 'style="background-color: #d4edda;"' : '';
                echo "<tr $destaque>";
                echo "<td>" . $alt['id_alternativa'] . "</td>";
                echo "<td>" . $alt['id_questao'] . "</td>";
                echo "<td>" . substr($alt['texto'], 0, 50) . "...</td>";
                echo "<td>" . ($alt['eh_correta'] ? '✅ SIM' : '❌ NÃO') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ Nenhum dado encontrado na tabela alternativas</p>";
        }
        
    } else {
        echo "<p>❌ Tabela 'alternativas' NÃO existe!</p>";
    }
    
    // Verificar dados na tabela questoes (campos alternativa_a, etc.)
    echo "<h3>Dados dos campos alternativa_* na tabela questoes:</h3>";
    $sql_questoes_alt = "SELECT id_questao, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, alternativa_correta FROM questoes WHERE id_assunto = 8 LIMIT 5";
    $stmt_questoes = $pdo->prepare($sql_questoes_alt);
    $stmt_questoes->execute();
    $questoes_alt = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($questoes_alt)) {
        foreach ($questoes_alt as $q) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h4>Questão ID: " . $q['id_questao'] . "</h4>";
            echo "<p><strong>A:</strong> " . ($q['alternativa_a'] ?: '❌ VAZIO') . "</p>";
            echo "<p><strong>B:</strong> " . ($q['alternativa_b'] ?: '❌ VAZIO') . "</p>";
            echo "<p><strong>C:</strong> " . ($q['alternativa_c'] ?: '❌ VAZIO') . "</p>";
            echo "<p><strong>D:</strong> " . ($q['alternativa_d'] ?: '❌ VAZIO') . "</p>";
            echo "<p><strong>E:</strong> " . ($q['alternativa_e'] ?: '❌ VAZIO') . "</p>";
            echo "<p><strong>Correta:</strong> " . ($q['alternativa_correta'] ?: '❌ VAZIO') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ Nenhuma questão encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>