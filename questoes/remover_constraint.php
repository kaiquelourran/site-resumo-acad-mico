<?php
require_once 'conexao.php';

echo "<h1>🔧 REMOVENDO CONSTRAINT UNIQUE</h1>";

try {
    // Remover constraint UNIQUE diretamente
    echo "<p>Removendo constraint 'unique_questao' (se existir)...</p>";
    try { $pdo->exec("ALTER TABLE respostas_usuario DROP INDEX unique_questao"); } catch (Exception $e) { /* índice pode não existir */ }
    echo "<p style='color: green;'>✅ Tentativa de remover 'unique_questao' concluída</p>";
    
    echo "<p>Removendo constraint 'unique_user_questao' (se existir)...</p>";
    try { $pdo->exec("ALTER TABLE respostas_usuario DROP INDEX unique_user_questao"); } catch (Exception $e) { /* índice pode não existir */ }
    echo "<p style='color: green;'>✅ Tentativa de remover 'unique_user_questao' concluída</p>";
    
    // Verificar se foram removidas
    $stmt1 = $pdo->query("SHOW INDEX FROM respostas_usuario WHERE Key_name = 'unique_questao'");
    $exist1 = $stmt1->fetch();
    $stmt2 = $pdo->query("SHOW INDEX FROM respostas_usuario WHERE Key_name = 'unique_user_questao'");
    $exist2 = $stmt2->fetch();
    
    if (!$exist1 && !$exist2) {
        echo "<p style='color: green;'>✅ Confirmação: Constraints removidas com sucesso!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Aviso: Ainda existem índices únicos ativos:</p>";
        if ($exist1) echo "<p>- unique_questao</p>";
        if ($exist2) echo "<p>- unique_user_questao</p>";
    }
    
    echo "<h2>🎉 PRONTO!</h2>";
    echo "<p>Agora você pode responder as questões múltiplas vezes.</p>";
    echo "<p><a href='quiz_vertical_filtros.php?id=8' style='background: #0072FF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🎯 IR PARA O QUIZ</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Tentar método alternativo
    try {
        echo "<p>Tentando método alternativo...</p>";
        $pdo->exec("ALTER TABLE respostas_usuario DROP KEY unique_questao");
        echo "<p style='color: green;'>✅ Constraint removida com método alternativo!</p>";
    } catch (Exception $e2) {
        echo "<p style='color: red;'>❌ Método alternativo também falhou: " . htmlspecialchars($e2->getMessage()) . "</p>";
    }
}
?>
