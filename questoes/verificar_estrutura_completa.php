<?php
// Script para verificar estrutura completa do JavaScript

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "🔍 VERIFICAÇÃO COMPLETA DA ESTRUTURA JAVASCRIPT\n";
echo "==============================================\n\n";

// Extrair apenas o JavaScript do segundo bloco
$inicio = strpos($conteudo, '<script>', strpos($conteudo, '<script>') + 1); // Segundo <script>
$fim = strrpos($conteudo, '</script>');

if ($inicio === false || $fim === false) {
    echo "❌ Blocos <script> não encontrados\n";
    exit;
}

$javascript = substr($conteudo, $inicio + 8, $fim - $inicio - 8);
$linhas = explode("\n", $javascript);

echo "📊 Total de linhas JavaScript: " . count($linhas) . "\n\n";

// Verificar estrutura de chaves
$chaves = 0;
$parenteses = 0;
$colchetes = 0;
$erros = [];
$funcoes = [];

foreach ($linhas as $num => $linha) {
    $linhaNum = $num + 1;
    
    // Contar chaves
    $chaves += substr_count($linha, '{') - substr_count($linha, '}');
    $parenteses += substr_count($linha, '(') - substr_count($linha, ')');
    $colchetes += substr_count($linha, '[') - substr_count($linha, ']');
    
    // Verificar funções
    if (preg_match('/function\s+(\w+)/', $linha, $matches)) {
        $funcoes[] = ['nome' => $matches[1], 'linha' => $linhaNum];
    }
    
    // Verificar problemas específicos
    if (strpos($linha, 'function') !== false && strpos($linha, '{') === false && strpos($linha, '//') === false) {
        $erros[] = "Linha $linhaNum: Função sem chave de abertura: " . trim($linha);
    }
    
    if ($chaves < 0) {
        $erros[] = "Linha $linhaNum: Chave de fechamento sem abertura (chaves: $chaves)";
    }
    
    if ($parenteses < 0) {
        $erros[] = "Linha $linhaNum: Parêntese de fechamento sem abertura (parênteses: $parenteses)";
    }
    
    if ($colchetes < 0) {
        $erros[] = "Linha $linhaNum: Colchete de fechamento sem abertura (colchetes: $colchetes)";
    }
    
    // Verificar aspas não fechadas
    $aspas_simples = substr_count($linha, "'") - substr_count($linha, "\\'");
    $aspas_duplas = substr_count($linha, '"') - substr_count($linha, '\\"');
    
    if ($aspas_simples % 2 !== 0) {
        $erros[] = "Linha $linhaNum: Aspas simples não fechadas: " . trim($linha);
    }
    
    if ($aspas_duplas % 2 !== 0) {
        $erros[] = "Linha $linhaNum: Aspas duplas não fechadas: " . trim($linha);
    }
    
    // Verificar caracteres especiais
    if (preg_match('/[^\x00-\x7F]/', $linha)) {
        $erros[] = "Linha $linhaNum: Caracteres não-ASCII: " . trim($linha);
    }
}

echo "📊 Contadores finais:\n";
echo "   Chaves: $chaves\n";
echo "   Parênteses: $parenteses\n";
echo "   Colchetes: $colchetes\n\n";

echo "🔧 Funções encontradas:\n";
foreach ($funcoes as $funcao) {
    echo "   - {$funcao['nome']} (linha {$funcao['linha']})\n";
}
echo "\n";

if ($chaves === 0 && $parenteses === 0 && $colchetes === 0) {
    echo "✅ Estrutura de chaves, parênteses e colchetes está correta\n";
} else {
    echo "❌ Problemas de estrutura encontrados:\n";
    if ($chaves !== 0) echo "   - Chaves desbalanceadas: $chaves\n";
    if ($parenteses !== 0) echo "   - Parênteses desbalanceados: $parenteses\n";
    if ($colchetes !== 0) echo "   - Colchetes desbalanceados: $colchetes\n";
}

if (!empty($erros)) {
    echo "\n❌ Erros encontrados:\n";
    foreach (array_slice($erros, 0, 20) as $erro) { // Mostrar apenas os primeiros 20
        echo "   - $erro\n";
    }
    if (count($erros) > 20) {
        echo "   ... e mais " . (count($erros) - 20) . " erros\n";
    }
} else {
    echo "\n✅ Nenhum erro de sintaxe encontrado\n";
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Se há erros, corrija-os primeiro\n";
echo "2. Teste o quiz no navegador\n";
echo "3. Verifique o console para erros JavaScript\n";
?>
