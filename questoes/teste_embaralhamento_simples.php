<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE EMBARALHAMENTO SIMPLES</h1>";

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
    $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Questão #$questao_id:</h3>";
    
    // Mostrar alternativas originais
    echo "<h4>1. Alternativas ORIGINAIS (ordem do banco):</h4>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
    // Testar embaralhamento múltiplas vezes
    echo "<h4>2. Testando embaralhamento múltiplas vezes:</h4>";
    
    for ($i = 1; $i <= 5; $i++) {
        echo "<h5>Teste $i:</h5>";
        
        // Usar seed baseado na sessão + número do teste
        $seed = $questao_id * 1000 + $_SESSION['quiz_seed'] + $i;
        srand($seed);
        
        // Fazer uma cópia das alternativas
        $alternativas_embaralhadas = $alternativas;
        shuffle($alternativas_embaralhadas);
        
        echo "<p>Seed usado: $seed</p>";
        foreach ($alternativas_embaralhadas as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
        }
        echo "<hr>";
    }
    
    // Testar embaralhamento com seed fixo (como no quiz_vertical_filtros.php)
    echo "<h4>3. Testando embaralhamento com seed fixo (como no quiz_vertical_filtros.php):</h4>";
    
    $seed_fixo = $questao_id * 1000 + $_SESSION['quiz_seed'];
    srand($seed_fixo);
    
    $alternativas_embaralhadas = $alternativas;
    shuffle($alternativas_embaralhadas);
    
    echo "<p>Seed usado: $seed_fixo</p>";
    foreach ($alternativas_embaralhadas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
    // Verificar se o embaralhamento está funcionando
    echo "<h4>4. Verificação do embaralhamento:</h4>";
    
    // Fazer 3 embaralhamentos com seeds diferentes
    $embaralhamentos = [];
    for ($i = 0; $i < 3; $i++) {
        $seed = $questao_id * 1000 + $_SESSION['quiz_seed'] + $i;
        srand($seed);
        $alt_emb = $alternativas;
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Próximos passos:</h2>";
echo "<p>1. Se o embaralhamento está funcionando, o problema pode estar em outro lugar</p>";
echo "<p>2. Se não está funcionando, preciso corrigir o código</p>";
echo "<p>3. Verificar se há problema na lógica de verificação</p>";
?>
