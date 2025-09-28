<!DOCTYPE html>
<html>
<head>
    <title>Debug Comunicação Quiz.js ↔ processar_resposta.php</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .result { margin: 10px 0; padding: 10px; background: #f5f5f5; }
        .error { background: #ffebee; color: #c62828; }
        .success { background: #e8f5e8; color: #2e7d32; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Debug: Comunicação Quiz.js ↔ processar_resposta.php</h1>
    
    <div class="test-section">
        <h2>Teste 1: Envio direto via JavaScript (simulando quiz.js)</h2>
        <button onclick="testarEnvioJS()">Testar Envio JS</button>
        <div id="resultado-js" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>Teste 2: Verificar se dados chegam corretamente</h2>
        <button onclick="testarDados()">Testar Dados</button>
        <div id="resultado-dados" class="result"></div>
    </div>
    
    <div class="test-section">
        <h2>Teste 3: Verificar resposta do servidor</h2>
        <button onclick="testarResposta()">Testar Resposta</button>
        <div id="resultado-resposta" class="result"></div>
    </div>

    <script>
        function testarEnvioJS() {
            const resultado = document.getElementById('resultado-js');
            resultado.innerHTML = 'Enviando...';
            
            // Simula exatamente como o quiz.js envia
            fetch('processar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_questao=76&id_alternativa=322`
            })
            .then(response => {
                console.log('Status:', response.status);
                console.log('Headers:', [...response.headers.entries()]);
                return response.text(); // Primeiro como texto para debug
            })
            .then(text => {
                console.log('Resposta raw:', text);
                try {
                    const data = JSON.parse(text);
                    resultado.innerHTML = `
                        <div class="success">
                            <strong>Sucesso!</strong><br>
                            Status: ${data.sucesso ? 'OK' : 'ERRO'}<br>
                            Correta: ${data.correta}<br>
                            Acertos: ${data.acertos}<br>
                            Resposta completa: ${JSON.stringify(data, null, 2)}
                        </div>
                    `;
                } catch (e) {
                    resultado.innerHTML = `
                        <div class="error">
                            <strong>Erro ao parsear JSON:</strong><br>
                            ${e.message}<br>
                            <strong>Resposta raw:</strong><br>
                            <pre>${text}</pre>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                resultado.innerHTML = `
                    <div class="error">
                        <strong>Erro de requisição:</strong><br>
                        ${error.message}
                    </div>
                `;
            });
        }
        
        function testarDados() {
            const resultado = document.getElementById('resultado-dados');
            resultado.innerHTML = 'Testando...';
            
            fetch('debug_post_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_questao=76&id_alternativa=322`
            })
            .then(response => response.json())
            .then(data => {
                resultado.innerHTML = `
                    <div class="success">
                        <strong>Dados recebidos:</strong><br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            })
            .catch(error => {
                resultado.innerHTML = `
                    <div class="error">
                        <strong>Erro:</strong> ${error.message}
                    </div>
                `;
            });
        }
        
        function testarResposta() {
            const resultado = document.getElementById('resultado-resposta');
            resultado.innerHTML = 'Testando...';
            
            // Teste com dados inválidos para ver se retorna erro correto
            fetch('processar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_questao=0&id_alternativa=0`
            })
            .then(response => {
                console.log('Status erro:', response.status);
                return response.json();
            })
            .then(data => {
                resultado.innerHTML = `
                    <div class="error">
                        <strong>Teste com dados inválidos:</strong><br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            })
            .catch(error => {
                resultado.innerHTML = `
                    <div class="error">
                        <strong>Erro:</strong> ${error.message}
                    </div>
                `;
            });
        }
    </script>
</body>
</html>