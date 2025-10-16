<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Buscar todos os assuntos que têm questões
$sql = "SELECT a.id_assunto, a.nome, COUNT(q.id_questao) as total_questoes 
        FROM assuntos a 
        INNER JOIN questoes q ON a.id_assunto = q.id_assunto 
        GROUP BY a.id_assunto, a.nome 
        ORDER BY a.nome";

$result = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Assunto - Questões</title>
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="modern-style.css">
    <style>
        /* Container principal dos assuntos */
        .assuntos-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 20px;
        }
        
        /* Barra de ferramentas para filtro/busca */
        .subjects-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }
        .search-input {
            flex: 1;
            padding: 12px 14px;
            border: 1px solid #e9eef3;
            border-radius: 10px;
            background: #fff;
            color: #222;
            font-size: 0.95rem;
            transition: box-shadow .2s ease, border-color .2s ease;
        }
        .search-input::placeholder { color: #999; }
        .search-input:focus {
            outline: none;
            border-color: #0072FF;
            box-shadow: 0 0 0 4px rgba(0,114,255,0.18);
        }

        /* Grid responsivo de assuntos */
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }
        
        /* Card de assunto - paleta azul padronizada */
        .assunto-card {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            cursor: pointer;
            border: 1px solid #e9eef3;
        }
        .assunto-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 26px rgba(0,114,255,0.18);
            border-color: #0072FF;
        }
        .assunto-card:focus {
            outline: 3px solid rgba(0,114,255,0.35);
            outline-offset: 2px;
        }
        
        .assunto-titulo {
            font-size: 1.2rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 8px;
        }
        
        .assunto-info {
            color: #666;
            font-size: 0.95rem;
        }
        
        .questoes-count {
            background: linear-gradient(90deg, #00C6FF 0%, #0072FF 100%);
            color: #FFFFFF;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
        }
        
        .voltar-btn {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: #FFFFFF;
            padding: 12px 18px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            box-shadow: 0 8px 18px rgba(0,114,255,0.28);
            font-weight: 700;
        }
        .voltar-btn:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 12px 26px rgba(0,114,255,0.32); filter: brightness(1.03); }
        .voltar-btn:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
        
        /* Header padrão herdado do global (modern-style.css) — sem overrides locais para manter padronização do index.php */
        /* Removidos estilos locais de .page-header, breadcrumb e user-info para seguir o padrão global */

        /* Fundo com gradiente azul na subjects-page */
        body.subjects-page {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-size: cover;
        }

        /* Header da subjects-page idêntico ao da index-page */
        .subjects-page .header .breadcrumb .header-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 10px 24px rgba(0,114,255,0.12);
            border-radius: 16px;
            padding: 14px 20px 16px 44px;
            position: relative;
        }
        .subjects-page .header .breadcrumb .header-container::before {
            content: "";
            position: absolute;
            left: 16px;
            top: 12px;
            bottom: 12px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .subjects-page .header .breadcrumb-link,
        .subjects-page .header .breadcrumb-current {
            font-size: 1.08rem;
            font-weight: 800;
            color: #111827;
            padding: 10px 14px;
            border-radius: 10px;
            background-color: #FFFFFF;
            border: 1px solid #CFE8FF;
            box-shadow: 0 1px 3px rgba(0,114,255,0.10);
        }
        .subjects-page .header .breadcrumb-current { color: #0057D9; }
        .subjects-page .header .breadcrumb-link:hover {
            background-color: #F0F7FF;
            color: #0057D9;
            border-color: #BBDDFF;
        }
        .subjects-page .header .breadcrumb-separator { color: #6B7280; font-size: 1rem; }

        .subjects-page .header .user-info {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
            margin-bottom: 0 !important;
            animation: none !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .subjects-page .header .user-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: #111827;
            font-weight: 700;
        }
        .subjects-page .header .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 0 3px 8px rgba(0,114,255,0.25);
        }
        .subjects-page .header .user-name {
            font-size: 0.92rem;
            color: #111827;
            margin: 0;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .subjects-page .header .user-name { max-width: 120px; }
        }
        @media (max-width: 480px) {
            .subjects-page .header .user-name { display: none; }
            .subjects-page .header .user-avatar { width: 26px; height: 26px; font-size: 0.85rem; }
        }

        /* Ocultar o botão Entrar na subjects-page para destacar 'Sair' */
        .subjects-page .header .header-btn.primary { display: none !important; }

        /* Estilo destacado para o botão Sair no header da subjects-page (vermelho de ação) */
        .subjects-page .header a.header-btn[href="logout.php"] {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(180deg, #ff4b5a 0%, #dc3545 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(220,53,69,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            letter-spacing: 0;
            font-size: 0.95rem;
        }
        .subjects-page .header a.header-btn[href="logout.php"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(220,53,69,0.40);
            filter: brightness(1.02);
        }
        .subjects-page .header a.header-btn[href="logout.php"]:focus {
            outline: 3px solid rgba(220,53,69,0.45);
            outline-offset: 2px;
        }
        .subjects-page .header a.header-btn[href="logout.php"]::before { content: none; }

        /* Botão 'Ir para o Site' compacto */
        .subjects-page .header a.header-btn.site-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,114,255,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            font-size: 0.95rem;
        }
        .subjects-page .header a.header-btn.site-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,114,255,0.40);
            filter: brightness(1.02);
        }
        .subjects-page .header a.header-btn.site-link:focus {
            outline: 3px solid rgba(0,114,255,0.35);
            outline-offset: 2px;
        }

        /* Destaque para o título e subtítulo da página de assuntos */
        .subjects-page .page-header .header-container {
            max-width: 1100px;
            margin: 16px auto 24px;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 12px 28px rgba(0,114,255,0.14);
            border-radius: 16px;
            padding: 18px 24px 20px 56px;
            position: relative;
        }
        .subjects-page .page-header .header-container::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 14px;
            bottom: 14px;
            width: 8px;
            border-radius: 8px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .subjects-page .page-title {
            margin: 0;
            font-size: 1.95rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: 0.2px;
        }
        .subjects-page .page-subtitle {
            margin-top: 6px;
            color: #475569;
            font-size: 1.06rem;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .subjects-page .page-title { font-size: 1.6rem; }
            .subjects-page .page-subtitle { font-size: 0.98rem; }
        }
        @media (max-width: 480px) {
            .subjects-page .page-title { font-size: 1.45rem; }
            .subjects-page .page-subtitle { font-size: 0.95rem; }
        }
        @media (max-width: 768px) {
            .subjects-page .page-title { font-size: 1.6rem; }
            .subjects-page .page-subtitle { font-size: 0.98rem; }
        }
        @media (max-width: 480px) {
            .subjects-page .page-title { font-size: 1.45rem; }
            .subjects-page .page-subtitle { font-size: 0.95rem; }
        }
    </style>
