<?php
require_once 'conexao.php';

echo "<h1>🧹 LIMPANDO DUPLICATAS</h1>";

try {
    // 1. Verificar quantas respostas existem
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetch()['total'];
    echo "<p>Total de respostas antes da limpeza: <strong>$total</strong></p>";
    
    // 2. Mostrar duplicatas por questão
    $stmt = $pdo->query("
        SELECT id_questao, COUNT(*) as quantidade 
        FROM respostas_usuario 
        GROUP BY id_questao 
        HAVING COUNT(*) > 1
        ORDER BY quantidade DESC
    ");
    $duplicatas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($duplicatas)) {
        echo "<h2>📊 Questões com múltiplas respostas:</h2>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID Questão</th><th>Quantidade</th><th>Ação</th></tr>";
        
        foreach ($duplicatas as $dup) {
            echo "<tr>";
            echo "<td>{$dup['id_questao']}</td>";
            echo "<td>{$dup['quantidade']}</td>";
            echo "<td>";
            
            // Manter apenas a resposta mais recente
            $pdo->exec("
                DELETE r1 FROM respostas_usuario r1
                INNER JOIN respostas_usuario r2 
                WHERE r1.id_questao = r2.id_questao 
                AND r1.id < r2.id
                AND r1.id_questao = {$dup['id_questao']}
            ");
            
            echo "✅ Mantida apenas a mais recente";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ Nenhuma duplicata encontrada!</p>";
    }
    
    // 3. Verificar resultado final
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total_final = $stmt->fetch()['total'];
    echo "<p>Total de respostas após limpeza: <strong>$total_final</strong></p>";
    
    // 4. Verificar questões únicas
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_questao) as questoes FROM respostas_usuario");
    $questoes_unicas = $stmt->fetch()['questoes'];
    echo "<p>Questões únicas respondidas: <strong>$questoes_unicas</strong></p>";
    
    echo "<h2 style='color: green;'>🎉 LIMPEZA CONCLUÍDA!</h2>";
    echo "<p>Agora você tem apenas uma resposta por questão (a mais recente).</p>";
    echo "<p><a href='quiz_vertical_filtros.php?id=8' style='background: #0072FF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🎯 IR PARA O QUIZ</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
