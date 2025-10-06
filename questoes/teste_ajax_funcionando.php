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
        
        // Buscar alternativas
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$id_questao]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($alternativas_questao)) {
            echo json_encode([
                'success' => false,
                'message' => 'Nenhuma alternativa encontrada'
            ]);
            exit;
        }
        
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
        
        if (!$id_alternativa) {
            echo json_encode([
                'success' => false,
                'message' => 'Alternativa não encontrada: ' . $alternativa_selecionada
            ]);
            exit;
        }
        
        // Encontrar alternativa correta
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
        
        // Inserir resposta
        $stmt_resposta = $pdo->prepare("
            INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            id_alternativa = VALUES(id_alternativa), 
            acertou = VALUES(acertou), 
            data_resposta = VALUES(data_resposta)
        ");
        $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
        
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
            'message' => 'Erro: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Se não for POST, mostrar formulário
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste AJAX Funcionando</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .test-btn { 
            background: linear-gradient(135deg, #0072FF, #00C6FF); 
            color: white; 
            border: none; 
            padding: 15px 25px; 
            margin: 10px; 
            cursor: pointer; 
            border-radius: 8px; 
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .test-btn:hover { 
            background: linear-gradient(135deg, #0056CC, #0099CC); 
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,114,255,0.3);
        }
        #resultado { 
            margin-top: 20px; 
            padding: 20px; 
            background: #f8f9fa; 
            border-radius: 8px; 
            white-space: pre-wrap; 
            border-left: 4px solid #0072FF;
            min-height: 100px;
        }
        .success { color: #28a745; background: #d4edda; border-left-color: #28a745; }
        .error { color: #dc3545; background: #f8d7da; border-left-color: #dc3545; }
        h1 { color: #0072FF; text-align: center; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Teste AJAX Funcionando</h1>
        
        <div class="info">
            <p><strong>Este teste simula exatamente o que deveria acontecer no quiz_vertical_filtros.php</strong></p>
            <p>• Questão: 92</p>
            <p>• Alternativas: A, B, C, D</p>
            <p>• Clique em qualquer alternativa para testar</p>
        </div>
        
        <div style="text-align: center;">
            <button class="test-btn" onclick="testarAjax('A')">🔤 Alternativa A</button>
            <button class="test-btn" onclick="testarAjax('B')">🔤 Alternativa B</button>
            <button class="test-btn" onclick="testarAjax('C')">🔤 Alternativa C</button>
            <button class="test-btn" onclick="testarAjax('D')">🔤 Alternativa D</button>
        </div>
        
        <div id="resultado">Clique em uma alternativa para testar...</div>
    </div>
    
    <script>
        function testarAjax(letra) {
            const formData = new FormData();
            formData.append('id_questao', '92');
            formData.append('alternativa_selecionada', letra);
            
            document.getElementById('resultado').innerHTML = '⏳ Enviando requisição...';
            
            fetch('teste_ajax_funcionando.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                let html = '<h3>📋 Resposta Recebida:</h3>';
                html += '<p><strong>✅ Success:</strong> ' + data.success + '</p>';
                html += '<p><strong>🎯 Acertou:</strong> ' + (data.acertou ? 'SIM' : 'NÃO') + '</p>';
                html += '<p><strong>💬 Mensagem:</strong> ' + data.message + '</p>';
                html += '<p><strong>🔑 ID Alternativa Correta:</strong> ' + data.alternativa_correta + '</p>';
                
                if (data.success) {
                    html = '<div class="success">' + html + '</div>';
                } else {
                    html = '<div class="error">' + html + '</div>';
                }
                
                document.getElementById('resultado').innerHTML = html;
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('resultado').innerHTML = '<div class="error"><h3>❌ Erro na Requisição:</h3><p>' + error + '</p></div>';
            });
        }
    </script>
</body>
</html>

