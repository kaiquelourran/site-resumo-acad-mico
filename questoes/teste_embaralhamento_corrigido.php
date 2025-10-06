<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE EMBARALHAMENTO CORRIGIDO</h1>";

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
    
    echo "<h3>2. Testando diferentes métodos de embaralhamento:</h3>";
    
    // Método 1: Seed baseado no ID + data (atual)
    echo "<h4>Método 1: Seed ID + data</h4>";
    $seed1 = $id_questao + (int)date('Ymd');
    srand($seed1);
    $alt1 = $alternativas_questao;
    shuffle($alt1);
    echo "<p>Seed: $seed1</p>";
    foreach ($alt1 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Método 2: Seed baseado apenas no ID
    echo "<h4>Método 2: Seed apenas ID</h4>";
    $seed2 = $id_questao;
    srand($seed2);
    $alt2 = $alternativas_questao;
    shuffle($alt2);
    echo "<p>Seed: $seed2</p>";
    foreach ($alt2 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Método 3: Seed baseado no ID + timestamp
    echo "<h4>Método 3: Seed ID + timestamp</h4>";
    $seed3 = $id_questao + time();
    srand($seed3);
    $alt3 = $alternativas_questao;
    shuffle($alt3);
    echo "<p>Seed: $seed3</p>";
    foreach ($alt3 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Método 4: Seed aleatório
    echo "<h4>Método 4: Seed aleatório</h4>";
    $seed4 = rand(1, 1000);
    srand($seed4);
    $alt4 = $alternativas_questao;
    shuffle($alt4);
    echo "<p>Seed: $seed4</p>";
    foreach ($alt4 as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
    }
    
    // Verificar qual método funcionou
    echo "<h3>3. Verificando qual método funcionou:</h3>";
    
    $metodos = [
        'ID + data' => $alt1,
        'ID apenas' => $alt2,
        'ID + timestamp' => $alt3,
        'Aleatório' => $alt4
    ];
    
    foreach ($metodos as $nome => $alt) {
        $mudou = false;
        for ($i = 0; $i < count($alternativas_questao); $i++) {
            if ($alternativas_questao[$i]['id_alternativa'] !== $alt[$i]['id_alternativa']) {
                $mudou = true;
                break;
            }
        }
        echo "<p style='color: " . ($mudou ? 'green' : 'red') . ";'>" . 
             ($mudou ? '✅' : '❌') . " $nome: " . 
             ($mudou ? 'FUNCIONOU' : 'NÃO FUNCIONOU') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Verifique qual método funcionou</p>";
echo "<p>2. Se nenhum funcionou, o problema é mais profundo</p>";
echo "<p>3. Se algum funcionou, vou corrigir o código com esse método</p>";
?>
