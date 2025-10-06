<?php
require_once 'conexao.php';

echo "=== TESTE DE VERIFICAÇÃO DE ALTERNATIVAS ===\n\n";

try {
    $id_questao = 92; // Questão de teste
    
    // Buscar alternativas da questão
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Alternativas da questão $id_questao:\n";
    foreach ($alternativas_questao as $index => $alt) {
        echo "  $index: ID={$alt['id_alternativa']}, Texto=" . substr($alt['texto'], 0, 50) . "...\n";
        // Verificar qual coluna indica se é correta
        foreach ($alt as $campo => $valor) {
            if ($campo != 'texto' && $campo != 'id_alternativa' && $campo != 'id_questao' && $valor == 1) {
                echo "    $campo = $valor (CORRETA)\n";
            }
        }
    }
    echo "\n";
    
    // Embaralhar
    $seed = $id_questao + (int)date('Ymd');
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "Após embaralhamento:\n";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        echo "  $letra: ID={$alt['id_alternativa']}, Texto=" . substr($alt['texto'], 0, 50) . "...\n";
        // Verificar qual coluna indica se é correta
        foreach ($alt as $campo => $valor) {
            if ($campo != 'texto' && $campo != 'id_alternativa' && $campo != 'id_questao' && $valor == 1) {
                echo "    $campo = $valor (CORRETA)\n";
            }
        }
    }
    echo "\n";
    
    // Testar seleção de diferentes letras
    echo "Testando seleção de letras:\n";
    foreach ($letras as $letra) {
        $id_alternativa = null;
        foreach ($alternativas_questao as $index => $alternativa) {
            $letra_atual = $letras[$index] ?? ($index + 1);
            if ($letra_atual === $letra) {
                $id_alternativa = $alternativa['id_alternativa'];
                break;
            }
        }
        echo "  Letra $letra -> ID: " . ($id_alternativa ?: 'NÃO ENCONTRADO') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
?>

