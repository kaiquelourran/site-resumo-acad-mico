<?php
require_once 'conexao.php';

echo "<h1>Estrutura da Tabela Questões</h1>";

try {
    $stmt = $pdo->query("DESCRIBE questoes");
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar algumas questões de exemplo
    echo "<h2>Exemplo de Questões (Assunto ID 8)</h2>";
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_assunto = ? LIMIT 3");
    $stmt->execute([8]);
    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($questoes) > 0) {
        echo "<table border='1'>";
        // Cabeçalho
        echo "<tr>";
        foreach (array_keys($questoes[0]) as $coluna) {
            echo "<th>$coluna</th>";
        }
        echo "</tr>";
        
        // Dados
        foreach ($questoes as $questao) {
            echo "<tr>";
            foreach ($questao as $valor) {
                echo "<td>" . htmlspecialchars(substr($valor, 0, 50)) . (strlen($valor) > 50 ? '...' : '') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>