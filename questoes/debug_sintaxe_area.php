<?php
// Script para debugar sintaxe JavaScript na área da linha 2816

$arquivo = 'quiz_vertical_filtros.php';
$linhas = file($arquivo);

echo "🔍 DEBUG DE SINTAXE JAVASCRIPT - ÁREA 2816\n";
echo "==========================================\n\n";

// Verificar linhas ao redor da 2816
$inicio = 2800;
$fim = 2850;

echo "Verificando linhas $inicio a $fim:\n\n";

for ($i = $inicio; $i <= $fim && $i < count($linhas); $i++) {
    $linha = $linhas[$i];
    $num = $i + 1;
    
    echo "Linha $num: " . rtrim($linha) . "\n";
    
    // Verificar problemas comuns de sintaxe
    if (strpos($linha, 'function') !== false && strpos($linha, '{') === false && strpos($linha, '//') === false) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Função sem chave de abertura\n";
    }
    
    if (strpos($linha, '}') !== false && strpos($linha, '{') === false) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Chave de fechamento sem abertura\n";
    }
    
    if (strpos($linha, ')') !== false && strpos($linha, '(') === false) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Parêntese de fechamento sem abertura\n";
    }
    
    if (strpos($linha, ']') !== false && strpos($linha, '[') === false) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Colchete de fechamento sem abertura\n";
    }
    
    // Verificar caracteres especiais
    if (preg_match('/[^\x00-\x7F]/', $linha)) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Caracteres não-ASCII encontrados\n";
    }
    
    // Verificar aspas não fechadas
    $aspas_simples = substr_count($linha, "'") - substr_count($linha, "\\'");
    $aspas_duplas = substr_count($linha, '"') - substr_count($linha, '\\"');
    
    if ($aspas_simples % 2 !== 0) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Aspas simples não fechadas\n";
    }
    
    if ($aspas_duplas % 2 !== 0) {
        echo "  ⚠️ POSSÍVEL PROBLEMA: Aspas duplas não fechadas\n";
    }
    
    echo "\n";
}

echo "🔧 PRÓXIMOS PASSOS:\n";
echo "1. Verifique as linhas marcadas com ⚠️\n";
echo "2. Corrija os problemas encontrados\n";
echo "3. Teste novamente o quiz\n";
?>
