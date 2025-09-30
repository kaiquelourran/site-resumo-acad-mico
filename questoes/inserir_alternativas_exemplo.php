<?php
require_once 'conexao.php';

echo "<h2>Inserir Alternativas de Exemplo</h2>";

try {
    // Buscar questões sem alternativas
    $sql = "SELECT id_questao, enunciado FROM questoes WHERE (alternativa_a IS NULL OR alternativa_a = '') AND id_assunto = 8";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Questões encontradas sem alternativas: " . count($questoes) . "</h3>";
    
    if (!empty($questoes)) {
        foreach ($questoes as $questao) {
            $id_questao = $questao['id_questao'];
            $enunciado_curto = substr($questao['enunciado'], 0, 100) . "...";
            
            echo "<h4>Questão ID: $id_questao</h4>";
            echo "<p><strong>Enunciado:</strong> $enunciado_curto</p>";
            
            // Inserir alternativas de exemplo
            $sql_update = "UPDATE questoes SET 
                alternativa_a = 'Alternativa A - Opção relacionada ao desenvolvimento motor fino',
                alternativa_b = 'Alternativa B - Opção relacionada ao desenvolvimento cognitivo',
                alternativa_c = 'Alternativa C - Opção relacionada ao desenvolvimento social',
                alternativa_d = 'Alternativa D - Opção relacionada ao desenvolvimento emocional',
                alternativa_e = 'Alternativa E - Opção relacionada ao desenvolvimento físico',
                alternativa_correta = 'A'
                WHERE id_questao = ?";
            
            $stmt_update = $pdo->prepare($sql_update);
            if ($stmt_update->execute([$id_questao])) {
                echo "<p>✅ Alternativas inseridas com sucesso!</p>";
            } else {
                echo "<p>❌ Erro ao inserir alternativas</p>";
            }
        }
        
        echo "<hr><h3>Verificação após inserção:</h3>";
        $sql_check = "SELECT id_questao, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, alternativa_correta FROM questoes WHERE id_assunto = 8";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute();
        $questoes_verificacao = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($questoes_verificacao as $q) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h4>Questão ID: " . $q['id_questao'] . "</h4>";
            echo "<p><strong>A:</strong> " . substr($q['alternativa_a'], 0, 50) . "...</p>";
            echo "<p><strong>B:</strong> " . substr($q['alternativa_b'], 0, 50) . "...</p>";
            echo "<p><strong>C:</strong> " . substr($q['alternativa_c'], 0, 50) . "...</p>";
            echo "<p><strong>D:</strong> " . substr($q['alternativa_d'], 0, 50) . "...</p>";
            echo "<p><strong>E:</strong> " . substr($q['alternativa_e'], 0, 50) . "...</p>";
            echo "<p><strong>Correta:</strong> " . $q['alternativa_correta'] . "</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p>✅ Todas as questões já possuem alternativas!</p>";
        
        // Mostrar questões existentes
        $sql_existing = "SELECT id_questao, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, alternativa_correta FROM questoes WHERE id_assunto = 8";
        $stmt_existing = $pdo->prepare($sql_existing);
        $stmt_existing->execute();
        $questoes_existentes = $stmt_existing->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Questões existentes com alternativas:</h3>";
        foreach ($questoes_existentes as $q) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<h4>Questão ID: " . $q['id_questao'] . "</h4>";
            echo "<p><strong>A:</strong> " . substr($q['alternativa_a'], 0, 50) . "...</p>";
            echo "<p><strong>B:</strong> " . substr($q['alternativa_b'], 0, 50) . "...</p>";
            echo "<p><strong>C:</strong> " . substr($q['alternativa_c'], 0, 50) . "...</p>";
            echo "<p><strong>D:</strong> " . substr($q['alternativa_d'], 0, 50) . "...</p>";
            echo "<p><strong>E:</strong> " . substr($q['alternativa_e'], 0, 50) . "...</p>";
            echo "<p><strong>Correta:</strong> " . $q['alternativa_correta'] . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>