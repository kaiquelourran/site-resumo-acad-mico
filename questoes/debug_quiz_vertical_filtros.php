<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG QUIZ VERTICAL FILTROS</h1>";

// Simular dados de teste
$_POST['id_questao'] = '1';
$_POST['alternativa_selecionada'] = 'A';
$_POST['ajax_request'] = '1';

echo "<h2>1. Dados de entrada:</h2>";
echo "<p>ID Questão: " . $_POST['id_questao'] . "</p>";
echo "<p>Alternativa Selecionada: " . $_POST['alternativa_selecionada'] . "</p>";

try {
    $id_questao = (int)$_POST['id_questao'];
    $alternativa_selecionada = $_POST['alternativa_selecionada'];
    
    echo "<h2>2. Testando conexão com banco:</h2>";
    if ($pdo) {
        echo "<p style='color: green;'>✅ Conexão com banco OK</p>";
    } else {
        echo "<p style='color: red;'>❌ ERRO: Falha na conexão com banco</p>";
        exit;
    }
    
    echo "<h2>3. Buscando alternativas da questão $id_questao:</h2>";
    
    // Buscar as alternativas da questão
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Alternativas encontradas: " . count($alternativas_questao) . "</p>";
    
    if (empty($alternativas_questao)) {
        echo "<p style='color: red;'>ERRO: Nenhuma alternativa encontrada para a questão $id_questao!</p>";
        exit;
    }
    
    echo "<h3>Alternativas originais:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Texto</th><th>eh_correta</th></tr>";
    foreach ($alternativas_questao as $i => $alt) {
        echo "<tr>";
        echo "<td>" . $alt['id_alternativa'] . "</td>";
        echo "<td>" . htmlspecialchars($alt['texto']) . "</td>";
        echo "<td>" . $alt['eh_correta'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>4. Embaralhando alternativas:</h2>";
    $seed = $id_questao + (int)date('Ymd');
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<p>Seed usado: $seed</p>";
    
    echo "<h3>Alternativas após embaralhamento:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Posição</th><th>Letra</th><th>ID</th><th>Texto</th><th>eh_correta</th></tr>";
    
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        echo "<tr>";
        echo "<td>$index</td>";
        echo "<td>$letra</td>";
        echo "<td>" . $alt['id_alternativa'] . "</td>";
        echo "<td>" . htmlspecialchars($alt['texto']) . "</td>";
        echo "<td>" . $alt['eh_correta'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>5. Encontrando alternativa correta:</h2>";
    $alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            break;
        }
    }
    
    if ($alternativa_correta) {
        echo "<p style='color: green;'>✅ Alternativa correta encontrada: ID " . $alternativa_correta['id_alternativa'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ ERRO: Nenhuma alternativa correta encontrada!</p>";
        exit;
    }
    
    echo "<h2>6. Mapeando letra selecionada para ID:</h2>";
    $id_alternativa = null;
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        if ($letra === strtoupper($alternativa_selecionada)) {
            $id_alternativa = $alt['id_alternativa'];
            echo "<p>Letra $letra (posição $index) → ID " . $alt['id_alternativa'] . "</p>";
            break;
        }
    }
    
    if ($id_alternativa) {
        echo "<p style='color: green;'>✅ ID da alternativa selecionada: $id_alternativa</p>";
    } else {
        echo "<p style='color: red;'>❌ ERRO: Não encontrou ID para letra $alternativa_selecionada</p>";
        exit;
    }
    
    echo "<h2>7. Verificando se acertou:</h2>";
    if ($alternativa_correta && $id_alternativa) {
        $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        echo "<p>ID selecionado: $id_alternativa</p>";
        echo "<p>ID correto: " . $alternativa_correta['id_alternativa'] . "</p>";
        echo "<p style='color: " . ($acertou ? 'green' : 'red') . ";'>" . ($acertou ? '✅ ACERTOU!' : '❌ ERROU!') . "</p>";
        
        echo "<h2>8. Testando inserção no banco:</h2>";
        
        // Simular user_id
        $user_id = 1; // Para teste
        
        $stmt_resposta = $pdo->prepare("
            INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            id_alternativa = VALUES(id_alternativa), 
            acertou = VALUES(acertou), 
            data_resposta = VALUES(data_resposta)
        ");
        
        try {
            $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
            echo "<p style='color: green;'>✅ Resposta salva no banco com sucesso!</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ ERRO ao salvar no banco: " . $e->getMessage() . "</p>";
        }
        
        echo "<h2>9. Resposta JSON que seria enviada:</h2>";
        
        // Encontrar a letra da alternativa correta após embaralhamento
        $letra_correta = '';
        foreach ($alternativas_questao as $index => $alt) {
            if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                $letra_correta = $letras[$index] ?? ($index + 1);
                break;
            }
        }
        
        $resposta = [
            'success' => true,
            'acertou' => (bool)$acertou,
            'alternativa_correta' => $letra_correta,
            'explicacao' => '',
            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
        ];
        
        echo "<pre>" . json_encode($resposta, JSON_PRETTY_PRINT) . "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ ERRO: Não foi possível verificar se acertou</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERRO: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<h2>10. Teste de requisição AJAX simulada:</h2>";
echo "<p>Testando se o processamento funciona via POST...</p>";

// Simular requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['id_questao'] = '1';
$_POST['alternativa_selecionada'] = 'A';
$_POST['ajax_request'] = '1';

// Capturar output
ob_start();
include 'quiz_vertical_filtros.php';
$output = ob_get_clean();

echo "<h3>Resposta do quiz_vertical_filtros.php:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";
?>
