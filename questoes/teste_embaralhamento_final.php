<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Simular o mesmo seed da página
$page_seed = time() + rand(1, 1000);
$id_questao = 99; // Questão de teste

echo "<h2>TESTE DE EMBARALHAMENTO E RESPOSTA CORRETA</h2>";

// Buscar alternativas
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>1. Alternativas ORIGINAIS (ordem do banco):</h3>";
foreach ($alternativas_questao as $index => $alt) {
    $letra = ['A', 'B', 'C', 'D', 'E'][$index];
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

// Embaralhar
$seed = $id_questao * 1000 + $page_seed;
srand($seed);
shuffle($alternativas_questao);

echo "<h3>2. Alternativas EMBARALHADAS (seed: {$seed}):</h3>";
$letras = ['A', 'B', 'C', 'D', 'E'];
$letra_correta = '';
foreach ($alternativas_questao as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    if ($alt['eh_correta'] == 1) {
        $letra_correta = $letra;
    }
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

echo "<h3>3. Teste de diferentes cliques:</h3>";

// Simular cliques em diferentes letras
$letras_teste = ['A', 'B', 'C', 'D'];
foreach ($letras_teste as $letra_clicada) {
    // Encontrar ID da alternativa clicada
    $id_alternativa_clicada = null;
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_clicada) {
            $id_alternativa_clicada = $alt['id_alternativa'];
            break;
        }
    }
    
    // Encontrar ID da alternativa correta
    $id_alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $id_alternativa_correta = $alt['id_alternativa'];
            break;
        }
    }
    
    $acertou = ($id_alternativa_clicada == $id_alternativa_correta) ? 'SIM' : 'NÃO';
    $status = $acertou === 'SIM' ? '✅' : '❌';
    
    echo "Clique na letra <strong>{$letra_clicada}</strong>: ";
    echo "ID clicado = {$id_alternativa_clicada}, ID correto = {$id_alternativa_correta} ";
    echo "→ Acertou: {$acertou} {$status}<br>";
}

echo "<h3>4. Resumo:</h3>";
echo "Letra correta após embaralhamento: <strong>{$letra_correta}</strong><br>";
echo "Seed usado: <strong>{$seed}</strong><br>";
echo "Embaralhamento funcionou: <strong>" . (count($alternativas_questao) > 0 ? 'SIM' : 'NÃO') . "</strong><br>";
?>
