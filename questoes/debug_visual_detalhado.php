<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>🔍 DEBUG VISUAL DETALHADO</h1>";

// Simular exatamente o que acontece no quiz_vertical_filtros.php
$id_questao = 92;
$filtro_ativo = 'respondidas';

// Buscar questão
$stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
$stmt->execute([$id_questao]);
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$questao) {
    echo "<p style='color: red;'>Questão não encontrada</p>";
    exit;
}

// Buscar alternativas
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$id_questao]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

// Embaralhar
$seed = $id_questao + (int)date('Ymd');
srand($seed);
shuffle($alternativas_questao);

// Simular que a questão foi respondida - vamos simular que selecionou a alternativa correta
$alternativa_correta_id = null;
foreach ($alternativas_questao as $alt) {
    if ($alt['eh_correta'] == 1) {
        $alternativa_correta_id = $alt['id_alternativa'];
        break;
    }
}

// Simular dados da questão como se fosse do banco
$questao['id_alternativa'] = $alternativa_correta_id; // Simular que selecionou a correta

echo "<h2>Dados da Simulação:</h2>";
echo "<p><strong>Questão ID:</strong> {$questao['id_questao']}</p>";
echo "<p><strong>Filtro:</strong> $filtro_ativo</p>";
echo "<p><strong>ID da alternativa selecionada (simulada):</strong> $alternativa_correta_id</p>";

echo "<h2>Alternativas após embaralhamento:</h2>";

$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($alternativas_questao as $index => $alternativa) {
    $letra = $letras[$index] ?? ($index + 1);
    
    // Aplicar EXATAMENTE a mesma lógica do arquivo original
    $is_selected = false;
    if ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') {
        $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
    }
    
    $is_correct = ($alternativa['eh_correta'] == 1);
    $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
    
    $class = '';
    if ($is_answered && ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas')) {
        if ($is_correct) {
            $class = 'alternativa-correta';
        } elseif ($is_selected) {
            $class = 'alternativa-incorreta';
        }
    }
    
    $cor_fundo = '#f8f9fa';
    if ($class === 'alternativa-correta') $cor_fundo = '#d4edda';
    if ($class === 'alternativa-incorreta') $cor_fundo = '#f8d7da';
    
    echo "<div style='border: 2px solid #ddd; padding: 15px; margin: 10px 0; background: $cor_fundo;'>";
    echo "<h3>Alternativa $letra (Posição $index)</h3>";
    echo "<p><strong>ID:</strong> {$alternativa['id_alternativa']}</p>";
    echo "<p><strong>Texto:</strong> " . htmlspecialchars(substr($alternativa['texto'], 0, 80)) . "...</p>";
    echo "<p><strong>eh_correta:</strong> " . ($alternativa['eh_correta'] ?? 'NULL') . "</p>";
    echo "<p><strong>is_selected:</strong> " . ($is_selected ? 'SIM' : 'NÃO') . "</p>";
    echo "<p><strong>is_correct:</strong> " . ($is_correct ? 'SIM' : 'NÃO') . "</p>";
    echo "<p><strong>is_answered:</strong> " . ($is_answered ? 'SIM' : 'NÃO') . "</p>";
    echo "<p><strong>Classe aplicada:</strong> " . ($class ?: 'NENHUMA') . "</p>";
    echo "<p><strong>Resultado visual:</strong> " . 
         ($class === 'alternativa-correta' ? '🟢 VERDE (CORRETA)' : 
          ($class === 'alternativa-incorreta' ? '🔴 VERMELHO (INCORRETA)' : '⚪ NEUTRO')) . "</p>";
    echo "</div>";
}

echo "<h2>Análise Detalhada:</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Valores das variáveis:</strong></p>";
echo "<ul>";
echo "<li><code>filtro_ativo</code>: '$filtro_ativo'</li>";
echo "<li><code>questao['id_alternativa']</code>: '{$questao['id_alternativa']}'</li>";
echo "<li><code>is_answered</code>: " . (($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']) ? 'true' : 'false') . "</li>";
echo "</ul>";

echo "<p><strong>Lógica aplicada:</strong></p>";
echo "<ul>";
echo "<li>Se <code>is_answered = true</code> E <code>filtro_ativo != 'todas'</code> E <code>filtro_ativo != 'nao-respondidas'</code></li>";
echo "<li>Então: Se <code>is_correct = true</code> → <code>alternativa-correta</code> (VERDE)</li>";
echo "<li>Senão: Se <code>is_selected = true</code> → <code>alternativa-incorreta</code> (VERMELHO)</li>";
echo "</ul>";
echo "</div>";

// Teste específico: vamos ver se a alternativa correta está sendo identificada corretamente
echo "<h2>Teste Específico da Alternativa Correta:</h2>";
$alternativa_correta_encontrada = null;
foreach ($alternativas_questao as $index => $alt) {
    if ($alt['eh_correta'] == 1) {
        $alternativa_correta_encontrada = $alt;
        $letra_correta = $letras[$index] ?? ($index + 1);
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h3>✅ Alternativa Correta Encontrada:</h3>";
        echo "<p><strong>Letra:</strong> $letra_correta</p>";
        echo "<p><strong>ID:</strong> {$alt['id_alternativa']}</p>";
        echo "<p><strong>Texto:</strong> " . htmlspecialchars(substr($alt['texto'], 0, 100)) . "...</p>";
        echo "<p><strong>eh_correta:</strong> {$alt['eh_correta']}</p>";
        echo "<p><strong>Deve aparecer como:</strong> 🟢 VERDE (alternativa-correta)</p>";
        echo "</div>";
        break;
    }
}

if (!$alternativa_correta_encontrada) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>❌ ERRO: Nenhuma alternativa correta encontrada!</h3>";
    echo "</div>";
}

// Incluir CSS para visualizar as classes
?>
<style>
.alternativa-correta {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
    border-color: #28a745 !important;
    color: #155724 !important;
}

.alternativa-incorreta {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
    border-color: #dc3545 !important;
    color: #721c24 !important;
}
</style>


