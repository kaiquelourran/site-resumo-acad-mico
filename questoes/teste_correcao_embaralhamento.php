<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE CORREÇÃO DO EMBARALHAMENTO</h1>";

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
    
    // Testar embaralhamento da EXIBIÇÃO (como no quiz_vertical_filtros.php)
    echo "<h3>2. Embaralhamento da EXIBIÇÃO (linha 1034):</h3>";
    $seed_exibicao = $questao['id_questao'] + time() + rand(1, 1000);
    srand($seed_exibicao);
    $alternativas_exibicao = $alternativas_questao;
    shuffle($alternativas_exibicao);
    
    echo "<p>Seed usado: $seed_exibicao</p>";
    foreach ($alternativas_exibicao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Encontrar letra correta na exibição
    $letra_correta_exibicao = '';
    $alternativa_correta_exibicao = null;
    foreach ($alternativas_exibicao as $index => $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta_exibicao = $alt;
            $letra_correta_exibicao = $letras[$index] ?? ($index + 1);
            break;
        }
    }
    
    echo "<p><strong>Letra correta na exibição: $letra_correta_exibicao</strong></p>";
    
    // Testar embaralhamento do PROCESSAMENTO (como no quiz_vertical_filtros.php)
    echo "<h3>3. Embaralhamento do PROCESSAMENTO (linha 31):</h3>";
    $seed_processamento = $questao['id_questao'] + time() + rand(1, 1000);
    srand($seed_processamento);
    $alternativas_processamento = $alternativas_questao;
    shuffle($alternativas_processamento);
    
    echo "<p>Seed usado: $seed_processamento</p>";
    foreach ($alternativas_processamento as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Encontrar letra correta no processamento
    $letra_correta_processamento = '';
    $alternativa_correta_processamento = null;
    foreach ($alternativas_processamento as $index => $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta_processamento = $alt;
            $letra_correta_processamento = $letras[$index] ?? ($index + 1);
            break;
        }
    }
    
    echo "<p><strong>Letra correta no processamento: $letra_correta_processamento</strong></p>";
    
    // Verificar se os seeds são iguais
    echo "<h3>4. Verificação dos seeds:</h3>";
    echo "<p>Seed da exibição: $seed_exibicao</p>";
    echo "<p>Seed do processamento: $seed_processamento</p>";
    
    if ($seed_exibicao === $seed_processamento) {
        echo "<p style='color: green;'>✅ Seeds são iguais - embaralhamento consistente!</p>";
    } else {
        echo "<p style='color: red;'>❌ Seeds são diferentes - embaralhamento inconsistente!</p>";
    }
    
    // Verificar se as letras corretas são iguais
    echo "<h3>5. Verificação das letras corretas:</h3>";
    echo "<p>Letra correta na exibição: $letra_correta_exibicao</p>";
    echo "<p>Letra correta no processamento: $letra_correta_processamento</p>";
    
    if ($letra_correta_exibicao === $letra_correta_processamento) {
        echo "<p style='color: green;'>✅ Letras corretas são iguais - verificação consistente!</p>";
    } else {
        echo "<p style='color: red;'>❌ Letras corretas são diferentes - verificação inconsistente!</p>";
    }
    
    // Simular clique na letra correta
    echo "<h3>6. Simulando clique na letra correta ($letra_correta_exibicao):</h3>";
    
    // Mapear a letra clicada para o ID no processamento
    $id_alternativa_clicada = null;
    foreach ($alternativas_processamento as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_correta_exibicao) {
            $id_alternativa_clicada = $alt['id_alternativa'];
            break;
        }
    }
    
    // Verificar se acertou
    $acertou = ($id_alternativa_clicada == $alternativa_correta_processamento['id_alternativa']) ? 1 : 0;
    
    echo "<p>Letra clicada: $letra_correta_exibicao</p>";
    echo "<p>ID da alternativa clicada: $id_alternativa_clicada</p>";
    echo "<p>ID da alternativa correta: " . $alternativa_correta_processamento['id_alternativa'] . "</p>";
    echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
    
    if ($acertou) {
        echo "<p style='color: green;'>✅ CORREÇÃO FUNCIONOU! Alternativa correta é marcada como correta!</p>";
    } else {
        echo "<p style='color: red;'>❌ CORREÇÃO NÃO FUNCIONOU! Alternativa correta ainda é marcada como incorreta!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Próximos passos:</h2>";
echo "<p>1. Se a correção funcionou, teste no quiz real</p>";
echo "<p>2. Se não funcionou, preciso investigar mais</p>";
echo "<p>3. O problema pode estar em outro lugar</p>";
?>
