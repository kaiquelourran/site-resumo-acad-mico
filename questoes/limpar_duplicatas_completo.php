<?php
session_start();
require_once 'conexao.php';

echo "<h1>🧹 LIMPEZA COMPLETA DE DUPLICATAS</h1>";

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
        
        $total_removidas = 0;
        foreach ($duplicatas as $dup) {
            echo "<tr>";
            echo "<td>{$dup['id_questao']}</td>";
            echo "<td>{$dup['quantidade']}</td>";
            echo "<td>";
            
            // Contar quantas serão removidas
            $quantidade_remover = $dup['quantidade'] - 1;
            $total_removidas += $quantidade_remover;
            
            // Manter apenas a resposta mais recente
            $pdo->exec("
                DELETE r1 FROM respostas_usuario r1
                INNER JOIN respostas_usuario r2 
                WHERE r1.id_questao = r2.id_questao 
                AND r1.id < r2.id
                AND r1.id_questao = {$dup['id_questao']}
            ");
            
            echo "✅ Removidas $quantidade_remover respostas antigas";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p style='color: green; font-weight: bold;'>Total de respostas duplicadas removidas: <strong>$total_removidas</strong></p>";
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
    
    // 5. Mostrar estatísticas por questão
    echo "<h2>📈 Estatísticas por questão:</h2>";
    $stmt = $pdo->query("
        SELECT 
            r.id_questao,
            COUNT(*) as total_respostas,
            SUM(r.acertou) as acertos,
            (COUNT(*) - SUM(r.acertou)) as erros,
            ROUND((SUM(r.acertou) / COUNT(*)) * 100, 1) as percentual_acerto
        FROM respostas_usuario r
        GROUP BY r.id_questao
        ORDER BY r.id_questao
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($stats)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID Questão</th><th>Total</th><th>Acertos</th><th>Erros</th><th>% Acerto</th></tr>";
        
        foreach ($stats as $stat) {
            $cor = $stat['percentual_acerto'] >= 50 ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$stat['id_questao']}</td>";
            echo "<td>{$stat['total_respostas']}</td>";
            echo "<td style='color: green;'>{$stat['acertos']}</td>";
            echo "<td style='color: red;'>{$stat['erros']}</td>";
            echo "<td style='color: $cor; font-weight: bold;'>{$stat['percentual_acerto']}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2 style='color: green;'>🎉 LIMPEZA CONCLUÍDA COM SUCESSO!</h2>";
    echo "<p>Agora você tem apenas uma resposta por questão (a mais recente).</p>";
    echo "<p>As estatísticas estão atualizadas e funcionando corretamente.</p>";
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='quiz_vertical_filtros.php?id=8' style='background: #0072FF; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;'>🎯 IR PARA O QUIZ</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
