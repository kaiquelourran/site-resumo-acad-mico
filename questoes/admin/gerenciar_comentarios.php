<?php
session_start();

// Verifica se o usuário é um administrador
if (!isset($_SESSION['tipo_usuario']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['user_type'] !== 'admin')) {
    header('Location: /admin/login.php'); // Redireciona para a página de login se não for admin
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Comentários - Resumo Acadêmico</title>
    <link rel="stylesheet" href="../modern-style.css">
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
    
    /* CSS específico para admin-comentarios-page (igual ao index-page) */
    .admin-comentarios-page .header .breadcrumb .header-container {
        max-width: 1100px;
        margin: 0 auto;
        background: #FFFFFF;
        border: 2px solid #dbeafe;
        box-shadow: 0 10px 24px rgba(0,114,255,0.12);
        border-radius: 16px;
        padding: 14px 20px 16px 44px;
        position: relative;
    }
    .admin-comentarios-page .header .breadcrumb .header-container::before {
        content: "";
        position: absolute;
        left: 16px;
        top: 12px;
        bottom: 12px;
        width: 6px;
        border-radius: 6px;
        background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
    }
    .admin-comentarios-page .header .breadcrumb-link,
    .admin-comentarios-page .header .breadcrumb-current {
        font-size: 1.08rem;
        font-weight: 800;
        color: #111827;
        padding: 10px 14px;
        border-radius: 10px;
        background-color: #FFFFFF;
        border: 1px solid #CFE8FF;
        box-shadow: 0 1px 3px rgba(0,114,255,0.10);
    }
    .admin-comentarios-page .header .breadcrumb-current { color: #0057D9; }
    .admin-comentarios-page .header .breadcrumb-link:hover {
        background-color: #F0F7FF;
        color: #0057D9;
        border-color: #BBDDFF;
    }
    .admin-comentarios-page .header .breadcrumb-separator { color: #6B7280; font-size: 1rem; }
    
    /* Remover fundo em cápsula do container dos botões no header */
    .admin-comentarios-page .header .user-info {
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
    
    /* Bloco de perfil compacto e alinhado com os botões */
    .admin-comentarios-page .header .user-profile {
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
    .admin-comentarios-page .header .user-avatar {
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
    .admin-comentarios-page .header .user-name {
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
        .admin-comentarios-page .header .user-name { max-width: 120px; }
    }
    @media (max-width: 480px) {
        .admin-comentarios-page .header .user-name { display: none; }
        .admin-comentarios-page .header .user-avatar {
            width: 26px; height: 26px; font-size: 0.85rem;
        }
    }
    
    /* Ocultar o botão Entrar */
    .admin-comentarios-page .header .header-btn.primary { display: none !important; }
    
    /* Ocultar TODOS os botões Sair que não sejam vermelhos */
    .admin-comentarios-page .header a.header-btn[href="../logout.php"]:not(.logout-red) { 
        display: none !important; 
    }
    
    /* Ocultar botões Sair azuis por qualquer seletor */
    .admin-comentarios-page .header a[href="../logout.php"]:not(.logout-red) { 
        display: none !important; 
    }
    
    /* Ocultar botões Sair azuis por classe */
    .admin-comentarios-page .header .header-btn:not(.logout-red)[href="../logout.php"] { 
        display: none !important; 
    }
    
    /* Ocultar QUALQUER botão Sair que não seja vermelho - CSS SUPER AGRESSIVO */
    .admin-comentarios-page .header a[href="../logout.php"]:not(.logout-red),
    .admin-comentarios-page .header .header-btn[href="../logout.php"]:not(.logout-red),
    .admin-comentarios-page .header a.header-btn[href="../logout.php"]:not(.logout-red) { 
        display: none !important; 
        visibility: hidden !important;
        opacity: 0 !important;
        height: 0 !important;
        width: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        font-size: 0 !important;
    }
    
    /* Estilo destacado para o botão Sair no header (vermelho de ação) */
    .admin-comentarios-page .header a.header-btn.logout-red {
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
    .admin-comentarios-page .header a.header-btn.logout-red:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(220,53,69,0.40);
        filter: brightness(1.02);
    }
    .admin-comentarios-page .header a.header-btn.logout-red:focus {
        outline: 3px solid rgba(220,53,69,0.45);
        outline-offset: 2px;
    }
    .admin-comentarios-page .header a.header-btn.logout-red::before {
        content: none;
    }
    
    /* Botão 'Ir para o Site' compacto */
    .admin-comentarios-page .header a.header-btn.site-link {
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
    .admin-comentarios-page .header a.header-btn.site-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(0,114,255,0.40);
        filter: brightness(1.02);
    }
    .admin-comentarios-page .header a.header-btn.site-link:focus {
        outline: 3px solid rgba(0,114,255,0.35);
        outline-offset: 2px;
    }

    /* Estilos específicos para a página de gerenciar comentários */
    .modern-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .modern-table th { background: linear-gradient(135deg, #0072FF 0%, #00C6FF 100%); color: #fff; padding: 16px; text-align: left; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
    .modern-table td { padding: 16px; border-bottom: 1px solid #f0f2f5; vertical-align: top; }
    .modern-table tr:hover { background: #f8f9fa; }
    .modern-table tr:last-child td { border-bottom: none; }

    /* Correção: evitar corte lateral dos botões e permitir rolagem horizontal */
    .admin-comentarios-page .card { overflow: visible; }
    .admin-comentarios-page .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .admin-comentarios-page .modern-table { min-width: 900px; }
    .admin-comentarios-page .modern-table td:last-child { min-width: 260px; white-space: nowrap; }
    .admin-comentarios-page .modern-table td .btn { white-space: nowrap; }

    .badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-success { background: #d4edda; color: #155724; }
    .badge-danger { background: #f8d7da; color: #721c24; }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid transparent; }
    .alert-info { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
    .alert-danger { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }

    .loading { text-align: center; padding: 40px; color: #666; }
    .loading i { font-size: 2rem; margin-bottom: 16px; color: #0072FF; }

    @media (max-width: 768px) {
        .modern-table { font-size: 14px; }
        .modern-table th, .modern-table td { padding: 12px 8px; }
        /* Em telas pequenas, permitir quebra de linha nos botões */
        .admin-comentarios-page .modern-table td:last-child { white-space: normal; }
    }
    </style>
</head>
<body class="admin-comentarios-page">
<?php
// Configuração do breadcrumb para a página de gerenciar comentários
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => '../index.php', 'current' => false],
    ['icon' => '👨‍💼', 'text' => 'Dashboard Admin', 'link' => 'dashboard.php', 'current' => false],
    ['icon' => '💬', 'text' => 'Gerenciar Comentários', 'link' => '', 'current' => true]
];

$page_title = 'Gerenciar Comentários';
$page_subtitle = 'Administrar comentários reportados e inativos';
include '../header.php';
?>
    <script>
    // Ajustes de header para admin-comentarios-page
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.body.classList.contains('admin-comentarios-page')) return;
        const header = document.querySelector('.header');
        if (!header) return;
        const userInfo = header.querySelector('.user-info');
        if (!userInfo) return;
        
        // Remover TODOS os botões Sair existentes (múltiplos seletores)
        const selectors = [
            'a.header-btn[href="../logout.php"]',
            'a[href="../logout.php"]',
            '.header-btn[href="../logout.php"]'
        ];
        
        selectors.forEach(selector => {
            const btns = header.querySelectorAll(selector);
            btns.forEach(btn => {
                if (!btn.classList.contains('logout-red')) {
                    btn.remove();
                }
            });
        });
        
        // Adicionar apenas o botão Sair vermelho
        const a = document.createElement('a');
        a.href = '../logout.php';
        a.className = 'header-btn logout-red';
        a.setAttribute('aria-label', 'Sair da sessão');
        a.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Sair</span>';
        userInfo.appendChild(a);
        
        // Perfil do usuário
        let profile = userInfo.querySelector('.user-profile');
        <?php
        $displayNameAdmin = '';
        foreach ([
            'usuario_nome','usuario','nome','user_name','username','login','nome_usuario','nomeCompleto'
        ] as $k) {
            if (isset($_SESSION[$k]) && trim($_SESSION[$k]) !== '') { $displayNameAdmin = $_SESSION[$k]; break; }
        }
        ?>
        const userName = "<?php echo htmlspecialchars($displayNameAdmin, ENT_QUOTES, 'UTF-8'); ?>";
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
        
        const loginBtn = header.querySelector('a.header-btn.primary[href="../login.php"]');
        if (loginBtn) loginBtn.style.display = 'none';
        
        // Botão Ir para o Site
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

        <!-- Conteúdo Principal -->
        <div class="card">
            <h2 class="card-title">📋 Comentários Reportados</h2>
    <p>Aqui você pode visualizar e gerenciar comentários que foram reportados por usuários ou apagados por administradores.</p>

            <div id="reportedCommentsList">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
        <p>Carregando comentários...</p>
    </div>
</div>
        </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const commentsListDiv = document.getElementById('reportedCommentsList');
    let allReportedComments = [];
    let showCount = 0;
    const PAGE_SIZE = 3;

    function loadReportedComments() {
        fetch('../api_comentarios.php?get_reported_comments=true', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allReportedComments = data.data;
                showCount = PAGE_SIZE;
                renderComments(allReportedComments, showCount);
            } else {
                commentsListDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Erro ao carregar comentários</strong><br>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao buscar comentários reportados:', error);
            commentsListDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Erro de rede</strong><br>
                    Não foi possível carregar os comentários. Verifique sua conexão e tente novamente.
                </div>
            `;
        });
    }

    function renderComments(comments, count) {
        if (comments.length === 0) {
            commentsListDiv.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Nenhum comentário encontrado</strong><br>
                    Não há comentários reportados ou inativos no momento.
                </div>
            `;
            return;
        }

        const visibleComments = comments.slice(0, Math.min(count, comments.length));

        let html = `
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-question-circle"></i> Questão</th>
                            <th><i class="fas fa-comment"></i> Comentário</th>
                            <th><i class="fas fa-user"></i> Autor</th>
                            <th><i class="fas fa-calendar"></i> Data</th>
                            <th><i class="fas fa-flag"></i> Status</th>
                            <th><i class="fas fa-user-secret"></i> Denúncia</th>
                            <th><i class="fas fa-cogs"></i> Ações</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        visibleComments.forEach(comment => {
            const statusText = comment.ativo == 0 ? 'Inativo' : 'Ativo';
            const statusBadge = comment.ativo == 0 ? 'badge-danger' : 'badge-success';
            const statusIcon = comment.ativo == 0 ? 'fas fa-ban' : 'fas fa-check-circle';
            const comentarioTruncado = comment.comentario.length > 100 
                ? comment.comentario.substring(0, 100) + '...' 
                : comment.comentario;
            html += `
                <tr>
                    <td><strong>#${comment.id_comentario}</strong></td>
                    <td>
                        <a href="../quiz_vertical_filtros.php?id=${comment.id_questao}&filtro=todas" 
                           target="_blank" 
                           class="btn btn-sm" 
                           style="padding: 4px 8px; font-size: 12px;">
                            <i class="fas fa-external-link-alt"></i> Questão ${comment.id_questao}
                        </a>
                    </td>
                    <td>
                        <div style="max-width: 300px; word-wrap: break-word;">
                            ${comentarioTruncado}
                        </div>
                    </td>
                    <td>
                        <div>
                            <strong>${comment.nome_usuario || 'Anônimo'}</strong><br>
                            <small style="color: #666;">${comment.email_usuario || 'N/A'}</small>
                        </div>
                    </td>
                    <td>
                        <small>${comment.data_criacao}</small>
                    </td>
                    <td>
                        <span class="badge ${statusBadge}">
                            <i class="${statusIcon}"></i> ${statusText}
                        </span>
                        ${comment.reportado == 1 ? `<span class="badge badge-danger" style="margin-left:6px;"><i class="fas fa-flag"></i> Reportado</span>` : ''}
                    </td>
                    <td>
                        ${comment.total_denuncias ? `<div>
                            <strong>${comment.reporter_nome || 'Usuário'}</strong><br>
                            <small style="color:#666;">${comment.reporter_email || comment.reporter_ip || 'N/A'}</small><br>
                            <small style="color:#888;">${comment.reporter_data || ''}</small>
                            ${comment.reporter_tipo ? `<span class="badge badge-warning" style="margin-right:6px;">${String(comment.reporter_tipo || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</span>` : ''}
                            ${comment.reporter_motivo ? `<small style="color:#555; display:block; margin-top:4px;">Motivo: ${String(comment.reporter_motivo || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</small>` : ''}
                            <span class="badge badge-info" style="margin-left:6px;">${comment.total_denuncias} denúncia(s)</span>
                        </div>` : '<small style="color:#888;">Sem detalhes</small>'}
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            ${comment.ativo == 0 ? `
                            <button class="btn btn-success btn-sm btn-ativar" 
                                    data-id="${comment.id_comentario}"
                                    style="padding: 6px 12px; font-size: 12px;">
                                <i class="fas fa-check"></i> Ativar
                            </button>` : `
                            <button class="btn btn-warning btn-sm btn-desativar" 
                                    data-id="${comment.id_comentario}"
                                    style="padding: 6px 12px; font-size: 12px;">
                                <i class="fas fa-ban"></i> Desativar
                            </button>`}
                            <button class="btn btn-danger btn-sm btn-excluir-permanente" 
                                    data-id="${comment.id_comentario}"
                                    style="padding: 6px 12px; font-size: 12px;">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        </div>
        `;

        if (comments.length > count) {
            html += `
                <div style="text-align: center; margin-top: 12px;">
                    <button id="btnCarregarMaisReportados" class="btn btn-primary" style="padding: 8px 16px;">
                        <i class="fas fa-plus"></i> Carregar mais
                    </button>
                </div>
            `;
        }
        
        commentsListDiv.innerHTML = html;

        document.querySelectorAll('.btn-ativar').forEach(button => {
            button.addEventListener('click', function() {
                const comentarioId = this.dataset.id;
                updateCommentStatus(comentarioId, 1);
            });
        });

        document.querySelectorAll('.btn-desativar').forEach(button => {
            button.addEventListener('click', function() {
                const comentarioId = this.dataset.id;
                updateCommentStatus(comentarioId, 0);
            });
        });

        document.querySelectorAll('.btn-excluir-permanente').forEach(button => {
            button.addEventListener('click', function() {
                const comentarioId = this.dataset.id;
                deleteCommentPermanently(comentarioId);
            });
        });

        const loadMoreBtn = document.getElementById('btnCarregarMaisReportados');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                showCount = Math.min(showCount + PAGE_SIZE, allReportedComments.length);
                renderComments(allReportedComments, showCount);
            });
        }
    }

    function updateCommentStatus(comentarioId, status) {
        if (!confirm(`Tem certeza que deseja ${status === 1 ? 'ativar' : 'desativar'} este comentário?`)) return;

        fetch('../api_comentarios.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_comentario: comentarioId,
                acao: status === 1 ? 'ativar' : 'desativar' // Nova ação para ativar/desativar
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                loadReportedComments(); // Recarregar a lista
            } else {
                showNotification(`Erro: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar status do comentário:', error);
            showNotification('Erro de rede ao atualizar status do comentário.', 'error');
        });
    }

    function deleteCommentPermanently(comentarioId) {
        if (!confirm('ATENÇÃO: Tem certeza que deseja EXCLUIR PERMANENTEMENTE este comentário? Esta ação não pode ser desfeita.')) return;

        fetch('../api_comentarios.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_comentario: comentarioId,
                acao: 'excluir_permanente' // Nova ação para exclusão permanente
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                loadReportedComments(); // Recarregar a lista
            } else {
                showNotification(`Erro: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao excluir comentário permanentemente:', error);
            showNotification('Erro de rede ao excluir comentário permanentemente.', 'error');
        });
    }

    // Função para mostrar notificações modernas
    function showNotification(message, type = 'info') {
        // Remover notificação anterior se existir
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `notification alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;

        const icon = type === 'success' ? 'fas fa-check-circle' : 
                    type === 'error' ? 'fas fa-exclamation-triangle' : 
                    'fas fa-info-circle';

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="${icon}" style="font-size: 1.2rem;"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="background: none; border: none; color: inherit; cursor: pointer; margin-left: auto;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    // Adicionar estilos de animação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    loadReportedComments(); // Carrega os comentários ao iniciar a página
});
</script>

<?php include '../footer.php'; ?>
</body>
</html>