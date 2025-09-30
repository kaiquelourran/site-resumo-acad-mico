<?php
require_once 'conexao.php';

echo "<h2>Teste Final - Alternativas Corretas</h2>";

try {
    // Buscar uma questão específica
    $sql_questao = "SELECT * FROM questoes WHERE id_assunto = 8 LIMIT 1";
    $stmt_questao = $pdo->prepare($sql_questao);
    $stmt_questao->execute();
    $questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<h3>Questão ID: " . $questao['id_questao'] . "</h3>";
        echo "<p><strong>Enunciado:</strong> " . htmlspecialchars($questao['enunciado']) . "</p>";
        
        // Buscar alternativas da tabela 'alternativas'
        echo "<h4>Alternativas da tabela 'alternativas':</h4>";
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$questao['id_questao']]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($alternativas_questao)) {
            $letras = ['A', 'B', 'C', 'D', 'E'];
            foreach ($alternativas_questao as $index => $alternativa) {
                $letra = $letras[$index] ?? ($index + 1);
                $correta = $alternativa['eh_correta'] ? ' ✅ CORRETA' : '';
                echo "<p><strong>$letra:</strong> " . htmlspecialchars($alternativa['texto']) . "$correta</p>";
            }
        } else {
            echo "<p>❌ Nenhuma alternativa encontrada na tabela 'alternativas'</p>";
        }
        
        // Comparar com os campos alternativa_* da tabela questoes
        echo "<h4>Campos alternativa_* da tabela questoes (antigo sistema):</h4>";
        echo "<p><strong>A:</strong> " . ($questao['alternativa_a'] ?: '❌ VAZIO') . "</p>";
        echo "<p><strong>B:</strong> " . ($questao['alternativa_b'] ?: '❌ VAZIO') . "</p>";
        echo "<p><strong>C:</strong> " . ($questao['alternativa_c'] ?: '❌ VAZIO') . "</p>";
        echo "<p><strong>D:</strong> " . ($questao['alternativa_d'] ?: '❌ VAZIO') . "</p>";
        echo "<p><strong>E:</strong> " . ($questao['alternativa_e'] ?: '❌ VAZIO') . "</p>";
        echo "<p><strong>Correta:</strong> " . ($questao['alternativa_correta'] ?: '❌ VAZIO') . "</p>";
        
        // Simular o HTML que seria gerado
        echo "<h4>HTML que seria gerado no quiz:</h4>";
        echo "<div style='border: 1px solid #ccc; padding: 15px; background: #f9f9f9;'>";
        
        if (!empty($alternativas_questao)) {
            foreach ($alternativas_questao as $index => $alternativa) {
                $letra = $letras[$index] ?? ($index + 1);
                $class = $alternativa['eh_correta'] ? 'style="background-color: #d4edda; border: 2px solid #28a745;"' : 'style="background-color: #f8f9fa; border: 1px solid #dee2e6;"';
                echo "<div class='alternative' $class>";
                echo "<div class='alternative-letter'>$letra</div>";
                echo "<div class='alternative-text'>" . htmlspecialchars($alternativa['texto']) . "</div>";
                echo "</div><br>";
            }
        } else {
            echo "<p>❌ Nenhuma alternativa seria exibida</p>";
        }
        
        echo "</div>";
        
        // Conclusão
        echo "<h4>Conclusão:</h4>";
        if (!empty($alternativas_questao)) {
            echo "<p>✅ <strong>SUCESSO!</strong> As alternativas corretas da tabela 'alternativas' agora serão exibidas no quiz.</p>";
            echo "<p>✅ Total de alternativas: " . count($alternativas_questao) . "</p>";
        } else {
            echo "<p>❌ <strong>PROBLEMA!</strong> Ainda não há alternativas na tabela 'alternativas' para esta questão.</p>";
        }
        
    } else {
        echo "<p>❌ Nenhuma questão encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>