<?php
require_once 'conexao.php';

echo "<h2>Executando Migração do Banco de Dados</h2>";

try {
    // 1. Remover constraint UNIQUE
    echo "<p>1. Removendo constraint UNIQUE 'unique_questao'...</p>";
    $pdo->exec("ALTER TABLE respostas_usuario DROP INDEX unique_questao");
    echo "<p style='color: green;'>✅ Constraint UNIQUE removida com sucesso!</p>";
    
    // 2. Adicionar índice normal
    echo "<p>2. Adicionando índice normal 'idx_questao'...</p>";
    $pdo->exec("ALTER TABLE respostas_usuario ADD INDEX idx_questao (id_questao)");
    echo "<p style='color: green;'>✅ Índice normal adicionado com sucesso!</p>";
    
    // 3. Verificar estrutura final
    echo "<p>3. Verificando estrutura final...</p>";
    $stmt = $pdo->query("SHOW INDEX FROM respostas_usuario");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Nome do Índice</th><th>Coluna</th><th>Único</th></tr>";
    foreach ($indices as $indice) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($indice['Key_name']) . "</td>";
        echo "<td>" . htmlspecialchars($indice['Column_name']) . "</td>";
        echo "<td>" . ($indice['Non_unique'] == 0 ? 'Sim' : 'Não') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3 style='color: green;'>✅ Migração concluída com sucesso!</h3>";
    echo "<p>Agora você pode responder as questões múltiplas vezes e ver as estatísticas.</p>";
    echo "<p><a href='quiz_vertical_filtros.php?id=8'>🎯 Ir para o Quiz</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
