<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

// Simular exatamente o que está acontecendo no quiz_vertical_filtros.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_questao']) && isset($_POST['alternativa_selecionada'])) {
    header('Content-Type: application/json');
    
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
        
        // Debug: verificar se encontrou a alternativa
        if (!$id_alternativa) {
            echo json_encode([
                'success' => false,
                'message' => 'ERRO: Não encontrou alternativa para letra: ' . $alternativa_selecionada
            ]);
            exit;
        }
        
        // Buscar a alternativa correta para esta questão
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            // Usar apenas o campo que sabemos que existe
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        // Debug: verificar alternativa correta encontrada
        if (!$alternativa_correta) {
            echo json_encode([
                'success' => false,
                'message' => 'ERRO: Nenhuma alternativa correta encontrada!'
            ]);
            exit;
        }
        
        if ($alternativa_correta && $id_alternativa) {
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
            
            // Inserir ou atualizar resposta
            $stmt_resposta = $pdo->prepare("
                INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                id_alternativa = VALUES(id_alternativa), 
                acertou = VALUES(acertou), 
                data_resposta = VALUES(data_resposta)
            ");
            $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
            
            // Retornar resposta JSON
            echo json_encode([
                'success' => true,
                'acertou' => (bool)$acertou,
                'alternativa_correta' => $alternativa_correta['id_alternativa'],
                'explicacao' => '', // Explicação não disponível na tabela alternativas
                'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
            ]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Se não for POST, mostrar formulário de teste
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste Final</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-btn { 
            background: #0072FF; 
            color: white; 
            border: none; 
            padding: 15px 25px; 
            margin: 10px; 
            cursor: pointer; 
            border-radius: 8px; 
            font-size: 16px;
        }
        .test-btn:hover { background: #0056CC; }
        #resultado { 
            margin-top: 20px; 
            padding: 20px; 
            background: #f8f9fa; 
            border-radius: 8px; 
            white-space: pre-wrap; 
            border-left: 4px solid #0072FF;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <h1>🧪 Teste Final - Simulação do quiz_vertical_filtros.php</h1>
    
    <p>Este teste simula exatamente o que acontece no arquivo original.</p>
    
    <div>
        <button class="test-btn" onclick="testarAjax('A')">🔤 Testar Alternativa A</button>
        <button class="test-btn" onclick="testarAjax('B')">🔤 Testar Alternativa B</button>
        <button class="test-btn" onclick="testarAjax('C')">🔤 Testar Alternativa C</button>
        <button class="test-btn" onclick="testarAjax('D')">🔤 Testar Alternativa D</button>
    </div>
    
    <div id="resultado"></div>
    
    <script>
        function testarAjax(letra) {
            const formData = new FormData();
            formData.append('id_questao', '92');
            formData.append('alternativa_selecionada', letra);
            
            document.getElementById('resultado').innerHTML = '⏳ Enviando requisição...';
            
            fetch('teste_final.php', {
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
                let html = '<h3>📋 Resposta recebida:</h3>';
                html += '<p><strong>✅ Success:</strong> ' + data.success + '</p>';
                html += '<p><strong>🎯 Acertou:</strong> ' + data.acertou + '</p>';
                html += '<p><strong>💬 Mensagem:</strong> ' + data.message + '</p>';
                html += '<p><strong>🔑 Alternativa Correta ID:</strong> ' + data.alternativa_correta + '</p>';
                
                if (data.success) {
                    html = '<div class="success">' + html + '</div>';
                } else {
                    html = '<div class="error">' + html + '</div>';
                }
                
                document.getElementById('resultado').innerHTML = html;
            })
            .catch(error => {
                console.error('Erro:', error);
                document.getElementById('resultado').innerHTML = '<div class="error"><h3>❌ Erro na requisição:</h3><p>' + error + '</p></div>';
            });
        }
    </script>
</body>
</html>


