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
    <title>Teste Final - Alternativas</title>
    <link rel="stylesheet" href="modern-style.css">
    <link rel="stylesheet" href="alternative-fix.css">
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
        
        .question-card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
        }
        
        .question-number {
            font-weight: 700;
            font-size: 1.1em;
        }
        
        .question-status {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .question-text {
            font-size: 1.1em;
            line-height: 1.6;
            color: #2d3748;
            margin-bottom: 25px;
        }
        
        .alternatives-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
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
        <h1>🎯 Teste Final - Alternativas</h1>
        
        <div class="question-card" id="questao-<?php echo $questao['id_questao']; ?>" data-respondida="false">
            <div class="question-header">
                <div class="question-number">Questão #<?php echo $questao['id_questao']; ?></div>
                <div class="question-status">❓ Não Respondida</div>
            </div>
            <div class="question-text">
                <?php echo htmlspecialchars($questao['pergunta']); ?>
            </div>
            <div class="alternatives-container">
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
            const questaoCard = document.querySelector('.question-card');
            
            let info = `<div><strong>Alternativas encontradas:</strong> ${alternatives.length}</div>`;
            info += `<div><strong>Questão respondida:</strong> ${questaoCard.dataset.respondida}</div>`;
            
            alternatives.forEach((alt, index) => {
                const rect = alt.getBoundingClientRect();
                const computedStyle = window.getComputedStyle(alt);
                
                info += `<div><strong>Alt ${index + 1}:</strong></div>`;
                info += `<div>&nbsp;&nbsp;pointer-events: ${computedStyle.pointerEvents}</div>`;
                info += `<div>&nbsp;&nbsp;cursor: ${computedStyle.cursor}</div>`;
                info += `<div>&nbsp;&nbsp;---</div>`;
            });
            
            debugInfo.innerHTML = info;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            log('DOM carregado!');
            
            setTimeout(() => {
                updateDebugInfo();
                
                const alternatives = document.querySelectorAll('.alternative');
                const questaoCard = document.querySelector('.question-card');
                
                log(`Encontradas ${alternatives.length} alternativas`);
                
                alternatives.forEach((alternative, index) => {
                    log(`Configurando alternativa ${index + 1}`);
                    
                    alternative.addEventListener('click', function(e) {
                        log(`🎯 CLIQUE DETECTADO na alternativa ${index + 1}!`);
                        
                        // Verificar se já foi respondida
                        if (questaoCard.dataset.respondida === 'true') {
                            log('❌ Questão já foi respondida, ignorando clique');
                            return;
                        }
                        
                        // Destacar a alternativa clicada
                        const todasAlternativas = questaoCard.querySelectorAll('.alternative');
                        todasAlternativas.forEach(alt => {
                            alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                            alt.style.background = '';
                            alt.style.borderColor = '';
                        });
                        
                        // Marcar como selecionada
                        this.style.background = '#e3f2fd';
                        this.style.borderColor = '#2196f3';
                        
                        // Marcar questão como respondida
                        questaoCard.dataset.respondida = 'true';
                        
                        // Desabilitar cliques
                        todasAlternativas.forEach(alt => {
                            alt.style.pointerEvents = 'none';
                            alt.style.cursor = 'default';
                        });
                        
                        log('✅ Questão marcada como respondida, cliques desabilitados');
                        updateDebugInfo();
                    });
                });
                
            }, 500);
        });
    </script>
</body>
</html>

