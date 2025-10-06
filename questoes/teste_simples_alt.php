<?php
require_once 'conexao.php';

// Testar questão 92
$id_questao = 92;

echo "<h1>Teste Simples - Questão $id_questao</h1>";

// Buscar alternativas
$stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt->execute([$id_questao]);
$alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Alternativas da questão:</h2>";
foreach ($alternativas as $i => $alt) {
    echo "<p><strong>Alternativa $i:</strong></p>";
    echo "<ul>";
    foreach ($alt as $campo => $valor) {
        echo "<li>$campo: " . htmlspecialchars($valor) . "</li>";
    }
    echo "</ul>";
}

// Testar embaralhamento
echo "<h2>Teste de embaralhamento:</h2>";
$seed = $id_questao + (int)date('Ymd');
srand($seed);
shuffle($alternativas);

$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $eh_correta = $alt['eh_correta'] ?? 'N/A';
    echo "<p>Posição $index (Letra $letra): ID={$alt['id_alternativa']}, eh_correta=$eh_correta</p>";
}
?>

