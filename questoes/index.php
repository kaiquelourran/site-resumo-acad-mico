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
            <div class="card fade-in">
                <span class="card-icon">🎯</span>
                <h3 class="card-title">Fazer Questões</h3>
                <p class="card-description">Teste seus conhecimentos</p>
                <a href="escolher_assunto.php" class="btn">Iniciar Questões</a>
            </div>

            <?php if ($_SESSION['user_type'] === 'admin'): ?>
            <!-- Card Gerenciar - Apenas para Admins -->
            <div class="card fade-in">
                <span class="card-icon">📋</span>
                <h3 class="card-title">Gerenciar Questões</h3>
                <p class="card-description">Visualize, edite e organize todas as questões do sistema de forma prática.</p>
                <a href="gerenciar_questoes_sem_auth.php" class="btn">Gerenciar</a>
            </div>


            <?php endif; ?>
        </div>

        <?php if ($_SESSION['user_type'] === 'admin'): ?>
        <div style="margin-top: 50px;">
            <h2 style="text-align: center; margin-bottom: 30px; color: #333; font-size: 2em;">🔧 Área Administrativa</h2>
            <div class="cards-container">
                <div class="card fade-in" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <span class="card-icon">👨‍💼</span>
                    <h3 class="card-title">Dashboard Admin</h3>
                    <p class="card-description">Acesse o painel administrativo completo do sistema.</p>
                    <a href="admin/dashboard.php" class="btn" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Dashboard</a>
                </div>

                <div class="card fade-in" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                    <span class="card-icon">📝</span>
                    <h3 class="card-title">Adicionar Assunto</h3>
                    <p class="card-description">Crie novos assuntos para organizar as questões.</p>
                    <a href="admin/add_assunto.php" class="btn" style="background: rgba(255,255,255,0.2); border: 2px solid white;">Novo Assunto</a>
                </div>

                <div class="card fade-in" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                    <span class="card-icon">❓</span>
                    <h3 class="card-title">Adicionar Questão</h3>
                    <p class="card-description">Interface administrativa para criar questões completas.</p>
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