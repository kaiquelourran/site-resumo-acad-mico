<?php
// Debug do processamento de resposta
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG PROCESSAMENTO DE RESPOSTA ===\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "Método: POST\n";
    echo "POST data: " . print_r($_POST, true) . "\n";
    
    if (isset($_POST['id_questao']) && isset($_POST['alternativa_selecionada'])) {
        $id_questao = (int)$_POST['id_questao'];
        $alternativa_selecionada = $_POST['alternativa_selecionada'];
        
        echo "ID Questão: $id_questao\n";
        echo "Alternativa Selecionada: $alternativa_selecionada\n";
        
        // Simular o processamento
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $id_alternativa = null;
        
        // Simular alternativas (dados de teste)
        $alternativas_questao = [
            ['id_alternativa' => 1, 'eh_correta' => 0],
            ['id_alternativa' => 2, 'eh_correta' => 1],
            ['id_alternativa' => 3, 'eh_correta' => 0],
            ['id_alternativa' => 4, 'eh_correta' => 0]
        ];
        
        // Embaralhar
        $seed = $id_questao + (int)date('Ymd');
        srand($seed);
        shuffle($alternativas_questao);
        
        echo "Alternativas após embaralhamento:\n";
        foreach ($alternativas_questao as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            echo "  $letra: ID={$alt['id_alternativa']}, Correta={$alt['eh_correta']}\n";
        }
        
        // Mapear letra para ID
        foreach ($alternativas_questao as $index => $alternativa) {
            $letra = $letras[$index] ?? ($index + 1);
            if ($letra === strtoupper($alternativa_selecionada)) {
                $id_alternativa = $alternativa['id_alternativa'];
                break;
            }
        }
        
        echo "ID da alternativa encontrado: " . ($id_alternativa ?: 'NULL') . "\n";
        
        // Encontrar alternativa correta
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        echo "Alternativa correta: " . ($alternativa_correta ? $alternativa_correta['id_alternativa'] : 'Nenhuma') . "\n";
        
        if ($alternativa_correta && $id_alternativa) {
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
            echo "Resultado: acertou = $acertou\n";
            
            $response = [
                'success' => true,
                'acertou' => (bool)$acertou,
                'alternativa_correta' => $alternativa_correta['id_alternativa'],
                'explicacao' => 'Teste de explicação',
                'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            echo "ERRO: Não foi possível processar a resposta\n";
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao processar resposta'
            ]);
        }
    } else {
        echo "ERRO: Dados POST incompletos\n";
    }
} else {
    echo "Método: " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo "Use POST para testar\n";
}
?>

