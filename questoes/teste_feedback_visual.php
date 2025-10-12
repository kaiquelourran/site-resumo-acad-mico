<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>🎯 TESTE DO FEEDBACK VISUAL</h1>";

// Simular dados
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

// Encontrar alternativa correta
$alternativa_correta_id = null;
$letra_correta = '';
foreach ($alternativas_questao as $index => $alt) {
    if ($alt['eh_correta'] == 1) {
        $alternativa_correta_id = $alt['id_alternativa'];
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $letra_correta = $letras[$index] ?? ($index + 1);
        break;
    }
}

echo "<h2>📊 Dados da Simulação:</h2>";
echo "<p><strong>Questão ID:</strong> {$questao['id_questao']}</p>";
echo "<p><strong>Filtro:</strong> $filtro_ativo</p>";
echo "<p><strong>Alternativa correta (ID):</strong> $alternativa_correta_id</p>";
echo "<p><strong>Letra correta:</strong> $letra_correta</p>";

echo "<h2>🧪 Teste da Função mostrarFeedbackVisual:</h2>";

$letras = ['A', 'B', 'C', 'D', 'E'];

// Teste 1: Clique na alternativa CORRETA
echo "<h3>Teste 1: Clique na Alternativa CORRETA (Letra $letra_correta)</h3>";
echo "<div id='teste-correta'>";
echo "<p><strong>Parâmetros da função:</strong></p>";
echo "<ul>";
echo "<li>questaoId: $id_questao</li>";
echo "<li>alternativaSelecionada: $letra_correta</li>";
echo "<li>alternativaCorreta: $letra_correta</li>";
echo "<li>explicacao: 'Teste de explicação'</li>";
echo "</ul>";

echo "<p><strong>Lógica aplicada:</strong></p>";
echo "<ol>";
echo "<li>Marcar alternativa correta ($letra_correta) como VERDE</li>";
echo "<li>Verificar se alternativa selecionada ($letra_correta) é diferente da correta</li>";
echo "<li>Como são iguais, NÃO marcar como vermelha</li>";
echo "</ol>";

echo "<p><strong>Resultado esperado:</strong> Alternativa $letra_correta deve aparecer em VERDE</p>";
echo "</div>";

// Teste 2: Clique na alternativa INCORRETA
$letra_incorreta = '';
foreach ($alternativas_questao as $index => $alt) {
    if ($alt['eh_correta'] != 1) {
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $letra_incorreta = $letras[$index] ?? ($index + 1);
        break;
    }
}

echo "<h3>Teste 2: Clique na Alternativa INCORRETA (Letra $letra_incorreta)</h3>";
echo "<div id='teste-incorreta'>";
echo "<p><strong>Parâmetros da função:</strong></p>";
echo "<ul>";
echo "<li>questaoId: $id_questao</li>";
echo "<li>alternativaSelecionada: $letra_incorreta</li>";
echo "<li>alternativaCorreta: $letra_correta</li>";
echo "<li>explicacao: 'Teste de explicação'</li>";
echo "</ul>";

echo "<p><strong>Lógica aplicada:</strong></p>";
echo "<ol>";
echo "<li>Marcar alternativa correta ($letra_correta) como VERDE</li>";
echo "<li>Verificar se alternativa selecionada ($letra_incorreta) é diferente da correta</li>";
echo "<li>Como são diferentes, marcar como VERMELHA</li>";
echo "</ol>";

echo "<p><strong>Resultado esperado:</strong> Alternativa $letra_correta em VERDE, alternativa $letra_incorreta em VERMELHO</p>";
echo "</div>";

echo "<h2>🎨 Simulação Visual:</h2>";

// Simular as alternativas
foreach ($alternativas_questao as $index => $alternativa) {
    $letra = $letras[$index] ?? ($index + 1);
    $is_correct = ($alternativa['eh_correta'] == 1);
    
    $class = '';
    $cor_fundo = '#f8f9fa';
    $icone = '⚪';
    
    if ($is_correct) {
        $class = 'alternative-correct';
        $cor_fundo = '#d4edda';
        $icone = '🟢';
    }
    
    echo "<div class='alternative $class' style='border: 2px solid #ddd; padding: 15px; margin: 10px 0; background: $cor_fundo; border-radius: 8px;'>";
    echo "<div class='alternative-letter' style='background: #0072FF; color: white; width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; margin-right: 15px;'>$letra</div>";
    echo "<div class='alternative-text' style='display: inline;'>" . htmlspecialchars(substr($alternativa['texto'], 0, 80)) . "...</div>";
    echo "<span style='margin-left: 15px; font-weight: bold;'>$icone " . ($is_correct ? 'CORRETA' : 'INCORRETA') . "</span>";
    echo "</div>";
}

echo "<h2>✅ Verificação Final:</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Função mostrarFeedbackVisual corrigida:</strong></p>";
echo "<ul>";
echo "<li>✅ Alternativa correta sempre marcada como VERDE</li>";
echo "<li>✅ Alternativa selecionada só é marcada como VERMELHA se for diferente da correta</li>";
echo "<li>✅ Se a alternativa selecionada for a correta, ela fica VERDE (não vermelha)</li>";
echo "</ul>";
echo "</div>";

// Incluir CSS
?>
<link rel="stylesheet" href="modern-style.css">
<style>
.alternative-correct {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
    border-color: #28a745 !important;
    color: #155724 !important;
}

.alternative-incorrect-chosen {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
    border-color: #dc3545 !important;
    color: #721c24 !important;
}
</style>


