<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Redireciona se a sessão do quiz não existir
if (!isset($_SESSION['quiz_progress'])) {
    header('Location: index.php');
    exit;
}

$acertos = $_SESSION['quiz_progress']['acertos'];
$numero_de_questoes_por_quiz = 5;
$porcentagem_acertos = ($acertos / $numero_de_questoes_por_quiz) * 100;
$mensagem_final = '';
$class_mensagem = '';

if ($porcentagem_acertos >= 80) {
    $mensagem_final = "Parabéns! Você mandou muito bem!";
    $class_mensagem = "correta";
} else if ($porcentagem_acertos >= 50) {
    $mensagem_final = "Muito bom! Continue estudando para melhorar.";
    $class_mensagem = "media";
} else {
    $mensagem_final = "Não desanime! Reveja o conteúdo e tente novamente.";
    $class_mensagem = "incorreta";
}

// Limpa a sessão do quiz para que um novo possa começar
unset($_SESSION['quiz_progress']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado das Questões</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
    <style>
        /* Estilos para o cabeçalho */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 150px;
            text-shadow: 5px 1px 3px rgba(0, 0, 0, 0.4);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        header h1 {
            color: white;
            margin: 0;
            font-size: 1.5em;
        }

        .login-area {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-area a {
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .login-area a:hover {
            transform: translateY(-2px);
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .separator {
            color: #ffffff;
        }

        /* Estilos para o conteúdo principal */
        .conteudo-principal {
            max-width: 900px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.432);
            border-radius: 10px;
            text-align: center;
            margin: 173px auto 80px auto;
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Estilos para o card de resultado */
        .resultado-card {
            text-align: center;
            padding: 30px;
            animation: scaleIn 0.6s ease-in-out;
        }
        
        .resultado-card h2 {
            font-size: 2em;
            margin-bottom: 10px;
            color: #0056b3;
        }
        
        .resultado-card p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        
        .resultado-card .pontuacao-final {
            font-size: 3em;
            font-weight: bold;
            color: #0072FF;
            margin: 20px 0;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
            animation: pulse 2s infinite;
        }
        
        .resultado-card .mensagem {
            padding: 15px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1.1em;
            margin: 20px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .resultado-card .mensagem:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .resultado-card .mensagem.correta {
            background-color: #d4edda;
            color: #155724;
        }
        
        .resultado-card .mensagem.media {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .resultado-card .mensagem.incorreta {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Estilos para ações */
        .actions-right {
            margin-top: 30px;
            text-align: center;
        }

        .btn-primary {
            display: inline-block;
            background-image: linear-gradient(to right, #00C6FF, #0072FF);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        /* Rodapé fixo */
        footer {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            color: white;
            text-align: center;
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .footer-creditos {
            font-size: 0.9em;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Media queries para responsividade */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                min-height: auto;
                padding: 15px 5px;
            }
            
            .login-area {
                margin-top: 10px;
                flex-direction: column;
                gap: 5px;
            }
            
            .conteudo-principal {
                margin: 203px auto 80px auto;
                padding: 15px;
            }
            
            .resultado-card {
                padding: 15px;
            }
            
            .resultado-card h2 {
                font-size: 1.7em;
            }
            
            .resultado-card .pontuacao-final {
                font-size: 2.5em;
            }
        }

        @media (max-width: 480px) {
            header h1 {
                font-size: 1.3em;
            }
            
            .conteudo-principal {
                margin: 180px auto 80px auto;
                padding: 10px;
            }
            
            .resultado-card h2 {
                font-size: 1.5em;
            }
            
            .resultado-card p {
                font-size: 1em;
            }
            
            .resultado-card .pontuacao-final {
                font-size: 2em;
            }
            
            .resultado-card .mensagem {
                font-size: 1em;
                padding: 10px;
            }
            
            .btn-primary {
                padding: 10px 20px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Resultado das Questões</h1>
        <div class="login-area">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="perfil_usuario.php" aria-label="Ver meu desempenho">Meu Desempenho</a>
                <span class="separator">|</span>
                <a href="logout.php" aria-label="Sair da conta">Sair</a>
                <span class="separator">|</span>
                <a href="index.php">Voltar aos Assuntos</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <span class="separator">|</span>
                <a href="cadastro.php">Cadastro</a>
                <span class="separator">|</span>
                <a href="index.php">Voltar aos Assuntos</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="conteudo-principal">
        <div class="resultado-card">
            <h2>Seu Desempenho</h2>
            <p>Você acertou:</p>
            <div class="pontuacao-final"><?= htmlspecialchars($acertos) ?> de <?= htmlspecialchars($numero_de_questoes_por_quiz) ?></div>
            <p>Sua pontuação final é: <?= htmlspecialchars(number_format($porcentagem_acertos, 0)) ?>%</p>
            <div class="mensagem <?= htmlspecialchars($class_mensagem) ?>">
                <?= htmlspecialchars($mensagem_final) ?>
            </div>
            
            <div class="actions-right">
                <a href="index.php?novo=1" class="btn btn-primary">Tentar novamente</a>
            </div>
        </div>
    </main>
    
    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>