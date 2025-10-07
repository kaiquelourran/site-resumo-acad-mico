<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h2>DEBUG DE SEEDS</h2>";

$id_assunto = 8; // Assunto de teste
$id_questao = 99; // Questão de teste

// Simular geração de seed (como na exibição)
$page_seed = time() + rand(1, 1000);
$_SESSION['page_seed_' . $id_assunto] = $page_seed;

echo "<h3>1. Seed gerado na exibição:</h3>";
echo "page_seed: {$page_seed}<br>";
echo "seed_sessao: " . ($_SESSION['page_seed_' . $id_assunto] ?? 'NÃO ENCONTRADO') . "<br>";

// Simular exibição
$seed_exibicao = $id_questao * 1000 + $page_seed;
echo "seed_exibicao: {$seed_exibicao}<br>";

// Simular processamento POST
$seed_sessao_post = $_SESSION['page_seed_' . $id_assunto] ?? $page_seed;
$seed_post = $id_questao * 1000 + $seed_sessao_post;
echo "seed_post: {$seed_post}<br>";

echo "<h3>2. Verificação:</h3>";
echo "Seeds são iguais: " . ($seed_exibicao === $seed_post ? 'SIM ✅' : 'NÃO ❌') . "<br>";

// Testar embaralhamento
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>3. Teste de embaralhamento:</h3>";

// Embaralhar com seed da exibição
srand($seed_exibicao);
$alt_exibicao = $alternativas;
shuffle($alt_exibicao);

echo "Alternativas embaralhadas (exibição):<br>";
foreach ($alt_exibicao as $index => $alt) {
    $letra = ['A', 'B', 'C', 'D', 'E'][$index];
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

// Embaralhar com seed do POST
srand($seed_post);
$alt_post = $alternativas;
shuffle($alt_post);

echo "<br>Alternativas embaralhadas (POST):<br>";
foreach ($alt_post as $index => $alt) {
    $letra = ['A', 'B', 'C', 'D', 'E'][$index];
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

echo "<h3>4. Resultado:</h3>";
$arrays_iguais = ($alt_exibicao === $alt_post);
echo "Arrays embaralhados são iguais: " . ($arrays_iguais ? 'SIM ✅' : 'NÃO ❌') . "<br>";

if (!$arrays_iguais) {
    echo "<strong>PROBLEMA: Os arrays estão diferentes!</strong><br>";
    echo "Isso explica por que o gabarito está errado.<br>";
}
?>
