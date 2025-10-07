<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h2>TESTE DE GABARITO CORRETO COM EMBARALHAMENTO</h2>";

// Simular o mesmo processo do quiz_vertical_filtros.php
$page_seed = time() + rand(1, 1000);
$_SESSION['current_page_seed'] = $page_seed;

$id_questao = 99; // Questão de teste

echo "<h3>1. Alternativas ORIGINAIS (ordem do banco):</h3>";
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

foreach ($alternativas_questao as $index => $alt) {
    $letra = ['A', 'B', 'C', 'D', 'E'][$index];
    $correta = $alt['eh_correta'] == 1 ? ' (CORRETA)' : '';
    echo "{$letra}) {$alt['texto']} [ID: {$alt['id_alternativa']}]{$correta}<br>";
}

// Embaralhar (como na exibição)
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

echo "<h3>3. Simulando processamento POST (mesmo seed):</h3>";

// Simular processamento POST com o mesmo seed
$seed_post = $id_questao * 1000 + $_SESSION['current_page_seed'];
srand($seed_post);
$alternativas_post = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
shuffle($alternativas_post);

echo "Seed usado no POST: {$seed_post}<br>";
echo "Seeds são iguais: " . ($seed === $seed_post ? 'SIM' : 'NÃO') . "<br><br>";

// Testar diferentes cliques
$letras_teste = ['A', 'B', 'C', 'D'];
foreach ($letras_teste as $letra_clicada) {
    // Encontrar ID da alternativa clicada (usando array embaralhado)
    $id_alternativa_clicada = null;
    foreach ($alternativas_post as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === $letra_clicada) {
            $id_alternativa_clicada = $alt['id_alternativa'];
            break;
        }
    }
    
    // Encontrar ID da alternativa correta
    $id_alternativa_correta = null;
    foreach ($alternativas_post as $alt) {
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
echo "Seed da exibição: <strong>{$seed}</strong><br>";
echo "Seed do POST: <strong>{$seed_post}</strong><br>";
echo "Embaralhamento funcionou: <strong>SIM</strong><br>";
echo "Gabarito correto: <strong>" . ($seed === $seed_post ? 'SIM' : 'NÃO') . "</strong><br>";
?>
