<?php
require_once 'conexao.php';

echo "<h2>🔍 Verificação Detalhada do Banco de Dados</h2>";

try {
    // Verificar se o assunto existe
    echo "<h3>📋 1. Verificando Assuntos</h3>";
    $stmt = $pdo->prepare("SELECT * FROM assuntos");
    $stmt->execute();
    $assuntos = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome do Assunto</th></tr>";
    foreach ($assuntos as $assunto) {
        echo "<tr><td>{$assunto['id_assunto']}</td><td>{$assunto['nome']}</td></tr>";
    }
    echo "</table>";
    
    // Verificar questões do assunto específico
    echo "<h3>📝 2. Verificando Questões - MARCOS DO DESENVOLVIMENTO INFANTIL</h3>";
    $stmt = $pdo->prepare("SELECT q.*, a.nome as assunto_nome FROM questoes q 
                          JOIN assuntos a ON q.id_assunto = a.id_assunto 
                          WHERE a.nome = 'MARCOS DO DESENVOLVIMENTO INFANTIL' 
                          ORDER BY q.id_questao DESC");
    $stmt->execute();
    $questoes = $stmt->fetchAll();
    
    if ($questoes) {
        echo "<p style='color: green;'>✅ Encontradas " . count($questoes) . " questões</p>";
        
        foreach ($questoes as $index => $questao) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
            echo "<h4>Questão ID: {$questao['id_questao']} (#" . ($index + 1) . ")</h4>";
            echo "<p><strong>Assunto:</strong> {$questao['assunto_nome']}</p>";
            echo "<p><strong>Enunciado:</strong> " . substr($questao['enunciado'], 0, 200) . "...</p>";
            echo "<p><strong>Resposta Correta:</strong> {$questao['resposta_correta']}</p>";
            echo "</div>";
        }
    } else {
        echo "<p style='color: red;'>❌ Nenhuma questão encontrada para este assunto!</p>";
    }
    
    // Verificar total geral de questões
    echo "<h3>📊 3. Estatísticas Gerais</h3>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes");
    $stmt->execute();
    $total_geral = $stmt->fetch()['total'];
    echo "<p><strong>Total de questões no banco:</strong> {$total_geral}</p>";
    
    // Verificar questões por assunto
    $stmt = $pdo->prepare("SELECT a.nome, COUNT(q.id_questao) as total 
                          FROM assuntos a 
                          LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                          GROUP BY a.id_assunto, a.nome");
    $stmt->execute();
    $stats = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Assunto</th><th>Quantidade de Questões</th></tr>";
    foreach ($stats as $stat) {
        echo "<tr><td>{$stat['nome']}</td><td>{$stat['total']}</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="quiz_sem_login.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🧪 Testar Quiz</a>
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">📋 Gerenciar</a>
</div>