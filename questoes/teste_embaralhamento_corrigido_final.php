<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DO EMBARALHAMENTO CORRIGIDO</h1>";

// Testar com a questão 99
$id_questao = 99;

echo "<h2>Testando questão #$id_questao com a nova lógica:</h2>";

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
    
    echo "<h3>2. Testando NOVA lógica de embaralhamento (como no código corrigido):</h3>";
    
    // Usar a nova lógica: ID + time() + rand(1, 1000)
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
         ($mudou ? '✅ NOVA LÓGICA FUNCIONOU!' : '❌ NOVA LÓGICA NÃO FUNCIONOU!') . "</p>";
    
    echo "<h3>3. Testando múltiplas vezes (deve variar a cada execução):</h3>";
    
    for ($teste = 1; $teste <= 3; $teste++) {
        echo "<h4>Teste $teste:</h4>";
        
        $seed_teste = $id_questao + time() + rand(1, 1000);
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Se funcionou, as alternativas devem embaralhar no quiz_vertical_filtros.php</p>";
echo "<p>2. Teste: <a href='quiz_vertical_filtros.php?id=8&filtro=todas' target='_blank'>quiz_vertical_filtros.php</a></p>";
echo "<p>3. Teste: <a href='quiz.php' target='_blank'>quiz.php</a></p>";
echo "<p>4. Recarregue a página várias vezes para ver se as alternativas mudam</p>";
?>
