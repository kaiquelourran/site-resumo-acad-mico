<?php
// Script para corrigir erros de sintaxe JavaScript

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "🔍 Analisando arquivo para erros de sintaxe...\n";

// 1. Verificar se há caracteres problemáticos
$problemas = [];
$linhas = explode("\n", $conteudo);

foreach ($linhas as $num => $linha) {
    $linha_num = $num + 1;
    
    // Verificar caracteres não-ASCII problemáticos
    if (preg_match('/[^\x00-\x7F]/', $linha)) {
        // Verificar se é em comentário ou string
        if (!preg_match('/^\s*\/\//', $linha) && !preg_match('/^\s*\*/', $linha)) {
            $problemas[] = "Linha $linha_num: Caracteres especiais encontrados";
        }
    }
    
    // Verificar aspas não fechadas
    $aspas_simples = substr_count($linha, "'");
    $aspas_duplas = substr_count($linha, '"');
    if ($aspas_simples % 2 !== 0 || $aspas_duplas % 2 !== 0) {
        $problemas[] = "Linha $linha_num: Possível aspas não fechadas";
    }
}

if (!empty($problemas)) {
    echo "⚠️ Problemas encontrados:\n";
    foreach ($problemas as $problema) {
        echo "   - $problema\n";
    }
} else {
    echo "✅ Nenhum problema óbvio encontrado\n";
}

// 2. Corrigir problemas conhecidos
$correcoes = [
    // Corrigir aspas problemáticas
    '"' => '"',
    '"' => '"',
    ''' => "'",
    ''' => "'",
    
    // Corrigir caracteres especiais em strings JavaScript
    'Usuário Anônimo' => 'Usuario Anonimo',
    'Enviando...' => 'Enviando...',
    'Responder' => 'Responder',
    
    // Corrigir comentários problemáticos
    '// Pode ser obtido de um campo oculto' => '// Pode ser obtido de um campo oculto',
];

foreach ($correcoes as $busca => $substituicao) {
    $conteudo = str_replace($busca, $substituicao, $conteudo);
}

// 3. Verificar se a função safeSetTextContent está bem formada
if (strpos($conteudo, 'function safeSetTextContent') === false) {
    echo "⚠️ Função safeSetTextContent não encontrada, adicionando...\n";
    
    $funcao = '
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
    
    $conteudo = str_replace(
        '// Funções para gerenciar comentários',
        $funcao . '\n        // Funções para gerenciar comentários',
        $conteudo
    );
}

// 4. Verificar sintaxe JavaScript básica
$js_start = strpos($conteudo, '<script>');
$js_end = strrpos($conteudo, '</script>');

if ($js_start !== false && $js_end !== false) {
    $js_code = substr($conteudo, $js_start + 8, $js_end - $js_start - 8);
    
    // Verificar parênteses balanceados
    $abertos = substr_count($js_code, '(');
    $fechados = substr_count($js_code, ')');
    if ($abertos !== $fechados) {
        echo "⚠️ Parênteses não balanceados: $abertos abertos, $fechados fechados\n";
    }
    
    // Verificar chaves balanceadas
    $abertos = substr_count($js_code, '{');
    $fechados = substr_count($js_code, '}');
    if ($abertos !== $fechados) {
        echo "⚠️ Chaves não balanceadas: $abertos abertos, $fechados fechados\n";
    }
    
    // Verificar colchetes balanceados
    $abertos = substr_count($js_code, '[');
    $fechados = substr_count($js_code, ']');
    if ($abertos !== $fechados) {
        echo "⚠️ Colchetes não balanceados: $abertos abertos, $fechados fechados\n";
    }
}

// 5. Salvar arquivo corrigido
file_put_contents($arquivo, $conteudo);

echo "✅ Arquivo corrigido e salvo!\n";
echo "🔧 Correções aplicadas:\n";
echo "   - Caracteres especiais normalizados\n";
echo "   - Aspas corrigidas\n";
echo "   - Verificação de sintaxe JavaScript\n";
echo "   - Função safeSetTextContent garantida\n";
echo "\n🚀 Teste agora o sistema!\n";
?>
