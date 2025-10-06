<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>SIMULAÇÃO DO QUIZ VERTICAL FILTROS</h1>";

// Simular parâmetros do quiz_vertical_filtros.php
$id_questao = 99;
$alternativa_selecionada = 'C'; // Simular que o usuário selecionou C

echo "<h2>Simulando questão #$id_questao, alternativa selecionada: $alternativa_selecionada</h2>";

try {
    // Buscar alternativas da tabela 'alternativas'
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>1. Alternativas ORIGINAIS (ordem do banco):</h3>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Embaralhar as alternativas (como no código)
    $seed = $id_questao + time() + rand(1, 1000);
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<h3>2. Alternativas EMBARALHADAS (seed: $seed):</h3>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Mapear a letra selecionada para o ID da alternativa (como no código)
    $id_alternativa = null;
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === strtoupper($alternativa_selecionada)) {
            $id_alternativa = $alternativa['id_alternativa'];
            break;
        }
    }
    
    echo "<h3>3. Mapeamento da letra selecionada:</h3>";
    echo "<p>Letra selecionada: $alternativa_selecionada</p>";
    echo "<p>ID da alternativa selecionada: $id_alternativa</p>";
    
    // Buscar a alternativa correta para esta questão
    $alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            break;
        }
    }
    
    echo "<h3>4. Alternativa correta:</h3>";
    if ($alternativa_correta) {
        echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
        echo "<p>Texto: " . htmlspecialchars($alternativa_correta['texto']) . "</p>";
        
        // Encontrar a letra da alternativa correta após embaralhamento
        $letra_correta = '';
        foreach ($alternativas_questao as $index => $alt) {
            if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                $letra_correta = $letras[$index] ?? ($index + 1);
                break;
            }
        }
        echo "<p>Letra da alternativa correta após embaralhamento: $letra_correta</p>";
    } else {
        echo "<p style='color: red;'>❌ Nenhuma alternativa correta encontrada!</p>";
    }
    
    if ($alternativa_correta && $id_alternativa) {
        $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        echo "<h3>5. Verificação final:</h3>";
        echo "<p>ID selecionado: $id_alternativa</p>";
        echo "<p>ID correto: " . $alternativa_correta['id_alternativa'] . "</p>";
        echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
        
        echo "<p style='color: " . ($acertou ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>" . 
             ($acertou ? '✅ RESPOSTA CORRETA!' : '❌ RESPOSTA INCORRETA!') . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro: alternativa não encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Próximos passos:</h2>";
echo "<p>1. Verifique se a lógica está funcionando corretamente</p>";
echo "<p>2. Se não estiver, preciso corrigir o código</p>";
echo "<p>3. Se estiver, o problema pode ser no JavaScript ou na exibição</p>";
?>
