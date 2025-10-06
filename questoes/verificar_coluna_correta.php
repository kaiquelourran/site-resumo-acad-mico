<?php
require_once 'conexao.php';

echo "=== VERIFICAÇÃO DA COLUNA CORRETA ===\n\n";

try {
    // Verificar estrutura da tabela alternativas
    $stmt = $pdo->query("DESCRIBE alternativas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Colunas da tabela alternativas:\n";
    foreach ($colunas as $coluna) {
        echo "- " . $coluna['Field'] . " (" . $coluna['Type'] . ")\n";
    }
    echo "\n";
    
    // Testar diferentes nomes de coluna
    $nomes_coluna = ['eh_correta', 'correta', 'is_correct', 'correct', 'acertou'];
    
    foreach ($nomes_coluna as $nome) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas WHERE $nome = 1");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✓ Coluna '$nome' existe: " . $result['total'] . " alternativas corretas\n";
        } catch (Exception $e) {
            echo "✗ Coluna '$nome' NÃO existe\n";
        }
    }
    
    echo "\n";
    
    // Mostrar exemplo de dados da questão 92
    echo "Dados da questão 92:\n";
    $stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = 92 LIMIT 5");
    $stmt->execute();
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($dados) {
        foreach ($dados as $alt) {
            echo "ID: {$alt['id_alternativa']}, Texto: " . substr($alt['texto'], 0, 50) . "...\n";
            // Mostrar todas as colunas disponíveis
            foreach ($alt as $campo => $valor) {
                if ($campo != 'texto' && $campo != 'id_alternativa' && $campo != 'id_questao') {
                    echo "  $campo: $valor\n";
                }
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

