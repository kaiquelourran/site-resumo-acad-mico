<?php
require_once 'conexao.php';

echo "<h2>Debug Questão Raw</h2>";

// Buscar uma questão específica
$sql = "SELECT * FROM questoes WHERE id_assunto = 8 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    echo "<h3>Dados brutos da questão:</h3>";
    echo "<pre>";
    print_r($questao);
    echo "</pre>";
    
    echo "<h3>Verificação campo por campo:</h3>";
    $campos_alternativas = ['alternativa_a', 'alternativa_b', 'alternativa_c', 'alternativa_d', 'alternativa_e'];
    
    foreach ($campos_alternativas as $campo) {
        $valor = isset($questao[$campo]) ? $questao[$campo] : 'CAMPO NÃO EXISTE';
        $vazio = empty($questao[$campo]) ? 'SIM' : 'NÃO';
        echo "<p><strong>$campo:</strong> Existe: " . (isset($questao[$campo]) ? 'SIM' : 'NÃO') . " | Vazio: $vazio | Valor: " . substr($valor, 0, 100) . "</p>";
    }
    
    echo "<h3>Teste do loop das alternativas:</h3>";
    $alternativas = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $letra) {
        $campo_alternativa = 'alternativa_' . strtolower($letra);
        $existe = isset($questao[$campo_alternativa]);
        $vazio = empty($questao[$campo_alternativa]);
        $valor = $existe ? $questao[$campo_alternativa] : 'N/A';
        
        echo "<p><strong>Letra $letra (campo: $campo_alternativa):</strong></p>";
        echo "<ul>";
        echo "<li>Campo existe: " . ($existe ? 'SIM' : 'NÃO') . "</li>";
        echo "<li>Campo vazio: " . ($vazio ? 'SIM' : 'NÃO') . "</li>";
        echo "<li>Condição !empty(): " . (!empty($questao[$campo_alternativa]) ? 'TRUE (vai renderizar)' : 'FALSE (não vai renderizar)') . "</li>";
        echo "<li>Valor: " . htmlspecialchars(substr($valor, 0, 100)) . "</li>";
        echo "</ul>";
    }
} else {
    echo "<p>❌ Nenhuma questão encontrada!</p>";
}

// Verificar estrutura da tabela
echo "<hr><h3>Estrutura da tabela questoes:</h3>";
$sql_desc = "DESCRIBE questoes";
$stmt_desc = $pdo->query($sql_desc);
$colunas = $stmt_desc->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse;'>";
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
?>