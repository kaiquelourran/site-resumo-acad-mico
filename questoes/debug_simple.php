<?php
require_once 'conexao.php';

$sql = "SELECT * FROM questoes WHERE id_assunto = 1 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug Simples</h2>";
echo "<p><strong>Questão encontrada:</strong> " . ($questao ? 'SIM' : 'NÃO') . "</p>";

if ($questao) {
    echo "<p><strong>ID:</strong> " . $questao['id_questao'] . "</p>";
    echo "<p><strong>Enunciado:</strong> " . substr($questao['enunciado'], 0, 100) . "...</p>";
    
    $alternativas = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $letra) {
        $campo = 'alternativa_' . strtolower($letra);
        $valor = $questao[$campo] ?? '';
        echo "<p><strong>$letra:</strong> " . ($valor ? substr($valor, 0, 50) . "..." : "VAZIA") . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>HTML das Alternativas:</h3>";
    
    foreach ($alternativas as $letra) {
        $campo = 'alternativa_' . strtolower($letra);
        if (!empty($questao[$campo])) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
            echo "<strong>$letra:</strong> " . htmlspecialchars($questao[$campo]);
            echo "</div>";
        }
    }
}
?>