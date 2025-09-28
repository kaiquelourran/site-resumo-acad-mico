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
    <link rel="stylesheet" href="modern-style.css">
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">🏆</div>
            <h1 class="title">Resultado das Questões</h1>
            <p class="subtitle">Veja seu desempenho</p>
        </div>
        
        <div class="user-info">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="perfil_usuario.php" class="user-link">👤 Meu Desempenho</a>
                <a href="logout.php" class="user-link">🚪 Sair</a>
                <a href="index.php" class="user-link">🏠 Menu Principal</a>
            <?php else: ?>
                <a href="login.php" class="user-link">🔐 Login</a>
                <a href="cadastro.php" class="user-link">📝 Cadastro</a>
                <a href="index.php" class="user-link">🏠 Menu Principal</a>
            <?php endif; ?>
        </div>
        
        <div class="stats-container">
            <div class="stat-card slide-in-left">
                <div class="stat-number"><?= htmlspecialchars($acertos) ?></div>
                <div class="stat-label">Acertos</div>
            </div>
            <div class="stat-card slide-in-up">
                <div class="stat-number"><?= htmlspecialchars($numero_de_questoes_por_quiz) ?></div>
                <div class="stat-label">Total de Questões</div>
            </div>
            <div class="stat-card slide-in-right">
                <div class="stat-number"><?= htmlspecialchars(number_format($porcentagem_acertos, 0)) ?>%</div>
                <div class="stat-label">Aproveitamento</div>
            </div>
        </div>
        
        <div style="text-align: center; margin: 40px 0;">
            <?php if ($porcentagem_acertos >= 80): ?>
                <div class="alert alert-success fade-in">
                    🏆 <?= htmlspecialchars($mensagem_final) ?>
                </div>
            <?php elseif ($porcentagem_acertos >= 50): ?>
                <div class="alert alert-info fade-in">
                    👍 <?= htmlspecialchars($mensagem_final) ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning fade-in">
                    📚 <?= htmlspecialchars($mensagem_final) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="index.php?novo=1" class="btn" style="margin: 10px;">🔄 Tentar Novamente</a>
            <a href="index.php" class="btn btn-secondary" style="margin: 10px;">🏠 Voltar ao Menu</a>
        </div>
    </div>
</body>
</html>