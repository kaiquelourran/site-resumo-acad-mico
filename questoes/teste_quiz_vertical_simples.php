<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Simular usuário logado para teste
$_SESSION['id_usuario'] = 1;

echo "<h1>TESTE QUIZ VERTICAL SIMPLES</h1>";

// Testar conexão
if ($pdo) {
    echo "<p style='color: green;'>✅ Conexão com banco OK</p>";
} else {
    echo "<p style='color: red;'>❌ Falha na conexão</p>";
    exit;
}

// Testar se há questões
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes");
$stmt->execute();
$total = $stmt->fetchColumn();
echo "<p>Total de questões no banco: $total</p>";

// Testar se há alternativas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alternativas");
$stmt->execute();
$total_alt = $stmt->fetchColumn();
echo "<p>Total de alternativas no banco: $total_alt</p>";

// Testar uma questão específica
$stmt = $pdo->prepare("SELECT * FROM questoes LIMIT 1");
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    echo "<h2>Testando questão ID: " . $questao['id_questao'] . "</h2>";
    echo "<p>Enunciado: " . htmlspecialchars($questao['enunciado']) . "</p>";
    
    // Buscar alternativas
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao['id_questao']]);
    $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Alternativas:</h3>";
    foreach ($alternativas as $i => $alt) {
        $letra = ['A', 'B', 'C', 'D'][$i] ?? ($i + 1);
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . " (Correta: " . ($alt['eh_correta'] ? 'SIM' : 'NÃO') . ")</p>";
    }
    
    // Testar processamento de resposta
    echo "<h2>Testando processamento de resposta:</h2>";
    
    // Simular POST
    $_POST['id_questao'] = $questao['id_questao'];
    $_POST['alternativa_selecionada'] = 'A';
    $_POST['ajax_request'] = '1';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Capturar output
    ob_start();
    
    // Incluir o código de processamento do quiz_vertical_filtros.php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_questao']) && isset($_POST['alternativa_selecionada'])) {
        try {
            $id_questao = (int)$_POST['id_questao'];
            $alternativa_selecionada = $_POST['alternativa_selecionada'];
            
            // Buscar as alternativas da questão para mapear a letra correta
            $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
            $stmt_alt->execute([$id_questao]);
            $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
            
            // Embaralhar da mesma forma que na exibição
            $seed = $id_questao + (int)date('Ymd');
            srand($seed);
            shuffle($alternativas_questao);
            
            // Mapear a letra selecionada para o ID da alternativa
            $letras = ['A', 'B', 'C', 'D', 'E'];
            $id_alternativa = null;
            foreach ($alternativas_questao as $index => $alternativa) {
                $letra = $letras[$index] ?? ($index + 1);
                if ($letra === strtoupper($alternativa_selecionada)) {
                    $id_alternativa = $alternativa['id_alternativa'];
                    break;
                }
            }
            
            // Buscar a alternativa correta para esta questão
            $alternativa_correta = null;
            foreach ($alternativas_questao as $alt) {
                if ($alt['eh_correta'] == 1) {
                    $alternativa_correta = $alt;
                    break;
                }
            }
            
            if ($alternativa_correta && $id_alternativa) {
                $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
                
                // Inserir ou atualizar resposta
                $user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
                if (!$user_id) {
                    echo "ERRO: user_id não encontrado na sessão";
                } else {
                    $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE 
                        id_alternativa = VALUES(id_alternativa), 
                        acertou = VALUES(acertou), 
                        data_resposta = VALUES(data_resposta)
                    ");
                    $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
                    
                    // Se for uma requisição AJAX, retornar JSON
                    if (isset($_POST['ajax_request'])) {
                        // Encontrar a letra da alternativa correta após embaralhamento
                        $letra_correta = '';
                        $letras = ['A', 'B', 'C', 'D', 'E'];
                        foreach ($alternativas_questao as $index => $alt) {
                            if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                                $letra_correta = $letras[$index] ?? ($index + 1);
                                break;
                            }
                        }
                        
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'acertou' => (bool)$acertou,
                            'alternativa_correta' => $letra_correta,
                            'explicacao' => '',
                            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
                        ]);
                    }
                }
            } else {
                echo "ERRO: Não foi possível processar a resposta";
            }
        } catch (Exception $e) {
            echo "ERRO: " . $e->getMessage();
        }
    }
    
    $output = ob_get_clean();
    echo "<h3>Resposta do processamento:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Nenhuma questão encontrada no banco</p>";
}
?>
