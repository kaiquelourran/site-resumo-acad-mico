<?php
require_once 'conexao.php';

// Buscar uma questão específica
$id_questao = 92;
echo "<h1>TESTE DEFINITIVO - Questão $id_questao</h1>";

// Buscar alternativas
$stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt->execute([$id_questao]);
$alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Alternativas encontradas:</h2>";
echo "<pre>";
print_r($alternativas);
echo "</pre>";

// Identificar qual campo marca como correta
$campos_possiveis = ['eh_correta', 'correta', 'is_correct', 'correct', 'acertou', 'correta_flag', 'flag_correta', 'marcada_correta'];

echo "<h2>Testando campos para identificar alternativa correta:</h2>";
foreach ($campos_possiveis as $campo) {
    if (isset($alternativas[0][$campo])) {
        echo "<p><strong>$campo:</strong> ";
        foreach ($alternativas as $i => $alt) {
            echo "Alt $i = " . $alt[$campo] . " | ";
        }
        echo "</p>";
    }
}

// Testar com campo 'texto' ou similar
$campos_texto = ['texto', 'alternativa_texto', 'descricao', 'conteudo', 'text', 'alternativa'];
echo "<h2>Campos de texto disponíveis:</h2>";
foreach ($campos_texto as $campo) {
    if (isset($alternativas[0][$campo])) {
        echo "<p><strong>$campo:</strong> ";
        foreach ($alternativas as $i => $alt) {
            echo "Alt $i = '" . htmlspecialchars($alt[$campo]) . "' | ";
        }
        echo "</p>";
    }
}

// Simular o embaralhamento
echo "<h2>Teste de embaralhamento:</h2>";
$seed = $id_questao + (int)date('Ymd');
srand($seed);
shuffle($alternativas);

echo "<p>Seed usado: $seed</p>";
echo "<p>Alternativas após embaralhamento:</p>";
echo "<pre>";
print_r($alternativas);
echo "</pre>";

// Testar a lógica de identificação
$letras = ['A', 'B', 'C', 'D', 'E'];
echo "<h2>Teste de identificação de alternativa correta:</h2>";

foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    echo "<p>Alternativa $letra (índice $index):</p>";
    echo "<ul>";
    foreach ($campos_possiveis as $campo) {
        if (isset($alt[$campo])) {
            echo "<li>$campo = " . $alt[$campo] . " " . ($alt[$campo] == 1 ? "✅ CORRETA" : "❌") . "</li>";
        }
    }
    echo "</ul>";
}
?>

