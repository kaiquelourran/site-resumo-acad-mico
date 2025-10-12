<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Configurar para exibir erros
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Verificar se é uma requisição AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_request'])) {
    header('Content-Type: application/json');
    
    $id_questao = (int)$_POST['id_questao'];
    $alternativa_selecionada = strtoupper(trim($_POST['alternativa_selecionada']));
    $user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 1;
    
    try {
        // Buscar as alternativas da questão para mapear a letra correta
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$id_questao]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapear a letra selecionada para o ID da alternativa (ordem original do banco)
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $id_alternativa = null;
        foreach ($alternativas_questao as $index => $alternativa) {
            $letra = $letras[$index] ?? ($index + 1);
            if (strtoupper($letra) === strtoupper($alternativa_selecionada)) {
                $id_alternativa = $alternativa['id_alternativa'];
                break;
            }
        }
        
        // Buscar a alternativa correta para esta questão (ordem original do banco)
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        if ($alternativa_correta && $id_alternativa) {
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
            
            // Inserir resposta (permitir múltiplas respostas)
            try {
                $stmt_check = $pdo->query("DESCRIBE respostas_usuario");
                $colunas = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                $tem_user_id = in_array('user_id', $colunas);
                
                if ($tem_user_id) {
                    $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
                } else {
                    $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
                }
            } catch (Exception $e) {
                error_log("ERRO ao verificar estrutura da tabela: " . $e->getMessage());
                $stmt_resposta = $pdo->prepare("
                    INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
            }
            
            // Encontrar a letra da alternativa correta
            $letra_correta = null;
            foreach ($alternativas_questao as $index => $alt) {
                if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                    $letra_correta = $letras[$index] ?? ($index + 1);
                    break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'acertou' => (bool)$acertou,
                'alternativa_correta' => $letra_correta,
                'explicacao' => '',
                'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erro: Alternativa não encontrada'
            ]);
        }
    } catch (Exception $e) {
        error_log("ERRO no processamento: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Processamento normal da página
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro = $_GET['filtro'] ?? 'todas';
$questao_inicial = isset($_GET['questao_inicial']) ? (int)$_GET['questao_inicial'] : 0;

// Buscar questões do assunto
$stmt_questoes = $pdo->prepare("
    SELECT q.*, a.nome as nome_assunto 
    FROM questoes q 
    JOIN assuntos a ON q.id_assunto = a.id_assunto 
    WHERE q.id_assunto = ?
");
$stmt_questoes->execute([$id_assunto]);
$questoes = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);

// Aplicar filtros
if ($filtro === 'certas') {
    $questoes_filtradas = [];
    foreach ($questoes as $questao) {
        $stmt_resposta = $pdo->prepare("
            SELECT COUNT(*) as total, SUM(acertou) as acertos 
            FROM respostas_usuario 
            WHERE id_questao = ?
        ");
        $stmt_resposta->execute([$questao['id_questao']]);
        $stats = $stmt_resposta->fetch(PDO::FETCH_ASSOC);
        
        if ($stats['total'] > 0 && $stats['acertos'] > 0) {
            $questoes_filtradas[] = $questao;
        }
    }
    $questoes = $questoes_filtradas;
} elseif ($filtro === 'erradas') {
    $questoes_filtradas = [];
    foreach ($questoes as $questao) {
        $stmt_resposta = $pdo->prepare("
            SELECT COUNT(*) as total, SUM(acertou) as acertos 
            FROM respostas_usuario 
            WHERE id_questao = ?
        ");
        $stmt_resposta->execute([$questao['id_questao']]);
        $stats = $stmt_resposta->fetch(PDO::FETCH_ASSOC);
        
        if ($stats['total'] > 0 && $stats['acertos'] == 0) {
            $questoes_filtradas[] = $questao;
        }
    }
    $questoes = $questoes_filtradas;
}

// Encontrar questão inicial
$questao_atual = null;
$indice_atual = 0;
if ($questao_inicial > 0) {
    foreach ($questoes as $index => $questao) {
        if ($questao['id_questao'] == $questao_inicial) {
            $questao_atual = $questao;
            $indice_atual = $index;
            break;
        }
    }
}

if (!$questao_atual && !empty($questoes)) {
    $questao_atual = $questoes[0];
    $indice_atual = 0;
}

if (!$questao_atual) {
    die("Nenhuma questão encontrada para este assunto.");
}

// Buscar alternativas da questão atual
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$questao_atual['id_questao']]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas da questão atual
$stmt_stats = $pdo->prepare("
    SELECT COUNT(*) as total, SUM(acertou) as acertos 
    FROM respostas_usuario 
    WHERE id_questao = ?
");
$stmt_stats->execute([$questao_atual['id_questao']]);
$stats_questao = $stmt_stats->fetch(PDO::FETCH_ASSOC);

$total_respostas = $stats_questao['total'] ?? 0;
$acertos = $stats_questao['acertos'] ?? 0;
$erros = $total_respostas - $acertos;
$percentual_acerto = $total_respostas > 0 ? round(($acertos / $total_respostas) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?php echo htmlspecialchars($questao_atual['nome_assunto']); ?></title>
    <link rel="stylesheet" href="modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .quiz-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .question-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .question-number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .question-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .status-certa {
            background: #d4edda;
            color: #155724;
        }
        
        .status-errada {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-nao-respondida {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .question-text {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 25px;
        }
        
        .alternatives-container {
            margin-bottom: 20px;
        }
        
        .alternative {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        
        .alternative:hover {
            background: #e9ecef;
            border-color: #007bff;
        }
        
        .alternative-letter {
            background: #007bff;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .alternative-text {
            flex: 1;
        }
        
        .alternative-correct {
            background: #d4edda !important;
            border-color: #28a745 !important;
            color: #155724;
        }
        
        .alternative-correct .alternative-letter {
            background: #28a745;
        }
        
        .alternative-incorrect-chosen {
            background: #f8d7da !important;
            border-color: #dc3545 !important;
            color: #721c24;
        }
        
        .alternative-incorrect-chosen .alternative-letter {
            background: #dc3545;
        }
        
        .stats-toggle-container {
            margin-top: 20px;
            text-align: center;
        }
        
        .stats-toggle-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .stats-toggle-btn:hover {
            background: #5a6268;
        }
        
        .stats-toggle-btn.active {
            background: #007bff;
        }
        
        .stats-panel {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .stats-loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .stats-content {
            display: none;
        }
        
        .stats-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stats-chart {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-chart h4 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 14px;
            text-align: center;
        }
        
        .stats-chart canvas {
            max-height: 200px;
        }
        
        .stats-history-list {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .stats-history-item:last-child {
            border-bottom: none;
        }
        
        .stats-history-item.correct {
            color: #155724;
        }
        
        .stats-history-item.incorrect {
            color: #721c24;
        }
        
        .stats-history-date {
            font-size: 13px;
        }
        
        .stats-history-result {
            font-weight: bold;
            font-size: 13px;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .nav-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .nav-btn:hover {
            background: #0056b3;
        }
        
        .nav-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .nav-info {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        
        .filters {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .filter-btn {
            background: #e9ecef;
            color: #495057;
            border: 1px solid #ced4da;
            padding: 8px 16px;
            margin: 0 5px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            background: #dee2e6;
        }
        
        .filter-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        @media (max-width: 768px) {
            .stats-charts {
                grid-template-columns: 1fr;
            }
            
            .navigation {
                flex-direction: column;
                gap: 15px;
            }
            
            .question-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="quiz-container">
        <h1 style="text-align: center; margin-bottom: 30px; color: #333;">
            <i class="fas fa-question-circle"></i> Quiz - <?php echo htmlspecialchars($questao_atual['nome_assunto']); ?>
        </h1>
        
        <!-- Filtros -->
        <div class="filters">
            <a href="?id=<?php echo $id_assunto; ?>&filtro=todas" class="filter-btn <?php echo $filtro === 'todas' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Todas
            </a>
            <a href="?id=<?php echo $id_assunto; ?>&filtro=certas" class="filter-btn <?php echo $filtro === 'certas' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> Certas
            </a>
            <a href="?id=<?php echo $id_assunto; ?>&filtro=erradas" class="filter-btn <?php echo $filtro === 'erradas' ? 'active' : ''; ?>">
                <i class="fas fa-times-circle"></i> Erradas
            </a>
        </div>
        
        <!-- Questão Atual -->
        <div class="question-card" id="questao-<?php echo $questao_atual['id_questao']; ?>" data-respondida="false">
            <div class="question-header">
                <div class="question-number">
                    Questão #<?php echo $questao_atual['id_questao']; ?>
                </div>
                <div class="question-status status-<?php echo $total_respostas > 0 ? ($acertos > 0 ? 'certa' : 'errada') : 'nao-respondida'; ?>">
                    <?php if ($total_respostas > 0): ?>
                        <?php if ($acertos > 0): ?>
                            <i class="fas fa-check-circle"></i> Acertou
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> Errou
                        <?php endif; ?>
                    <?php else: ?>
                        <i class="fas fa-question-circle"></i> Não respondida
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="question-text">
                <?php echo nl2br(htmlspecialchars($questao_atual['enunciado'])); ?>
            </div>
            
            <form class="questoes-form" data-questao-id="<?php echo $questao_atual['id_questao']; ?>">
                <div class="alternatives-container">
                    <?php
                    $letras = ['A', 'B', 'C', 'D', 'E'];
                    foreach ($alternativas_questao as $index => $alternativa):
                        $letra = $letras[$index] ?? ($index + 1);
                        $is_correct = ($alternativa['eh_correta'] == 1);
                    ?>
                        <div class="alternative" 
                             data-alternativa="<?php echo $letra; ?>" 
                             data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>" 
                             data-questao-id="<?php echo $questao_atual['id_questao']; ?>">
                            <div class="alternative-letter"><?php echo $letra; ?></div>
                            <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
            
            <!-- Botão de Estatísticas -->
            <div class="stats-toggle-container">
                <button class="stats-toggle-btn" data-questao-id="<?php echo $questao_atual['id_questao']; ?>">
                    <i class="fas fa-chart-bar"></i> Ver Estatísticas
                </button>
            </div>
            
            <!-- Painel de Estatísticas -->
            <div class="stats-panel" id="stats-<?php echo $questao_atual['id_questao']; ?>">
                <div class="stats-loading">
                    <i class="fas fa-spinner fa-spin"></i> Carregando estatísticas...
                </div>
                <div class="stats-content">
                    <div class="stats-charts">
                        <div class="stats-chart">
                            <h4>Percentual de Rendimento</h4>
                            <canvas id="chart-pie-<?php echo $questao_atual['id_questao']; ?>" width="200" height="200"></canvas>
                        </div>
                        <div class="stats-chart">
                            <h4>Alternativas mais respondidas</h4>
                            <canvas id="chart-bar-<?php echo $questao_atual['id_questao']; ?>" width="200" height="200"></canvas>
                        </div>
                    </div>
                    <div class="stats-history-list">
                        <h4>Histórico de Respostas</h4>
                        <div id="history-<?php echo $questao_atual['id_questao']; ?>">
                            <!-- Histórico será carregado via JavaScript -->
                        </div>
                        <div class="history-load-more" style="text-align:center; margin-top:10px;">
                            <button class="load-more-btn" data-role="history">Carregar mais</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navegação -->
        <div class="navigation">
            <?php if ($indice_atual > 0): ?>
                <a href="?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro; ?>&questao_inicial=<?php echo $questoes[$indice_atual - 1]['id_questao']; ?>" class="nav-btn">
                    <i class="fas fa-arrow-left"></i> Anterior
                </a>
            <?php else: ?>
                <span class="nav-btn" style="background: #6c757d; cursor: not-allowed;">
                    <i class="fas fa-arrow-left"></i> Anterior
                </span>
            <?php endif; ?>
            
            <div class="nav-info">
                Questão <?php echo $indice_atual + 1; ?> de <?php echo count($questoes); ?>
            </div>
            
            <?php if ($indice_atual < count($questoes) - 1): ?>
                <a href="?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro; ?>&questao_inicial=<?php echo $questoes[$indice_atual + 1]['id_questao']; ?>" class="nav-btn">
                    Próxima <i class="fas fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <span class="nav-btn" style="background: #6c757d; cursor: not-allowed;">
                    Próxima <i class="fas fa-arrow-right"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Load Chart.js library
        const chartScript = document.createElement('script');
        chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        document.head.appendChild(chartScript);

        // Statistics toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const statsButtons = document.querySelectorAll('.stats-toggle-btn');
            
            statsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const questaoId = this.dataset.questaoId;
                    const statsPanel = document.getElementById('stats-' + questaoId);
                    const isOpen = statsPanel.style.display !== 'none';
                    
                    if (isOpen) {
                        // Close panel
                        statsPanel.style.display = 'none';
                        this.classList.remove('active');
                    } else {
                        // Open panel and load stats
                        statsPanel.style.display = 'block';
                        this.classList.add('active');
                        loadStatistics(questaoId);
                    }
                });
            });
        });

        function loadStatistics(questaoId) {
            const statsPanel = document.getElementById('stats-' + questaoId);
            const loadingDiv = statsPanel.querySelector('.stats-loading');
            const contentDiv = statsPanel.querySelector('.stats-content');
            
            loadingDiv.style.display = 'block';
            contentDiv.style.display = 'none';
            
            // Fetch statistics from API
            fetch('api_estatisticas.php?id_questao=' + questaoId)
                .then(response => response.json())
                .then(data => {
                    loadingDiv.style.display = 'none';
                    contentDiv.style.display = 'block';
                    
                    // Wait for Chart.js to load
                    if (typeof Chart === 'undefined') {
                        setTimeout(() => renderCharts(questaoId, data), 500);
                    } else {
                        renderCharts(questaoId, data);
                    }
                })
                .catch(error => {
                    loadingDiv.innerHTML = '<p style="color: #dc3545;">Erro ao carregar estatísticas</p>';
                });
        }

        function renderCharts(questaoId, data) {
            // Pie Chart - Correct vs Incorrect
            const pieCtx = document.getElementById('chart-pie-' + questaoId).getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Acertos', 'Erros'],
                    datasets: [{
                        data: [data.acertos, data.erros],
                        backgroundColor: ['#5FD08D', '#E06B7D'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { font: { size: 11 } }
                        }
                    }
                }
            });
            
            // Bar Chart - Alternatives distribution
            const barCtx = document.getElementById('chart-bar-' + questaoId).getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: ['A', 'B', 'C', 'D', 'E'],
                    datasets: [{
                        label: 'Respostas',
                        data: [
                            data.alternativas.A || 0,
                            data.alternativas.B || 0,
                            data.alternativas.C || 0,
                            data.alternativas.D || 0,
                            data.alternativas.E || 0
                        ],
                        backgroundColor: ['#FFB84D', '#5DADE2', '#F4D03F', '#A3CB7F', '#EC7063']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            });
            
            // Render history list paginada
            const historyList = document.querySelector('#stats-' + questaoId + ' .stats-history-list');
            const all = (data.historico || []).slice();
            if (all.length > 0) {
                window.HISTORY_STATE = window.HISTORY_STATE || {};
                window.HISTORY_STATE[questaoId] = { all: all, visibleCount: Math.min(5, all.length) };
                renderHistory(questaoId, all.slice(0, 5));
                initHistoryLoadMore(questaoId);
            } else {
                historyList.innerHTML = '<p style="text-align: center; color: #6c757d;">Você ainda não respondeu esta questão.</p>';
            }
        }

        // Funções para histórico paginado
        function renderHistory(questaoId, items) {
            const container = document.querySelector('#history-' + questaoId);
            const panel = document.querySelector('#stats-' + questaoId);
            const listWrapper = panel.querySelector('.stats-history-list');
            const btn = panel.querySelector('.history-load-more .load-more-btn');
            if (!container || !listWrapper) return;
            const titleEl = listWrapper.querySelector('h4');
            if (titleEl) { titleEl.insertAdjacentElement('afterend', container); }
            container.innerHTML = items.map(item => `
                <div class="stats-history-item ${item.acertou ? 'correct' : 'incorrect'}">
                    <span class="stats-history-date">Em ${item.data}, você respondeu a opção ${item.alternativa}.</span>
                    <span class="stats-history-result">
                        ${item.acertou ? '<i class="fas fa-check-circle"></i> Você acertou!' : '<i class="fas fa-times-circle"></i> Você errou!'}
                    </span>
                </div>
            `).join('');
            const state = (window.HISTORY_STATE || {})[questaoId];
            if (btn) {
                btn.style.display = state && state.visibleCount < state.all.length ? 'inline-block' : 'none';
            }
        }
        
        function initHistoryLoadMore(questaoId) {
            const btn = document.querySelector(`#stats-${questaoId} .history-load-more .load-more-btn`);
            const state = (window.HISTORY_STATE = window.HISTORY_STATE || {});
            if (!btn) return;
            btn.onclick = function() {
                const s = (window.HISTORY_STATE || {})[questaoId];
                if (!s) return;
                s.visibleCount = Math.min(s.visibleCount + 5, s.all.length);
                renderHistory(questaoId, s.all.slice(0, s.visibleCount));
            };
        }

        // Event listeners para as alternativas - VERSÃO SIMPLIFICADA
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, configurando alternativas...');
            
            // Configurar TODAS as alternativas de uma vez
            const todasAlternativas = document.querySelectorAll('.alternative');
            console.log('Total de alternativas encontradas:', todasAlternativas.length);
            
            todasAlternativas.forEach((alternativa, index) => {
                console.log('Configurando alternativa', index + 1);
                
                // Garantir que seja clicável
                alternativa.style.pointerEvents = 'auto';
                alternativa.style.cursor = 'pointer';
                alternativa.style.position = 'relative';
                alternativa.style.zIndex = '10';
                
                // Remover classes de feedback
                alternativa.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                
                // Adicionar event listener diretamente
                alternativa.addEventListener('click', function(e) {
                    console.log('🔥 CLIQUE DETECTADO!', this);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest('.question-card');
                    
                    console.log('Questão ID:', questaoId);
                    console.log('Alternativa selecionada:', alternativaSelecionada);
                    console.log('Questão card:', questaoCard);
                    
                    // Verificar se já foi respondida
                    if (questaoCard.dataset.respondida === 'true') {
                        console.log('Questão já respondida, ignorando...');
                        return;
                    }
                    
                    // Destacar a alternativa clicada imediatamente
                    const todasAlternativas = questaoCard.querySelectorAll('.alternative');
                    todasAlternativas.forEach(alt => {
                        alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                        alt.style.background = '';
                        alt.style.borderColor = '';
                    });
                    
                    // Marcar como selecionada
                    this.style.background = '#e3f2fd';
                    this.style.borderColor = '#2196f3';
                    
                    // Marcar questão como respondida
                    questaoCard.dataset.respondida = 'true';
                    
                    // Enviar resposta via AJAX
                    const formData = new FormData();
                    formData.append('id_questao', questaoId);
                    formData.append('alternativa_selecionada', alternativaSelecionada);
                    formData.append('ajax_request', '1');
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Resposta recebida:', response);
                        console.log('Status:', response.status);
                        return response.text();
                    })
                    .then(data => {
                        console.log('Dados recebidos:', data);
                        try {
                            const jsonData = JSON.parse(data);
                            console.log('JSON parseado:', jsonData);
                            
                            if (jsonData.success) {
                                console.log('Sucesso! Mostrando feedback...');
                                
                                // Feedback visual simples
                                const alternativaCorreta = jsonData.alternativa_correta;
                                const acertou = alternativaSelecionada === alternativaCorreta;
                                
                                // Marcar alternativa correta em verde
                                const altCorreta = questaoCard.querySelector(`[data-alternativa="${alternativaCorreta}"]`);
                                if (altCorreta) {
                                    altCorreta.classList.add('alternative-correct');
                                }
                                
                                // Marcar alternativa selecionada
                                if (acertou) {
                                    this.classList.add('alternative-correct');
                                } else {
                                    this.classList.add('alternative-incorrect-chosen');
                                }
                                
                                // Desabilitar cliques após mostrar feedback
                                setTimeout(() => {
                                    todasAlternativas.forEach(alt => {
                                        alt.style.pointerEvents = 'none';
                                        alt.style.cursor = 'default';
                                    });
                                }, 1000);
                                
                            } else {
                                console.log('Erro na resposta:', jsonData.message);
                                // Reabilitar cliques em caso de erro
                                questaoCard.dataset.respondida = 'false';
                            }
                        } catch (e) {
                            console.error('Erro ao fazer parse do JSON:', e);
                            console.log('Dados brutos:', data);
                            // Reabilitar cliques em caso de erro
                            questaoCard.dataset.respondida = 'false';
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição:', error);
                        // Reabilitar cliques em caso de erro
                        questaoCard.dataset.respondida = 'false';
                    });
                });
            });
        });

    </script>
</body>
</html>
