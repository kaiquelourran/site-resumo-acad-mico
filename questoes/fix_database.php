<?php
require_once 'conexao.php';

echo "<h1>🔧 CORRIGINDO BANCO DE DADOS</h1>";

try {
    // 1. Verificar se a constraint existe
    echo "<h2>1. Verificando constraint UNIQUE...</h2>";
    $stmt = $pdo->query("SHOW INDEX FROM respostas_usuario WHERE Key_name = 'unique_questao'");
    $constraint = $stmt->fetch();
    
    if ($constraint) {
        echo "<p style='color: orange;'>⚠️ Constraint 'unique_questao' encontrada - removendo...</p>";
        
        // 2. Remover constraint UNIQUE
        $pdo->exec("ALTER TABLE respostas_usuario DROP INDEX unique_questao");
        echo "<p style='color: green;'>✅ Constraint UNIQUE removida com sucesso!</p>";
    } else {
        echo "<p style='color: green;'>✅ Constraint UNIQUE não existe</p>";
    }
    
    // 3. Adicionar índice normal se não existir
    echo "<h2>2. Verificando índice normal...</h2>";
    $stmt = $pdo->query("SHOW INDEX FROM respostas_usuario WHERE Key_name = 'idx_questao'");
    $index = $stmt->fetch();
    
    if (!$index) {
        $pdo->exec("ALTER TABLE respostas_usuario ADD INDEX idx_questao (id_questao)");
        echo "<p style='color: green;'>✅ Índice normal adicionado!</p>";
    } else {
        echo "<p style='color: green;'>✅ Índice normal já existe</p>";
    }
    
    // 4. Testar inserção múltipla
    echo "<h2>3. Testando inserção múltipla...</h2>";
    $test_questao = 999; // ID de teste
    $test_alt = 999; // ID de teste
    
    // Inserir primeira resposta
    $pdo->exec("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) VALUES ($test_questao, $test_alt, 1)");
    echo "<p style='color: green;'>✅ Primeira inserção OK</p>";
    
    // Tentar inserir segunda resposta (deve funcionar agora)
    $pdo->exec("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) VALUES ($test_questao, $test_alt, 0)");
    echo "<p style='color: green;'>✅ Segunda inserção OK - Múltiplas respostas funcionando!</p>";
    
    // Limpar dados de teste
    $pdo->exec("DELETE FROM respostas_usuario WHERE id_questao = $test_questao");
    echo "<p style='color: blue;'>🧹 Dados de teste removidos</p>";
    
    echo "<h2 style='color: green;'>🎉 CORREÇÃO CONCLUÍDA COM SUCESSO!</h2>";
    echo "<p>Agora você pode responder as questões múltiplas vezes e ver as estatísticas.</p>";
    echo "<p><a href='quiz_vertical_filtros.php?id=8' style='background: #0072FF; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🎯 IR PARA O QUIZ</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
