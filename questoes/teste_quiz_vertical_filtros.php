<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE QUIZ VERTICAL FILTROS</h1>";

// Gerar seed de sessão para embaralhamento consistente
if (!isset($_SESSION['quiz_seed'])) {
    $_SESSION['quiz_seed'] = rand(1, 10000);
}

echo "<h2>Seed de sessão: " . $_SESSION['quiz_seed'] . "</h2>";

// Simular parâmetros do quiz_vertical_filtros.php
$id_assunto = 8;
$filtro_ativo = 'todas';
$questao_inicial = 92;

echo "<h2>Testando questão #$questao_inicial:</h2>";

try {
    // Buscar questão específica
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
    $stmt->execute([$questao_inicial]);
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$questao) {
        echo "<p style='color: red;'>❌ Questão não encontrada</p>";
        exit;
    }
    
    echo "<h3>Questão encontrada: #" . $questao['id_questao'] . "</h3>";
    echo "<p>" . htmlspecialchars($questao['enunciado']) . "</p>";
    
    // Buscar alternativas da tabela 'alternativas'
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao['id_questao']]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>1. Alternativas ORIGINAIS (ordem do banco):</h3>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Simular o embaralhamento do quiz_vertical_filtros.php
    echo "<h3>2. Embaralhamento do quiz_vertical_filtros.php:</h3>";
    
    // Usar exatamente a mesma lógica do quiz_vertical_filtros.php
    $seed = $questao['id_questao'] * 1000 + ($_SESSION['quiz_seed'] ?? 0);
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<p>Seed usado: $seed</p>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Encontrar letra correta após embaralhamento
    $letra_correta = '';
    foreach ($alternativas_questao as $index => $alt) {
        if ($alt['eh_correta'] == 1) {
            $letra_correta = $letras[$index] ?? ($index + 1);
            break;
        }
    }
    
    echo "<p><strong>Letra correta após embaralhamento: $letra_correta</strong></p>";
    
    // Testar múltiplos embaralhamentos
    echo "<h3>3. Testando múltiplos embaralhamentos:</h3>";
    
    for ($i = 1; $i <= 5; $i++) {
        echo "<h4>Teste $i:</h4>";
        
        // Usar seed diferente para cada teste
        $seed_teste = $questao['id_questao'] * 1000 + $_SESSION['quiz_seed'] + $i;
        srand($seed_teste);
        
        $alt_teste = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        shuffle($alt_teste);
        
        echo "<p>Seed usado: $seed_teste</p>";
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
    
    // Verificar se o embaralhamento está funcionando
    echo "<h3>4. Verificação do embaralhamento:</h3>";
    
    // Fazer 3 embaralhamentos com seeds diferentes
    $embaralhamentos = [];
    for ($i = 0; $i < 3; $i++) {
        $seed = $questao['id_questao'] * 1000 + $_SESSION['quiz_seed'] + $i;
        srand($seed);
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Próximos passos:</h2>";
echo "<p>1. Se o embaralhamento está funcionando, o problema pode estar na exibição</p>";
echo "<p>2. Se não está funcionando, preciso corrigir o código</p>";
echo "<p>3. Verificar se há problema na lógica de verificação</p>";
?>
