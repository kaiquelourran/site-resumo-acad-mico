<?php
require_once 'conexao.php';

echo "<h2>Verificação da Tabela Alternativas</h2>";

try {
    // Verificar estrutura da tabela
    $stmt = $pdo->query("DESCRIBE alternativas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estrutura da tabela alternativas:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach($colunas as $coluna) {
        echo "<tr>";
        echo "<td><strong>" . $coluna['Field'] . "</strong></td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar dados de exemplo
    echo "<h3>Exemplo de dados (primeiras 5 alternativas):</h3>";
    $stmt = $pdo->query("SELECT * FROM alternativas ORDER BY id_alternativa LIMIT 5");
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($dados) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach(array_keys($dados[0]) as $coluna) {
            echo "<th>" . $coluna . "</th>";
        }
        echo "</tr>";
        
        foreach($dados as $linha) {
            echo "<tr>";
            foreach($linha as $valor) {
                echo "<td>" . htmlspecialchars($valor) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum dado encontrado na tabela alternativas.</p>";
    }
    
    // Verificar se existe coluna 'letra'
    $tem_letra = false;
    foreach($colunas as $coluna) {
        if ($coluna['Field'] === 'letra') {
            $tem_letra = true;
            break;
        }
    }
    
    echo "<h3>Verificação da coluna 'letra':</h3>";
    if ($tem_letra) {
        echo "<p style='color: green;'>✓ A coluna 'letra' existe na tabela.</p>";
    } else {
        echo "<p style='color: red;'>✗ A coluna 'letra' NÃO existe na tabela.</p>";
        echo "<p><strong>PROBLEMA IDENTIFICADO:</strong> A query no quiz_vertical.php está tentando buscar 'alt_resp.letra', mas essa coluna não existe!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>