<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirecionar para página de login
    header('Location: login.php');
    exit;
}

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Questões - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
    /* Padrão visual alinhado ao login */
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
    .header .title { color: #333333; }
    .header .subtitle { color: #333333; }
    .user-info a { color: #0072FF; text-decoration: none; font-weight: 600; }
    .user-info a:hover { text-decoration: underline; }
    /* Estatísticas */
    .stats-container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
    .stat-card { background: #FFFFFF; border: 1px solid #e1e5e9; border-radius: 12px; padding: 20px; min-width: 200px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .stat-number { color: #0072FF; font-weight: 700; font-size: 2rem; }
    .stat-label { color: #333; }
    /* Cards */
    .cards-container { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; }
    .card { background: #FFFFFF; border: 1px solid #e1e5e9; border-radius: 12px; padding: 24px; width: 300px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .card-title { color: #333333; }
    .card-description { color: #666666; }
    .btn { display: inline-block; padding: 12px 18px; border-radius: 8px; background: linear-gradient(to top, #00C6FF, #0072FF); color: #fff; border: none; font-weight: 600; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,114,255,0.3); }
    .btn:active { transform: translateY(0); }
    /* Interações e acessibilidade */
    .btn:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
    .btn[aria-busy="true"] { cursor: wait; opacity: .8; }
    .card { transition: transform .2s ease, box-shadow .2s ease; }
    .card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,114,255,0.18); }
    .header { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .header .subtitle { font-size: 1rem; color: #555; }
    @media (max-width: 768px) {
        .main-container { margin: 20px; padding: 20px; }
        .card { width: 100%; }
    }
    /* Legibilidade em cards com gradiente */
    .card[style*="linear-gradient"] .card-title { color: #fff; }
    .card[style*="linear-gradient"] .card-description { color: #f8f9fa; }
    .card[style*="linear-gradient"] .btn { box-shadow: 0 6px 16px rgba(0,114,255,0.35); }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">🎓</div>
            <h1 class="title">Sistema de Questões</h1>
            <p class="subtitle">Resumo Acadêmico - Terapia Ocupacional</p>
            <div class="user-info">
                <span>
                    Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                    (<?php echo $_SESSION['user_type'] === 'admin' ? 'Administrador' : 'Usuário'; ?>)
                </span>
                <a href="logout.php">Sair</a>
            </div>
        </div>

        <?php
        // Buscar estatísticas do sistema
        try {
            $stmt_assuntos = $pdo->query("SELECT COUNT(*) as total FROM assuntos");
            $total_assuntos = $stmt_assuntos->fetch()['total'];
            
            $stmt_questoes = $pdo->query("SELECT COUNT(*) as total FROM questoes");
            $total_questoes = $stmt_questoes->fetch()['total'];
            
            $stmt_alternativas = $pdo->query("SELECT COUNT(*) as total FROM alternativas");
            $total_alternativas = $stmt_alternativas->fetch()['total'];
        } catch (Exception $e) {
            $total_assuntos = 0;
            $total_questoes = 0;
            $total_alternativas = 0;
        }
        ?>

        <div class="stats-container">
            <div class="stat-card slide-in-right">
                <div class="stat-number"><?php echo $total_assuntos; ?></div>
                <div class="stat-label">Assuntos</div>
            </div>
            <div class="stat-card slide-in-right">
                <div class="stat-number"><?php echo $total_questoes; ?></div>
                <div class="stat-label">Questões</div>
            </div>
            <div class="stat-card slide-in-right">
                <div class="stat-number"><?php echo $total_alternativas; ?></div>
                <div class="stat-label">Alternativas</div>
            </div>
        </div>

        <div class="cards-container">
            <!-- Card Questões -->
            <div class="card fade-in" style="background: linear-gradient(to top, #00C6FF, #0072FF); color: white;">
                <span class="card-icon">🎯</span>
                <h3 class="card-title" style="color:#fff;">Fazer Questões</h3>
                <p class="card-description" style="color:#f8f9fa;">Teste seus conhecimentos</p>
                <a href="escolher_assunto.php" class="btn" style="box-shadow:0 6px 16px rgba(0,114,255,0.35);">Iniciar Questões</a>
            </div>

            <?php if ($_SESSION['user_type'] === 'admin'): ?>
            <!-- Card Gerenciar - Apenas para Admins -->
            <div class="card fade-in" style="background: linear-gradient(to top, #00C6FF, #0072FF); color: white;">
                <span class="card-icon">📋</span>
                <h3 class="card-title" style="color:#fff;">Gerenciar Questões</h3>
                <p class="card-description" style="color:#f8f9fa;">Visualize, edite e organize todas as questões do sistema de forma prática.</p>
                <a href="gerenciar_questoes_sem_auth.php" class="btn" style="box-shadow:0 6px 16px rgba(0,114,255,0.35);">Gerenciar</a>
            </div>


            <?php endif; ?>
        </div>

        <?php if ($_SESSION['user_type'] === 'admin'): ?>
        <div style="margin-top: 50px;">
            <h2 style="text-align: center; margin-bottom: 30px; color: #333; font-size: 2em;">🔧 Área Administrativa</h2>
            <div class="cards-container">
                <div class="card fade-in" style="background: linear-gradient(to top, #00C6FF, #0072FF); color: white;">
                    <span class="card-icon">👨‍💼</span>
                    <h3 class="card-title" style="color:#fff;">Dashboard Admin</h3>
                    <p class="card-description" style="color:#f8f9fa;">Acesse o painel administrativo completo do sistema.</p>
                    <a href="admin/dashboard.php" class="btn" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Dashboard</a>
                </div>

                <div class="card fade-in" style="background: linear-gradient(to top, #00C6FF, #0072FF); color: white;">
                    <span class="card-icon">📝</span>
                    <h3 class="card-title" style="color:#fff;">Adicionar Assunto</h3>
                    <p class="card-description" style="color:#f8f9fa;">Crie novos assuntos para organizar as questões.</p>
                    <a href="admin/add_assunto.php" class="btn" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Novo Assunto</a>
                </div>

                <div class="card fade-in" style="background: linear-gradient(to top, #00C6FF, #0072FF); color: white;">
                    <span class="card-icon">❓</span>
                    <h3 class="card-title" style="color:#fff;">Adicionar Questão</h3>
                    <p class="card-description" style="color:#f8f9fa;">Interface administrativa para criar questões completas.</p>
                    <a href="admin/add_questao.php" class="btn" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Nova Questão</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center; margin-top: 50px; padding: 40px; background: rgba(102, 126, 234, 0.1); border-radius: 16px;">
            <h2 style="color: #667eea; margin-bottom: 15px;">🔒 Área Administrativa</h2>
            <p style="color: #666; font-size: 1.1em;">Área restrita para administradores</p>
            <p style="color: #888;">Faça login como administrador para acessar essas funcionalidades</p>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 50px; padding: 30px; color: #666; border-top: 2px solid #f0f0f0;">
            <p style="font-size: 1.1em; margin-bottom: 5px;">&copy; 2024 Resumo Acadêmico - Sistema de Questões</p>
            <p style="color: #888;">Desenvolvido para Terapia Ocupacional</p>
        </div>
    </div>
</body>
</html>