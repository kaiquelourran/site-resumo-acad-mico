<?php
// Header padrão para o sistema de questões
// Configuração de breadcrumb padrão se não foi definida
if (!isset($breadcrumb_items)) {
    $breadcrumb_items = [
        ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false]
    ];
}

// Configuração do título da página se não foi definida
if (!isset($page_title)) {
    $page_title = 'Sistema de Questões';
}

// Configuração do subtítulo se não foi definida
if (!isset($page_subtitle)) {
    $page_subtitle = 'Resumo Acadêmico - Terapia Ocupacional';
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Header Moderno Redesenhado -->
<header class="header">
    <div class="header-container header-topbar">
        <!-- Logo Section - Novo Design -->
        <div class="logo-section">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="brand-info">
                <a href="index.php" class="brand-title">Resumo Acadêmico</a>
                <span class="brand-subtitle">Terapia Ocupacional</span>
            </div>
        </div>

        <!-- Navigation - Redesenhada -->
        <nav class="header-nav">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Início</span>
            </a>
            <a href="escolher_assunto.php" class="nav-link">
                <i class="fas fa-book"></i>
                <span>Questões</span>
            </a>
            <a href="desempenho.php" class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Desempenho</span>
            </a>
        </nav>


        <!-- User Info Section - Novo Design -->
        <div class="user-info">
            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['usuario_nome'], 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                </div>
                <a href="logout.php" class="header-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            <?php else: ?>
                <a href="login.php" class="header-btn primary">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Entrar</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Breadcrumb Navigation - Redesenhado -->
    <?php if (!empty($breadcrumb_items)): ?>
    <div class="breadcrumb">
        <div class="header-container">
            <nav class="breadcrumb-nav">
                <?php foreach ($breadcrumb_items as $index => $item): ?>
                    <?php if ($index > 0): ?>
                        <i class="fas fa-chevron-right breadcrumb-separator"></i>
                    <?php endif; ?>
                    
                    <?php if (isset($item['current']) && $item['current']): ?>
                        <span class="breadcrumb-current">
                            <?php echo $item['icon'] . ' ' . htmlspecialchars($item['text']); ?>
                        </span>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" class="breadcrumb-link">
                            <?php echo $item['icon'] . ' ' . htmlspecialchars($item['text']); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page Title Section - Redesenhado -->
    <?php if (!empty($page_title)): ?>
    <div class="page-header">
        <div class="header-container">
            <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php if (!empty($page_subtitle)): ?>
                <p class="page-subtitle"><?php echo htmlspecialchars($page_subtitle); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</header>

<div class="main-container">
    <main>