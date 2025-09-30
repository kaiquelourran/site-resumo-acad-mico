<?php
require_once 'conexao.php';

echo "<h2>Verificação da Estrutura da Tabela 'questoes'</h2>";

try {
    // Mostrar estrutura da tabela questoes
    echo "<h3>Estrutura da tabela 'questoes':</h3>";
    $stmt = $pdo->query("DESCRIBE questoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar exemplo de dados
    echo "<h3>Exemplo de dados da tabela 'questoes' (primeiros 3 registros):</h3>";
    $stmt = $pdo->query("SELECT * FROM questoes LIMIT 3");
    $exemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($exemplos)) {
        echo "<table border='1'>";
        // Cabeçalho
        echo "<tr>";
        foreach (array_keys($exemplos[0]) as $coluna) {
            echo "<th>" . htmlspecialchars($coluna) . "</th>";
        }
        echo "</tr>";
        
        // Dados
        foreach ($exemplos as $exemplo) {
            echo "<tr>";
            foreach ($exemplo as $valor) {
                echo "<td>" . htmlspecialchars(substr($valor, 0, 50)) . (strlen($valor) > 50 ? '...' : '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar se existem colunas alternativa_a, alternativa_b, etc.
    echo "<h3>Verificação de colunas de alternativas:</h3>";
    $colunas_alternativas = ['alternativa_a', 'alternativa_b', 'alternativa_c', 'alternativa_d'];
    foreach ($colunas_alternativas as $coluna_alt) {
        $existe = false;
        foreach ($colunas as $coluna) {
            if ($coluna['Field'] === $coluna_alt) {
                $existe = true;
                break;
            }
        }
        echo "<p>Coluna '$coluna_alt': " . ($existe ? "EXISTE" : "NÃO EXISTE") . "</p>";
    }
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>