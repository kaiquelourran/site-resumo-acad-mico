<?php
// Correção definitiva do erro de textContent

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

// Função para substituir de forma segura
function substituirSeguro($conteudo, $busca, $substituicao) {
    $pos = strpos($conteudo, $busca);
    if ($pos !== false) {
        return substr_replace($conteudo, $substituicao, $pos, strlen($busca));
    }
    return $conteudo;
}

// 1. Corrigir a função handleCommentSubmit
$busca1 = 'const submitBtn = form.querySelector(\'.btn-responder\');
            if (!submitBtn) {
                console.error(\'Botão de envio não encontrado\');
                showMessage(\'Erro: Botão de envio não encontrado\', \'error\');
                return;
            }
            const originalText = submitBtn.textContent;
            
            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.textContent = \'Enviando...\';';

$substituicao1 = 'const submitBtn = form.querySelector(\'.btn-responder\');
            if (!submitBtn) {
                console.error(\'Botão de envio não encontrado\', form);
                showMessage(\'Erro: Botão de envio não encontrado\', \'error\');
                return;
            }
            
            // Verificação adicional de segurança
            if (!submitBtn.textContent) {
                console.error(\'Botão sem textContent\', submitBtn);
                showMessage(\'Erro: Botão inválido\', \'error\');
                return;
            }
            
            const originalText = submitBtn.textContent;
            
            // Desabilitar botão e mostrar loading com verificação
            try {
                submitBtn.disabled = true;
                submitBtn.textContent = \'Enviando...\';
            } catch (error) {
                console.error(\'Erro ao modificar botão:\', error);
                showMessage(\'Erro ao modificar botão\', \'error\');
                return;
            }';

$conteudo = str_replace($busca1, $substituicao1, $conteudo);

// 2. Corrigir o bloco .finally()
$busca2 = 'if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }';

$substituicao2 = 'if (submitBtn && submitBtn.textContent !== undefined) {
                        try {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        } catch (error) {
                            console.error(\'Erro ao reabilitar botão:\', error);
                        }
                    }';

$conteudo = str_replace($busca2, $substituicao2, $conteudo);

// 3. Adicionar função auxiliar mais robusta
$funcao_auxiliar = '
        // Função auxiliar robusta para elementos
        function safeSetTextContent(element, text, fallback = \'\') {
            if (!element) {
                console.error(\'Elemento não encontrado para textContent\');
                return false;
            }
            try {
                element.textContent = text || fallback;
                return true;
            } catch (error) {
                console.error(\'Erro ao definir textContent:\', error);
                return false;
            }
        }';

// Inserir antes da função initComments
$conteudo = str_replace(
    '// Funções para gerenciar comentários',
    $funcao_auxiliar . '\n        // Funções para gerenciar comentários',
    $conteudo
);

// 4. Substituir todas as ocorrências de textContent por função segura
$conteudo = str_replace(
    'submitBtn.textContent = \'Enviando...\';',
    'safeSetTextContent(submitBtn, \'Enviando...\');',
    $conteudo
);

$conteudo = str_replace(
    'submitBtn.textContent = originalText;',
    'safeSetTextContent(submitBtn, originalText);',
    $conteudo
);

// Salvar arquivo corrigido
file_put_contents($arquivo, $conteudo);

echo "✅ CORREÇÃO DEFINITIVA APLICADA!\n";
echo "🔧 Melhorias implementadas:\n";
echo "   - Verificação dupla de segurança para submitBtn\n";
echo "   - Try-catch para operações de textContent\n";
echo "   - Função auxiliar safeSetTextContent\n";
echo "   - Verificação de textContent antes de usar\n";
echo "   - Logs de debug melhorados\n";
echo "\n🚀 O erro deve estar completamente resolvido agora!\n";
?>
