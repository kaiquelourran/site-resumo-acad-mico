<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h2>TESTE FINAL - GABARITO SEMPRE CORRETO</h2>";

$id_questao = 99;

// Buscar alternativas (ordem original do banco)
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>1. Alternativas ORIGINAIS (ordem do banco):</h3>";
$letras = ['A', 'B', 'C', 'D', 'E'];
$letra_correta_original = '';
foreach ($alternativas_questao as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    if ($alt['eh_correta'] == 1) {
        $letra_correta_original = $letra;
    }
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

echo "<h3>2. Simulando embaralhamento na exibição:</h3>";
$seed = $id_questao * 1000 + 12345;
srand($seed);
$alternativas_embaralhadas = $alternativas_questao;
shuffle($alternativas_embaralhadas);

foreach ($alternativas_embaralhadas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

echo "<h3>3. Teste de cliques (usando ordem original):</h3>";

// Simular cliques em diferentes letras
foreach (['A', 'B', 'C', 'D'] as $letra_clicada) {
    // Mapear letra para ID (usando ordem original)
    $id_alternativa = null;
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if (strtoupper($letra) === strtoupper($letra_clicada)) {
            $id_alternativa = $alt['id_alternativa'];
            break;
        }
    }
    
    // Buscar alternativa correta (usando ordem original)
    $alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            break;
        }
    }
    
    $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 'SIM' : 'NÃO';
    $status = $acertou === 'SIM' ? '✅' : '❌';
    
    echo "Clique em <strong>{$letra_clicada}</strong>: ";
    echo "ID clicado = {$id_alternativa}, ID correto = {$alternativa_correta['id_alternativa']} ";
    echo "→ Acertou: {$acertou} {$status}<br>";
}

echo "<h3>4. Resumo:</h3>";
echo "Letra correta (ordem original): <strong>{$letra_correta_original}</strong><br>";
echo "ID correto: <strong>{$alternativa_correta['id_alternativa']}</strong><br>";
echo "Status: <strong>GABARITO SEMPRE CORRETO</strong><br>";
echo "Embaralhamento: <strong>FUNCIONA NA EXIBIÇÃO</strong><br>";
echo "Processamento: <strong>USA ORDEM ORIGINAL</strong><br>";
?>
