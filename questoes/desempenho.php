<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Buscar estatísticas do usuário
try {
    $user_id = $_SESSION['user_id'];
    
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Desempenho - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="desempenho-body">
    <?php 
    $breadcrumb_items = [
        ['icon' => 'fas fa-home', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
        ['icon' => 'fas fa-chart-line', 'text' => 'Desempenho', 'link' => '', 'current' => true]
    ];
    include 'header.php'; 
    ?>

    <main id="main-scroll-content" class="desempenho-main">
        <!-- Header da Página -->
        <section class="desempenho-hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-title">
                        <i class="fas fa-chart-line"></i>
                        Meu Desempenho
                    </h1>
                    <p class="hero-subtitle">Acompanhe sua evolução e estatísticas detalhadas</p>
                </div>
                <div class="hero-user">
                    <div class="user-avatar">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="user-info">
                        <h3 class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></h3>
                        <p class="user-level">Estudante Ativo</p>
                    </div>
                </div>
            </div>
        </section>

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

        <!-- Gráficos -->
        <section class="charts-section">
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-bar"></i>
                            Progresso dos Últimos 7 Dias
                        </h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-pie"></i>
                            Performance por Assunto
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
            const cards = document.querySelectorAll('.stat-card, .chart-card, .subject-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>