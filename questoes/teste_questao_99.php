<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE ESPECÍFICO DA QUESTÃO #99</h1>";

$id_questao = 99;

echo "<h2>1. Buscando questão #99:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
    $stmt->execute([$id_questao]);
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<p>✅ Questão encontrada: " . htmlspecialchars($questao['enunciado']) . "</p>";
    } else {
        echo "<p>❌ Questão não encontrada</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Buscando alternativas da questão #99:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt->execute([$id_questao]);
    $alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de alternativas: " . count($alternativas) . "</p>";
    
    echo "<h3>Alternativas ORIGINAIS (ordem do banco):</h3>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testando embaralhamento (como no quiz_vertical_filtros.php):</h2>";

// Usar a mesma lógica do quiz_vertical_filtros.php
$seed = $id_questao + (int)date('Ymd');
srand($seed);
$alternativas_embaralhadas = $alternativas;
shuffle($alternativas_embaralhadas);

echo "<p>Seed usado: $seed</p>";
echo "<p>Data atual: " . date('Ymd') . "</p>";

echo "<h3>Alternativas EMBARALHADAS:</h3>";
foreach ($alternativas_embaralhadas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
    echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
}

echo "<h2>4. Verificando se mudou a ordem:</h2>";
$mudou_ordem = false;
for ($i = 0; $i < count($alternativas); $i++) {
    if ($alternativas[$i]['id_alternativa'] !== $alternativas_embaralhadas[$i]['id_alternativa']) {
        $mudou_ordem = true;
        break;
    }
}

echo "<p style='color: " . ($mudou_ordem ? 'green' : 'red') . ";'>" . 
     ($mudou_ordem ? '✅ Embaralhamento funcionou - ordem mudou!' : '❌ Embaralhamento não funcionou - ordem igual!') . "</p>";

echo "<h2>5. Testando múltiplas vezes para ver se varia:</h2>";
for ($teste = 1; $teste <= 3; $teste++) {
    echo "<h3>Teste $teste:</h3>";
    
    $seed_teste = $id_questao + (int)date('Ymd') + $teste;
    srand($seed_teste);
    $alt_teste = $alternativas;
    shuffle($alt_teste);
    
    echo "<p>Seed: $seed_teste</p>";
    foreach ($alt_teste as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    echo "<hr>";
}

echo "<h2>6. Simulando HTML real do quiz_vertical_filtros.php:</h2>";
echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9;'>";
echo "<h3>Questão #$id_questao</h3>";
echo "<p>" . htmlspecialchars($questao['enunciado']) . "</p>";
echo "<div class='alternatives-container'>";

foreach ($alternativas_embaralhadas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta_class = $alt['eh_correta'] ? 'alternative-correct' : '';
    echo "<div class='alternative $correta_class' data-alternativa='$letra' data-alternativa-id='" . $alt['id_alternativa'] . "' data-questao-id='$id_questao'>";
    echo "<span class='alternative-letter'>$letra)</span>";
    echo "<span class='alternative-text'>" . htmlspecialchars($alt['texto']) . "</span>";
    echo "</div>";
}

echo "</div>";
echo "</div>";

echo "<h2>7. Próximos passos:</h2>";
echo "<p>1. Verifique se as alternativas estão em ordem diferente</p>";
echo "<p>2. Se não estiverem, o problema é no embaralhamento</p>";
echo "<p>3. Se estiverem, o problema pode ser no JavaScript</p>";
?>
