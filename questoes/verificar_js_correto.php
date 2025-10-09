<?php
// Script para verificar sintaxe JavaScript corretamente
$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "🔍 VERIFICAÇÃO DE SINTAXE JAVASCRIPT\n";
echo "====================================\n\n";

// Extrair apenas o JavaScript
$inicio = strpos($conteudo, '<script>');
$fim = strrpos($conteudo, '</script>');

if ($inicio === false || $fim === false) {
    echo "❌ Tags <script> não encontradas\n";
    exit;
}

$javascript = substr($conteudo, $inicio + 8, $fim - $inicio - 8);
$linhas = explode("\n", $javascript);

echo "📊 Total de linhas JavaScript: " . count($linhas) . "\n\n";

// Verificar chaves
$chaves = 0;
$parenteses = 0;
$colchetes = 0;
$erros = [];

foreach ($linhas as $num => $linha) {
    $linhaNum = $num + 1;
    
    // Contar chaves
    $chaves += substr_count($linha, '{') - substr_count($linha, '}');
    $parenteses += substr_count($linha, '(') - substr_count($linha, ')');
    $colchetes += substr_count($linha, '[') - substr_count($linha, ']');
    
    // Verificar funções sem chaves (apenas em linhas que contêm 'function')
    if (strpos($linha, 'function') !== false && strpos($linha, '{') === false && strpos($linha, '//') === false) {
        $erros[] = "Linha $linhaNum: Função sem chave de abertura: " . trim($linha);
    }
    
    // Verificar chaves desbalanceadas em linhas específicas
    if ($chaves < 0) {
        $erros[] = "Linha $linhaNum: Chave de fechamento sem abertura";
    }
    
    // Verificar parênteses desbalanceados
    if ($parenteses < 0) {
        $erros[] = "Linha $linhaNum: Parêntese de fechamento sem abertura";
    }
    
    // Verificar colchetes desbalanceados
    if ($colchetes < 0) {
        $erros[] = "Linha $linhaNum: Colchete de fechamento sem abertura";
    }
}

echo "📊 Contadores:\n";
echo "   Chaves: $chaves\n";
echo "   Parênteses: $parenteses\n";
echo "   Colchetes: $colchetes\n\n";

if ($chaves === 0 && $parenteses === 0 && $colchetes === 0) {
    echo "✅ Sintaxe JavaScript parece estar correta\n";
} else {
    echo "❌ Possíveis problemas de sintaxe:\n";
    if ($chaves !== 0) echo "   - Chaves desbalanceadas: $chaves\n";
    if ($parenteses !== 0) echo "   - Parênteses desbalanceados: $parenteses\n";
    if ($colchetes !== 0) echo "   - Colchetes desbalanceados: $colchetes\n";
}

if (!empty($erros)) {
    echo "\n❌ Erros encontrados:\n";
    foreach (array_slice($erros, 0, 10) as $erro) { // Mostrar apenas os primeiros 10
        echo "   - $erro\n";
    }
    if (count($erros) > 10) {
        echo "   ... e mais " . (count($erros) - 10) . " erros\n";
    }
} else {
    echo "\n✅ Nenhum erro de sintaxe encontrado\n";
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Se há erros, corrija-os primeiro\n";
echo "2. Teste o quiz no navegador\n";
echo "3. Verifique o console para erros JavaScript\n";
?>
