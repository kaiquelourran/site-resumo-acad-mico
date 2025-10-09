<?php
// Script para corrigir o erro JavaScript de textContent

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

// Substituir todas as ocorrências problemáticas
$correcoes = [
    // Corrigir handleCommentSubmit
    'const submitBtn = form.querySelector(\'.btn-responder\');
            if (!submitBtn) {
                console.error(\'Botão de envio não encontrado\');
                showMessage(\'Erro: Botão de envio não encontrado\', \'error\');
                return;
            }
            const originalText = submitBtn.textContent;' => 
    'const submitBtn = form.querySelector(\'.btn-responder\');
            if (!submitBtn) {
                console.error(\'Botão de envio não encontrado\', form);
                showMessage(\'Erro: Botão de envio não encontrado\', \'error\');
                return;
            }
            const originalText = submitBtn.textContent || \'Responder\';',
    
    // Corrigir .finally() blocks
    'submitBtn.disabled = false;
                    submitBtn.textContent = originalText;' =>
    'if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }'
];

foreach ($correcoes as $busca => $substituicao) {
    $conteudo = str_replace($busca, $substituicao, $conteudo);
}

// Adicionar verificação adicional no início da função
$verificacao_adicional = '
            // Verificação adicional de segurança
            if (!form || !form.id) {
                console.error(\'Formulário inválido\', form);
                showMessage(\'Erro: Formulário inválido\', \'error\');
                return;
            }';

$conteudo = str_replace(
    'const form = e.target;',
    'const form = e.target;
            ' . $verificacao_adicional,
    $conteudo
);

// Salvar arquivo corrigido
file_put_contents($arquivo, $conteudo);

echo "✅ Arquivo corrigido com sucesso!\n";
echo "🔧 Correções aplicadas:\n";
echo "   - Verificação adicional de formulário\n";
echo "   - Fallback para textContent\n";
echo "   - Verificação de segurança em .finally()\n";
echo "   - Logs de debug melhorados\n";
echo "\n🚀 Teste agora o sistema de comentários!\n";
?>
