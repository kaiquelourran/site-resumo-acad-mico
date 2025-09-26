<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Redireciona para a página de login se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit();
}

// Busca todos os assuntos para listar
$stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
$assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);

// Define as variáveis de sessão para os dados do usuário
$logado = isset($_SESSION['id_usuario']);
$nome_usuario = $logado ? $_SESSION['nome_usuario'] : '';
$tipo_usuario = $logado ? $_SESSION['tipo_usuario'] : ''; // Obtém o tipo de usuário

// Adiciona a proteção CSRF, se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ----- CÓDIGO DO RANKING -----
try {
    $stmt_ranking = $pdo->query("
        SELECT
            u.nome AS nome_usuario,
            COUNT(*) AS questoes_respondidas,
            SUM(r.acertou) AS questoes_corretas
        FROM
            respostas_usuarios r
        JOIN
            usuarios u ON r.id_usuario = u.id_usuario
        GROUP BY
            u.id_usuario
        ORDER BY
            questoes_corretas DESC, questoes_respondidas DESC
        LIMIT 10;
    ");
    $ranking = $stmt_ranking->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $ranking_status = "Não foi possível carregar o ranking.";
}
// ----- FIM DO CÓDIGO DO RANKING -----

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questões - Escolha o Assunto</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
    <style>
        /* Ajustes no cabeçalho para alinhamento e design */
        header {
            display: flex;
            justify-content: space-between; /* Alinha os elementos nas extremidades */
            align-items: center;
            padding: 10px 20px;
            /* A partir daqui, são os seus estilos originais */
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
            color: #ffffff; /* Cor branca para links de login */
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .login-area a:hover {
            transform: translateY(-2px);
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .user-greeting, .separator {
            font-size: 1em;
            color: #ffffff; /* Cor branca para o texto de saudação e separador */
        }
        
        /* Estilos do conteúdo principal (mantidos como antes) */
        .conteudo-principal {
            max-width: 900px;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.432);
            border-radius: 10px;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        .lista-assuntos {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .lista-assuntos li {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            text-align: left;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .lista-assuntos li:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #0056b3;
        }
        
        .lista-assuntos a {
            text-decoration: none;
            font-size: 1.3em;
            color: #0056b3;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }
        
        .lista-assuntos a:hover {
            color: #003d82;
        }
        
        .btn-excluir {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: all 0.3s ease;
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .btn-excluir:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Media Query para desktop e tablets maiores */
        @media (min-width: 769px) {
            .conteudo-principal {
                margin: 173px auto 80px auto;
            }
        }
        
        /* Media Query para telas menores (celulares e tablets) */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                min-height: auto;
                padding: 15px 5px;
            }
            .login-area {
                flex-direction: column;
                align-items: center;
                gap: 5px;
                margin-top: 10px;
            }
            .conteudo-principal {
                margin: 203px auto 80px auto;
                padding: 15px;
            }
            .ranking-table {
                font-size: 0.9em;
            }
        }
        
        /* Media Query para telas muito pequenas */
        @media (max-width: 480px) {
            header h1 {
                font-size: 1.3em;
            }
            .login-area {
                font-size: 0.9em;
            }
            .conteudo-principal {
                margin: 180px auto 80px auto;
                padding: 10px;
            }
            .ranking-table th, .ranking-table td {
                padding: 6px;
                font-size: 0.8em;
            }
        }

        /* ----- ESTILOS DO RANKING (NOVOS) ----- */
        .ranking-container {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .ranking-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .ranking-table th, .ranking-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .ranking-table th {
            background-color: #eee;
        }
        .ranking-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .ranking-table td:first-child {
            font-weight: bold;
            text-align: center;
        }
        .ranking-table tbody tr:first-child {
            background-color: #ffeb3b; /* Cor para o primeiro lugar */
        }
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
    </style>
</head>
<body>
    <header>
        <h1>Bem-vindo <?= htmlspecialchars($nome_usuario) ?>!</h1>
        <div class="login-area">
            <?php if ($logado): ?>
                <a href="perfil_usuario.php">Meu Desempenho</a> 
                <?php if ($tipo_usuario == 'admin'): ?>
                    <span class="separator">|</span>
                    <a href="admin/dashboard.php">Painel Admin</a>
                <?php endif; ?>
                <span class="separator">|</span>
                <a href="logout.php" class="logout-link">Sair</a>
                <span class="separator">|</span>
                <a href="../index.html">Voltar ao Site</a>
            <?php else: ?>
                <a href="login.php" class="login-link">Login</a>
                <span class="separator">|</span>
                <a href="cadastro.php" class="cadastro-link">Cadastro</a>
                <span class="separator">|</span>
                <a href="../index.html">Voltar ao Site</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="conteudo-principal">
        <div class="ranking-container">
            <h2>Ranking de Usuários (Top 10)</h2>
            <?php if (isset($ranking_status)): ?>
                <p><?= htmlspecialchars($ranking_status) ?></p>
            <?php elseif (empty($ranking)): ?>
                <p>Nenhum usuário no ranking ainda. Seja o primeiro a responder!</p>
            <?php else: ?>
                <table class="ranking-table">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Usuário</th>
                            <th>Acertos</th>
                            <th>Perguntas Respondidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $posicao = 1; ?>
                        <?php foreach ($ranking as $usuario_rank): ?>
                            <tr>
                                <td><?= $posicao++ ?>º</td>
                                <td><?= htmlspecialchars($usuario_rank['nome_usuario']) ?></td>
                                <td><?= htmlspecialchars($usuario_rank['questoes_corretas']) ?></td>
                                <td><?= htmlspecialchars($usuario_rank['questoes_respondidas']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Seção de Filtros (preparada para futuras funcionalidades) -->
        <div class="filtros-container" style="margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; border: 1px solid #dee2e6;">
            <h3 style="margin-bottom: 15px; color: #495057;">Filtrar Assuntos</h3>
            <div class="filtros-opcoes" style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                <button class="btn-filtro" data-filtro="todos" style="background: #0056b3; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">Todos</button>
                <button class="btn-filtro" data-filtro="dificil" style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">Difíceis</button>
                <button class="btn-filtro" data-filtro="facil" style="background: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">Fáceis</button>
                <button class="btn-filtro" data-filtro="recentes" style="background: #17a2b8; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer;">Recentes</button>
            </div>
        </div>
        
        <p>Selecione um assunto para começar.</p>
        <?php if (!empty($assuntos)): ?>
            <ul class="lista-assuntos">
                <?php foreach ($assuntos as $assunto): ?>
                    <li>
                        <a href="listar_questoes.php?id=<?= htmlspecialchars($assunto['id_assunto']) ?>">
                            <?= htmlspecialchars($assunto['nome']) ?>
                        </a>
                        <?php if ($tipo_usuario == 'admin'): ?>
                            <form action="admin/excluir_assunto.php" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este assunto?');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($assunto['id_assunto']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nenhum assunto encontrado.</p>
        <?php endif; ?>
    </main>
    
    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>