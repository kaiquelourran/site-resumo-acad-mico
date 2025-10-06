<?php
require_once 'conexao.php';

echo "=== TESTE DE ESTRUTURA DA TABELA ALTERNATIVAS ===\n\n";

try {
    // Verificar estrutura da tabela alternativas
    $stmt = $pdo->query("DESCRIBE alternativas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas da tabela alternativas:\n";
    foreach ($colunas as $coluna) {
        echo "- " . $coluna['Field'] . " (" . $coluna['Type'] . ")\n";
    }
    echo "\n";
    
    // Testar consulta com diferentes nomes de coluna
    echo "Testando consultas:\n";
    
    // Teste 1: eh_correta
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas WHERE eh_correta = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Coluna 'eh_correta' existe: " . $result['total'] . " alternativas corretas\n";
    } catch (Exception $e) {
        echo "✗ Coluna 'eh_correta' NÃO existe: " . $e->getMessage() . "\n";
    }
    
    // Teste 2: correta
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas WHERE correta = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Coluna 'correta' existe: " . $result['total'] . " alternativas corretas\n";
    } catch (Exception $e) {
        echo "✗ Coluna 'correta' NÃO existe: " . $e->getMessage() . "\n";
    }
    
    // Teste 3: explicacao
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas WHERE explicacao IS NOT NULL");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Coluna 'explicacao' existe: " . $result['total'] . " alternativas com explicação\n";
    } catch (Exception $e) {
        echo "✗ Coluna 'explicacao' NÃO existe: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Mostrar exemplo de dados
    echo "Exemplo de dados (questão 92):\n";
    $stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = 92 LIMIT 5");
    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($dados) {
        foreach ($dados as $alt) {
            echo "ID: {$alt['id_alternativa']}, Texto: " . substr($alt['texto'], 0, 50) . "...\n";
            if (isset($alt['eh_correta'])) {
                echo "  eh_correta: " . $alt['eh_correta'] . "\n";
            }
            if (isset($alt['correta'])) {
                echo "  correta: " . $alt['correta'] . "\n";
            }
            if (isset($alt['explicacao'])) {
                echo "  explicacao: " . substr($alt['explicacao'], 0, 30) . "...\n";
            }
            echo "\n";
        }
    } else {
        echo "Nenhum dado encontrado para questão 92\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>

