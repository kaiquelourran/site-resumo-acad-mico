<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE VERIFICAÇÃO DE RESPOSTA CORRETA</h1>";

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
    
    // Encontrar a alternativa correta
    $alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            break;
        }
    }
    
    echo "<h3>2. Alternativa correta encontrada:</h3>";
    if ($alternativa_correta) {
        echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
        echo "<p>Texto: " . htmlspecialchars($alternativa_correta['texto']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Nenhuma alternativa correta encontrada!</p>";
    }
    
    echo "<h3>3. Testando embaralhamento e verificação:</h3>";
    
    // Embaralhar as alternativas
    $seed = $id_questao + time() + rand(1, 1000);
    srand($seed);
    $alternativas_embaralhadas = $alternativas_questao;
    shuffle($alternativas_embaralhadas);
    
    echo "<p>Seed usado: $seed</p>";
    
    echo "<h4>Alternativas EMBARALHADAS:</h4>";
    foreach ($alternativas_embaralhadas as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    echo "<h3>4. Testando verificação de resposta correta:</h3>";
    
    // Simular que o usuário selecionou a alternativa correta (letra C após embaralhamento)
    $letra_selecionada = 'C';
    $id_alternativa_selecionada = null;
    
    // Encontrar o ID da alternativa selecionada
    foreach ($alternativas_embaralhadas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_selecionada) {
            $id_alternativa_selecionada = $alt['id_alternativa'];
            break;
        }
    }
    
    echo "<p>Letra selecionada: $letra_selecionada</p>";
    echo "<p>ID da alternativa selecionada: $id_alternativa_selecionada</p>";
    echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
    
    // Verificar se acertou
    $acertou = ($id_alternativa_selecionada == $alternativa_correta['id_alternativa']) ? 1 : 0;
    echo "<p style='color: " . ($acertou ? 'green' : 'red') . "; font-size: 18px; font-weight: bold;'>" . 
         ($acertou ? '✅ RESPOSTA CORRETA!' : '❌ RESPOSTA INCORRETA!') . "</p>";
    
    echo "<h3>5. Testando com resposta incorreta:</h3>";
    
    // Simular que o usuário selecionou uma alternativa incorreta (letra A após embaralhamento)
    $letra_selecionada_errada = 'A';
    $id_alternativa_selecionada_errada = null;
    
    foreach ($alternativas_embaralhadas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_selecionada_errada) {
            $id_alternativa_selecionada_errada = $alt['id_alternativa'];
            break;
        }
    }
    
    echo "<p>Letra selecionada: $letra_selecionada_errada</p>";
    echo "<p>ID da alternativa selecionada: $id_alternativa_selecionada_errada</p>";
    echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
    
    // Verificar se acertou
    $acertou_errada = ($id_alternativa_selecionada_errada == $alternativa_correta['id_alternativa']) ? 1 : 0;
    echo "<p style='color: " . ($acertou_errada ? 'green' : 'red') . "; font-size: 18px; font-weight: bold;'>" . 
         ($acertou_errada ? '✅ RESPOSTA CORRETA!' : '❌ RESPOSTA INCORRETA!') . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Próximos passos:</h2>";
echo "<p>1. Verifique se a lógica de verificação está funcionando</p>";
echo "<p>2. Se não estiver, preciso corrigir o código</p>";
echo "<p>3. Se estiver, o problema pode ser no JavaScript</p>";
?>
