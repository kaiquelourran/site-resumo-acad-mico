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
    <title>Painel Admin - Resumo Acadêmico</title>
    <link rel="stylesheet" href="../modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header {
            position: relative;
        }
        
        .header-nav {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 2;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <header class="header">
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
            <div class="logo">
                <img src="../../fotos/Logotipo_resumo_academico.png" alt="Resumo Acadêmico">
            </div>
            <div class="title-section">
                <h1>Painel de Administração</h1>
                <p class="subtitle">Bem-vindo, <?= htmlspecialchars($_SESSION['nome_usuario']) ?>!</p>
            </div>
        </header>

        <div class="user-info">
            <a href="../perfil_usuario.php" class="btn btn-outline">Meu Desempenho</a>
            <a href="../logout.php" class="btn btn-danger">Sair</a>
        </div>

        <main class="content">
            <section class="admin-section">
                <h2>Gerenciamento de Conteúdo</h2>
                <div class="buttons-grid">
                    <a href="gerenciar_questoes_sem_auth.php" class="btn btn-primary">Gerenciar Questões</a>
                    <a href="gerenciar_assuntos.php" class="btn btn-primary">Gerenciar Assuntos</a>
                    <a href="add_questao.php" class="btn btn-success">Adicionar Questão</a>
                    <a href="add_assunto.php" class="btn btn-secondary">Adicionar Assunto</a>
                    <a href="../index.php" class="btn btn-outline">Voltar ao Site</a>
                </div>
            </section>
            
            <section class="metrics-section">
                <h2>Métricas Gerais do Site</h2>
                <div class="stats-container">
                    <div class="stat-card slide-in">
                        <h3>Usuários Cadastrados</h3>
                        <div class="stat-number"><?= htmlspecialchars($total_usuarios) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>Questões Respondidas</h3>
                        <div class="stat-number"><?= htmlspecialchars($total_respostas_geral) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>Logins Hoje</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_hoje) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>Logins Última Semana</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_semana) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>Logins Último Mês</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_mes) ?></div>
                    </div>
                </div>
            </section>

            <section class="analytics-section">
                <div class="cards-container">
                    <div class="card">
                        <h4>Assuntos mais difíceis (taxa de acerto)</h4>
                        <ul class="analytics-list">
                            <?php if (!empty($assuntos_mais_dificeis)): foreach ($assuntos_mais_dificeis as $row): ?>
                                <li><?= htmlspecialchars($row['assunto']) ?> — <?= htmlspecialchars($row['taxa']) ?>%</li>
                            <?php endforeach; else: ?>
                                <li>Sem dados suficientes.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card">
                        <h4>Distribuição de dificuldade</h4>
                        <ul class="analytics-list">
                            <li>Difíceis (< 40%): <?= htmlspecialchars($buckets['dificeis'] ?? 0) ?></li>
                            <li>Médias (40–70%): <?= htmlspecialchars($buckets['medias'] ?? 0) ?></li>
                            <li>Fáceis (> 70%): <?= htmlspecialchars($buckets['faceis'] ?? 0) ?></li>
                        </ul>
                    </div>
                    <div class="card">
                        <h4>Questões mais erradas</h4>
                        <ul class="analytics-list">
                            <?php if (!empty($questoes_mais_erradas)): foreach ($questoes_mais_erradas as $q): ?>
                                <li>#<?= htmlspecialchars($q['id_questao']) ?> — <?= htmlspecialchars($q['taxa_erro']) ?>% erros</li>
                            <?php endforeach; else: ?>
                                <li>Sem dados suficientes.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </footer>
    </div>

    <script>
        // Função para voltar à página anterior
        function goBack() {
            // Verifica se há histórico de navegação
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não há histórico, vai para a página principal
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>