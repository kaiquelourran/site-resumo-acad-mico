<?php
require_once 'conexao.php';

// Parâmetros de teste
$id_assunto = 1;
$filtro_ativo = 'todas';
$questao_inicial = 1;

// Query para buscar uma questão específica
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
        WHERE q.id_assunto = ? LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_assunto]);
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debug HTML Output</h2>";
echo "<h3>Dados da Questão:</h3>";
echo "<pre>";
print_r($questao);
echo "</pre>";

echo "<h3>HTML das Alternativas:</h3>";
echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";

if ($questao) {
    $alternativas = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas as $letra) {
        $campo_alternativa = 'alternativa_' . strtolower($letra);
        if (!empty($questao[$campo_alternativa])) {
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
            echo "</div><br>";
            
            echo "<p><strong>Debug info para $letra:</strong> Campo: $campo_alternativa, Conteúdo: " . ($questao[$campo_alternativa] ? 'SIM' : 'NÃO') . "</p>";
        } else {
            echo "<p><strong>Alternativa $letra:</strong> VAZIA (campo: $campo_alternativa)</p>";
        }
    }
} else {
    echo "<p>Nenhuma questão encontrada!</p>";
}

echo "</div>";
?>

<style>
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
</style>