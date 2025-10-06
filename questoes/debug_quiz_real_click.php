<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG QUIZ REAL - SIMULAÇÃO DE CLIQUE</h1>";

// Simular parâmetros do quiz_vertical_filtros.php
$id_assunto = 8;
$filtro_ativo = 'todas';
$questao_inicial = 92; // Usar a questão 92 que aparece na imagem

echo "<h2>Simulando quiz_vertical_filtros.php com questão #$questao_inicial:</h2>";

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
    
    // Embaralhar as alternativas para que a resposta correta apareça em posições diferentes
    // Usar seed aleatório para que as alternativas mudem a cada carregamento
    $seed = $questao['id_questao'] + time() + rand(1, 1000);
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<h3>2. Alternativas EMBARALHADAS (seed: $seed):</h3>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Encontrar a letra da alternativa correta após embaralhamento
    $letra_correta = '';
    $alternativa_correta = null;
    foreach ($alternativas_questao as $index => $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            $letra_correta = $letras[$index] ?? ($index + 1);
            break;
        }
    }
    
    echo "<h3>3. Alternativa correta após embaralhamento:</h3>";
    echo "<p>Letra correta: $letra_correta</p>";
    echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
    echo "<p>Texto: " . htmlspecialchars($alternativa_correta['texto']) . "</p>";
    
    // Simular cliques em diferentes alternativas
    echo "<h3>4. Simulando cliques (como no quiz real):</h3>";
    
    $cliques_teste = ['A', 'B', 'C', 'D'];
    foreach ($cliques_teste as $letra_clicada) {
        echo "<h4>Clique na letra $letra_clicada:</h4>";
        
        // Mapear a letra clicada para o ID
        $id_alternativa_clicada = null;
        foreach ($alternativas_questao as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            if ($letra === $letra_clicada) {
                $id_alternativa_clicada = $alt['id_alternativa'];
                break;
            }
        }
        
        // Verificar se acertou
        $acertou = ($id_alternativa_clicada == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        echo "<p>Letra clicada: $letra_clicada</p>";
        echo "<p>ID da alternativa clicada: $id_alternativa_clicada</p>";
        echo "<p>ID da alternativa correta: " . $alternativa_correta['id_alternativa'] . "</p>";
        echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
        
        // Simular resposta JSON
        $resposta_json = [
            'success' => true,
            'acertou' => (bool)$acertou,
            'alternativa_correta' => $letra_correta,
            'explicacao' => '',
            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
        ];
        
        echo "<p>Resposta JSON: " . json_encode($resposta_json) . "</p>";
        echo "<p style='color: " . ($acertou ? 'green' : 'red') . ";'>" . 
             ($acertou ? '✅ CORRETO' : '❌ INCORRETO') . "</p>";
        echo "<hr>";
    }
    
    // Simular o processamento do quiz_vertical_filtros.php
    echo "<h3>5. Simulando processamento do quiz_vertical_filtros.php:</h3>";
    
    // Simular POST request
    $_POST['id_questao'] = $questao['id_questao'];
    $_POST['alternativa_selecionada'] = 'B'; // Simular clique na letra B
    $_POST['ajax_request'] = '1';
    
    echo "<p>Simulando POST request:</p>";
    echo "<p>id_questao: " . $_POST['id_questao'] . "</p>";
    echo "<p>alternativa_selecionada: " . $_POST['alternativa_selecionada'] . "</p>";
    
    // Processar como no quiz_vertical_filtros.php
    $id_questao = $_POST['id_questao'];
    $alternativa_selecionada = $_POST['alternativa_selecionada'];
    
    // Mapear a letra selecionada para o ID
    $id_alternativa_selecionada = null;
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $alternativa_selecionada) {
            $id_alternativa_selecionada = $alt['id_alternativa'];
            break;
        }
    }
    
    echo "<p>ID da alternativa selecionada: $id_alternativa_selecionada</p>";
    
    // Verificar se acertou
    $acertou = ($id_alternativa_selecionada == $alternativa_correta['id_alternativa']) ? 1 : 0;
    
    echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
    
    // Resposta JSON
    $resposta_json = [
        'success' => true,
        'acertou' => (bool)$acertou,
        'alternativa_correta' => $letra_correta,
        'explicacao' => '',
        'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
    ];
    
    echo "<p>Resposta JSON final: " . json_encode($resposta_json) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Próximos passos:</h2>";
echo "<p>1. Verifique se a lógica está funcionando corretamente</p>";
echo "<p>2. Se não estiver, preciso corrigir o código</p>";
echo "<p>3. Se estiver, o problema pode ser no JavaScript</p>";
?>
