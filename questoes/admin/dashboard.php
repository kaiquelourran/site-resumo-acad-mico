<?php
session_start();

// Verifica se o usuário está logado E se ele tem o tipo 'admin'
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Incluir o arquivo de conexão
require_once __DIR__ . '/../conexao.php';

// --- Consultas para métricas gerais do site ---
// Total de usuários cadastrados
$stmt_usuarios = $pdo->query("SELECT COUNT(*) AS total_usuarios FROM usuarios");
$total_usuarios = $stmt_usuarios->fetch(PDO::FETCH_ASSOC)['total_usuarios'];

// Total de questões respondidas por todos os usuários
$stmt_respostas_geral = $pdo->query("SELECT COUNT(*) AS total_respostas_geral FROM respostas_usuarios");
$total_respostas_geral = $stmt_respostas_geral->fetch(PDO::FETCH_ASSOC)['total_respostas_geral'];

// NOVO: Contagem de usuários que fizeram login hoje
$stmt_usuarios_hoje = $pdo->query("SELECT COUNT(DISTINCT id_usuario) AS usuarios_hoje FROM usuarios WHERE DATE(ultimo_login) = CURDATE()");
$usuarios_hoje = $stmt_usuarios_hoje->fetch(PDO::FETCH_ASSOC)['usuarios_hoje'];

// NOVO: Contagem de usuários que fizeram login na última semana
$stmt_usuarios_semana = $pdo->query("SELECT COUNT(DISTINCT id_usuario) AS usuarios_semana FROM usuarios WHERE ultimo_login >= CURDATE() - INTERVAL 7 DAY");
$usuarios_semana = $stmt_usuarios_semana->fetch(PDO::FETCH_ASSOC)['usuarios_semana'];

// NOVO: Contagem de usuários que fizeram login no último mês
$stmt_usuarios_mes = $pdo->query("SELECT COUNT(DISTINCT id_usuario) AS usuarios_mes FROM usuarios WHERE ultimo_login >= CURDATE() - INTERVAL 30 DAY");
$usuarios_mes = $stmt_usuarios_mes->fetch(PDO::FETCH_ASSOC)['usuarios_mes'];

// Métricas adicionais de conteúdo e desempenho
// Acerto por assunto
$sql_acerto_por_assunto = "
    SELECT a.nome AS assunto,
           SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) AS acertos,
           COUNT(*) AS total,
           ROUND(SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 0) AS taxa
    FROM respostas_usuarios r
    JOIN questoes q ON q.id_questao = r.id_questao
    JOIN assuntos a ON a.id_assunto = q.id_assunto
    GROUP BY a.id_assunto
    ORDER BY taxa ASC
    LIMIT 5";
$assuntos_mais_dificeis = $pdo->query($sql_acerto_por_assunto)->fetchAll(PDO::FETCH_ASSOC);

