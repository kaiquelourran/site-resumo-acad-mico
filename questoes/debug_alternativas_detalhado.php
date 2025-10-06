<?php
require_once 'conexao.php';

echo "=== DEBUG DETALHADO DE ALTERNATIVAS ===\n\n";

try {
    $id_questao = 92; // Questão de teste
    
    // 1. Buscar alternativas da questão
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "1. ALTERNATIVAS ORIGINAIS:\n";
    foreach ($alternativas_questao as $index => $alt) {
        echo "  $index: ID={$alt['id_alternativa']}, Texto=" . substr($alt['texto'], 0, 50) . "...\n";
        // Verificar todas as colunas
        foreach ($alt as $campo => $valor) {
            if ($campo != 'texto' && $campo != 'id_alternativa' && $campo != 'id_questao') {
                echo "    $campo = $valor\n";
            }
        }
    }
    echo "\n";
    
    // 2. Embaralhar (mesmo processo do código)
    $seed = $id_questao + (int)date('Ymd');
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "2. APÓS EMBARALHAMENTO (seed: $seed):\n";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        echo "  $letra: ID={$alt['id_alternativa']}, Texto=" . substr($alt['texto'], 0, 50) . "...\n";
        // Verificar todas as colunas
        foreach ($alt as $campo => $valor) {
            if ($campo != 'texto' && $campo != 'id_alternativa' && $campo != 'id_questao') {
                echo "    $campo = $valor\n";
            }
        }
    }
    echo "\n";
    
    // 3. Encontrar alternativa correta
    $coluna_correta = null;
    $nomes_possiveis = ['eh_correta', 'correta', 'is_correct', 'correct', 'acertou'];
    
    foreach ($nomes_possiveis as $nome) {
        try {
            $stmt_test = $pdo->prepare("SELECT COUNT(*) as total FROM alternativas WHERE $nome = 1 LIMIT 1");
            $stmt_test->execute();
            $coluna_correta = $nome;
            echo "3. COLUNA CORRETA ENCONTRADA: $nome\n";
            break;
        } catch (Exception $e) {
            echo "3. Coluna '$nome' não existe\n";
            continue;
        }
    }
    
    if ($coluna_correta) {
        $stmt_correta = $pdo->prepare("SELECT id_alternativa FROM alternativas WHERE id_questao = ? AND $coluna_correta = 1 LIMIT 1");
        $stmt_correta->execute([$id_questao]);
        $alternativa_correta = $stmt_correta->fetch(PDO::FETCH_ASSOC);
        
        echo "4. ALTERNATIVA CORRETA (ID): " . ($alternativa_correta ? $alternativa_correta['id_alternativa'] : 'NENHUMA') . "\n";
        
        // Encontrar qual letra corresponde à alternativa correta após embaralhamento
        $letra_correta = null;
        foreach ($alternativas_questao as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                $letra_correta = $letra;
                break;
            }
        }
        
        echo "5. LETRA CORRETA APÓS EMBARALHAMENTO: " . ($letra_correta ?: 'NÃO ENCONTRADA') . "\n\n";
        
        // 6. Testar todas as letras
        echo "6. TESTANDO TODAS AS LETRAS:\n";
        foreach ($letras as $letra) {
            $id_alternativa = null;
            foreach ($alternativas_questao as $index => $alternativa) {
                $letra_atual = $letras[$index] ?? ($index + 1);
                if ($letra_atual === $letra) {
                    $id_alternativa = $alternativa['id_alternativa'];
                    break;
                }
            }
            
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
            $status = $acertou ? '✅ CORRETA' : '❌ ERRADA';
            
            echo "  $letra -> ID: " . ($id_alternativa ?: 'NÃO ENCONTRADO') . " $status\n";
        }
        
    } else {
        echo "4. ERRO: Nenhuma coluna de alternativa correta encontrada!\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>

