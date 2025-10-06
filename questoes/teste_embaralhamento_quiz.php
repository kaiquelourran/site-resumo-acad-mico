<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE EMBARALHAMENTO NO QUIZ</h1>";

// Buscar uma questão para testar
$stmt = $pdo->prepare("SELECT * FROM questoes LIMIT 1");
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    echo "<h2>Questão ID: " . $questao['id_questao'] . "</h2>";
    echo "<p>Enunciado: " . htmlspecialchars($questao['enunciado']) . "</p>";
    
    // Buscar alternativas
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao['id_questao']]);
    $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Alternativas ORIGINAIS (sem embaralhamento):</h3>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    echo "<h3>Testando embaralhamento com seed fixo (como no quiz.php):</h3>";
    
    // Usar a mesma lógica do quiz.php
    $seed = $questao['id_questao'] + (int)date('Ymd');
    srand($seed);
    $alternativas_embaralhadas = $alternativas;
    shuffle($alternativas_embaralhadas);
    
    echo "<p>Seed usado: $seed</p>";
    echo "<p>Data atual: " . date('Ymd') . "</p>";
    
    foreach ($alternativas_embaralhadas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    echo "<h3>Testando novamente com mesmo seed (deve ser igual):</h3>";
    srand($seed);
    $alternativas_embaralhadas2 = $alternativas;
    shuffle($alternativas_embaralhadas2);
    
    foreach ($alternativas_embaralhadas2 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Verificar se são iguais
    $sao_iguais = true;
    for ($i = 0; $i < count($alternativas_embaralhadas); $i++) {
        if ($alternativas_embaralhadas[$i]['id_alternativa'] !== $alternativas_embaralhadas2[$i]['id_alternativa']) {
            $sao_iguais = false;
            break;
        }
    }
    
    echo "<p style='color: " . ($sao_iguais ? 'green' : 'red') . ";'>" . 
         ($sao_iguais ? '✅ Embaralhamento consistente!' : '❌ Embaralhamento inconsistente!') . "</p>";
    
    echo "<h3>Testando com seed diferente (deve ser diferente):</h3>";
    $seed2 = $questao['id_questao'] + (int)date('Ymd') + 1;
    srand($seed2);
    $alternativas_embaralhadas3 = $alternativas;
    shuffle($alternativas_embaralhadas3);
    
    echo "<p>Seed usado: $seed2</p>";
    
    foreach ($alternativas_embaralhadas3 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Verificar se são diferentes
    $sao_diferentes = false;
    for ($i = 0; $i < count($alternativas_embaralhadas); $i++) {
        if ($alternativas_embaralhadas[$i]['id_alternativa'] !== $alternativas_embaralhadas3[$i]['id_alternativa']) {
            $sao_diferentes = true;
            break;
        }
    }
    
    echo "<p style='color: " . ($sao_diferentes ? 'green' : 'red') . ";'>" . 
         ($sao_diferentes ? '✅ Embaralhamento variável!' : '❌ Embaralhamento sempre igual!') . "</p>";
    
} else {
    echo "<p style='color: red;'>❌ Nenhuma questão encontrada no banco</p>";
}

echo "<h2>Testando no quiz real:</h2>";
echo "<p><a href='quiz.php' target='_blank'>Acessar quiz.php</a></p>";
echo "<p><a href='quiz_vertical_filtros.php?id=8&filtro=todas' target='_blank'>Acessar quiz_vertical_filtros.php</a></p>";
?>
