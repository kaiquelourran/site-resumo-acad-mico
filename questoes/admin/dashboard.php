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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Resumo Acadêmico</title>
    <link rel="stylesheet" href="../modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* Padrão visual alinhado ao index.php */
    body {
        background-image: linear-gradient(to top, #00C6FF, #0072FF);
        min-height: 100vh;
        margin: 0;
    }
    .main-container {
        max-width: 1100px;
        margin: 40px auto;
        background: #FFFFFF;
        border-radius: 16px;
        border: 1px solid transparent;
        background-image: linear-gradient(#FFFFFF, #FFFFFF), linear-gradient(to top, #00C6FF, #0072FF);
        background-origin: border-box;
        background-clip: padding-box, border-box;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        padding: 30px;
    }
    .user-info a { text-decoration: none; font-weight: 600; }
    .user-info a:hover { text-decoration: none; }
    /* Botões de navegação aprimorados */
    .nav-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%); color: #fff; border: 1px solid #bfe0ff; font-weight: 800; text-decoration: none; box-shadow: 0 8px 18px rgba(0,114,255,0.28); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; letter-spacing: .2px; }
    .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(0,114,255,0.32); filter: brightness(1.03); }
    .nav-btn:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
    .nav-btn.secondary { background: linear-gradient(180deg, #6c757d 0%, #495057 100%); border-color: #adb5bd; box-shadow: 0 8px 18px rgba(108,117,125,0.28); }
    .nav-btn.secondary:hover { box-shadow: 0 12px 26px rgba(108,117,125,0.32); }
    .nav-btn.danger { background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); border-color: #dc3545; box-shadow: 0 8px 18px rgba(220,53,69,0.28); }
    .nav-btn.danger:hover { box-shadow: 0 12px 26px rgba(220,53,69,0.32); }
    /* Cards */
    .card { background: #FFFFFF; border: 1px solid #e1e5e9; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .card-title { color: #333333; margin-bottom: 16px; }
    .card-description { color: #666666; }
    .btn { display: inline-block; padding: 12px 18px; border-radius: 8px; background: linear-gradient(to top, #00C6FF, #0072FF); color: #fff; border: none; font-weight: 600; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,114,255,0.3); }
    .btn:active { transform: translateY(0); }
    .btn:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
    .btn[aria-busy="true"] { cursor: wait; opacity: .8; }
    .btn-primary { background: linear-gradient(to top, #007bff, #0056b3); }
    .btn-success { background: linear-gradient(to top, #28a745, #1e7e34); }
    .btn-warning { background: linear-gradient(to top, #ffc107, #e0a800); color: #212529; }
    .btn-danger { background: linear-gradient(to top, #dc3545, #c82333); }
    .btn-secondary { background: linear-gradient(to top, #6c757d, #545b62); }
    .btn-outline { background: transparent; border: 2px solid #0072FF; color: #0072FF; }
    .btn-outline:hover { background: #0072FF; color: #fff; }
    /* Garantir que os botões sejam clicáveis */
    .btn, .nav-btn { 
        cursor: pointer; 
        user-select: none; 
            position: relative;
        z-index: 10;
        display: inline-block;
        text-decoration: none;
    }
    .btn:disabled, .nav-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn:disabled:hover, .nav-btn:disabled:hover { transform: none; box-shadow: none; }
    /* Garantir que os links não tenham problemas de sobreposição */
    .buttons-grid a, .user-actions a {
        position: relative;
        z-index: 10;
        pointer-events: auto;
    }
    /* Estatísticas */
    .stats-container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
    .stat-card { background: #FFFFFF; border: 1px solid #e1e5e9; border-radius: 12px; padding: 20px; min-width: 200px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .stat-number { color: #0072FF; font-weight: 700; font-size: 2rem; }
    .stat-label { color: #333; }
    .stat-card h3 { color: #333; margin-bottom: 10px; font-size: 1rem; }
    /* Seções */
    .admin-section, .metrics-section, .analytics-section { margin-bottom: 40px; }
    .admin-section h2, .metrics-section h2 { color: #333; margin-bottom: 20px; font-size: 1.5rem; }
    .buttons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
    .cards-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .analytics-list { list-style: none; padding: 0; margin: 0; }
    .analytics-list li { padding: 8px 0; border-bottom: 1px solid #f0f0f0; color: #666; }
    .analytics-list li:last-child { border-bottom: none; }
    /* Header estiloso */
    .app-header { background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%); border-radius: 16px; padding: 18px; color: #fff; box-shadow: 0 12px 30px rgba(0,114,255,0.25); margin-bottom: 24px; position: relative; }
    .app-header .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .app-header .brand { display: flex; align-items: center; gap: 12px; }
    .app-header .logo { font-size: 1.8rem; }
    .app-header .titles .title { margin: 0; color: #fff; font-size: 1.8rem; }
    .app-header .titles .subtitle { margin: 2px 0 0; color: #eaf6ff; font-size: 1rem; }
    .app-header .user-actions { display: flex; align-items: center; gap: 12px; }
    .header-nav { position: absolute; top: 18px; left: 18px; z-index: 2; }
    .btn-back { background: rgba(255, 255, 255, 0.2); color: white; border: 2px solid rgba(255, 255, 255, 0.3); padding: 10px 20px; border-radius: 25px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px); }
    .btn-back:hover { background: rgba(255, 255, 255, 0.3); border-color: rgba(255, 255, 255, 0.5); transform: translateY(-2px); }
    /* Footer */
    .footer { text-align: center; padding: 20px; color: #666; border-top: 1px solid #e9ecef; margin-top: 40px; }
    /* Responsividade */
    @media (max-width: 768px) {
        html, body { overflow-x: hidden; }
        .main-container { margin: 16px auto; padding: 18px; max-width: calc(100% - 32px); }
        .app-header .header-inner { flex-direction: column; align-items: flex-start; }
        .app-header .user-actions { width: 100%; justify-content: space-between; flex-wrap: wrap; }
        .stats-container { flex-direction: column; gap: 12px; }
        .stat-card { width: 100%; }
        .buttons-grid { grid-template-columns: 1fr; }
        .cards-container { grid-template-columns: 1fr; }
        .header-nav { position: static; margin-bottom: 16px; }
    }
    @media (max-width: 480px) {
        .app-header .titles .title { font-size: 1.4rem; }
        .stat-number { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="app-header">
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
            <div class="header-inner">
                <div class="brand">
                    <span class="logo">👨‍💼</span>
                    <div class="titles">
                        <h1 class="title">Painel de Administração</h1>
                        <p class="subtitle">Bem-vindo, <?= htmlspecialchars($_SESSION['nome_usuario']) ?>!</p>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="../perfil_usuario.php" class="nav-btn secondary">📊 Meu Desempenho</a>
                    <a href="../logout.php" class="nav-btn danger">🚪 Sair</a>
            </div>
            </div>
        </div>

        <main class="content">
            <section class="admin-section">
                <h2>Gerenciamento de Conteúdo</h2>
                <div class="buttons-grid">
                    <a href="../gerenciar_questoes_sem_auth.php" class="btn btn-primary">📋 Gerenciar Questões</a>
                    <a href="gerenciar_assuntos.php" class="btn btn-primary">📚 Gerenciar Assuntos</a>
                    <a href="gerenciar_comentarios.php" class="btn btn-primary">💬 Gerenciar Comentários</a>
                    <a href="add_questao.php" class="btn btn-success">➕ Adicionar Questão</a>
                    <a href="add_assunto.php" class="btn btn-secondary">📝 Adicionar Assunto</a>
                    <a href="../index.php" class="btn btn-outline">🏠 Voltar ao Site</a>
                </div>
            </section>
            
            <section class="metrics-section">
                <h2>📊 Métricas Gerais do Site</h2>
                <div class="stats-container">
                    <div class="stat-card slide-in">
                        <h3>👥 Usuários Cadastrados</h3>
                        <div class="stat-number"><?= htmlspecialchars($total_usuarios) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>🎯 Questões Respondidas</h3>
                        <div class="stat-number"><?= htmlspecialchars($total_respostas_geral) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>📅 Logins Hoje</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_hoje) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>📆 Logins Última Semana</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_semana) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>📈 Logins Último Mês</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_mes) ?></div>
                    </div>
                </div>
            </section>

            <section class="analytics-section">
                <h2>📈 Análises e Relatórios</h2>
                <div class="cards-container">
                    <div class="card">
                        <h4>🎯 Assuntos mais difíceis (taxa de acerto)</h4>
                        <ul class="analytics-list">
                            <?php if (!empty($assuntos_mais_dificeis)): foreach ($assuntos_mais_dificeis as $row): ?>
                                <li><?= htmlspecialchars($row['assunto']) ?> — <strong><?= htmlspecialchars($row['taxa']) ?>%</strong></li>
                            <?php endforeach; else: ?>
                                <li>Sem dados suficientes.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card">
                        <h4>📊 Distribuição de dificuldade</h4>
                        <ul class="analytics-list">
                            <li>🔴 Difíceis (< 40%): <strong><?= htmlspecialchars($buckets['dificeis'] ?? 0) ?></strong></li>
                            <li>🟡 Médias (40–70%): <strong><?= htmlspecialchars($buckets['medias'] ?? 0) ?></strong></li>
                            <li>🟢 Fáceis (> 70%): <strong><?= htmlspecialchars($buckets['faceis'] ?? 0) ?></strong></li>
                        </ul>
                    </div>
                    <div class="card">
                        <h4>❌ Questões mais erradas</h4>
                        <ul class="analytics-list">
                            <?php if (!empty($questoes_mais_erradas)): foreach ($questoes_mais_erradas as $q): ?>
                                <li>#<?= htmlspecialchars($q['id_questao']) ?> — <strong><?= htmlspecialchars($q['taxa_erro']) ?>%</strong> erros</li>
                            <?php endforeach; else: ?>
                                <li>Sem dados suficientes.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p>🚀 Desenvolvido por <strong>Resumo Acadêmico</strong> &copy; 2025</p>
        </footer>
    </div>

    <script>
        // Função para voltar à página anterior
        function goBack() {
            console.log('Botão Voltar clicado');
            // Verifica se há histórico de navegação
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não há histórico, vai para a página principal
                window.location.href = '../index.php';
            }
        }

        // Adicionar event listeners para debug
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard carregado');
            
            // Verificar todos os links
            const links = document.querySelectorAll('a');
            console.log(`Total de links encontrados: ${links.length}`);
            
            links.forEach((link, index) => {
                console.log(`Link ${index + 1}: ${link.href} - Texto: ${link.textContent.trim()}`);
                
                // Verificar se o link está visível e clicável
                const rect = link.getBoundingClientRect();
                const isVisible = rect.width > 0 && rect.height > 0;
                const isClickable = getComputedStyle(link).pointerEvents !== 'none';
                
                console.log(`  - Visível: ${isVisible}, Clicável: ${isClickable}`);
                
                // Adicionar event listener para debug
                link.addEventListener('click', function(e) {
                    console.log(`Link clicado: ${this.href} - Texto: ${this.textContent.trim()}`);
                    
                    // Verificar se o link tem href válido
                    if (!this.href || this.href === '#' || this.href === 'javascript:void(0)') {
                        console.warn('Link sem href válido!');
                        e.preventDefault();
                    }
                });
            });

            // Verificar todos os botões
            const buttons = document.querySelectorAll('button');
            console.log(`Total de botões encontrados: ${buttons.length}`);
            
            buttons.forEach((button, index) => {
                console.log(`Botão ${index + 1}: ${button.textContent.trim()}`);
                
                // Adicionar event listener para debug
                button.addEventListener('click', function(e) {
                    console.log(`Botão clicado: ${this.textContent.trim()}`);
                });
            });
            
            // Teste de clique em qualquer lugar da página
            document.addEventListener('click', function(e) {
                console.log('Clique detectado em:', e.target.tagName, e.target.className);
            });
        });
    </script>
</body>
</html>