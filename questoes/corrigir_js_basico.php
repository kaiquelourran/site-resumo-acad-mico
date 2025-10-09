<?php
// Script basico para corrigir sintaxe JavaScript

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "Corrigindo sintaxe JavaScript...\n";

$originalContent = $conteudo;

// Corrigir caracteres especiais comuns
$conteudo = str_replace('"', '"', $conteudo);
$conteudo = str_replace('"', '"', $conteudo);
$conteudo = str_replace(''', "'", $conteudo);
$conteudo = str_replace(''', "'", $conteudo);
$conteudo = str_replace('–', '-', $conteudo);
$conteudo = str_replace('—', '-', $conteudo);
$conteudo = str_replace('…', '...', $conteudo);

// Corrigir strings especificas problematicas
$conteudo = str_replace('Usuario Anonimo', 'Usuario Anonimo', $conteudo);
$conteudo = str_replace('Elemento não encontrado', 'Elemento nao encontrado', $conteudo);
$conteudo = str_replace('Erro ao definir textContent', 'Erro ao definir textContent', $conteudo);
$conteudo = str_replace('Formulário inválido', 'Formulario invalido', $conteudo);
$conteudo = str_replace('Botão de envio não encontrado', 'Botao de envio nao encontrado', $conteudo);

// Corrigir quebras de linha em strings JavaScript
$conteudo = preg_replace('/"([^"]*)\n([^"]*)"/', '"$1 $2"', $conteudo);

// Salvar arquivo se houver mudancas
if ($conteudo !== $originalContent) {
    file_put_contents($arquivo, $conteudo);
    echo "Arquivo corrigido com sucesso!\n";
    echo "Correcoes aplicadas:\n";
    echo "   - Caracteres especiais removidos\n";
    echo "   - Strings problematicas corrigidas\n";
    echo "   - Quebras de linha em strings corrigidas\n";
} else {
    echo "Nenhuma correcao necessaria\n";
}

echo "\nTeste agora o quiz!\n";
?>
