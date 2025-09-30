<?php
require_once __DIR__ . '/conexao.php';

echo "<h2>Limpeza de Respostas para Teste</h2>";

try {
    // Verificar quantas respostas existem no assunto 8
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario ru INNER JOIN questoes q ON ru.id_questao = q.id_questao WHERE q.id_assunto = 8");
    $stmt->execute();
    $total_antes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "<p><strong>Respostas antes da limpeza:</strong> $total_antes</p>";
    
    // Listar as respostas que serão removidas
    echo "<h3>Respostas que serão removidas:</h3>";
    $stmt_list = $pdo->prepare("SELECT ru.id, ru.id_questao, q.enunciado, ru.acertou FROM respostas_usuario ru INNER JOIN questoes q ON ru.id_questao = q.id_questao WHERE q.id_assunto = 8 ORDER BY ru.id DESC LIMIT 3");
    $stmt_list->execute();
    $respostas_para_remover = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($respostas_para_remover) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>ID Questão</th><th>Enunciado (resumo)</th><th>Acertou</th></tr>";
        foreach ($respostas_para_remover as $resposta) {
            echo "<tr>";
            echo "<td>{$resposta['id']}</td>";
            echo "<td>{$resposta['id_questao']}</td>";
            echo "<td>" . htmlspecialchars(substr($resposta['enunciado'], 0, 80)) . "...</td>";
            echo "<td>" . ($resposta['acertou'] ? 'SIM' : 'NÃO') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Remover as 3 últimas respostas do assunto 8
        $ids_para_remover = array_column($respostas_para_remover, 'id');
        $placeholders = str_repeat('?,', count($ids_para_remover) - 1) . '?';
        
        $stmt_delete = $pdo->prepare("DELETE FROM respostas_usuario WHERE id IN ($placeholders)");
        $stmt_delete->execute($ids_para_remover);
        
        echo "<p><strong>✅ {$stmt_delete->rowCount()} respostas removidas com sucesso!</strong></p>";
        
        // Verificar quantas respostas restaram
        $stmt_depois = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario ru INNER JOIN questoes q ON ru.id_questao = q.id_questao WHERE q.id_assunto = 8");
        $stmt_depois->execute();
        $total_depois = $stmt_depois->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "<p><strong>Respostas após a limpeza:</strong> $total_depois</p>";
        echo "<p><strong>Questões agora disponíveis para clique:</strong> " . (5 - $total_depois) . "</p>";
        
        // Listar questões que agora podem ser clicadas
        echo "<h3>Questões agora disponíveis para teste:</h3>";
        $stmt_disponiveis = $pdo->prepare("SELECT q.id_questao, LEFT(q.enunciado, 100) as enunciado_resumo FROM questoes q LEFT JOIN respostas_usuario ru ON q.id_questao = ru.id_questao WHERE q.id_assunto = 8 AND ru.id IS NULL");
        $stmt_disponiveis->execute();
        $questoes_disponiveis = $stmt_disponiveis->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($questoes_disponiveis) > 0) {
            foreach ($questoes_disponiveis as $questao) {
                echo "<p><strong>ID {$questao['id_questao']}:</strong> " . htmlspecialchars($questao['enunciado_resumo']) . "...</p>";
            }
        }
        
    } else {
        echo "<p>Nenhuma resposta encontrada para remover.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Erro:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Agora você pode testar o clique nas alternativas!</strong></p>";
echo "<p><a href='quiz_vertical_filtros.php?id=8&filtro=todas' target='_blank'>🔗 Abrir Quiz para Teste</a></p>";
?>