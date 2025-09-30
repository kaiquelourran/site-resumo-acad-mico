<?php
require_once 'conexao.php';

// Parâmetros de teste
$id_assunto = 8;
$questao_inicial = 92;

// Buscar uma questão específica para debug
$sql = "SELECT * FROM questoes WHERE id_assunto = ? AND id_questao = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_assunto, $questao_inicial]);
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug - Questão ID: $questao_inicial</h2>";

if ($questao) {
    echo "<h3>Dados da questão:</h3>";
    echo "<pre>";
    print_r($questao);
    echo "</pre>";
    
    echo "<h3>Alternativas encontradas:</h3>";
    $letras = ['A', 'B', 'C', 'D'];
    foreach ($letras as $letra) {
        $campo_alternativa = 'alternativa_' . strtolower($letra);
        $valor = $questao[$campo_alternativa] ?? '';
        echo "<p><strong>$letra:</strong> " . ($valor ? htmlspecialchars($valor) : '<em>VAZIO</em>') . "</p>";
    }
    
    echo "<h3>Teste de geração HTML:</h3>";
    echo '<div class="alternativas-container">';
    foreach ($letras as $letra) {
        $campo_alternativa = 'alternativa_' . strtolower($letra);
        if (!empty($questao[$campo_alternativa])) {
            echo '<div class="alternativa" data-letra="' . $letra . '">';
            echo '<div class="letra-alternativa">' . $letra . '</div>';
            echo '<div class="texto-alternativa">' . htmlspecialchars($questao[$campo_alternativa]) . '</div>';
            echo '</div>';
        }
    }
    echo '</div>';
    
} else {
    echo "<p>Questão não encontrada!</p>";
}

// Buscar todas as questões do assunto
echo "<h3>Todas as questões do assunto $id_assunto:</h3>";
$sql_all = "SELECT id_questao, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d FROM questoes WHERE id_assunto = ? ORDER BY id_questao LIMIT 5";
$stmt_all = $pdo->prepare($sql_all);
$stmt_all->execute([$id_assunto]);
$todas_questoes = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

foreach ($todas_questoes as $q) {
    echo "<h4>Questão {$q['id_questao']}:</h4>";
    echo "<p><strong>Enunciado:</strong> " . substr($q['enunciado'], 0, 100) . "...</p>";
    echo "<p><strong>A:</strong> " . ($q['alternativa_a'] ? 'OK' : 'VAZIO') . "</p>";
    echo "<p><strong>B:</strong> " . ($q['alternativa_b'] ? 'OK' : 'VAZIO') . "</p>";
    echo "<p><strong>C:</strong> " . ($q['alternativa_c'] ? 'OK' : 'VAZIO') . "</p>";
    echo "<p><strong>D:</strong> " . ($q['alternativa_d'] ? 'OK' : 'VAZIO') . "</p>";
    echo "<hr>";
}
?>