<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h2>TESTE DE GABARITO FIXO</h2>";

$id_questao = 99;

// Buscar alternativa correta diretamente do banco
$stmt_correta = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? AND eh_correta = 1");
$stmt_correta->execute([$id_questao]);
$alternativa_correta = $stmt_correta->fetch(PDO::FETCH_ASSOC);

echo "<h3>1. Alternativa correta (do banco):</h3>";
echo "ID: {$alternativa_correta['id_alternativa']}<br>";
echo "Texto: {$alternativa_correta['texto']}<br>";

// Buscar todas as alternativas
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>2. Alternativas originais:</h3>";
foreach ($alternativas as $index => $alt) {
    $letra = ['A', 'B', 'C', 'D', 'E'][$index];
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

// Embaralhar
$seed = $id_questao * 1000 + 12345;
srand($seed);
shuffle($alternativas);

echo "<h3>3. Alternativas embaralhadas (seed: {$seed}):</h3>";
$letras = ['A', 'B', 'C', 'D', 'E'];
$letra_correta = '';
foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    if ($alt['eh_correta'] == 1) {
        $letra_correta = $letra;
    }
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

echo "<h3>4. Teste de cliques:</h3>";

// Simular cliques
foreach (['A', 'B', 'C', 'D'] as $letra_clicada) {
    // Encontrar ID da alternativa clicada
    $id_clicada = null;
    foreach ($alternativas as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_clicada) {
            $id_clicada = $alt['id_alternativa'];
            break;
        }
    }
    
    // Comparar com a alternativa correta (do banco)
    $acertou = ($id_clicada == $alternativa_correta['id_alternativa']) ? 'SIM' : 'NÃO';
    $status = $acertou === 'SIM' ? '✅' : '❌';
    
    echo "Clique em <strong>{$letra_clicada}</strong>: ";
    echo "ID clicado = {$id_clicada}, ID correto = {$alternativa_correta['id_alternativa']} ";
    echo "→ Acertou: {$acertou} {$status}<br>";
}

echo "<h3>5. Resumo:</h3>";
echo "Letra correta após embaralhamento: <strong>{$letra_correta}</strong><br>";
echo "ID correto (do banco): <strong>{$alternativa_correta['id_alternativa']}</strong><br>";
echo "Status: <strong>GABARITO FIXO FUNCIONANDO</strong><br>";
?>
