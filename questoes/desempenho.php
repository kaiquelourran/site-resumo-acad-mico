<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado (opcional para teste)
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

// Buscar estatísticas do usuário
try {
    $user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 1; // Usar 1 como padrão
    
    // Buscar dados da tabela respostas_usuario
    
    // Total de respostas (sem filtro de user_id pois a coluna não existe)
    $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario");
    $stmt_total->execute();
    $total_respostas = $stmt_total->fetch()['total'];
    
    // Respostas corretas (sem filtro de user_id pois a coluna não existe)
    $stmt_corretas = $pdo->prepare("SELECT COUNT(*) as corretas FROM respostas_usuario WHERE acertou = 1");
    $stmt_corretas->execute();
    $respostas_corretas = $stmt_corretas->fetch()['corretas'];
    
    // Calcular percentual de acerto
    $percentual_acerto = $total_respostas > 0 ? round(($respostas_corretas / $total_respostas) * 100, 1) : 0;
    
    // Estatísticas por assunto (sem filtro de user_id)
    $stmt_assuntos = $pdo->prepare("
        SELECT 
            a.nome as nome_assunto,
            COUNT(r.id) as total_questoes,
            SUM(r.acertou) as acertos,
            ROUND((SUM(r.acertou) / COUNT(r.id)) * 100, 1) as percentual
        FROM respostas_usuario r
        JOIN questoes q ON r.id_questao = q.id_questao
        JOIN assuntos a ON q.id_assunto = a.id_assunto
        GROUP BY a.id_assunto, a.nome
        ORDER BY percentual DESC
    ");
    $stmt_assuntos->execute();
    $stats_assuntos = $stmt_assuntos->fetchAll();
    
    // Últimas atividades (sem filtro de user_id)
    $stmt_atividades = $pdo->prepare("
        SELECT 
            a.nome as nome_assunto,
            q.enunciado as pergunta,
            r.acertou as resposta_correta,
            r.data_resposta
        FROM respostas_usuario r
        JOIN questoes q ON r.id_questao = q.id_questao
        JOIN assuntos a ON q.id_assunto = a.id_assunto
        ORDER BY r.data_resposta DESC
        LIMIT 10
    ");
    $stmt_atividades->execute();
    $atividades_recentes = $stmt_atividades->fetchAll();
    
    // Estatísticas por período - sem filtro de user_id
    // 24 horas
    $stmt_24h = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE data_resposta >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmt_24h->execute();
    $questoes_24h = $stmt_24h->fetch()['total'];
    
    // 7 dias
    $stmt_7d = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE data_resposta >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt_7d->execute();
    $questoes_7d = $stmt_7d->fetch()['total'];
    
    // 365 dias
    $stmt_365d = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE data_resposta >= DATE_SUB(NOW(), INTERVAL 365 DAY)");
    $stmt_365d->execute();
    $questoes_365d = $stmt_365d->fetch()['total'];
    
    // Sempre (total geral)
    $questoes_sempre = $total_respostas;
    
} catch (Exception $e) {
    $total_respostas = 0;
    $respostas_corretas = 0;
    $percentual_acerto = 0;
    $stats_assuntos = [];
    $atividades_recentes = [];
    $questoes_24h = 0;
    $questoes_7d = 0;
    $questoes_365d = 0;
    $questoes_sempre = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Desempenho - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        /* Background gradiente azul */
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
            margin: 0;
        }
        
        /* Container principal */
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
        
        /* Estilos específicos para desempenho-page - copiados da index-page */
        .desempenho-page .header .breadcrumb .header-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 10px 24px rgba(0,114,255,0.12);
            border-radius: 16px;
            padding: 14px 20px 16px 44px;
            position: relative;
        }
        .desempenho-page .header .breadcrumb .header-container::before {
            content: "";
            position: absolute;
            left: 16px;
            top: 12px;
            bottom: 12px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .desempenho-page .header .breadcrumb-link,
        .desempenho-page .header .breadcrumb-current {
            font-size: 1.08rem;
            font-weight: 800;
            color: #111827;
            padding: 10px 14px;
            border-radius: 10px;
            background-color: #FFFFFF;
            border: 1px solid #CFE8FF;
            box-shadow: 0 1px 3px rgba(0,114,255,0.10);
        }
        .desempenho-page .header .breadcrumb-current { color: #0057D9; }
        .desempenho-page .header .breadcrumb-link:hover {
            background-color: #F0F7FF;
            color: #0057D9;
            border-color: #BBDDFF;
        }
        .desempenho-page .header .breadcrumb-separator { color: #6B7280; font-size: 1rem; }
        /* Remover fundo em cápsula do container dos botões no header (apenas na desempenho) */
        .desempenho-page .header .user-info {
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
        .desempenho-page .header .user-profile {
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
        .desempenho-page .header .user-avatar {
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
        .desempenho-page .header .user-name {
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
            .desempenho-page .header .user-name { max-width: 120px; }
        }
        @media (max-width: 480px) {
            .desempenho-page .header .user-name { display: none; }
            .desempenho-page .header .user-avatar {
                width: 26px; height: 26px; font-size: 0.85rem;
            }
        }
        /* Ocultar o botão Entrar na desempenho para destacar 'Sair' */
        .desempenho-page .header .header-btn.primary { display: none !important; }
        /* Estilo destacado para o botão Sair no header da desempenho (vermelho de ação) */
        .desempenho-page .header a.header-btn[href="logout.php"] {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #dc3545 0%, #ff4b5a 100%);
            color: #fff;
            border: 1px solid #dc3545;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(220,53,69,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            letter-spacing: .1px;
        }
        .desempenho-page .header a.header-btn[href="logout.php"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(220,53,69,0.40);
            filter: brightness(1.02);
        }
        .desempenho-page .header a.header-btn[href="logout.php"]:focus {
            outline: 3px solid rgba(220,53,69,0.45);
            outline-offset: 2px;
        }
        .desempenho-page .header a.header-btn[href="logout.php"]::before {
            content: none;
        }
        /* Botão 'Ir para o Site' compacto */
        .desempenho-page .header a.header-btn.site-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #0072FF 0%, #00C6FF 100%);
            color: #fff;
            border: 1px solid #0072FF;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,114,255,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            letter-spacing: .1px;
        }
        .desempenho-page .header a.header-btn.site-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,114,255,0.40);
            filter: brightness(1.02);
        }
        .desempenho-page .header a.header-btn.site-link:focus {
            outline: 3px solid rgba(0,114,255,0.35);
            outline-offset: 2px;
        }
        
        /* Destaque para o título e subtítulo da página de desempenho - igual ao subjects-page */
        .desempenho-page .page-header .header-container {
            max-width: 1100px;
            margin: 16px auto 24px;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 12px 28px rgba(0,114,255,0.14);
            border-radius: 16px;
            padding: 18px 20px 20px 48px;
            position: relative;
        }
        .desempenho-page .page-header .header-container::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 14px;
            bottom: 14px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .desempenho-page .page-title {
            margin: 0;
            font-size: 1.95rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: 0.2px;
        }
        .desempenho-page .page-subtitle {
            margin-top: 6px;
            color: #475569;
            font-size: 1.06rem;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .desempenho-page .page-title { font-size: 1.6rem; }
            .desempenho-page .page-subtitle { font-size: 0.98rem; }
        }
        @media (max-width: 480px) {
            .desempenho-page .page-title { font-size: 1.45rem; }
            .desempenho-page .page-subtitle { font-size: 0.95rem; }
        }
        
        /* Melhorias UX/UI para a página de desempenho */
        .desempenho-main {
            padding: 0;
        }
        
        /* Estatísticas Principais - Design Melhorado */
        .stats-section {
            margin-bottom: 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00C6FF 0%, #0072FF 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px rgba(0,114,255,0.15);
            border-color: #0072FF;
        }
        
        .stat-card.stat-primary::before { background: linear-gradient(90deg, #0072FF 0%, #00C6FF 100%); }
        .stat-card.stat-success::before { background: linear-gradient(90deg, #28a745 0%, #20c997 100%); }
        .stat-card.stat-accuracy::before { background: linear-gradient(90deg, #ffc107 0%, #fd7e14 100%); }
        .stat-card.stat-subjects::before { background: linear-gradient(90deg, #6f42c1 0%, #e83e8c 100%); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 1.5rem;
            color: white;
        }
        
        .stat-primary .stat-icon { background: linear-gradient(135deg, #0072FF 0%, #00C6FF 100%); }
        .stat-success .stat-icon { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .stat-accuracy .stat-icon { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .stat-subjects .stat-icon { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 4px;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.95rem;
            color: #718096;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: #4a5568;
            font-weight: 500;
        }
        
        .stat-trend i {
            font-size: 0.9rem;
        }
        
        /* Seções de Conteúdo */
        .charts-section, .subjects-section, .activities-section {
            margin-bottom: 40px;
        }
        
        .section-header {
            margin-bottom: 24px;
            text-align: center;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .section-title i {
            color: #0072FF;
        }
        
        /* Cards de Gráficos */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }
        
        .chart-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0,114,255,0.12);
        }
        
        .chart-header {
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title i {
            color: #0072FF;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        /* Estilos para estatísticas de período */
        .period-stat {
            text-align: center;
            padding: 20px 0;
        }
        
        .period-number {
            font-size: 3rem;
            font-weight: 800;
            color: #0072FF;
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .period-label {
            font-size: 1rem;
            color: #718096;
            font-weight: 600;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }
        
        /* Cards de Assuntos */
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .subject-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .subject-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,114,255,0.12);
            border-color: #0072FF;
        }
        
        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .subject-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
        }
        
        .subject-percentage {
            font-size: 1.2rem;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
        }
        
        .subject-percentage.high { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .subject-percentage.medium { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        .subject-percentage.low { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }
        
        .subject-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .subject-stat {
            text-align: center;
        }
        
        .subject-stat .stat-label {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 4px;
        }
        
        .subject-stat .stat-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .subject-progress {
            margin-top: 16px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00C6FF 0%, #0072FF 100%);
            border-radius: 4px;
            transition: width 0.8s ease;
        }
        
        /* Lista de Atividades */
        .activities-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .activity-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            transform: translateX(4px);
            box-shadow: 0 8px 25px rgba(0,114,255,0.1);
            border-color: #0072FF;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }
        
        .activity-icon.correct { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .activity-icon.incorrect { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-subject {
            font-size: 0.9rem;
            font-weight: 600;
            color: #0072FF;
            margin-bottom: 4px;
        }
        
        .activity-question {
            font-size: 0.95rem;
            color: #4a5568;
            margin-bottom: 4px;
            line-height: 1.4;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #718096;
        }
        
        .activity-result {
            margin-left: auto;
        }
        
        .result-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .result-badge.success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .result-badge.error { background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%); }
        
        /* Estado Vazio */
        .empty-state, .empty-activities {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 16px;
        }
        
        .empty-state h3, .empty-activities h3 {
            font-size: 1.3rem;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .empty-state p, .empty-activities p {
            font-size: 1rem;
            margin-bottom: 24px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0072FF 0%, #00C6FF 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 18px rgba(0,114,255,0.28);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(0,114,255,0.32);
            filter: brightness(1.05);
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .subjects-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 250px;
            }
            
            .activity-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .activity-result {
                margin-left: 0;
                align-self: flex-end;
            }
        }
        
        /* Animações de entrada */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card, .chart-card, .subject-card, .activity-item {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body class="desempenho-page">
    <?php 
    $breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
    ['icon' => '📊', 'text' => 'Desempenho', 'link' => '', 'current' => true]
    ];
$page_title = '📊 Meu Desempenho';
$page_subtitle = 'Acompanhe sua evolução e estatísticas detalhadas';
    include 'header.php'; 
    ?>
    <script>
    // Ajustes de header para desempenho-page
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.body.classList.contains('desempenho-page')) return;
        const header = document.querySelector('.header');
        if (!header) return;
        const userInfo = header.querySelector('.user-info');
        if (!userInfo) return;
        
        // Botão Sair
        let logoutBtn = header.querySelector('a.header-btn[href="logout.php"]');
        if (!logoutBtn) {
            const a = document.createElement('a');
            a.href = 'logout.php';
            a.className = 'header-btn';
            a.setAttribute('aria-label', 'Sair da sessão');
            a.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Sair</span>';
            userInfo.appendChild(a);
        }
        
        // Perfil do usuário
        let profile = userInfo.querySelector('.user-profile');
        <?php
        $displayNameDesempenho = '';
        foreach ([
            'usuario_nome','usuario','nome','user_name','username','login','nome_usuario','nomeCompleto'
        ] as $k) {
            if (isset($_SESSION[$k]) && trim($_SESSION[$k]) !== '') { $displayNameDesempenho = $_SESSION[$k]; break; }
        }
        ?>
        const userName = "<?php echo htmlspecialchars($displayNameDesempenho, ENT_QUOTES, 'UTF-8'); ?>";
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

    <main id="main-scroll-content" class="desempenho-main">

        <!-- Estatísticas Principais -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $total_respostas; ?></div>
                        <div class="stat-label">Questões Respondidas</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i>
                        <span>Total geral</span>
                    </div>
                </div>

                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $respostas_corretas; ?></div>
                        <div class="stat-label">Respostas Corretas</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Acertos</span>
                    </div>
                </div>

                <div class="stat-card stat-accuracy">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $percentual_acerto; ?>%</div>
                        <div class="stat-label">Taxa de Acerto</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-<?php echo $percentual_acerto >= 70 ? 'fire' : 'chart-line'; ?>"></i>
                        <span><?php echo $percentual_acerto >= 70 ? 'Excelente!' : 'Continue!'; ?></span>
                    </div>
                </div>

                <div class="stat-card stat-subjects">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo count($stats_assuntos); ?></div>
                        <div class="stat-label">Assuntos Estudados</div>
                    </div>
                    <div class="stat-trend">
                        <i class="fas fa-layer-group"></i>
                        <span>Diversidade</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Estatísticas por Período -->
        <section class="charts-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i>
                    Questões por Período
                </h2>
            </div>
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-clock"></i>
                            Últimas 24 Horas
                        </h3>
                    </div>
                    <div class="period-stat">
                        <div class="period-number"><?php echo $questoes_24h; ?></div>
                        <div class="period-label">Questões Respondidas</div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-calendar-week"></i>
                            Últimos 7 Dias
                        </h3>
                    </div>
                    <div class="period-stat">
                        <div class="period-number"><?php echo $questoes_7d; ?></div>
                        <div class="period-label">Questões Respondidas</div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-calendar-alt"></i>
                            Últimos 365 Dias
                        </h3>
                    </div>
                    <div class="period-stat">
                        <div class="period-number"><?php echo $questoes_365d; ?></div>
                        <div class="period-label">Questões Respondidas</div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-infinity"></i>
                            Sempre
                        </h3>
                    </div>
                    <div class="period-stat">
                        <div class="period-number"><?php echo $questoes_sempre; ?></div>
                        <div class="period-label">Questões Respondidas</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Gráfico de Performance por Assunto -->
        <section class="charts-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    Performance por Assunto
                </h2>
            </div>
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-pie"></i>
                            Distribuição de Acertos
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="subjectChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <!-- Desempenho por Assunto -->
        <section class="subjects-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-books"></i>
                    Desempenho por Assunto
                </h2>
            </div>
            <div class="subjects-grid">
                <?php if (!empty($stats_assuntos)): ?>
                    <?php foreach($stats_assuntos as $assunto): ?>
                        <div class="subject-card">
                            <div class="subject-header">
                                <h4 class="subject-name"><?php echo htmlspecialchars($assunto['nome_assunto']); ?></h4>
                                <div class="subject-percentage <?php echo $assunto['percentual'] >= 70 ? 'high' : ($assunto['percentual'] >= 50 ? 'medium' : 'low'); ?>">
                                    <?php echo $assunto['percentual']; ?>%
                                </div>
                            </div>
                            <div class="subject-stats">
                                <div class="subject-stat">
                                    <span class="stat-label">Questões:</span>
                                    <span class="stat-value"><?php echo $assunto['total_questoes']; ?></span>
                                </div>
                                <div class="subject-stat">
                                    <span class="stat-label">Acertos:</span>
                                    <span class="stat-value"><?php echo $assunto['acertos']; ?></span>
                                </div>
                            </div>
                            <div class="subject-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $assunto['percentual']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3>Nenhum dado encontrado</h3>
                        <p>Comece respondendo questões para ver suas estatísticas aqui!</p>
                        <a href="escolher_assunto.php" class="btn-primary">
                            <i class="fas fa-play"></i>
                            Fazer Questões
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Atividades Recentes -->
        <section class="activities-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i>
                    Atividades Recentes
                </h2>
            </div>
            <div class="activities-list">
                <?php if (!empty($atividades_recentes)): ?>
                    <?php foreach($atividades_recentes as $atividade): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $atividade['resposta_correta'] ? 'correct' : 'incorrect'; ?>">
                                <i class="fas fa-<?php echo $atividade['resposta_correta'] ? 'check' : 'times'; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-subject"><?php echo htmlspecialchars($atividade['nome_assunto']); ?></div>
                                <div class="activity-question"><?php echo htmlspecialchars(substr($atividade['pergunta'], 0, 80)) . '...'; ?></div>
                                <div class="activity-time"><?php echo date('d/m/Y H:i', strtotime($atividade['data_resposta'])); ?></div>
                            </div>
                            <div class="activity-result">
                                <span class="result-badge <?php echo $atividade['resposta_correta'] ? 'success' : 'error'; ?>">
                                    <?php echo $atividade['resposta_correta'] ? 'Correto' : 'Incorreto'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-activities">
                        <div class="empty-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <p>Nenhuma atividade recente encontrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

    <?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dados para os gráficos
        const subjectData = <?php echo json_encode($stats_assuntos); ?>;

        // Gráfico de Performance por Assunto
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        new Chart(subjectCtx, {
            type: 'doughnut',
            data: {
                labels: subjectData.map(item => item.nome_assunto),
                datasets: [{
                    data: subjectData.map(item => item.percentual),
                    backgroundColor: [
                        '#0072FF',
                        '#00C6FF',
                        '#4A90E2',
                        '#7BB3F0',
                        '#A8D0F7',
                        '#D4E7FD'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Melhorias de UX/UI - Animações e Interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Animações de entrada escalonadas
            const cards = document.querySelectorAll('.stat-card, .chart-card, .subject-card, .activity-item');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animação das barras de progresso
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                
                setTimeout(() => {
                    bar.style.transition = 'width 1.2s cubic-bezier(0.4, 0, 0.2, 1)';
                    bar.style.width = width;
                }, 800 + (index * 200));
            });
            
            // Efeito de hover nos cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
            
            // Contador animado para os números das estatísticas
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(numberEl => {
                const text = numberEl.textContent.trim();
                const isPercentageInit = text.includes('%');
                let finalNumber = isPercentageInit
                    ? parseFloat(text.replace('%', '').replace(',', '.'))
                    : parseFloat(text.replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, ''));
                
                if (!isNaN(finalNumber) && finalNumber > 0) {
                    // Garantir que percentuais não passem de 100
                    if (isPercentageInit) finalNumber = Math.min(finalNumber, 100);
                    animateNumber(numberEl, 0, finalNumber, 1500);
                }
            });
            
            // Função para animar números
            function animateNumber(element, start, end, duration) {
                const startTime = performance.now();
                const isPercentage = element.textContent.includes('%');
                
                function updateNumber(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Easing function para suavizar a animação
                    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                    const currentNumberRaw = start + (end - start) * easeOutQuart;
                    
                    if (isPercentage) {
                        element.textContent = currentNumberRaw.toFixed(1).replace('.', ',') + '%';
                    } else {
                        element.textContent = Math.floor(currentNumberRaw).toLocaleString('pt-BR');
                    }
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateNumber);
                    }
                }
                
                requestAnimationFrame(updateNumber);
            }
            
            // Tooltip para as atividades recentes
            const activityItems = document.querySelectorAll('.activity-item');
            activityItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(8px)';
                    this.style.boxShadow = '0 12px 35px rgba(0,114,255,0.15)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                    this.style.boxShadow = '0 8px 25px rgba(0,114,255,0.1)';
                });
            });
            
            // Efeito de loading nos gráficos
            const chartContainers = document.querySelectorAll('.chart-container');
            chartContainers.forEach(container => {
                const canvas = container.querySelector('canvas');
                if (canvas) {
                    canvas.style.opacity = '0';
                    setTimeout(() => {
                        canvas.style.transition = 'opacity 0.8s ease';
                        canvas.style.opacity = '1';
                    }, 1000);
                }
            });
            
            // Adicionar indicador de carregamento se não houver dados
            const emptyStates = document.querySelectorAll('.empty-state, .empty-activities');
            emptyStates.forEach(emptyState => {
                emptyState.style.opacity = '0';
                setTimeout(() => {
                    emptyState.style.transition = 'opacity 0.6s ease';
                    emptyState.style.opacity = '1';
                }, 500);
            });
            
            // Efeito de parallax suave no scroll
            let ticking = false;
            function updateParallax() {
                const scrolled = window.pageYOffset;
                const parallaxElements = document.querySelectorAll('.stat-card, .chart-card');
                
                parallaxElements.forEach((element, index) => {
                    const speed = 0.1 + (index * 0.05);
                    const yPos = -(scrolled * speed);
                    element.style.transform = `translateY(${yPos}px)`;
                });
                
                ticking = false;
            }
            
            function requestTick() {
                if (!ticking) {
                    requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            }
            
            window.addEventListener('scroll', requestTick);
            
            // Adicionar feedback visual para interações
            const interactiveElements = document.querySelectorAll('.btn-primary, .subject-card, .activity-item');
            interactiveElements.forEach(element => {
                element.addEventListener('click', function(e) {
                    // Criar efeito de ripple
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(0, 114, 255, 0.3)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.pointerEvents = 'none';
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Adicionar CSS para animação de ripple
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>