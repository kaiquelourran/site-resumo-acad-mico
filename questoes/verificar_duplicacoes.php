<?php
require_once 'conexao.php';

echo "<h2>🔍 Verificando Duplicações - MARCOS DO DESENVOLVIMENTO INFANTIL</h2>";

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
    
    // Verificar questões duplicadas (mesmo enunciado)
    $stmt = $pdo->prepare("
        SELECT enunciado, COUNT(*) as total, GROUP_CONCAT(id_questao) as ids
        FROM questoes 
        WHERE id_assunto = ? 
        GROUP BY enunciado 
        HAVING COUNT(*) > 1
        ORDER BY enunciado
    ");
    $stmt->execute([$assunto_id]);
    $duplicadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicadas)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Nenhuma duplicação encontrada!</h3>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>⚠️ Questões Duplicadas Encontradas:</h3>";
        
        foreach ($duplicadas as $dup) {
            echo "<div style='margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #dc3545;'>";
            echo "<p><strong>Enunciado:</strong> " . substr($dup['enunciado'], 0, 100) . "...</p>";
            echo "<p><strong>Quantidade:</strong> " . $dup['total'] . " vezes</p>";
            echo "<p><strong>IDs:</strong> " . $dup['ids'] . "</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
    // Contar total de questões
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$assunto_id]);
    $total_questoes = $stmt->fetchColumn();
    
    echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📊 Resumo Atual</h3>";
    echo "<p><strong>Total de questões:</strong> $total_questoes</p>";
    echo "<p><strong>Questões duplicadas:</strong> " . count($duplicadas) . "</p>";
    echo "</div>";
    
    // Listar todas as questões para análise
    $stmt = $pdo->prepare("
        SELECT id_questao, LEFT(enunciado, 80) as enunciado_resumo
        FROM questoes 
        WHERE id_assunto = ? 
        ORDER BY id_questao
    ");
    $stmt->execute([$assunto_id]);
    $todas_questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e2e3e5; color: #383d41; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📋 Todas as Questões (Resumo)</h3>";
    foreach ($todas_questoes as $questao) {
        echo "<p><strong>ID " . $questao['id_questao'] . ":</strong> " . $questao['enunciado_resumo'] . "...</p>";
    }
    echo "</div>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="text-align: center; margin: 30px 0;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">📋 Gerenciar Questões</a>
    <a href="teste_sistema.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">🔍 Teste Sistema</a>
</div>