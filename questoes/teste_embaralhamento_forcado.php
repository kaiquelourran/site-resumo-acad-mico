<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE EMBARALHAMENTO FORÇADO</h1>";

// Testar com a questão 99
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
    
    echo "<h3>2. Testando embaralhamento FORÇADO:</h3>";
    
    // Forçar embaralhamento com seed diferente a cada execução
    $seed = $id_questao + time() + rand(1, 1000);
    srand($seed);
    $alternativas_embaralhadas = $alternativas_questao;
    shuffle($alternativas_embaralhadas);
    
    echo "<p>Seed usado: $seed</p>";
    echo "<p>Timestamp: " . time() . "</p>";
    
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
    
    echo "<p style='color: " . ($mudou ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>" . 
         ($mudou ? '✅ EMBARALHAMENTO FORÇADO FUNCIONOU!' : '❌ EMBARALHAMENTO FORÇADO NÃO FUNCIONOU!') . "</p>";
    
    // Se não funcionou, tentar método manual
    if (!$mudou) {
        echo "<h3>3. Tentando embaralhamento MANUAL:</h3>";
        
        // Embaralhamento manual
        $alternativas_manual = $alternativas_questao;
        for ($i = count($alternativas_manual) - 1; $i > 0; $i--) {
            $j = rand(0, $i);
            $temp = $alternativas_manual[$i];
            $alternativas_manual[$i] = $alternativas_manual[$j];
            $alternativas_manual[$j] = $temp;
        }
        
        echo "<p>Embaralhamento manual aplicado</p>";
        foreach ($alternativas_manual as $index => $alternativa) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
        }
        
        // Verificar se mudou manualmente
        $mudou_manual = false;
        for ($i = 0; $i < count($alternativas_questao); $i++) {
            if ($alternativas_questao[$i]['id_alternativa'] !== $alternativas_manual[$i]['id_alternativa']) {
                $mudou_manual = true;
                break;
            }
        }
        
        echo "<p style='color: " . ($mudou_manual ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>" . 
             ($mudou_manual ? '✅ EMBARALHAMENTO MANUAL FUNCIONOU!' : '❌ EMBARALHAMENTO MANUAL NÃO FUNCIONOU!') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Verifique se o embaralhamento funcionou</p>";
echo "<p>2. Se funcionou, vou corrigir o código com esse método</p>";
echo "<p>3. Se não funcionou, o problema é mais profundo</p>";
?>
