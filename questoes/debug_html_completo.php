<?php
require_once 'conexao.php';

$id_assunto = 8;
$filtro_ativo = 'todas';

// Query para buscar questões
$sql = "SELECT q.*, a.nome as assunto_nome, 
               CASE 
                   WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'acertada'
                   WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                   WHEN r.id_questao IS NOT NULL THEN 'respondida'
                   ELSE 'nao-respondida'
               END as status_resposta,
               r.id_alternativa
        FROM questoes q 
        LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
        LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
        WHERE q.id_assunto = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_assunto]);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Debug HTML Completo</h2>";
echo "<p><strong>Questões encontradas:</strong> " . count($questoes) . "</p>";

if (!empty($questoes)) {
    foreach ($questoes as $index => $questao) {
        echo "<hr>";
        echo "<h3>Questão #{$questao['id_questao']}</h3>";
        echo "<p><strong>Enunciado:</strong> " . htmlspecialchars(substr($questao['enunciado'], 0, 100)) . "...</p>";
        
        echo "<h4>Dados das Alternativas:</h4>";
        $alternativas = ['A', 'B', 'C', 'D', 'E'];
        foreach ($alternativas as $letra) {
            $campo = 'alternativa_' . strtolower($letra);
            $valor = $questao[$campo] ?? '';
            echo "<p><strong>$letra:</strong> " . ($valor ? "✅ " . substr($valor, 0, 50) . "..." : "❌ VAZIA") . "</p>";
        }
        
        echo "<h4>HTML Renderizado das Alternativas:</h4>";
        echo "<div style='border: 2px solid #007bff; padding: 15px; margin: 10px 0; background: #f8f9fa;'>";
        echo "<div class='alternatives-container'>";
        
        $alternativas_renderizadas = 0;
        foreach ($alternativas as $letra) {
            $campo_alternativa = 'alternativa_' . strtolower($letra);
            if (!empty($questao[$campo_alternativa])) {
                $alternativas_renderizadas++;
                
                // Converter id_alternativa para letra (1=A, 2=B, 3=C, 4=D, 5=E)
                $alternativa_selecionada_letra = '';
                if (!empty($questao['id_alternativa'])) {
                    $alternativa_selecionada_letra = chr(ord('A') + $questao['id_alternativa'] - 1);
                }
                
                $is_selected = ($alternativa_selecionada_letra === $letra);
                $is_correct = ($questao['alternativa_correta'] === $letra);
                $is_answered = !empty($questao['id_alternativa']);
                
                $class = '';
                if ($is_answered) {
                    if ($is_correct) {
                        $class = 'alternativa-correta';
                    } elseif ($is_selected && !$is_correct) {
                        $class = 'alternativa-incorreta';
                    }
                }
                
                echo "<div class='alternative $class' data-alternativa='$letra' data-questao-id='{$questao['id_questao']}'>";
                echo "<div class='alternative-letter'>$letra</div>";
                echo "<div class='alternative-text'>" . htmlspecialchars($questao[$campo_alternativa]) . "</div>";
                echo "</div>";
            }
        }
        
        echo "</div>";
        echo "</div>";
        echo "<p><strong>Alternativas renderizadas:</strong> $alternativas_renderizadas</p>";
        
        if ($alternativas_renderizadas == 0) {
            echo "<p style='color: red; font-weight: bold;'>⚠️ PROBLEMA: Nenhuma alternativa foi renderizada!</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Nenhuma questão encontrada!</p>";
}
?>

<style>
.alternatives-container {
    margin: 20px 0;
}

.alternative {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    margin: 10px 0;
}

.alternative:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.alternative-letter {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 20px;
    flex-shrink: 0;
    font-size: 1.1em;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.alternative-text {
    flex: 1;
    font-size: 1.05em;
    line-height: 1.6;
    color: #2c3e50;
    font-weight: 500;
}

.alternativa-correta {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-color: #28a745;
}

.alternativa-incorreta {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border-color: #dc3545;
}
</style>