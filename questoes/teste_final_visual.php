<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>🎯 TESTE FINAL - Verificação da Correção</h1>";

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

echo "<h2>📊 Dados da Simulação:</h2>";
echo "<p><strong>Questão ID:</strong> {$questao['id_questao']}</p>";
echo "<p><strong>Filtro:</strong> $filtro_ativo</p>";
echo "<p><strong>ID da alternativa selecionada (simulada):</strong> $alternativa_correta_id</p>";

echo "<h2>🔍 Aplicando a Lógica Corrigida:</h2>";

$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($alternativas_questao as $index => $alternativa) {
    $letra = $letras[$index] ?? ($index + 1);
    
    // Aplicar a lógica CORRIGIDA do arquivo original
    $is_correct = ($alternativa['eh_correta'] == 1);
    $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
    $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
    
    $class = '';
    if ($is_answered) {
        if ($is_correct) {
            $class = 'alternativa-correta';
        } elseif ($is_selected) {
            $class = 'alternativa-incorreta';
        }
    }
    
    $cor_fundo = '#f8f9fa';
    $icone = '⚪';
    if ($class === 'alternativa-correta') {
        $cor_fundo = '#d4edda';
        $icone = '🟢';
    } elseif ($class === 'alternativa-incorreta') {
        $cor_fundo = '#f8d7da';
        $icone = '🔴';
    }
    
    echo "<div style='border: 2px solid #ddd; padding: 15px; margin: 10px 0; background: $cor_fundo; border-radius: 8px;'>";
    echo "<h3>$icone Alternativa $letra (Posição $index)</h3>";
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

echo "<h2>✅ Verificação Final:</h2>";
$alternativa_correta_encontrada = false;
$alternativa_incorreta_encontrada = false;

foreach ($alternativas_questao as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $is_correct = ($alt['eh_correta'] == 1);
    $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alt['id_alternativa']);
    $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
    
    $class = '';
    if ($is_answered) {
        if ($is_correct) {
            $class = 'alternativa-correta';
            $alternativa_correta_encontrada = true;
        } elseif ($is_selected) {
            $class = 'alternativa-incorreta';
            $alternativa_incorreta_encontrada = true;
        }
    }
    
    if ($is_correct) {
        echo "<div style='background: " . ($class === 'alternativa-correta' ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
        echo "<h3>" . ($class === 'alternativa-correta' ? '✅ CORRETO' : '❌ ERRO') . " - Alternativa Correta (Letra $letra)</h3>";
        echo "<p><strong>ID:</strong> {$alt['id_alternativa']}</p>";
        echo "<p><strong>Classe aplicada:</strong> $class</p>";
        echo "<p><strong>Resultado:</strong> " . ($class === 'alternativa-correta' ? '🟢 VERDE (CORRETO!)' : '🔴 VERMELHO (ERRO!)') . "</p>";
        echo "</div>";
    }
}

if ($alternativa_correta_encontrada) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>🎉 SUCESSO!</h3>";
    echo "<p>A alternativa correta está aparecendo em VERDE como deveria!</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>❌ PROBLEMA!</h3>";
    echo "<p>A alternativa correta NÃO está aparecendo em VERDE!</p>";
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


