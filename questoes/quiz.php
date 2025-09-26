<?php
session_start();

require_once __DIR__ . '/conexao.php';

// Define o número de questões no início do arquivo
$numero_de_questoes_por_quiz = 5;

// Sempre atualiza o ID do assunto da sessão com base no que foi passado pela URL
// Inicializa a sessão para o quiz, se ainda não estiver
if (!isset($_SESSION['quiz_progress']) || isset($_GET['novo'])) {
    $_SESSION['quiz_progress'] = [
        'acertos' => 0,
        'respondidas' => [],
        'id_assunto' => isset($_GET['id']) ? (int)$_GET['id'] : 0,
    ];
}

// Atualiza o id_assunto se fornecido
if (isset($_GET['id'])) {
    $_SESSION['quiz_progress']['id_assunto'] = (int)$_GET['id'];
}

// Garante que o array 'respondidas' existe
if (!isset($_SESSION['quiz_progress']['respondidas'])) {
    $_SESSION['quiz_progress']['respondidas'] = [];
}

// Redireciona para a página de resultados se o número de questões foi alcançado
if (count($_SESSION['quiz_progress']['respondidas']) >= $numero_de_questoes_por_quiz) {
    header('Location: resultado.php');
    exit;
}

// Verifica se foi passada uma questão específica
if (isset($_GET['questao']) && !empty($_GET['questao'])) {
    // Busca a questão específica
    $id_questao_especifica = (int)$_GET['questao'];
    $stmt_questao = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
    $stmt_questao->execute([$id_questao_especifica]);
    $questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);
} else {
    // Busca uma questão aleatória que ainda não foi respondida na sessão
    $id_assunto_atual = $_SESSION['quiz_progress']['id_assunto'];
    $questoes_respondidas = $_SESSION['quiz_progress']['respondidas'];
    $sql = "SELECT * FROM questoes WHERE 1=1";
    $params = [];

    if ($id_assunto_atual > 0) {
        $sql .= " AND id_assunto = ?";
        $params[] = $id_assunto_atual;
    }

    if (!empty($questoes_respondidas)) {
        $placeholders = implode(',', array_fill(0, count($questoes_respondidas), '?'));
        $sql .= " AND id_questao NOT IN ($placeholders)";
        $params = array_merge($params, $questoes_respondidas);
    }

    $sql .= " ORDER BY RAND() LIMIT 1";

    $stmt_questao = $pdo->prepare($sql);
    $stmt_questao->execute($params);
    $questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);
}

if ($questao) {
    $stmt_alternativas = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY RAND()");
    $stmt_alternativas->execute([$questao['id_questao']]);
    $alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questões</title>
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

        /* Estilos para o placar e progresso */
        .placar-progresso {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1.1em;
            font-weight: bold;
        }

        .barra-progresso {
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .progresso {
            height: 100%;
            background-image: linear-gradient(to right, #00C6FF, #0072FF);
            border-radius: 5px;
            transition: width 0.5s ease-in-out;
        }

        /* Estilos para o card de questão */
        .card-questao {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            animation: slideIn 0.5s ease-in-out;
        }

        .card-questao h2 {
            color: #333;
            margin-top: 0;
            font-size: 1.3em;
            margin-bottom: 20px;
        }

        /* Estilos para as alternativas */
        .alternativas-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-align: left;
        }

        .alternativas-list label {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .alternativas-list label:hover {
            background-color: #f0f0f0;
            transform: translateX(5px);
        }

        .alternativas-list input[type="radio"] {
            margin-right: 10px;
        }

        /* Estilos para feedback e botões */
        .mensagem-status {
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            min-height: 20px;
        }

        .mensagem-status.correta {
            background-color: #d4edda;
            color: #155724;
            animation: pulse 1s;
        }

        .mensagem-status.incorreta {
            background-color: #f8d7da;
            color: #721c24;
            animation: shake 0.5s;
        }

        .botao-proxima-questao {
            background-image: linear-gradient(to right, #00C6FF, #0072FF);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .botao-proxima-questao:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
        }

        .botao-proxima-questao:active {
            transform: translateY(-1px);
        }

        /* Estilos para ações à direita */
        .actions-right {
            margin-top: 20px;
            text-align: right;
        }

        .voltar-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f8f9fa;
            color: #0056b3;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #0056b3;
            transition: all 0.3s ease;
        }

        .voltar-link:hover {
            background-color: #e2e6ea;
            transform: translateY(-2px);
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

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
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
            }
            
            .conteudo-principal {
                margin: 203px auto 80px auto;
                padding: 15px;
            }
            
            .card-questao {
                padding: 15px;
            }
            
            .card-questao h2 {
                font-size: 1.2em;
            }
            
            .alternativas-list label {
                padding: 10px;
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
            
            .placar-progresso {
                flex-direction: column;
                gap: 5px;
                align-items: center;
            }
            
            .card-questao h2 {
                font-size: 1.1em;
            }
            
            .alternativas-list label {
                padding: 8px;
                font-size: 0.9em;
            }
            
            .botao-proxima-questao {
                padding: 10px 20px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Questões Complementares</h1>
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
        <?php if ($questao): ?>
            <div class="placar-progresso">
                <p>Pontuação: <span id="placar-pontos"><?= htmlspecialchars($_SESSION['quiz_progress']['acertos']) ?></span></p>
                <p>Questão <span id="questao-atual"><?= count($_SESSION['quiz_progress']['respondidas']) + 1 ?></span> de <span id="total-questoes"><?= $numero_de_questoes_por_quiz ?></span></p>
            </div>
            <div class="barra-progresso">
                <div class="progresso" id="barra-progresso"></div>
            </div>

            <div class="card-questao">
                <h2><?= htmlspecialchars($questao['enunciado']) ?></h2>
                <div class="alternativas-list" data-id-questao="<?= htmlspecialchars($questao['id_questao']) ?>">
                    <?php foreach ($alternativas as $alternativa): ?>
                        <label 
                            for="alt-<?= htmlspecialchars($alternativa['id_alternativa']) ?>"
                            data-id-alternativa="<?= htmlspecialchars($alternativa['id_alternativa']) ?>"
                            data-correta="<?= $alternativa['correta'] == 1 ? 'true' : 'false' ?>">
                            <input type="radio" id="alt-<?= htmlspecialchars($alternativa['id_alternativa']) ?>" name="resposta" value="<?= htmlspecialchars($alternativa['id_alternativa']) ?>">
                            <?= htmlspecialchars($alternativa['texto']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mensagem-status" id="feedback-mensagem"></div>
            <button id="botao-proxima" class="btn btn-primary botao-proxima-questao">Próxima Questão</button>

        <?php else: ?>
            <p>Nenhuma questão encontrada para este assunto. Por favor, adicione mais questões ou volte para a página inicial.</p>
        <?php endif; ?>
        
        <div class="actions-right">
            <a href="index.php?novo=1" class="btn btn-outline">Voltar aos Assuntos</a>
        </div>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
    
    <script src="quiz.js"></script>
</body>
</html>