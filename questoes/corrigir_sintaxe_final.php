<?php
// Script para corrigir sintaxe JavaScript e caracteres especiais

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "🔧 CORRIGINDO SINTAXE JAVASCRIPT\n";
echo "================================\n\n";

$originalContent = $conteudo;

// 1. Remover caracteres especiais problemáticos
$conteudo = str_replace(['"', '"', ''', ''', '–', '—', '…'], ['"', '"', "'", "'", '-', '-', '...'], $conteudo);

// 2. Corrigir aspas problemáticas em strings JavaScript
$conteudo = preg_replace('/"([^"]*)"([^"]*)"([^"]*)"/', '"$1$2$3"', $conteudo);

// 3. Corrigir quebras de linha problemáticas em strings
$conteudo = preg_replace('/"([^"]*)\n([^"]*)"/', '"$1 $2"', $conteudo);

// 4. Corrigir caracteres especiais em comentários
$conteudo = str_replace('// Pode ser obtido de um campo oculto', '// Pode ser obtido de um campo oculto', $conteudo);

// 5. Verificar e corrigir aspas em strings específicas
$conteudo = str_replace("'Usuario Anonimo'", "'Usuario Anonimo'", $conteudo);
$conteudo = str_replace('"Usuario Anonimo"', "'Usuario Anonimo'", $conteudo);

// 6. Corrigir caracteres especiais em logs
$conteudo = str_replace('console.error("Elemento não encontrado para textContent");', 'console.error("Elemento nao encontrado para textContent");', $conteudo);
$conteudo = str_replace('console.error("Erro ao definir textContent:", error);', 'console.error("Erro ao definir textContent:", error);', $conteudo);

// 7. Corrigir caracteres especiais em mensagens
$conteudo = str_replace('showMessage("Erro: Formulário inválido", "error");', 'showMessage("Erro: Formulario invalido", "error");', $conteudo);
$conteudo = str_replace('showMessage("Erro: Botão de envio não encontrado", "error");', 'showMessage("Erro: Botao de envio nao encontrado", "error");', $conteudo);

// 8. Corrigir caracteres especiais em outros lugares
$conteudo = str_replace('console.error("Elemento não encontrado para textContent");', 'console.error("Elemento nao encontrado para textContent");', $conteudo);
$conteudo = str_replace('console.error("Erro ao definir textContent:", error);', 'console.error("Erro ao definir textContent:", error);', $conteudo);

// 9. Verificar se há problemas de codificação
$conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'UTF-8');

// 10. Salvar arquivo se houver mudanças
if ($conteudo !== $originalContent) {
    file_put_contents($arquivo, $conteudo);
    echo "✅ Arquivo corrigido com sucesso!\n";
    echo "🔧 Correções aplicadas:\n";
    echo "   - Caracteres especiais removidos\n";
    echo "   - Aspas problemáticas corrigidas\n";
    echo "   - Quebras de linha em strings corrigidas\n";
    echo "   - Codificação UTF-8 verificada\n";
} else {
    echo "ℹ️ Nenhuma correção necessária\n";
}

// Verificar sintaxe após correção
echo "\n🔍 Verificando sintaxe após correção...\n";

// Extrair JavaScript
$inicio = strpos($conteudo, '<script>');
$fim = strrpos($conteudo, '</script>');

if ($inicio !== false && $fim !== false) {
    $javascript = substr($conteudo, $inicio + 8, $fim - $inicio - 8);
    $linhas = explode("\n", $javascript);
    
    $chaves = 0;
    $parenteses = 0;
    $colchetes = 0;
    
    foreach ($linhas as $num => $linha) {
        $chaves += substr_count($linha, '{') - substr_count($linha, '}');
        $parenteses += substr_count($linha, '(') - substr_count($linha, ')');
        $colchetes += substr_count($linha, '[') - substr_count($linha, ']');
    }
    
    if ($chaves === 0 && $parenteses === 0 && $colchetes === 0) {
        echo "✅ Sintaxe JavaScript corrigida!\n";
    } else {
        echo "❌ Ainda há problemas de sintaxe:\n";
        if ($chaves !== 0) echo "   - Chaves: $chaves\n";
        if ($parenteses !== 0) echo "   - Parênteses: $parenteses\n";
        if ($colchetes !== 0) echo "   - Colchetes: $colchetes\n";
    }
}

echo "\n🚀 Teste agora o quiz!\n";
?>
