<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site em Manutenção - Resumo Acadêmico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .maintenance-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .maintenance-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 2rem;
        }

        .maintenance-message {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .estimated-time {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }

        .estimated-time strong {
            color: #667eea;
        }

        .contact-info {
            font-size: 0.9rem;
            color: #888;
            margin-top: 2rem;
        }

        .loading-dots {
            display: inline-block;
            margin-left: 5px;
        }

        .loading-dots span {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #667eea;
            margin: 0 2px;
            animation: loading 1.4s infinite ease-in-out both;
        }

        .loading-dots span:nth-child(1) { animation-delay: -0.32s; }
        .loading-dots span:nth-child(2) { animation-delay: -0.16s; }

        @keyframes loading {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }

        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <?php
    require_once 'maintenance_config.php';
    
    // Se não estiver em modo de manutenção, redireciona para a página inicial
    if (!is_maintenance_mode()) {
        header('Location: index.php');
        exit;
    }
    ?>
    
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        
        <h1>Site em Manutenção</h1>
        
        <div class="maintenance-message">
            <?php echo htmlspecialchars($maintenance_message); ?>
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <?php if (!empty($maintenance_end_time)): ?>
        <div class="estimated-time">
            <strong>Previsão de retorno:</strong><br>
            <?php echo date('d/m/Y às H:i', strtotime($maintenance_end_time)); ?>
        </div>
        <?php endif; ?>
        
        <button class="refresh-btn" onclick="location.reload()">
            🔄 Verificar Novamente
        </button>
        
        <div class="contact-info">
            Agradecemos sua compreensão!<br>
            <strong>Resumo Acadêmico</strong>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>