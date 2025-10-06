<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>🔍 TESTE VISUAL - Lógica de Exibição</h1>";

// Simular dados de uma questão respondida
$id_questao = 92;
$filtro_ativo = 'respondidas'; // Simular filtro de respondidas

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

// Simular que a questão foi respondida (ID da alternativa selecionada)
$resposta_simulada = $alternativas_questao[2]['id_alternativa']; // Simular que selecionou a 3ª alternativa

echo "<h2>Dados da Simulação:</h2>";
echo "<p><strong>Questão:</strong> {$questao['id_questao']}</p>";
echo "<p><strong>Filtro:</strong> $filtro_ativo</p>";
echo "<p><strong>Resposta simulada (ID):</strong> $resposta_simulada</p>";

echo "<h2>Alternativas após embaralhamento:</h2>";

$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($alternativas_questao as $index => $alternativa) {
    $letra = $letras[$index] ?? ($index + 1);
    
    // Aplicar a mesma lógica do arquivo original
    $is_selected = ($resposta_simulada == $alternativa['id_alternativa']);
    $is_correct = ($alternativa['eh_correta'] == 1);
    $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($resposta_simulada);
    
    $class = '';
    if ($is_answered && ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas')) {
        if ($is_correct) {
            $class = 'alternativa-correta';
        } elseif ($is_selected && !$is_correct) {
            $class = 'alternativa-incorreta';
        }
    }
    
    echo "<div style='border: 2px solid #ddd; padding: 15px; margin: 10px 0; background: " . 
         ($class === 'alternativa-correta' ? '#d4edda' : ($class === 'alternativa-incorreta' ? '#f8d7da' : '#f8f9fa')) . ";'>";
    
    echo "<h3>Alternativa $letra (Posição $index)</h3>";
    echo "<p><strong>ID:</strong> {$alternativa['id_alternativa']}</p>";
    echo "<p><strong>Texto:</strong> " . htmlspecialchars(substr($alternativa['texto'], 0, 100)) . "...</p>";
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

echo "<h2>Análise:</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Lógica aplicada:</strong></p>";
echo "<ul>";
echo "<li>Se <code>is_correct = true</code> → Classe <code>alternativa-correta</code> (VERDE)</li>";
echo "<li>Se <code>is_selected = true</code> E <code>is_correct = false</code> → Classe <code>alternativa-incorreta</code> (VERMELHO)</li>";
echo "<li>Caso contrário → Sem classe (NEUTRO)</li>";
echo "</ul>";
echo "</div>";

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

