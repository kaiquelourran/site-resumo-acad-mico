<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE EMBARALHAMENTO REAL</h1>";

// Simular parâmetros do quiz_vertical_filtros.php
$id_assunto = 8;
$filtro_ativo = 'erradas';
$questao_inicial = 92;

echo "<h2>Parâmetros:</h2>";
echo "<p>ID Assunto: $id_assunto</p>";
echo "<p>Filtro: $filtro_ativo</p>";
echo "<p>Questão Inicial: $questao_inicial</p>";

// Buscar questão específica (questão 99 como no seu exemplo)
$id_questao = 99;

echo "<h2>Testando questão #$id_questao:</h2>";

try {
    // Buscar alternativas da tabela 'alternativas'
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de alternativas encontradas: " . count($alternativas_questao) . "</p>";
    
    echo "<h3>Alternativas ORIGINAIS (ordem do banco):</h3>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    echo "<h3>Testando embaralhamento (como no quiz_vertical_filtros.php):</h3>";
    
    // Embaralhar as alternativas para que a resposta correta apareça em posições diferentes
    // Usar o ID da questão como seed para manter consistência durante a sessão
    $seed = $id_questao + (int)date('Ymd'); // Seed baseado no ID + data
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<p>Seed usado: $seed</p>";
    echo "<p>Data atual: " . date('Ymd') . "</p>";
    
    echo "<h4>Alternativas EMBARALHADAS:</h4>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alternativa['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alternativa['texto']) . $correta . " [ID: " . $alternativa['id_alternativa'] . "]</p>";
    }
    
    // Testar múltiplas vezes para ver se varia
    echo "<h3>Testando múltiplas vezes (deve ser igual com mesmo seed):</h3>";
    
    for ($teste = 1; $teste <= 3; $teste++) {
        echo "<h4>Teste $teste (mesmo seed):</h4>";
        
        $seed_teste = $id_questao + (int)date('Ymd');
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
    
    // Testar com seeds diferentes
    echo "<h3>Testando com seeds diferentes (deve variar):</h3>";
    
    for ($teste = 1; $teste <= 3; $teste++) {
        echo "<h4>Teste $teste (seed diferente):</h4>";
        
        $seed_teste = $id_questao + (int)date('Ymd') + $teste;
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
    
    // Simular HTML real
    echo "<h3>Simulando HTML real do quiz_vertical_filtros.php:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9;'>";
    echo "<h4>Questão #$id_questao</h4>";
    echo "<div class='alternatives-container'>";
    
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta_class = $alternativa['eh_correta'] ? 'alternative-correct' : '';
        echo "<div class='alternative $correta_class' data-alternativa='$letra' data-alternativa-id='" . $alternativa['id_alternativa'] . "' data-questao-id='$id_questao'>";
        echo "<span class='alternative-letter'>$letra)</span>";
        echo "<span class='alternative-text'>" . htmlspecialchars($alternativa['texto']) . "</span>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>Próximos passos:</h2>";
echo "<p>1. Verifique se as alternativas mudam de posição entre os testes</p>";
echo "<p>2. Se não mudarem, o problema é no embaralhamento</p>";
echo "<p>3. Se mudarem, o problema pode ser no JavaScript ou na exibição</p>";
?>
