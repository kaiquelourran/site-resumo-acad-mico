<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG DO EMBARALHAMENTO DAS ALTERNATIVAS</h1>";

// Testar com a questão 99 que você mostrou no console
$id_questao = 99;

echo "<h2>Testando questão #$id_questao:</h2>";

try {
    // Buscar alternativas da tabela 'alternativas'
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de alternativas: " . count($alternativas_questao) . "</p>";
    
    echo "<h3>1. Alternativas ORIGINAIS (ordem do banco):</h3>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    echo "<h3>2. Testando embaralhamento com seed fixo (como no código):</h3>";
    
    // Usar exatamente a mesma lógica do quiz_vertical_filtros.php
    $seed = $id_questao + (int)date('Ymd');
    srand($seed);
    $alternativas_embaralhadas = $alternativas_questao;
    shuffle($alternativas_embaralhadas);
    
    echo "<p>Seed usado: $seed</p>";
    echo "<p>Data atual: " . date('Ymd') . "</p>";
    
    foreach ($alternativas_embaralhadas as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Verificar se mudou
    $mudou = false;
    for ($i = 0; $i < count($alternativas_questao); $i++) {
        if ($alternativas_questao[$i]['id_alternativa'] !== $alternativas_embaralhadas[$i]['id_alternativa']) {
            $mudou = true;
            break;
        }
    }
    
    echo "<p style='color: " . ($mudou ? 'green' : 'red') . "; font-size: 18px; font-weight: bold;'>" . 
         ($mudou ? '✅ EMBARALHAMENTO FUNCIONOU!' : '❌ EMBARALHAMENTO NÃO FUNCIONOU!') . "</p>";
    
    echo "<h3>3. Testando múltiplas vezes com MESMO seed (deve ser igual):</h3>";
    
    for ($teste = 1; $teste <= 3; $teste++) {
        echo "<h4>Teste $teste (mesmo seed):</h4>";
        
        $seed_teste = $id_questao + (int)date('Ymd');
        srand($seed_teste);
        $alt_teste = $alternativas_questao;
        shuffle($alt_teste);
        
        echo "<p>Seed: $seed_teste</p>";
        foreach ($alt_teste as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
        }
        echo "<hr>";
    }
    
    echo "<h3>4. Testando com seeds DIFERENTES (deve variar):</h3>";
    
    for ($teste = 1; $teste <= 3; $teste++) {
        echo "<h4>Teste $teste (seed diferente):</h4>";
        
        $seed_teste = $id_questao + (int)date('Ymd') + $teste;
        srand($seed_teste);
        $alt_teste = $alternativas_questao;
        shuffle($alt_teste);
        
        echo "<p>Seed: $seed_teste</p>";
        foreach ($alt_teste as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
        }
        echo "<hr>";
    }
    
    echo "<h3>5. Testando com seed baseado apenas no ID da questão (sem data):</h3>";
    
    $seed_simples = $id_questao;
    srand($seed_simples);
    $alt_simples = $alternativas_questao;
    shuffle($alt_simples);
    
    echo "<p>Seed simples: $seed_simples</p>";
    foreach ($alt_simples as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Verificar se mudou com seed simples
    $mudou_simples = false;
    for ($i = 0; $i < count($alternativas_questao); $i++) {
        if ($alternativas_questao[$i]['id_alternativa'] !== $alt_simples[$i]['id_alternativa']) {
            $mudou_simples = true;
            break;
        }
    }
    
    echo "<p style='color: " . ($mudou_simples ? 'green' : 'red') . "; font-size: 18px; font-weight: bold;'>" . 
         ($mudou_simples ? '✅ EMBARALHAMENTO SIMPLES FUNCIONOU!' : '❌ EMBARALHAMENTO SIMPLES NÃO FUNCIONOU!') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Próximos passos:</h2>";
echo "<p>1. Verifique se o embaralhamento funcionou nos testes acima</p>";
echo "<p>2. Se não funcionou, o problema é na lógica de embaralhamento</p>";
echo "<p>3. Se funcionou, o problema pode ser na exibição ou no JavaScript</p>";
?>
