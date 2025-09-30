<?php
require_once 'conexao.php';

echo "=== VERIFICAÇÃO DE ALTERNATIVAS ===\n\n";

try {
    // 1. Verificar se existe tabela alternativas
    $sql_check = "SHOW TABLES LIKE 'alternativas'";
    $stmt = $pdo->query($sql_check);
    $tabela_existe = $stmt->rowCount() > 0;
    
    echo "1. TABELA ALTERNATIVAS EXISTE: " . ($tabela_existe ? "SIM" : "NÃO") . "\n\n";
    
    if ($tabela_existe) {
        // 2. Mostrar estrutura da tabela alternativas
        echo "2. ESTRUTURA DA TABELA ALTERNATIVAS:\n";
        $sql_desc = "DESCRIBE alternativas";
        $stmt_desc = $pdo->query($sql_desc);
        $colunas = $stmt_desc->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($colunas as $coluna) {
            echo "   - " . $coluna['Field'] . " (" . $coluna['Type'] . ")\n";
        }
        echo "\n";
        
        // 3. Contar alternativas na tabela
        $sql_count = "SELECT COUNT(*) as total FROM alternativas";
        $stmt_count = $pdo->query($sql_count);
        $total = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
        echo "3. TOTAL DE ALTERNATIVAS NA TABELA: $total\n\n";
        
        // 4. Mostrar algumas alternativas de exemplo
        echo "4. EXEMPLOS DE ALTERNATIVAS:\n";
        $sql_exemplos = "SELECT a.id_questao, a.texto, a.eh_correta, q.enunciado 
                        FROM alternativas a 
                        JOIN questoes q ON a.id_questao = q.id_questao 
                        WHERE q.id_assunto = 8 
                        LIMIT 10";
        $stmt_ex = $pdo->prepare($sql_exemplos);
        $stmt_ex->execute();
        $exemplos = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($exemplos as $ex) {
            echo "   Questão " . $ex['id_questao'] . ": " . substr($ex['texto'], 0, 50) . "... [" . ($ex['eh_correta'] ? "CORRETA" : "incorreta") . "]\n";
        }
        echo "\n";
    }
    
    // 5. Verificar campos alternativa_* na tabela questoes
    echo "5. CAMPOS ALTERNATIVA_* NA TABELA QUESTOES:\n";
    $sql_questoes = "SELECT id_questao, 
                           CASE WHEN alternativa_a IS NOT NULL AND alternativa_a != '' THEN 'SIM' ELSE 'NÃO' END as tem_a,
                           CASE WHEN alternativa_b IS NOT NULL AND alternativa_b != '' THEN 'SIM' ELSE 'NÃO' END as tem_b,
                           CASE WHEN alternativa_c IS NOT NULL AND alternativa_c != '' THEN 'SIM' ELSE 'NÃO' END as tem_c,
                           CASE WHEN alternativa_d IS NOT NULL AND alternativa_d != '' THEN 'SIM' ELSE 'NÃO' END as tem_d,
                           CASE WHEN alternativa_e IS NOT NULL AND alternativa_e != '' THEN 'SIM' ELSE 'NÃO' END as tem_e
                    FROM questoes 
                    WHERE id_assunto = 8 
                    LIMIT 5";
    $stmt_q = $pdo->prepare($sql_questoes);
    $stmt_q->execute();
    $questoes = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($questoes as $q) {
        echo "   Questão " . $q['id_questao'] . ": A=" . $q['tem_a'] . " B=" . $q['tem_b'] . " C=" . $q['tem_c'] . " D=" . $q['tem_d'] . " E=" . $q['tem_e'] . "\n";
    }
    echo "\n";
    
    // 6. CONCLUSÃO
    echo "6. CONCLUSÃO:\n";
    if ($tabela_existe && $total > 0) {
        echo "   ✅ O sistema usa a TABELA ALTERNATIVAS para salvar as alternativas\n";
        echo "   ✅ Existem $total alternativas salvas na tabela\n";
        echo "   ⚠️  Os campos alternativa_* na tabela questoes podem estar sendo ignorados\n";
    } else {
        echo "   ❌ O sistema NÃO tem tabela alternativas ou ela está vazia\n";
        echo "   ⚠️  Pode estar usando os campos alternativa_* na tabela questoes\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>