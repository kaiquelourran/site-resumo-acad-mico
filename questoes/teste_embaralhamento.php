<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE EMBARALHAMENTO DAS ALTERNATIVAS</h1>";

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
    
    echo "<h3>Testando embaralhamento 5 vezes:</h3>";
    
    for ($teste = 1; $teste <= 5; $teste++) {
        echo "<h4>Teste $teste:</h4>";
        
        // Embaralhar
        $seed = $questao['id_questao'] + (int)date('Ymd') + $teste; // Adicionar $teste para variar
        srand($seed);
        $alternativas_embaralhadas = $alternativas; // Copiar array
        shuffle($alternativas_embaralhadas);
        
        echo "<p>Seed usado: $seed</p>";
        
        foreach ($alternativas_embaralhadas as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
        }
        echo "<hr>";
    }
    
    echo "<h3>Teste com seed fixo (deve ser igual sempre):</h3>";
    $seed_fixo = $questao['id_questao'] + (int)date('Ymd');
    srand($seed_fixo);
    $alternativas_fixas = $alternativas;
    shuffle($alternativas_fixas);
    
    echo "<p>Seed fixo: $seed_fixo</p>";
    foreach ($alternativas_fixas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    echo "<h3>Teste novamente com mesmo seed (deve ser igual):</h3>";
    srand($seed_fixo);
    $alternativas_fixas2 = $alternativas;
    shuffle($alternativas_fixas2);
    
    foreach ($alternativas_fixas2 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Verificar se são iguais
    $sao_iguais = true;
    for ($i = 0; $i < count($alternativas_fixas); $i++) {
        if ($alternativas_fixas[$i]['id_alternativa'] !== $alternativas_fixas2[$i]['id_alternativa']) {
            $sao_iguais = false;
            break;
        }
    }
    
    echo "<p style='color: " . ($sao_iguais ? 'green' : 'red') . ";'>" . 
         ($sao_iguais ? '✅ Embaralhamento consistente!' : '❌ Embaralhamento inconsistente!') . "</p>";
    
} else {
    echo "<p style='color: red;'>❌ Nenhuma questão encontrada no banco</p>";
}
?>
