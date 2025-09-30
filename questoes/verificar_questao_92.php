<?php
require_once 'conexao.php';

echo "<h2>Verificação da Questão 92</h2>";

// Buscar questão específica
$sql = "SELECT * FROM questoes WHERE id_questao = 92";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    echo "<h3>✅ Questão encontrada!</h3>";
    echo "<p><strong>ID:</strong> {$questao['id_questao']}</p>";
    echo "<p><strong>Assunto:</strong> {$questao['id_assunto']}</p>";
    echo "<p><strong>Enunciado:</strong> " . substr($questao['enunciado'], 0, 200) . "...</p>";
    
    echo "<h3>Alternativas:</h3>";
    $letras = ['A', 'B', 'C', 'D'];
    $tem_alternativas = false;
    
    foreach ($letras as $letra) {
        $campo = 'alternativa_' . strtolower($letra);
        $valor = $questao[$campo] ?? '';
        $status = !empty($valor) ? '✅' : '❌';
        echo "<p>$status <strong>$letra:</strong> " . ($valor ? htmlspecialchars($valor) : '<em>VAZIO</em>') . "</p>";
        if (!empty($valor)) $tem_alternativas = true;
    }
    
    if (!$tem_alternativas) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; margin: 20px 0;'>";
        echo "<strong>⚠️ PROBLEMA ENCONTRADO:</strong> Esta questão não tem alternativas cadastradas no banco de dados!";
        echo "</div>";
    }
    
    echo "<h3>Estrutura completa da tabela:</h3>";
    echo "<pre>";
    print_r($questao);
    echo "</pre>";
    
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24;'>";
    echo "<strong>❌ ERRO:</strong> Questão 92 não encontrada no banco de dados!";
    echo "</div>";
}

// Verificar se existem questões do assunto 8 com alternativas
echo "<h3>Verificando outras questões do assunto 8:</h3>";
$sql_check = "SELECT id_questao, 
                     CASE WHEN alternativa_a IS NOT NULL AND alternativa_a != '' THEN 1 ELSE 0 END as tem_a,
                     CASE WHEN alternativa_b IS NOT NULL AND alternativa_b != '' THEN 1 ELSE 0 END as tem_b,
                     CASE WHEN alternativa_c IS NOT NULL AND alternativa_c != '' THEN 1 ELSE 0 END as tem_c,
                     CASE WHEN alternativa_d IS NOT NULL AND alternativa_d != '' THEN 1 ELSE 0 END as tem_d
              FROM questoes 
              WHERE id_assunto = 8 
              ORDER BY id_questao 
              LIMIT 10";
$stmt_check = $pdo->prepare($sql_check);
$stmt_check->execute();
$questoes_check = $stmt_check->fetchAll(PDO::FETCH_ASSOC);

foreach ($questoes_check as $q) {
    $total_alternativas = $q['tem_a'] + $q['tem_b'] + $q['tem_c'] + $q['tem_d'];
    $status = $total_alternativas > 0 ? '✅' : '❌';
    echo "<p>$status Questão {$q['id_questao']}: $total_alternativas alternativas</p>";
}
?>