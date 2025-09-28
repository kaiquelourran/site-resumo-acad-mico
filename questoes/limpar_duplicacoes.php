<?php
require_once 'conexao.php';

echo "<h2>🧹 Limpando Duplicações - MARCOS DO DESENVOLVIMENTO INFANTIL</h2>";

try {
    // Buscar o ID do assunto
    $stmt = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = ?");
    $stmt->execute(['MARCOS DO DESENVOLVIMENTO INFANTIL']);
    $assunto_id = $stmt->fetchColumn();
    
    if (!$assunto_id) {
        echo "<p style='color: red;'>❌ Assunto não encontrado!</p>";
        exit;
    }
    
    echo "<p>✅ Assunto encontrado com ID: $assunto_id</p>";
    
    // Contar questões antes da limpeza
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$assunto_id]);
    $total_antes = $stmt->fetchColumn();
    
    echo "<p><strong>Total de questões antes da limpeza:</strong> $total_antes</p>";
    
    // Encontrar questões duplicadas e manter apenas a primeira (menor ID)
    $stmt = $pdo->prepare("
        SELECT enunciado, MIN(id_questao) as manter_id, GROUP_CONCAT(id_questao ORDER BY id_questao) as todos_ids
        FROM questoes 
        WHERE id_assunto = ? 
        GROUP BY enunciado 
        HAVING COUNT(*) > 1
    ");
    $stmt->execute([$assunto_id]);
    $duplicadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $questoes_removidas = 0;
    
    if (!empty($duplicadas)) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>🔄 Processando Duplicações...</h3>";
        
        foreach ($duplicadas as $dup) {
            $todos_ids = explode(',', $dup['todos_ids']);
            $manter_id = $dup['manter_id'];
            
            // Remover todos os IDs exceto o primeiro (menor)
            $ids_para_remover = array_filter($todos_ids, function($id) use ($manter_id) {
                return $id != $manter_id;
            });
            
            if (!empty($ids_para_remover)) {
                echo "<p><strong>Questão:</strong> " . substr($dup['enunciado'], 0, 60) . "...</p>";
                echo "<p><strong>Mantendo ID:</strong> $manter_id</p>";
                echo "<p><strong>Removendo IDs:</strong> " . implode(', ', $ids_para_remover) . "</p>";
                
                // Remover alternativas das questões duplicadas
                foreach ($ids_para_remover as $id_remover) {
                    $stmt = $pdo->prepare("DELETE FROM alternativas WHERE id_questao = ?");
                    $stmt->execute([$id_remover]);
                }
                
                // Remover as questões duplicadas
                $placeholders = str_repeat('?,', count($ids_para_remover) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM questoes WHERE id_questao IN ($placeholders)");
                $stmt->execute($ids_para_remover);
                
                $questoes_removidas += count($ids_para_remover);
                echo "<p style='color: green;'>✅ Removidas " . count($ids_para_remover) . " duplicações</p>";
                echo "<hr>";
            }
        }
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Nenhuma duplicação encontrada!</h3>";
        echo "</div>";
    }
    
    // Contar questões após a limpeza
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$assunto_id]);
    $total_depois = $stmt->fetchColumn();
    
    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📊 Resultado da Limpeza</h3>";
    echo "<p><strong>Questões antes:</strong> $total_antes</p>";
    echo "<p><strong>Questões removidas:</strong> $questoes_removidas</p>";
    echo "<p><strong>Questões depois:</strong> $total_depois</p>";
    echo "<p><strong>Status:</strong> " . ($total_depois == 20 ? "✅ Perfeito! 20 questões únicas" : "⚠️ Verificar quantidade") . "</p>";
    echo "</div>";
    
    // Listar questões finais
    $stmt = $pdo->prepare("
        SELECT id_questao, LEFT(enunciado, 100) as enunciado_resumo
        FROM questoes 
        WHERE id_assunto = ? 
        ORDER BY id_questao
    ");
    $stmt->execute([$assunto_id]);
    $questoes_finais = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e2e3e5; color: #383d41; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📋 Questões Finais (Total: " . count($questoes_finais) . ")</h3>";
    foreach ($questoes_finais as $index => $questao) {
        $numero = $index + 1;
        echo "<p><strong>$numero. ID " . $questao['id_questao'] . ":</strong> " . $questao['enunciado_resumo'] . "...</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="text-align: center; margin: 30px 0;">
    <a href="verificar_duplicacoes.php" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">🔍 Verificar Novamente</a>
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">📋 Gerenciar Questões</a>
    <a href="teste_sistema.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">🔍 Teste Sistema</a>
</div>