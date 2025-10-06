<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG FINAL - QUIZ VERTICAL FILTROS</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;

echo "<h2>1. Testando conexão com banco:</h2>";
if ($pdo) {
    echo "<p style='color: green;'>✅ Conexão OK</p>";
} else {
    echo "<p style='color: red;'>❌ Falha na conexão</p>";
    exit;
}

echo "<h2>2. Testando estrutura da tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($colunas)) {
        echo "<p style='color: red;'>❌ Tabela não existe, criando...</p>";
        $sql_create = "CREATE TABLE respostas_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            id_questao INT NOT NULL,
            id_alternativa INT NOT NULL,
            acertou TINYINT(1) NOT NULL DEFAULT 0,
            data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_questao (user_id, id_questao)
        )";
        $pdo->exec($sql_create);
        echo "<p style='color: green;'>✅ Tabela criada!</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabela existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testando questão específica:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM questoes LIMIT 1");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<p>Questão encontrada: ID " . $questao['id_questao'] . "</p>";
        echo "<p>Enunciado: " . htmlspecialchars(substr($questao['enunciado'], 0, 100)) . "...</p>";
        
        // Buscar alternativas
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$questao['id_questao']]);
        $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Alternativas encontradas: " . count($alternativas) . "</p>";
        
        if (count($alternativas) > 0) {
            echo "<h3>Alternativas:</h3>";
            $letras = ['A', 'B', 'C', 'D', 'E'];
            foreach ($alternativas as $index => $alt) {
                $letra = $letras[$index] ?? ($index + 1);
                echo "<p>$letra) " . htmlspecialchars($alt['texto']) . " (Correta: " . ($alt['eh_correta'] ? 'SIM' : 'NÃO') . ")</p>";
            }
            
            // Testar processamento de resposta
            echo "<h2>4. Testando processamento de resposta:</h2>";
            
            // Simular POST
            $_POST['id_questao'] = $questao['id_questao'];
            $_POST['alternativa_selecionada'] = 'A';
            $_POST['ajax_request'] = '1';
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            // Processar resposta
            $id_questao = (int)$_POST['id_questao'];
            $alternativa_selecionada = $_POST['alternativa_selecionada'];
            
            // Buscar alternativas
            $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
            $stmt_alt->execute([$id_questao]);
            $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
            
            // Embaralhar
            $seed = $id_questao + (int)date('Ymd');
            srand($seed);
            shuffle($alternativas_questao);
            
            // Mapear letra para ID
            $letras = ['A', 'B', 'C', 'D', 'E'];
            $id_alternativa = null;
            foreach ($alternativas_questao as $index => $alternativa) {
                $letra = $letras[$index] ?? ($index + 1);
                if ($letra === strtoupper($alternativa_selecionada)) {
                    $id_alternativa = $alternativa['id_alternativa'];
                    break;
                }
            }
            
            // Encontrar alternativa correta
            $alternativa_correta = null;
            foreach ($alternativas_questao as $alt) {
                if ($alt['eh_correta'] == 1) {
                    $alternativa_correta = $alt;
                    break;
                }
            }
            
            if ($alternativa_correta && $id_alternativa) {
                $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
                
                echo "<p>ID selecionado: $id_alternativa</p>";
                echo "<p>ID correto: " . $alternativa_correta['id_alternativa'] . "</p>";
                echo "<p style='color: " . ($acertou ? 'green' : 'red') . ";'>" . ($acertou ? '✅ ACERTOU!' : '❌ ERROU!') . "</p>";
                
                // Testar inserção no banco
                echo "<h3>Testando inserção no banco:</h3>";
                $user_id = $_SESSION['id_usuario'];
                
                $stmt_resposta = $pdo->prepare("
                    INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
                    VALUES (?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    id_alternativa = VALUES(id_alternativa), 
                    acertou = VALUES(acertou), 
                    data_resposta = VALUES(data_resposta)
                ");
                
                $resultado = $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
                
                if ($resultado) {
                    echo "<p style='color: green;'>✅ Resposta salva no banco!</p>";
                } else {
                    echo "<p style='color: red;'>❌ Falha ao salvar no banco</p>";
                }
                
                // Encontrar letra correta após embaralhamento
                $letra_correta = '';
                foreach ($alternativas_questao as $index => $alt) {
                    if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                        $letra_correta = $letras[$index] ?? ($index + 1);
                        break;
                    }
                }
                
                // Resposta JSON
                $resposta = [
                    'success' => true,
                    'acertou' => (bool)$acertou,
                    'alternativa_correta' => $letra_correta,
                    'explicacao' => '',
                    'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
                ];
                
                echo "<h3>Resposta JSON que seria enviada:</h3>";
                echo "<pre>" . json_encode($resposta, JSON_PRETTY_PRINT) . "</pre>";
                
            } else {
                echo "<p style='color: red;'>❌ Não foi possível processar a resposta</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Nenhuma alternativa encontrada para esta questão</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Nenhuma questão encontrada no banco</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Teste de requisição AJAX real:</h2>";
echo "<p>Para testar, acesse: <a href='teste_ajax_simples.html' target='_blank'>teste_ajax_simples.html</a></p>";
?>