// Questões mais erradas (top 5)
$sql_mais_erradas = "
    SELECT q.id_questao, LEFT(q.enunciado, 80) AS enunciado,
           SUM(CASE WHEN r.acertou = 0 THEN 1 ELSE 0 END) AS erros,
           COUNT(*) AS total,
           ROUND(SUM(CASE WHEN r.acertou = 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 0) AS taxa_erro
    FROM respostas_usuarios r
    JOIN questoes q ON q.id_questao = r.id_questao
    GROUP BY q.id_questao
    HAVING total >= 3
    ORDER BY taxa_erro DESC, erros DESC
    LIMIT 5";
$questoes_mais_erradas = $pdo->query($sql_mais_erradas)->fetchAll(PDO::FETCH_ASSOC);

// Buckets de dificuldade por taxa de acerto
$sql_buckets = "
    SELECT
      SUM(CASE WHEN sub.taxa < 40 THEN 1 ELSE 0 END) AS dificeis,
      SUM(CASE WHEN sub.taxa BETWEEN 40 AND 70 THEN 1 ELSE 0 END) AS medias,
      SUM(CASE WHEN sub.taxa > 70 THEN 1 ELSE 0 END) AS faceis
    FROM (
      SELECT q.id_questao,
             ROUND(AVG(r.acertou)*100,0) AS taxa
      FROM respostas_usuarios r
      JOIN questoes q ON q.id_questao = r.id_questao
      GROUP BY q.id_questao
    ) sub";
$buckets = $pdo->query($sql_buckets)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .conteudo-principal {
            text-align: center;
            padding: 20px;
        }
        .conteudo-principal h2 {
            margin-top: 10px;
        }
        .botoes-admin {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 20px 0;
        }
        
        .botoes-admin .btn {
            flex: 1;
            min-width: 150px;
            max-width: 200px;
        }
        .dashboard-geral {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        .cards-inline { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; margin-top: 20px; }
        .mini-card { background:#fff; border-radius:8px; padding:14px 18px; box-shadow:0 2px 6px rgba(0,0,0,0.08); min-width:220px; }
        .mini-card h4 { margin:0 0 8px 0; font-size:1em; color:#333; }
        .mini-card ul { list-style:none; padding:0; margin:0; text-align:left; }
        .mini-card ul li { margin: 6px 0; font-size:0.95em; }
        .dashboard-info {
            display: flex;
            justify-content: center;
            flex-wrap: wrap; /* Adicionado para quebrar a linha se a tela for pequena */
            gap: 20px;
        }
        .info-card {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            flex: 1; /* Adicionado para que os cards ocupem espaço igual */
            min-width: 200px; /* Garante que os cards não fiquem muito pequenos */
        }
        .info-card h3 {
            margin-bottom: 5px;
            color: #333;
        }
        .info-card p {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>
    <header>
        <h1>Painel de Administração</h1>
        <p>Bem-vindo, <?= htmlspecialchars($_SESSION['nome_usuario']) ?>!</p>
        <div class="login-area">
            <a href="../perfil_usuario.php">Meu Desempenho</a>
            <a href="../logout.php" class="logout-link" aria-label="Sair da conta">Sair</a>
        </div>
    </header>

    <main class="conteudo-principal">
        <h2>Gerenciamento de Conteúdo</h2>
        <div class="botoes-admin">
            <a href="gerenciar_questoes.php" class="btn btn-primary">Gerenciar Questões</a>
            <a href="add_questao.php" class="btn btn-success">Adicionar Questão</a>
            <a href="add_assunto.php" class="btn btn-secondary">Adicionar Assunto</a>
            <a href="../index.php" class="btn btn-outline">Voltar ao Site</a>
            <a href="login.php?logout=1" class="btn btn-danger">Sair</a>
        </div>
        
        <div class="dashboard-geral">
            <h2>Métricas Gerais do Site</h2>
            <div class="dashboard-info">
                <div class="info-card">
                    <h3>Usuários Cadastrados</h3>
                    <p><?= htmlspecialchars($total_usuarios) ?></p>
                </div>
                <div class="info-card">
                    <h3>Questões Respondidas</h3>
                    <p><?= htmlspecialchars($total_respostas_geral) ?></p>
                </div>
                <div class="info-card">
                    <h3>Logins Hoje</h3>
                    <p><?= htmlspecialchars($usuarios_hoje) ?></p>
                </div>
                <div class="info-card">
                    <h3>Logins Última Semana</h3>
                    <p><?= htmlspecialchars($usuarios_semana) ?></p>
                </div>
                <div class="info-card">
                    <h3>Logins Último Mês</h3>
                    <p><?= htmlspecialchars($usuarios_mes) ?></p>
                </div>
            </div>
        </div>

        <div class="cards-inline">
            <div class="mini-card">
                <h4>Assuntos mais difíceis (taxa de acerto)</h4>
                <ul>
                    <?php if (!empty($assuntos_mais_dificeis)): foreach ($assuntos_mais_dificeis as $row): ?>
                        <li><?= htmlspecialchars($row['assunto']) ?> — <?= htmlspecialchars($row['taxa']) ?>%</li>
                    <?php endforeach; else: ?>
                        <li>Sem dados suficientes.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="mini-card">
                <h4>Distribuição de dificuldade</h4>
                <ul>
                    <li>Difíceis (< 40%): <?= htmlspecialchars($buckets['dificeis'] ?? 0) ?></li>
                    <li>Médias (40–70%): <?= htmlspecialchars($buckets['medias'] ?? 0) ?></li>
                    <li>Fáceis (> 70%): <?= htmlspecialchars($buckets['faceis'] ?? 0) ?></li>
                </ul>
            </div>
            <div class="mini-card">
                <h4>Questões mais erradas</h4>
                <ul>
                    <?php if (!empty($questoes_mais_erradas)): foreach ($questoes_mais_erradas as $q): ?>
                        <li>#<?= htmlspecialchars($q['id_questao']) ?> — <?= htmlspecialchars($q['taxa_erro']) ?>% erros</li>
                    <?php endforeach; else: ?>
                        <li>Sem dados suficientes.</li>
                    <?php endif; ?>
                </ul>
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