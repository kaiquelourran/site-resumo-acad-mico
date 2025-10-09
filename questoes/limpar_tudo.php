<?php
// Script para limpar todos os caracteres especiais

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "Limpando todos os caracteres especiais...\n";

// Remover todos os emojis e caracteres especiais
$conteudo = preg_replace('/[^\x00-\x7F]/', '', $conteudo);

// Corrigir palavras específicas que foram quebradas
$conteudo = str_replace('explicacao', 'explicacao', $conteudo);
$conteudo = str_replace('disponiveis', 'disponiveis', $conteudo);
$conteudo = str_replace('ja', 'ja', $conteudo);
$conteudo = str_replace('nao', 'nao', $conteudo);
$conteudo = str_replace('questao', 'questao', $conteudo);
$conteudo = str_replace('funcao', 'funcao', $conteudo);
$conteudo = str_replace('configuracao', 'configuracao', $conteudo);
$conteudo = str_replace('duplicacao', 'duplicacao', $conteudo);
$conteudo = str_replace('verificacao', 'verificacao', $conteudo);
$conteudo = str_replace('processamento', 'processamento', $conteudo);
$conteudo = str_replace('requisicao', 'requisicao', $conteudo);
$conteudo = str_replace('pagina', 'pagina', $conteudo);

file_put_contents($arquivo, $conteudo);

echo "Todos os caracteres especiais removidos!\n";
echo "Teste agora o quiz!\n";
?>
