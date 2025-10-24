<?php
/**
 * Script para corrigir referências ao logo com maiúscula
 * Substitui Logotipo_resumo_academico.png por minha-logo-apple.png
 */

$arquivos_para_corrigir = [
    // Raiz
    'index_fix.html',
    'manifest.json',
    'sobre_nos.php',
    'origem_to.html',
    'contato.php',
    'politica_privacidade.php',
    '500.php',
    '403.php',
    '404.php',
    'curriculo.html',
    
    // Infantil
    'infantil_01.html',
    'infantil_02.html',
    'infantil_03.html',
    'infantil_04.html',
    'infantil_05.html',
    'infantil_06.html',
    'infantil_07.html',
    'infantil_08.html',
    'infantil_09.html',
    'infantil_10.html',
];

$buscar = 'Logotipo_resumo_academico.png';
$substituir = 'minha-logo-apple.png';

$total_arquivos = 0;
$total_substituicoes = 0;

echo "<h1>🔧 Correção de Referências ao Logo</h1>\n";
echo "<style>body { font-family: Arial; background: #f5f5f5; padding: 20px; }</style>\n";
echo "<div style='background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>\n";

foreach ($arquivos_para_corrigir as $arquivo) {
    if (file_exists($arquivo)) {
        $conteudo = file_get_contents($arquivo);
        $count = substr_count($conteudo, $buscar);
        
        if ($count > 0) {
            $novo_conteudo = str_replace($buscar, $substituir, $conteudo);
            file_put_contents($arquivo, $novo_conteudo);
            
            echo "<p style='color: green;'>✅ <strong>$arquivo</strong>: $count substituição(ões)</p>\n";
            $total_arquivos++;
            $total_substituicoes += $count;
        } else {
            echo "<p style='color: gray;'>⏭️ <strong>$arquivo</strong>: nenhuma substituição necessária</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>$arquivo</strong>: arquivo não encontrado</p>\n";
    }
}

echo "</div>\n";
echo "<div style='background: #4CAF50; color: white; padding: 20px; border-radius: 10px; margin-top: 20px;'>\n";
echo "<h2>📊 Resumo:</h2>\n";
echo "<p><strong>Total de arquivos corrigidos:</strong> $total_arquivos</p>\n";
echo "<p><strong>Total de substituições:</strong> $total_substituicoes</p>\n";
echo "</div>\n";

echo "<div style='background: #2196F3; color: white; padding: 20px; border-radius: 10px; margin-top: 20px;'>\n";
echo "<h2>✅ Próximos Passos:</h2>\n";
echo "<ol>\n";
echo "<li>Fazer upload de todos os arquivos corrigidos para Hostinger</li>\n";
echo "<li>Testar o site: https://www.resumoacademico.com/</li>\n";
echo "<li>Verificar se o favicon aparece corretamente</li>\n";
echo "</ol>\n";
echo "</div>\n";
?>

