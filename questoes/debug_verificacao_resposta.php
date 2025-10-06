<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG VERIFICAÇÃO DE RESPOSTA</h1>";

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
    
    // Encontrar alternativa correta original
    $alternativa_correta_original = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta_original = $alt;
            break;
        }
    }
    
    echo "<h3>2. Alternativa correta ORIGINAL:</h3>";
    echo "<p>ID: " . $alternativa_correta_original['id_alternativa'] . "</p>";
    echo "<p>Texto: " . htmlspecialchars($alternativa_correta_original['texto']) . "</p>";
    
    // Simular o processamento do quiz_vertical_filtros.php
    echo "<h3>3. Simulando processamento do quiz_vertical_filtros.php:</h3>";
    
    // Embaralhar as alternativas para que a resposta correta apareça em posições diferentes
    // Usar seed aleatório para que as alternativas mudem a cada carregamento
    $seed = $questao['id_questao'] + time() + rand(1, 1000);
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<p>Seed usado: $seed</p>";
    echo "<p>Alternativas embaralhadas:</p>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Mapear a letra selecionada para o ID da alternativa
    $letras = ['A', 'B', 'C', 'D', 'E'];
    $id_alternativa = null;
    
    // Simular clique na letra B
    $alternativa_selecionada = 'B';
    echo "<p>Simulando clique na letra: $alternativa_selecionada</p>";
    
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === strtoupper($alternativa_selecionada)) {
            $id_alternativa = $alternativa['id_alternativa'];
            break;
        }
    }
    
    echo "<p>ID da alternativa selecionada: $id_alternativa</p>";
    
    // Buscar a alternativa correta para esta questão
    $alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        // Usar apenas o campo que sabemos que existe
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            break;
        }
    }
    
    echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
    
    if ($alternativa_correta && $id_alternativa) {
        $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
        
        // Encontrar a letra da alternativa correta após embaralhamento
        $letra_correta = '';
        $letras = ['A', 'B', 'C', 'D', 'E'];
        foreach ($alternativas_questao as $index => $alt) {
            if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                $letra_correta = $letras[$index] ?? ($index + 1);
                break;
            }
        }
        
        echo "<p>Letra da alternativa correta após embaralhamento: $letra_correta</p>";
        
        // Resposta JSON
        $resposta_json = [
            'success' => true,
            'acertou' => (bool)$acertou,
            'alternativa_correta' => $letra_correta, // Retornar a LETRA, não o ID
            'explicacao' => '', // Explicação não disponível na tabela alternativas
            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
        ];
        
        echo "<p>Resposta JSON: " . json_encode($resposta_json) . "</p>";
        
        if ($acertou) {
            echo "<p style='color: green;'>✅ LÓGICA FUNCIONANDO! Alternativa correta é marcada como correta!</p>";
        } else {
            echo "<p style='color: red;'>❌ LÓGICA COM PROBLEMA! Alternativa correta está sendo marcada como incorreta!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Erro: alternativa correta ou selecionada não encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Se a lógica está funcionando, o problema pode estar no JavaScript</p>";
echo "<p>2. Se a lógica não está funcionando, preciso corrigir o código</p>";
echo "<p>3. Verificar se há problema na verificação das respostas</p>";
?>
