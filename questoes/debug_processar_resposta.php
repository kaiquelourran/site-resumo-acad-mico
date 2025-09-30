<?php
// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

echo "<h1>Debug - Processar Resposta</h1>";

echo "<h3>Dados POST recebidos:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Dados SESSION:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

try {
    require_once __DIR__ . '/conexao.php';
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida</p>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $is_vertical_quiz = isset($_POST['alternativa_selecionada']) && isset($_POST['id_questao']);
        
        echo "<h3>Verificações:</h3>";
        echo "<p>É quiz vertical? " . ($is_vertical_quiz ? "SIM" : "NÃO") . "</p>";
        
        if ($is_vertical_quiz) {
            $id_questao = (int)$_POST['id_questao'];
            $alternativa_selecionada = $_POST['alternativa_selecionada'];
            
            echo "<p>ID Questão: $id_questao</p>";
            echo "<p>Alternativa Selecionada: $alternativa_selecionada</p>";
            
            // Buscar a questão
            $sql = "SELECT * FROM questoes WHERE id_questao = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_questao]);
            $questao = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($questao) {
                echo "<p style='color: green;'>✅ Questão encontrada</p>";
                echo "<h4>Dados da questão:</h4>";
                echo "<pre>";
                print_r($questao);
                echo "</pre>";
                
                $resposta_correta = $questao['resposta_correta'];
                $acertou = ($alternativa_selecionada === $resposta_correta);
                
                echo "<p>Resposta correta: $resposta_correta</p>";
                echo "<p>Acertou? " . ($acertou ? "SIM" : "NÃO") . "</p>";
                
                // Tentar inserir/atualizar resposta
                $sql_insert = "INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              id_alternativa = VALUES(id_alternativa), 
                              acertou = VALUES(acertou), 
                              data_resposta = CURRENT_TIMESTAMP";
                
                $stmt_insert = $pdo->prepare($sql_insert);
                $result = $stmt_insert->execute([$id_questao, ord($alternativa_selecionada) - ord('A') + 1, $acertou ? 1 : 0]);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Resposta salva no banco</p>";
                } else {
                    echo "<p style='color: red;'>❌ Erro ao salvar resposta</p>";
                    echo "<pre>";
                    print_r($stmt_insert->errorInfo());
                    echo "</pre>";
                }
                
                // Preparar resposta JSON
                $response = [
                    'success' => true,
                    'acertou' => $acertou,
                    'resposta_correta' => $resposta_correta,
                    'alternativa_selecionada' => $alternativa_selecionada,
                    'explicacao' => $questao['explicacao'] ?? ''
                ];
                
                echo "<h4>Resposta JSON que seria enviada:</h4>";
                echo "<pre>";
                print_r($response);
                echo "</pre>";
                
            } else {
                echo "<p style='color: red;'>❌ Questão não encontrada</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Não é uma requisição de quiz vertical</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Não é uma requisição POST</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<pre>";
    print_r($e->getTrace());
    echo "</pre>";
}
?>