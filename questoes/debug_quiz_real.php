<?php
require_once __DIR__ . '/conexao.php';

// Parâmetros do quiz
$id_assunto = 8;
$filtro = 'todas';

echo "<h2>Debug - Quiz Real (ID Assunto: $id_assunto, Filtro: $filtro)</h2>";

// Buscar questões como no quiz real
$where_clause = "WHERE q.id_assunto = ?";
$params = [$id_assunto];

if ($filtro === 'acertadas') {
    $where_clause .= " AND ru.acertou = 1";
} elseif ($filtro === 'erradas') {
    $where_clause .= " AND ru.acertou = 0";
} elseif ($filtro === 'nao_respondidas') {
    $where_clause .= " AND ru.id_resposta IS NULL";
}

$sql = "SELECT q.*, ru.id_alternativa, ru.acertou 
        FROM questoes q 
        LEFT JOIN respostas_usuario ru ON q.id_questao = ru.id_questao 
        $where_clause 
        ORDER BY q.id_questao 
        LIMIT 3";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Questões encontradas:</strong> " . count($questoes) . "</p>";

foreach ($questoes as $index => $questao) {
    echo "<h3>Questão " . ($index + 1) . " (ID: {$questao['id_questao']})</h3>";
    
    // Buscar alternativas
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao['id_questao']]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    $is_answered = !empty($questao['id_alternativa']);
    
    echo "<p><strong>Status:</strong> " . ($is_answered ? "RESPONDIDA" : "NÃO RESPONDIDA") . "</p>";
    echo "<p><strong>Alternativas encontradas:</strong> " . count($alternativas_questao) . "</p>";
    
    if ($is_answered) {
        echo "<p><strong>ID Alternativa Selecionada:</strong> {$questao['id_alternativa']}</p>";
        echo "<p><strong>Acertou:</strong> " . ($questao['acertou'] ? 'SIM' : 'NÃO') . "</p>";
    }
    
    echo "<h4>Alternativas:</h4>";
    
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $alt_index => $alternativa) {
        $letra = $letras[$alt_index] ?? ($alt_index + 1);
        
        // Verificar se esta alternativa foi selecionada pelo usuário
        $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
        $is_correct = ($alternativa['eh_correta'] == 1);
        
        $class = '';
        if ($is_answered) {
            if ($is_correct) {
                $class = 'alternativa-correta';
            } elseif ($is_selected && !$is_correct) {
                $class = 'alternativa-incorreta';
            }
        }
        
        $pointer_events = $is_answered ? 'pointer-events: none;' : '';
        
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0; background: " . 
             ($class === 'alternativa-correta' ? '#d4edda' : ($class === 'alternativa-incorreta' ? '#f8d7da' : '#f8f9fa')) . 
             "; $pointer_events'>";
        echo "<strong>$letra)</strong> " . htmlspecialchars($alternativa['texto']);
        echo "<br><small>ID: {$alternativa['id_alternativa']} | Correta: " . ($is_correct ? 'SIM' : 'NÃO') . 
             " | Selecionada: " . ($is_selected ? 'SIM' : 'NÃO') . 
             " | Clicável: " . ($pointer_events ? 'NÃO' : 'SIM') . "</small>";
        echo "</div>";
    }
    
    echo "<hr>";
}
?>