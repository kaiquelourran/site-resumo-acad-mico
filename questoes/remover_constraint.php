<?php
require_once 'conexao.php';

echo "<h1>🔧 REMOVENDO CONSTRAINT UNIQUE</h1>";

try {
    // Remover constraint UNIQUE diretamente
    echo "<p>Removendo constraint 'unique_questao'...</p>";
    $pdo->exec("ALTER TABLE respostas_usuario DROP INDEX unique_questao");
    echo "<p style='color: green;'>✅ Constraint removida!</p>";
    
    // Verificar se foi removida
    $stmt = $pdo->query("SHOW INDEX FROM respostas_usuario WHERE Key_name = 'unique_questao'");
    $constraint = $stmt->fetch();
    
    if (!$constraint) {
        echo "<p style='color: green;'>✅ Confirmação: Constraint removida com sucesso!</p>";
    } else {
        echo "<p style='color: red;'>❌ Erro: Constraint ainda existe</p>";
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
