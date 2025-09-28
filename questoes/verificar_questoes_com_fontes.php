<?php
require_once 'conexao.php';

echo "<h2>🔍 Verificação das Questões com Fontes</h2>";

try {
    // Buscar questões do assunto "MARCOS DO DESENVOLVIMENTO INFANTIL"
    $stmt = $pdo->prepare("SELECT id_questao, enunciado FROM questoes WHERE id_assunto = (SELECT id_assunto FROM assuntos WHERE nome = 'MARCOS DO DESENVOLVIMENTO INFANTIL') ORDER BY id_questao DESC LIMIT 10");
    $stmt->execute();
    $questoes = $stmt->fetchAll();
    
    if ($questoes) {
        echo "<h3>📝 Últimas 10 questões inseridas:</h3>";
        
        foreach ($questoes as $questao) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>Questão ID: {$questao['id_questao']}</h4>";
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
            echo "<strong>Enunciado (HTML):</strong><br>";
            echo "<pre>" . htmlspecialchars($questao['enunciado']) . "</pre>";
            echo "</div>";
            echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 3px; margin-top: 10px;'>";
            echo "<strong>Renderizado:</strong><br>";
            echo $questao['enunciado'];
            echo "</div>";
            
            // Verificar se contém tags de fonte
            if (strpos($questao['enunciado'], '<strong>') !== false && strpos($questao['enunciado'], 'Fonte:') !== false) {
                echo "<p style='color: green;'>✅ Contém fonte destacada</p>";
            } else {
                echo "<p style='color: red;'>❌ Não contém fonte destacada</p>";
            }
            echo "</div>";
        }
    } else {
        echo "<p>❌ Nenhuma questão encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📋 Gerenciar Questões</a>
    <a href="quiz_sem_login.php?questao=71" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Testar Questão</a>
</div>