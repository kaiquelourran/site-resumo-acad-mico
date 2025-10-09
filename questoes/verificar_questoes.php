<?php
require_once 'conexao.php';

try {
    // Verificar questões existentes
    $stmt = $pdo->query("SELECT id_questao, enunciado FROM questoes LIMIT 5");
    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📚 Questões Disponíveis:</h3>";
    if (empty($questoes)) {
        echo "❌ Nenhuma questão encontrada no banco de dados.";
    } else {
        foreach ($questoes as $questao) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "<strong>ID: " . $questao['id_questao'] . "</strong><br>";
            echo "<p>" . htmlspecialchars(substr($questao['enunciado'], 0, 100)) . "...</p>";
            echo "</div>";
        }
    }
    
    // Verificar assuntos
    $stmt = $pdo->query("SELECT id_assunto, nome FROM assuntos LIMIT 5");
    $assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📖 Assuntos Disponíveis:</h3>";
    if (empty($assuntos)) {
        echo "❌ Nenhum assunto encontrado no banco de dados.";
    } else {
        foreach ($assuntos as $assunto) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "<strong>ID: " . $assunto['id_assunto'] . "</strong> - " . htmlspecialchars($assunto['nome']);
            echo "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