</head>
<body class="subjects-page">
<?php
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
    ['icon' => '📚', 'text' => 'Assuntos', 'link' => 'escolher_assunto.php', 'current' => true]
];
$page_title = '🎯 Escolha um Assunto';
$page_subtitle = 'Selecione o assunto que deseja estudar';
include 'header.php';
?>
    <script>
    // Ajustes de header para subjects-page, espelhando index-page
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.body.classList.contains('subjects-page')) return;
        const header = document.querySelector('.header');
        if (!header) return;
        const userInfo = header.querySelector('.user-info');
        if (!userInfo) return;
        // garantir botão Sair
        let logoutBtn = header.querySelector('a.header-btn[href="logout.php"]');
        if (!logoutBtn) {
            const a = document.createElement('a');
            a.href = 'logout.php';
            a.className = 'header-btn';
            a.setAttribute('aria-label', 'Sair da sessão');
            a.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Sair</span>';
            userInfo.appendChild(a);
        }
        // perfil do usuário
        let profile = userInfo.querySelector('.user-profile');
        <?php
        $displayNameSubjects = '';
        foreach ([
            'usuario_nome','usuario','nome','user_name','username','login','nome_usuario','nomeCompleto'
        ] as $k) {
            if (isset($_SESSION[$k]) && trim($_SESSION[$k]) !== '') { $displayNameSubjects = $_SESSION[$k]; break; }
        }
        ?>
        const userName = "<?php echo htmlspecialchars($displayNameSubjects, ENT_QUOTES, 'UTF-8'); ?>";
        if (userName) {
            if (!profile) {
                const p = document.createElement('div');
                p.className = 'user-profile';
                const avatar = document.createElement('div');
                avatar.className = 'user-avatar';
                avatar.textContent = userName.trim().charAt(0).toUpperCase() || '?';
                const nameEl = document.createElement('span');
                nameEl.className = 'user-name';
                nameEl.textContent = userName;
                p.appendChild(avatar);
                p.appendChild(nameEl);
                userInfo.insertBefore(p, userInfo.firstChild);
            }
        }
        const loginBtn = header.querySelector('a.header-btn.primary[href="login.php"]');
        if (loginBtn) loginBtn.style.display = 'none';
        let siteBtn = header.querySelector('a.header-btn.site-link');
        if (!siteBtn) {
            const s = document.createElement('a');
            s.href = '../index.html';
            s.className = 'header-btn site-link';
            s.target = '_blank';
            s.rel = 'noopener';
            s.innerHTML = '<i class="fas fa-globe"></i><span>Ir para o Site</span>';
            userInfo.appendChild(s);
        }
    });
    </script>
    <div class="container">
        <div class="assuntos-container">
            <a href="index.php" class="voltar-btn" aria-label="Voltar para Início"><span>←</span> Voltar</a>
            
            <div class="subjects-toolbar">
                <input id="search-assunto" class="search-input" type="text" placeholder="Buscar assunto..." aria-label="Buscar assunto">
            </div>

            <?php if ($result && count($result) > 0): ?>
                <div class="subjects-grid" id="assuntos-grid">
                    <?php foreach($result as $assunto): ?>
                        <article class="assunto-card" tabindex="0" role="button"
                            aria-label="Assunto: <?php echo htmlspecialchars($assunto['nome']); ?>; clique para ver as questões"
                            data-name="<?php echo mb_strtolower($assunto['nome'], 'UTF-8'); ?>"
                            onclick="window.location.href='listar_questoes.php?id=<?php echo $assunto['id_assunto']; ?>'"
                            onkeydown="if(event.key==='Enter'){ window.location.href='listar_questoes.php?id=<?php echo $assunto['id_assunto']; ?>'; }">
                            <div class="assunto-titulo"><?php echo htmlspecialchars($assunto['nome']); ?></div>
                            <div class="assunto-info">Clique para ver as questões deste assunto</div>
                            <div class="questoes-count">
                                <?php echo $assunto['total_questoes']; ?> questões disponíveis
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="assunto-card empty-state">
                    <div class="assunto-titulo">Nenhum assunto encontrado</div>
                    <div class="assunto-info">
                        Não há assuntos com questões cadastradas no sistema.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Filtro de assuntos (client-side) mantendo lógica de servidor intacta
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('search-assunto');
        if (!input) return;
        input.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('.subjects-grid .assunto-card').forEach(function(card){
                var name = (card.getAttribute('data-name') || '').toLowerCase();
                card.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    });
    </script>
<?php include 'footer.php'; ?>
</body>
</html>