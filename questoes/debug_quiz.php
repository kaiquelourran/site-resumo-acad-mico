<?php
// Script para debug do quiz
echo "🔍 DEBUG DO QUIZ\n";
echo "================\n\n";

// Verificar se o arquivo existe
$arquivo = 'quiz_vertical_filtros.php';
if (file_exists($arquivo)) {
    echo "✅ Arquivo encontrado: $arquivo\n";
    
    // Verificar tamanho
    $tamanho = filesize($arquivo);
    echo "📊 Tamanho: " . number_format($tamanho) . " bytes\n";
    
    // Verificar se contém a função mostrarFeedbackVisual
    $conteudo = file_get_contents($arquivo);
    if (strpos($conteudo, 'function mostrarFeedbackVisual') !== false) {
        echo "✅ Função mostrarFeedbackVisual encontrada\n";
    } else {
        echo "❌ Função mostrarFeedbackVisual NÃO encontrada\n";
    }
    
    // Verificar se contém as classes CSS
    if (strpos($conteudo, '.alternative-correct') !== false) {
        echo "✅ Classe CSS alternative-correct encontrada\n";
    } else {
        echo "❌ Classe CSS alternative-correct NÃO encontrada\n";
    }
    
    if (strpos($conteudo, '.alternative-incorrect-chosen') !== false) {
        echo "✅ Classe CSS alternative-incorrect-chosen encontrada\n";
    } else {
        echo "❌ Classe CSS alternative-incorrect-chosen NÃO encontrada\n";
    }
    
    // Verificar se a função está sendo chamada
    if (strpos($conteudo, 'mostrarFeedbackVisual(') !== false) {
        echo "✅ Chamada da função mostrarFeedbackVisual encontrada\n";
    } else {
        echo "❌ Chamada da função mostrarFeedbackVisual NÃO encontrada\n";
    }
    
    // Verificar se há erros de sintaxe JavaScript
    $linhas = explode("\n", $conteudo);
    $erros = [];
    $chaves = 0;
    $parenteses = 0;
    
    foreach ($linhas as $num => $linha) {
        $chaves += substr_count($linha, '{') - substr_count($linha, '}');
        $parenteses += substr_count($linha, '(') - substr_count($linha, ')');
        
        if (strpos($linha, 'function') !== false && strpos($linha, '{') === false) {
            $erros[] = "Linha " . ($num + 1) . ": Função sem chave de abertura";
        }
    }
    
    if ($chaves !== 0) {
        $erros[] = "Chaves desbalanceadas: $chaves";
    }
    
    if ($parenteses !== 0) {
        $erros[] = "Parênteses desbalanceados: $parenteses";
    }
    
    if (empty($erros)) {
        echo "✅ Sintaxe JavaScript parece estar correta\n";
    } else {
        echo "❌ Possíveis erros de sintaxe JavaScript:\n";
        foreach ($erros as $erro) {
            echo "   - $erro\n";
        }
    }
    
} else {
    echo "❌ Arquivo não encontrado: $arquivo\n";
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Acesse: quiz_vertical_filtros.php?id=8&filtro=todas&questao_inicial=92\n";
echo "2. Abra o console do navegador (F12)\n";
echo "3. Clique em uma alternativa\n";
echo "4. Verifique os logs no console\n";
echo "5. Verifique se as classes CSS são aplicadas\n";
?>
