<?php
session_start();
require_once 'conexao.php';

// Buscar uma questão para teste
$stmt = $pdo->query("SELECT * FROM questoes LIMIT 1");
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$questao) {
    echo "Nenhuma questão encontrada";
    exit;
}

// Buscar alternativas
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$questao['id_questao']]);
$alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Simples - Alternativas</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .question {
            font-size: 1.2em;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .alternatives {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .alternative {
            background: linear-gradient(135deg, #ffffff 0%, #f7f8fc 100%);
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px 25px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        .alternative::before {
            content: attr(data-letter);
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9em;
            transition: all 0.3s ease;
            z-index: 1;
            pointer-events: none;
        }
        
        .alternative span {
            flex: 1;
            font-size: 1.05em;
            line-height: 1.5;
            color: #2d3748;
            margin-left: 50px;
            font-weight: 500;
        }
        
        .alternative:hover {
            border-color: #667eea;
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }
        
        .alternative:active {
            transform: translateX(4px) scale(0.98);
        }
        
        .alternative.clicked {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
            border-color: #28a745 !important;
            color: #155724 !important;
        }
        
        .alternative.clicked::before {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }
        
        .debug {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .debug h3 {
            margin-top: 0;
        }
        
        .debug-log {
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste Simples - Alternativas</h1>
        
        <div class="question">
            <strong>Questão:</strong> <?php echo htmlspecialchars($questao['pergunta']); ?>
        </div>
        
        <div class="alternatives">
            <?php 
            $letras = ['A', 'B', 'C', 'D', 'E'];
            foreach ($alternativas as $index => $alternativa): 
            ?>
                <div class="alternative" 
                     data-letter="<?php echo $letras[$index]; ?>"
                     data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
                     data-questao-id="<?php echo $questao['id_questao']; ?>">
                    <span><?php echo htmlspecialchars($alternativa['texto']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="debug">
            <h3>🔍 Debug Info</h3>
            <div id="debug-info">
                <div>Carregando...</div>
            </div>
            
            <h3>📝 Debug Log</h3>
            <div class="debug-log" id="debug-log">
                <div>Console de debug aparecerá aqui...</div>
            </div>
        </div>
    </div>

    <script>
        function log(message) {
            const debugLog = document.getElementById('debug-log');
            const timestamp = new Date().toLocaleTimeString();
            debugLog.innerHTML += `<div>[${timestamp}] ${message}</div>`;
            debugLog.scrollTop = debugLog.scrollHeight;
            console.log(message);
        }
        
        function updateDebugInfo() {
            const alternatives = document.querySelectorAll('.alternative');
            const debugInfo = document.getElementById('debug-info');
            
            let info = `<div><strong>Alternativas encontradas:</strong> ${alternatives.length}</div>`;
            
            alternatives.forEach((alt, index) => {
                const rect = alt.getBoundingClientRect();
                const computedStyle = window.getComputedStyle(alt);
                
                info += `<div><strong>Alt ${index + 1}:</strong> pointer-events=${computedStyle.pointerEvents}, cursor=${computedStyle.cursor}</div>`;
            });
            
            debugInfo.innerHTML = info;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            log('DOM carregado!');
            
            setTimeout(() => {
                updateDebugInfo();
                
                const alternatives = document.querySelectorAll('.alternative');
                log(`Encontradas ${alternativas.length} alternativas`);
                
                alternatives.forEach((alternative, index) => {
                    log(`Configurando alternativa ${index + 1}`);
                    
                    alternative.addEventListener('click', function(e) {
                        log(`🎯 CLIQUE DETECTADO na alternativa ${index + 1}!`);
                        log(`Evento: ${e.type}, Target: ${e.target.tagName}`);
                        
                        // Remover classe clicked de todas as alternativas
                        alternatives.forEach(alt => alt.classList.remove('clicked'));
                        
                        // Adicionar classe clicked à alternativa clicada
                        this.classList.add('clicked');
                        
                        // Atualizar debug info
                        updateDebugInfo();
                    });
                });
                
            }, 500);
        });
    </script>
</body>
</html>

