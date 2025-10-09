<?php
// Script para remover emojis e caracteres especiais

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "Removendo emojis e caracteres especiais...\n";

// Remover emojis comuns
$conteudo = str_replace('🔥', '', $conteudo);
$conteudo = str_replace('✅', '', $conteudo);
$conteudo = str_replace('❌', '', $conteudo);
$conteudo = str_replace('📊', '', $conteudo);
$conteudo = str_replace('🔍', '', $conteudo);
$conteudo = str_replace('🎯', '', $conteudo);
$conteudo = str_replace('💡', '', $conteudo);
$conteudo = str_replace('🧹', '', $conteudo);
$conteudo = str_replace('⚠️', '', $conteudo);
$conteudo = str_replace('🎉', '', $conteudo);

// Remover caracteres especiais
$conteudo = str_replace('já', 'ja', $conteudo);
$conteudo = str_replace('já', 'ja', $conteudo);
$conteudo = str_replace('disponíveis', 'disponiveis', $conteudo);
$conteudo = str_replace('explicação', 'explicacao', $conteudo);
$conteudo = str_replace('Explicação', 'Explicacao', $conteudo);

file_put_contents($arquivo, $conteudo);

echo "Emojis e caracteres especiais removidos!\n";
echo "Teste agora o quiz!\n";
?>
