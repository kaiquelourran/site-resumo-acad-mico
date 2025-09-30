<?php
require_once 'conexao.php';

echo "<h2>Debug Final - Alternativas</h2>";

// Buscar questões com alternativas
$sql = "SELECT * FROM questoes WHERE id_assunto = 8 LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    echo "<h3>Questão encontrada:</h3>";
    echo "<p><strong>ID:</strong> " . $questao['id_questao'] . "</p>";
    echo "<p><strong>Enunciado:</strong> " . substr($questao['enunciado'], 0, 100) . "...</p>";
    
    echo "<h3>Alternativas:</h3>";
    $alternativas = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $letra) {
        $campo = 'alternativa_' . strtolower($letra);
        $valor = $questao[$campo] ?? 'N/A';
        $vazio = empty($questao[$campo]);
        echo "<p><strong>$letra:</strong> " . ($vazio ? '❌ VAZIO' : '✅ ' . substr($valor, 0, 50)) . "</p>";
    }
    
    echo "<h3>Simulação do loop PHP:</h3>";
    echo "<div style='border: 2px solid #007bff; padding: 20px; margin: 20px 0; background-color: #f8f9fa;'>";
    echo "<h4>Alternativas renderizadas:</h4>";
    
    $contador = 0;
    foreach ($alternativas as $letra) {
        $campo_alternativa = 'alternativa_' . strtolower($letra);
        
        if (!empty($questao[$campo_alternativa])) {
            $contador++;
            echo "<div style='border: 1px solid #28a745; padding: 10px; margin: 5px 0; background-color: #d4edda;'>";
            echo "<strong>Alternativa $letra:</strong> " . htmlspecialchars($questao[$campo_alternativa]);
            echo "</div>";
        } else {
            echo "<div style='border: 1px solid #dc3545; padding: 10px; margin: 5px 0; background-color: #f8d7da;'>";
            echo "<strong>Alternativa $letra:</strong> ❌ VAZIA - NÃO SERÁ RENDERIZADA";
            echo "</div>";
        }
    }
    
    echo "<p><strong>Total de alternativas que serão renderizadas: $contador</strong></p>";
    echo "</div>";
    
    if ($contador > 0) {
        echo "<h3>✅ PROBLEMA RESOLVIDO!</h3>";
        echo "<p>As alternativas agora devem aparecer na página principal.</p>";
        echo "<p><a href='quiz_vertical_filtros.php?id=8&filtro=todas' target='_blank'>Testar página principal</a></p>";
    } else {
        echo "<h3>❌ PROBLEMA PERSISTE!</h3>";
        echo "<p>As alternativas ainda estão vazias.</p>";
    }
    
} else {
    echo "<p>❌ Nenhuma questão encontrada!</p>";
}
?>