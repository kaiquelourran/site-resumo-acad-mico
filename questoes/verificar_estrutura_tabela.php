<?php
require_once 'conexao.php';

echo "<h2>🔍 Verificação da Estrutura da Tabela 'questoes'</h2>";

try {
    // Verificar estrutura da tabela questoes
    $stmt = $pdo->prepare("DESCRIBE questoes");
    $stmt->execute();
    $colunas = $stmt->fetchAll();
    
    echo "<h3>📋 Colunas da tabela 'questoes':</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>{$coluna['Field']}</td>";
        echo "<td>{$coluna['Type']}</td>";
        echo "<td>{$coluna['Null']}</td>";
        echo "<td>{$coluna['Key']}</td>";
        echo "<td>{$coluna['Default']}</td>";
        echo "<td>{$coluna['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar também a estrutura da tabela assuntos
    echo "<h3>📋 Colunas da tabela 'assuntos':</h3>";
    $stmt = $pdo->prepare("DESCRIBE assuntos");
    $stmt->execute();
    $colunas_assuntos = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas_assuntos as $coluna) {
        echo "<tr>";
        echo "<td>{$coluna['Field']}</td>";
        echo "<td>{$coluna['Type']}</td>";
        echo "<td>{$coluna['Null']}</td>";
        echo "<td>{$coluna['Key']}</td>";
        echo "<td>{$coluna['Default']}</td>";
        echo "<td>{$coluna['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📋 Gerenciar Questões</a>
    <a href="teste_sistema.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Testar Sistema</a>
</div>