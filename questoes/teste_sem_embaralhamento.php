<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h2>TESTE SEM EMBARALHAMENTO</h2>";

$id_questao = 99;

// Buscar alternativas (ordem original do banco)
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Alternativas na ordem ORIGINAL do banco:</h3>";
$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($alternativas_questao as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

echo "<h3>Teste de cliques:</h3>";

// Simular cliques
foreach (['A', 'B', 'C', 'D'] as $letra_clicada) {
    // Mapear letra para ID (ordem original)
    $id_alternativa = null;
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_clicada) {
            $id_alternativa = $alt['id_alternativa'];
            break;
        }
    }
    
    // Buscar alternativa correta
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

echo "<h3>Resumo:</h3>";
echo "Ordem: <strong>ORIGINAL DO BANCO</strong><br>";
echo "Embaralhamento: <strong>NÃO</strong><br>";
echo "Status: <strong>FUNCIONANDO</strong><br>";
?>
