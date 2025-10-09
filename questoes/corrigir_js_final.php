<?php
// Script para corrigir sintaxe JavaScript

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "🔧 CORRIGINDO SINTAXE JAVASCRIPT\n";
echo "================================\n\n";

$originalContent = $conteudo;

// 1. Corrigir caracteres especiais comuns
$conteudo = str_replace('"', '"', $conteudo);
$conteudo = str_replace('"', '"', $conteudo);
$conteudo = str_replace(''', "'", $conteudo);
$conteudo = str_replace(''', "'", $conteudo);
$conteudo = str_replace('–', '-', $conteudo);
$conteudo = str_replace('—', '-', $conteudo);
$conteudo = str_replace('…', '...', $conteudo);

// 2. Corrigir strings específicas problemáticas
$conteudo = str_replace('Usuario Anonimo', 'Usuario Anonimo', $conteudo);
$conteudo = str_replace('Elemento não encontrado', 'Elemento nao encontrado', $conteudo);
$conteudo = str_replace('Erro ao definir textContent', 'Erro ao definir textContent', $conteudo);
$conteudo = str_replace('Formulário inválido', 'Formulario invalido', $conteudo);
$conteudo = str_replace('Botão de envio não encontrado', 'Botao de envio nao encontrado', $conteudo);

// 3. Corrigir quebras de linha em strings JavaScript
$conteudo = preg_replace('/"([^"]*)\n([^"]*)"/', '"$1 $2"', $conteudo);

// 4. Salvar arquivo se houver mudanças
if ($conteudo !== $originalContent) {
    file_put_contents($arquivo, $conteudo);
    echo "✅ Arquivo corrigido com sucesso!\n";
    echo "🔧 Correções aplicadas:\n";
    echo "   - Caracteres especiais removidos\n";
    echo "   - Strings problemáticas corrigidas\n";
    echo "   - Quebras de linha em strings corrigidas\n";
} else {
    echo "ℹ️ Nenhuma correção necessária\n";
}

echo "\n🚀 Teste agora o quiz!\n";
?>
