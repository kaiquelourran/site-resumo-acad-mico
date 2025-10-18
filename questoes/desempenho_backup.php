<?php
session_start();
require_once 'conexao.php';

// Verificação de modo de manutenção


// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Buscar estatísticas do usuário
try {
    $user_id = $_SESSION['id_usuario'];
    
    // Total de respostas do usuário
    $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?");
    $stmt_total->execute([$user_id]);
    $total_respostas = $stmt_total->fetch()['total'];
    
    // Respostas corretas
    $stmt_corretas = $pdo->prepare("SELECT COUNT(*) as corretas FROM respostas_usuario WHERE user_id = ? AND resposta_correta = 1");
    $stmt_corretas->execute([$user_id]);
    $respostas_corretas = $stmt_corretas->fetch()['corretas'];
    
    // Calcular percentual de acerto
    $percentual_acerto = $total_respostas > 0 ? round(($respostas_corretas / $total_respostas) * 100, 1) : 0;
    
    // Estatísticas por assunto
    $stmt_assuntos = $pdo->prepare("
        SELECT 
            a.nome_assunto,
            COUNT(r.id_resposta) as total_questoes,
            SUM(r.resposta_correta) as acertos,
            ROUND((SUM(r.resposta_correta) / COUNT(r.id_resposta)) * 100, 1) as percentual
        FROM respostas_usuario r
        JOIN questoes q ON r.id_questao = q.id_questao
        JOIN assuntos a ON q.id_assunto = a.id_assunto
        WHERE r.user_id = ?
        GROUP BY a.id_assunto, a.nome_assunto
        ORDER BY percentual DESC
    ");
    $stmt_assuntos->execute([$user_id]);
    $stats_assuntos = $stmt_assuntos->fetchAll();
    
    // Últimas atividades
    $stmt_atividades = $pdo->prepare("
        SELECT 
            a.nome_assunto,
            q.pergunta,
            r.resposta_correta,
            r.data_resposta
        FROM respostas_usuario r
        JOIN questoes q ON r.id_questao = q.id_questao
        JOIN assuntos a ON q.id_assunto = a.id_assunto
        WHERE r.user_id = ?
        ORDER BY r.data_resposta DESC
        LIMIT 10
    ");
    $stmt_atividades->execute([$user_id]);
    $atividades_recentes = $stmt_atividades->fetchAll();
    
    // Progresso semanal (últimos 7 dias)
    $stmt_semanal = $pdo->prepare("
        SELECT 
            DATE(data_resposta) as dia,
            COUNT(*) as questoes_respondidas,
            SUM(resposta_correta) as acertos
        FROM respostas_usuario 
        WHERE user_id = ? AND data_resposta >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(data_resposta)
        ORDER BY dia ASC
    ");
    $stmt_semanal->execute([$user_id]);
    $progresso_semanal = $stmt_semanal->fetchAll();
    
} catch (Exception $e) {
    $total_respostas = 0;
    $respostas_corretas = 0;
    $percentual_acerto = 0;
    $stats_assuntos = [];
    $atividades_recentes = [];
    $progresso_semanal = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Desempenho - Resumo Acadêmico</title>
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="modern-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Ícones SVG personalizados */
        .svg-icon {
            display: inline-block;
            width: 1.2em;
            height: 1.2em;
            vertical-align: middle;
            fill: currentColor;
        }
        
        .stat-icon, .chart-icon, .title-icon, .trend-icon, .btn-icon, .avatar-icon, .empty-icon, .activity-icon {
            display: inline-block;
            vertical-align: middle;
        }
        
        .stat-icon .svg-icon { width: 1.5em; height: 1.5em; }
        .chart-icon .svg-icon { width: 1.3em; height: 1.3em; }
        .title-icon .svg-icon { width: 1.4em; height: 1.4em; }
        .trend-icon .svg-icon { width: 1.1em; height: 1.1em; }
        .btn-icon .svg-icon { width: 1.2em; height: 1.2em; }
        .avatar-icon .svg-icon { width: 2em; height: 2em; }
        .empty-icon .svg-icon { width: 3em; height: 3em; }
        .activity-icon .svg-icon { width: 1.3em; height: 1.3em; }
        
        /* Fallback com Font Awesome */
        .fa-fallback {
            font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands', sans-serif;
            font-weight: 900;
        }
        
        /* Cores personalizadas para ícones */
        .stat-icon .svg-icon { color: #0072FF; }
        .chart-icon .svg-icon { color: #00C6FF; }
        .title-icon .svg-icon { color: #0072FF; }
        .trend-icon .svg-icon { color: #28a745; }
        .btn-icon .svg-icon { color: #ffffff; }
        .avatar-icon .svg-icon { color: #0072FF; }
        .empty-icon .svg-icon { color: #6c757d; }
        .activity-icon.correct .svg-icon { color: #28a745; }
        .activity-icon.incorrect .svg-icon { color: #dc3545; }
    </style>
</head>
<body>
    <?php 
    $breadcrumb_items = [
        ['icon' => '<svg class="svg-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
        ['icon' => '<svg class="svg-icon" viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>', 'text' => 'Desempenho', 'link' => '', 'current' => true]
    ];
    include 'header.php'; 
    ?>
    
    <div class="performance-container desempenho-page">
        <!-- Header da Página -->
        <div class="performance-header desempenho-header">
            <div class="performance-title-section">
                <h1 class="performance-title">
                    <span class="title-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                    </span>
                    Meu Desempenho
                </h1>
                <p class="performance-subtitle">Acompanhe sua evolução e estatísticas detalhadas</p>
            </div>
            <div class="performance-user-info">
                <div class="user-avatar">
                    <span class="avatar-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                        </svg>
                    </span>
                </div>
                <div class="user-details">
                    <h3 class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                    <p class="user-level">Estudante Ativo</p>
                </div>
            </div>
        </div>

        <!-- Estatísticas Principais -->
        <div class="performance-stats-grid">
            <div class="stat-card primary-stat">
                <div class="stat-icon-container">
                    <span class="stat-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $total_respostas; ?></div>
                    <div class="stat-label">Questões Respondidas</div>
                </div>
                <div class="stat-trend positive">
                    <span class="trend-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>
                        </svg>
                    </span>
                    <span class="trend-text">Total geral</span>
                </div>
            </div>

            <div class="stat-card success-stat">
                <div class="stat-icon-container">
                    <span class="stat-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $respostas_corretas; ?></div>
                    <div class="stat-label">Respostas Corretas</div>
                </div>
                <div class="stat-trend positive">
                    <span class="trend-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M7 8h10v2H7zm2-3h6v2H9zm1.99 11.5c.19 0 .37-.03.54-.08l8.15-2.66c.76-.25 1.32-.96 1.32-1.82V8c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v3.94c0 .86.56 1.58 1.32 1.82l8.15 2.66c.17.05.35.08.52.08z"/>
                        </svg>
                    </span>
                    <span class="trend-text">Acertos</span>
                </div>
            </div>

            <div class="stat-card accuracy-stat">
                <div class="stat-icon-container">
                    <span class="stat-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M7.5 11H2V9h5.5l-.75-1.5h2.5L11 11l-1.75 3.5h-2.5L7.5 13H2v-2h5.5zm9-7C14.01 4 12 6.01 12 8.5s2.01 4.5 4.5 4.5S21 10.99 21 8.5 18.99 4 16.5 4zm0 7C15.12 11 14 9.88 14 8.5S15.12 6 16.5 6 19 7.12 19 8.5 17.88 11 16.5 11zm-3 5.5c0 2.49 2.01 4.5 4.5 4.5s4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5-4.5 2.01-4.5 4.5zm2 0c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5-2.5-1.12-2.5-2.5z"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $percentual_acerto; ?>%</div>
                    <div class="stat-label">Taxa de Acerto</div>
                </div>
                <div class="stat-trend <?php echo $percentual_acerto >= 70 ? 'positive' : 'neutral'; ?>">
                    <span class="trend-icon">
                        <?php if ($percentual_acerto >= 70): ?>
                            <svg class="svg-icon" viewBox="0 0 24 24">
                                <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73l.03-.36C5.21 7.51 4 10.62 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8C20 8.61 17.41 3.8 13.5.67zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.77-.36 3.6-1.21 4.62-2.58.39 1.29.59 2.65.59 4.04 0 2.65-2.15 4.8-4.8 4.8z"/>
                            </svg>
                        <?php else: ?>
                            <svg class="svg-icon" viewBox="0 0 24 24">
                                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                            </svg>
                        <?php endif; ?>
                    </span>
                    <span class="trend-text"><?php echo $percentual_acerto >= 70 ? 'Excelente!' : 'Continue!'; ?></span>
                </div>
            </div>

            <div class="stat-card streak-stat">
                <div class="stat-icon-container">
                    <span class="stat-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M13 2.05v3.03c3.39.49 6 3.39 6 6.92 0 .9-.18 1.75-.48 2.54l2.6 1.53c.56-1.24.88-2.62.88-4.07 0-5.18-3.95-9.45-9-9.95zM12 19c-3.87 0-7-3.13-7-7 0-3.53 2.61-6.43 6-6.92V2.05c-5.06.5-9 4.76-9 9.95 0 5.52 4.47 10 9.99 10 3.31 0 6.24-1.61 8.06-4.09l-2.6-1.53C16.17 17.98 14.21 19 12 19z"/>
                        </svg>
                    </span>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($stats_assuntos); ?></div>
                    <div class="stat-label">Assuntos Estudados</div>
                </div>
                <div class="stat-trend positive">
                    <span class="trend-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                    </span>
                    <span class="trend-text">Diversidade</span>
                </div>
            </div>
        </div>

        <!-- Gráficos e Análises -->
        <div class="performance-charts-section">
            <div class="charts-grid">
                <!-- Gráfico de Progresso Semanal -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <span class="chart-icon">
                                <svg class="svg-icon" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                </svg>
                            </span>
                            Progresso dos Últimos 7 Dias
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Performance por Assunto -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <span class="chart-icon">
                                <svg class="svg-icon" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </span>
                            Performance por Assunto
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="subjectChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalhes por Assunto -->
        <div class="subjects-performance-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
                        </svg>
                    </span>
                    Desempenho por Assunto
                </h2>
            </div>
            <div class="subjects-grid">
                <?php if (!empty($stats_assuntos)): ?>
                    <?php foreach($stats_assuntos as $assunto): ?>
                        <div class="subject-performance-card">
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
                            <div class="subject-progress-bar">
                                <div class="progress-fill" style="width: <?php echo $assunto['percentual']; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <svg class="svg-icon" viewBox="0 0 24 24">
                                <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
                            </svg>
                        </div>
                        <h3>Nenhum dado encontrado</h3>
                        <p>Comece respondendo questões para ver suas estatísticas aqui!</p>
                        <a href="escolher_assunto.php" class="btn-primary">
                            <span class="btn-icon">
                                <svg class="svg-icon" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </span>
                            <span class="btn-text">Fazer Questões</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Atividades Recentes -->
        <div class="recent-activities-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-icon">
                        <svg class="svg-icon" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zM12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                    </span>
                    Atividades Recentes
                </h2>
            </div>
            <div class="activities-list">
                <?php if (!empty($atividades_recentes)): ?>
                    <?php foreach($atividades_recentes as $atividade): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo $atividade['resposta_correta'] ? 'correct' : 'incorrect'; ?>">
                                <?php if ($atividade['resposta_correta']): ?>
                                    <svg class="svg-icon" viewBox="0 0 24 24">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg class="svg-icon" viewBox="0 0 24 24">
                                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                    </svg>
                                <?php endif; ?>
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
                            <svg class="svg-icon" viewBox="0 0 24 24">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zM12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                        </div>
                        <p>Nenhuma atividade recente encontrada</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Dados para os gráficos
        const progressData = <?php echo json_encode($progresso_semanal); ?>;
        const subjectData = <?php echo json_encode($stats_assuntos); ?>;

        // Gráfico de Progresso Semanal
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: progressData.map(item => {
                    const date = new Date(item.dia);
                    return date.toLocaleDateString('pt-BR', { weekday: 'short', day: '2-digit' });
                }),
                datasets: [{
                    label: 'Questões Respondidas',
                    data: progressData.map(item => item.questoes_respondidas),
                    borderColor: '#0072FF',
                    backgroundColor: 'rgba(0, 114, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Acertos',
                    data: progressData.map(item => item.acertos),
                    borderColor: '#00C6FF',
                    backgroundColor: 'rgba(0, 198, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

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

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .chart-card, .subject-performance-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>