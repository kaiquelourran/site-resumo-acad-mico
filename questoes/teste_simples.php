<?php
require_once 'conexao.php';

echo "=== TESTE SIMPLES DE VERIFICAÇÃO ===\n\n";

try {
    $id_questao = 92;
    
    // Buscar alternativas
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Alternativas originais:\n";
    foreach ($alternativas as $i => $alt) {
        echo "  $i: ID={$alt['id_alternativa']}, Texto=" . substr($alt['texto'], 0, 30) . "...\n";
        // Mostrar todos os campos
        foreach ($alt as $campo => $valor) {
            if ($campo != 'texto' && $campo != 'id_questao') {
                echo "    $campo = $valor\n";
            }
        }
    }
    
    // Encontrar a correta
    $correta = null;
    foreach ($alternativas as $alt) {
        if (isset($alt['eh_correta']) && $alt['eh_correta'] == 1) {
            $correta = $alt;
            break;
        }
    }
    
    if ($correta) {
        echo "\nAlternativa correta encontrada: ID={$correta['id_alternativa']}\n";
        
        // Embaralhar
        $seed = $id_questao + (int)date('Ymd');
        srand($seed);
        shuffle($alternativas);
        
        echo "\nApós embaralhamento (seed: $seed):\n";
        $letras = ['A', 'B', 'C', 'D', 'E'];
        foreach ($alternativas as $i => $alt) {
            $letra = $letras[$i];
            $is_correta = ($alt['id_alternativa'] == $correta['id_alternativa']) ? ' ✅ CORRETA' : '';
            echo "  $letra: ID={$alt['id_alternativa']}, Texto=" . substr($alt['texto'], 0, 30) . "...$is_correta\n";
        }
        
        // Testar seleção
        echo "\nTestando seleção:\n";
        foreach ($letras as $letra) {
            $id_selecionado = null;
            foreach ($alternativas as $i => $alt) {
                if ($letras[$i] === $letra) {
                    $id_selecionado = $alt['id_alternativa'];
                    break;
                }
            }
            
            $acertou = ($id_selecionado == $correta['id_alternativa']) ? 'SIM' : 'NÃO';
            echo "  $letra -> ID: $id_selecionado, Acertou: $acertou\n";
        }
        
    } else {
        echo "\nERRO: Nenhuma alternativa correta encontrada!\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>

