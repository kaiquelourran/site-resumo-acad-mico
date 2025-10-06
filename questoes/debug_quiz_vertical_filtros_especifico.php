<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG QUIZ VERTICAL FILTROS ESPECÍFICO</h1>";

// Gerar seed de sessão para embaralhamento consistente
if (!isset($_SESSION['quiz_seed'])) {
    $_SESSION['quiz_seed'] = rand(1, 10000);
}

echo "<h2>Seed de sessão: " . $_SESSION['quiz_seed'] . "</h2>";

// Testar questão 92
$questao_id = 92;

try {
    // Buscar alternativas
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao_id]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Questão #$questao_id:</h3>";
    
    // Mostrar alternativas originais
    echo "<h4>1. Alternativas ORIGINAIS (ordem do banco):</h4>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
    // Testar embaralhamento exatamente como no quiz_vertical_filtros.php
    echo "<h4>2. Embaralhamento exatamente como no quiz_vertical_filtros.php:</h4>";
    
    // Usar exatamente a mesma lógica do quiz_vertical_filtros.php
    $seed = $questao_id * 1000 + ($_SESSION['quiz_seed'] ?? 0);
    echo "<p>Seed calculado: $seed</p>";
    
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<p>Alternativas após embaralhamento:</p>";
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
    // Encontrar letra correta
    $letra_correta = '';
    foreach ($alternativas_questao as $index => $alt) {
        if ($alt['eh_correta'] == 1) {
            $letra_correta = $letras[$index] ?? ($index + 1);
            break;
        }
    }
    
    echo "<p><strong>Letra correta após embaralhamento: $letra_correta</strong></p>";
    
    // Testar múltiplos embaralhamentos com a mesma lógica
    echo "<h4>3. Testando múltiplos embaralhamentos com a mesma lógica:</h4>";
    
    for ($i = 1; $i <= 5; $i++) {
        echo "<h5>Teste $i:</h5>";
        
        // Buscar alternativas novamente (como no quiz_vertical_filtros.php)
        $stmt_alt->execute([$questao_id]);
        $alt_teste = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        // Usar seed diferente para cada teste
        $seed_teste = $questao_id * 1000 + $_SESSION['quiz_seed'] + $i;
        echo "<p>Seed usado: $seed_teste</p>";
        
        srand($seed_teste);
        shuffle($alt_teste);
        
        echo "<p>Alternativas após embaralhamento:</p>";
        foreach ($alt_teste as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
        }
        
        // Encontrar letra correta
        $letra_correta_teste = '';
        foreach ($alt_teste as $index => $alt) {
            if ($alt['eh_correta'] == 1) {
                $letra_correta_teste = $letras[$index] ?? ($index + 1);
                break;
            }
        }
        
        echo "<p><strong>Letra correta: $letra_correta_teste</strong></p>";
        echo "<hr>";
    }
    
    // Verificar se o problema está na busca das alternativas
    echo "<h4>4. Verificando se o problema está na busca das alternativas:</h4>";
    
    // Fazer 3 embaralhamentos com seeds diferentes
    $embaralhamentos = [];
    for ($i = 0; $i < 3; $i++) {
        $seed = $questao_id * 1000 + $_SESSION['quiz_seed'] + $i;
        srand($seed);
        
        // Buscar alternativas novamente
        $stmt_alt->execute([$questao_id]);
        $alt_emb = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        shuffle($alt_emb);
        $embaralhamentos[] = $alt_emb;
    }
    
    // Verificar se os embaralhamentos são diferentes
    $sao_diferentes = true;
    for ($i = 0; $i < count($embaralhamentos) - 1; $i++) {
        for ($j = $i + 1; $j < count($embaralhamentos); $j++) {
            $ids1 = array_column($embaralhamentos[$i], 'id_alternativa');
            $ids2 = array_column($embaralhamentos[$j], 'id_alternativa');
            if ($ids1 === $ids2) {
                $sao_diferentes = false;
                break 2;
            }
        }
    }
    
    if ($sao_diferentes) {
        echo "<p style='color: green;'>✅ EMBARALHAMENTO FUNCIONANDO! As alternativas mudam de posição.</p>";
    } else {
        echo "<p style='color: red;'>❌ EMBARALHAMENTO NÃO FUNCIONANDO! As alternativas não mudam de posição.</p>";
    }
    
    // Testar se o problema está no srand
    echo "<h4>5. Testando se o problema está no srand:</h4>";
    
    $array_teste = [1, 2, 3, 4, 5];
    echo "<p>Array original: " . implode(', ', $array_teste) . "</p>";
    
    for ($i = 0; $i < 3; $i++) {
        $seed_teste = $questao_id * 1000 + $_SESSION['quiz_seed'] + $i;
        srand($seed_teste);
        $array_emb = $array_teste;
        shuffle($array_emb);
        echo "<p>Seed $seed_teste: " . implode(', ', $array_emb) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Próximos passos:</h2>";
echo "<p>1. Se o embaralhamento está funcionando, o problema pode estar na exibição</p>";
echo "<p>2. Se não está funcionando, preciso corrigir o código</p>";
echo "<p>3. Verificar se há problema na lógica de verificação</p>";
?>
