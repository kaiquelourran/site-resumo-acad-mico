<?php
// Script para debugar a linha 2816

$arquivo = 'quiz_vertical_filtros.php';
$linhas = file($arquivo);

echo "🔍 DEBUG DA LINHA 2816\n";
echo "=====================\n\n";

if (isset($linhas[2815])) { // Array é 0-indexed, então linha 2816 é índice 2815
    $linha = $linhas[2815];
    echo "Linha 2816: " . $linha . "\n\n";
    
    echo "Análise de caracteres:\n";
    echo "Tamanho: " . strlen($linha) . " caracteres\n";
    echo "Tamanho UTF-8: " . mb_strlen($linha, 'UTF-8') . " caracteres\n\n";
    
    echo "Caracteres individuais:\n";
    for ($i = 0; $i < strlen($linha); $i++) {
        $char = $linha[$i];
        $ord = ord($char);
        echo "Posição $i: '$char' (ASCII: $ord)\n";
    }
    
    echo "\nAnálise de bytes:\n";
    $bytes = unpack('C*', $linha);
    foreach ($bytes as $pos => $byte) {
        echo "Byte $pos: $byte\n";
    }
    
    echo "\nRepresentação hexadecimal:\n";
    echo bin2hex($linha) . "\n";
    
    echo "\nVerificando caracteres especiais:\n";
    if (preg_match('/[^\x00-\x7F]/', $linha)) {
        echo "❌ Encontrados caracteres não-ASCII!\n";
        preg_match_all('/[^\x00-\x7F]/', $linha, $matches);
        foreach ($matches[0] as $char) {
            echo "Caractere problemático: '$char' (Unicode: " . mb_ord($char) . ")\n";
        }
    } else {
        echo "✅ Apenas caracteres ASCII encontrados\n";
    }
    
} else {
    echo "❌ Linha 2816 não encontrada!\n";
    echo "Total de linhas no arquivo: " . count($linhas) . "\n";
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Verifique se há caracteres invisíveis\n";
echo "2. Verifique se há problemas de codificação\n";
echo "3. Corrija a linha se necessário\n";
?>
