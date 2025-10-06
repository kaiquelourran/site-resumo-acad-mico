<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

// Processar resposta se enviada via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_questao']) && isset($_POST['alternativa_selecionada'])) {
    header('Content-Type: application/json');
    
    try {
        $id_questao = (int)$_POST['id_questao'];
        $alternativa_selecionada = $_POST['alternativa_selecionada'];
        
        // Buscar as alternativas da questão
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$id_questao]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($alternativas_questao)) {
            echo json_encode([
                'success' => false,
                'message' => 'Nenhuma alternativa encontrada para esta questão'
            ]);
            exit;
        }
        
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
        
        if (!$id_alternativa) {
            echo json_encode([
                'success' => false,
                'message' => 'Alternativa não encontrada: ' . $alternativa_selecionada
            ]);
            exit;
        }
        
        // Buscar a alternativa correta
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        if (!$alternativa_correta) {
            echo json_encode([
                'success' => false,
                'message' => 'Nenhuma alternativa correta encontrada'
            ]);
            exit;
        }
        
        // Verificar se acertou
        $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        // Retornar resposta
        echo json_encode([
            'success' => true,
            'acertou' => (bool)$acertou,
            'alternativa_correta' => $alternativa_correta['id_alternativa'],
            'explicacao' => '',
            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Se não for POST, mostrar formulário de teste
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste AJAX Real</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-btn { 
            background: #0072FF; 
            color: white; 
            border: none; 
            padding: 10px 20px; 
            margin: 5px; 
            cursor: pointer; 
            border-radius: 5px; 
        }
        .test-btn:hover { background: #0056CC; }
        #resultado { 
            margin-top: 20px; 
            padding: 15px; 
            background: #f5f5f5; 
            border-radius: 5px; 
            white-space: pre-wrap; 
        }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Teste AJAX Real - Questão 92</h1>
    
    <div>
        <button class="test-btn" onclick="testarAjax('A')">Testar Alternativa A</button>
        <button class="test-btn" onclick="testarAjax('B')">Testar Alternativa B</button>
        <button class="test-btn" onclick="testarAjax('C')">Testar Alternativa C</button>
        <button class="test-btn" onclick="testarAjax('D')">Testar Alternativa D</button>
    </div>
    
    <div id="resultado"></div>
    
    <script>
        function testarAjax(letra) {
            const formData = new FormData();
            formData.append('id_questao', '92');
            formData.append('alternativa_selecionada', letra);
            
            document.getElementById('resultado').innerHTML = 'Enviando requisição...';
            
            fetch('teste_ajax_real.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                let html = '<h3>Resposta recebida:</h3>';
                html += '<p><strong>Success:</strong> ' + data.success + '</p>';
                html += '<p><strong>Acertou:</strong> ' + data.acertou + '</p>';
                html += '<p><strong>Mensagem:</strong> ' + data.message + '</p>';
                html += '<p><strong>Alternativa Correta ID:</strong> ' + data.alternativa_correta + '</p>';
                
                if (data.success) {
                    html = '<div class="success">' + html + '</div>';
                } else {
                    html = '<div class="error">' + html + '</div>';
                }
                
                document.getElementById('resultado').innerHTML = html;
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('resultado').innerHTML = '<div class="error"><h3>Erro na requisição:</h3><p>' + error + '</p></div>';
            });
        }
    </script>
</body>
</html>

