<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG DO EMBARALHAMENTO NO SERVIDOR</h1>";

// Simular POST request como se fosse do JavaScript
$_POST['id_questao'] = 99;
$_POST['alternativa_selecionada'] = 'C'; // Simular clique na letra C
$_POST['ajax_request'] = '1';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h2>Simulando POST request para questão 99, alternativa C:</h2>";

try {
    $id_questao = (int)$_POST['id_questao'];
    $alternativa_selecionada = $_POST['alternativa_selecionada'];
    
    echo "<p>ID da questão: $id_questao</p>";
    echo "<p>Alternativa selecionada: $alternativa_selecionada</p>";
    
    // Buscar as alternativas da questão para mapear a letra correta
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
    
    // Embaralhar da mesma forma que na exibição
    $seed = $id_questao + (int)date('Ymd');
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<h3>2. Alternativas EMBARALHADAS (seed: $seed):</h3>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Mapear a letra selecionada para o ID da alternativa
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
    
    if (!$id_alternativa) {
        echo "<p style='color: red;'>❌ ERRO: Não encontrou alternativa para letra: $alternativa_selecionada</p>";
        exit;
    }
    
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
        echo "<p style='color: red;'>❌ ERRO: Nenhuma alternativa correta encontrada!</p>";
        exit;
    }
    
    if ($alternativa_correta && $id_alternativa) {
        $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        echo "<h3>5. Verificação final:</h3>";
        echo "<p>ID selecionado: $id_alternativa</p>";
        echo "<p>ID correto: " . $alternativa_correta['id_alternativa'] . "</p>";
        echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
        
        // Simular resposta JSON como no código real
        $resposta_json = [
            'success' => true,
            'acertou' => (bool)$acertou,
            'alternativa_correta' => $letra_correta,
            'explicacao' => '',
            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
        ];
        
        echo "<h3>6. Resposta JSON que seria enviada:</h3>";
        echo "<pre>" . json_encode($resposta_json, JSON_PRETTY_PRINT) . "</pre>";
        
        echo "<p style='color: " . ($acertou ? 'green' : 'red') . "; font-size: 20px; font-weight: bold;'>" . 
             ($acertou ? '✅ RESPOSTA CORRETA!' : '❌ RESPOSTA INCORRETA!') . "</p>";
             
        if (!$acertou) {
            echo "<h3>7. Análise do problema:</h3>";
            echo "<p>Você selecionou a letra '$alternativa_selecionada' que corresponde ao ID $id_alternativa</p>";
            echo "<p>Mas a alternativa correta é a letra '$letra_correta' que corresponde ao ID " . $alternativa_correta['id_alternativa'] . "</p>";
            echo "<p>Para acertar, você deveria ter selecionado a letra '$letra_correta'</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ ERRO: alternativa não encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Próximos passos:</h2>";
echo "<p>1. Verifique se a lógica está funcionando corretamente</p>";
echo "<p>2. Se estiver, o problema pode ser no JavaScript ou na exibição</p>";
echo "<p>3. Teste no quiz real selecionando a letra correta</p>";
?>