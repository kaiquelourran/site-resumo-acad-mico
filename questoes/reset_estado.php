<?php
session_start();
require_once 'conexao.php';

echo "<h1>🔄 RESET COMPLETO DO ESTADO</h1>";

try {
    // 1. Limpar todas as respostas duplicadas
    echo "<h2>1. Limpando respostas duplicadas...</h2>";
    
    $stmt = $pdo->query("
        DELETE r1 FROM respostas_usuario r1
        INNER JOIN respostas_usuario r2 
        WHERE r1.id_questao = r2.id_questao 
        AND r1.id < r2.id
    ");
    $removidas = $stmt->rowCount();
    echo "<p style='color: green;'>✅ $removidas respostas duplicadas removidas</p>";
    
    // 2. Verificar estado atual
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetch()['total'];
    echo "<p>Total de respostas restantes: <strong>$total</strong></p>";
    
    // 3. Mostrar questões respondidas
    $stmt = $pdo->query("
        SELECT 
            r.id_questao,
            COUNT(*) as total_respostas,
            SUM(r.acertou) as acertos,
            MAX(r.data_resposta) as ultima_resposta
        FROM respostas_usuario r
        GROUP BY r.id_questao
        ORDER BY r.id_questao
    ");
    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($questoes)) {
        echo "<h2>2. Questões respondidas:</h2>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID Questão</th><th>Total Respostas</th><th>Acertos</th><th>Última Resposta</th></tr>";
        
        foreach ($questoes as $q) {
            $cor = $q['acertos'] > 0 ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$q['id_questao']}</td>";
            echo "<td>{$q['total_respostas']}</td>";
            echo "<td style='color: $cor;'>{$q['acertos']}</td>";
            echo "<td>{$q['ultima_resposta']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Limpar sessão
    echo "<h2>3. Limpando sessão...</h2>";
    unset($_SESSION['alternativasConfiguradas']);
    unset($_SESSION['quiz_seed']);
    echo "<p style='color: green;'>✅ Sessão limpa</p>";
    
    echo "<h2 style='color: green;'>🎉 RESET CONCLUÍDO!</h2>";
    echo "<p>O estado foi completamente limpo e resetado.</p>";
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='quiz_vertical_filtros.php?id=8' style='background: #0072FF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;'>🎯 IR PARA O QUIZ</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
