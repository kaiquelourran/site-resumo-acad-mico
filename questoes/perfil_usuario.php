<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Verificação de modo de manutenção


// Redireciona para a página de login se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$nome_usuario = $_SESSION['nome_usuario'];

// --- Consultas para o painel de usuário ---
$stmt = $pdo->prepare("SELECT acertou, data_resposta FROM respostas_usuarios WHERE id_usuario = ? ORDER BY data_resposta ASC");
$stmt->execute([$id_usuario]);
$respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_respostas_usuario = count($respostas);
$respostas_corretas_usuario = 0;
foreach ($respostas as $resposta) {
    if ($resposta['acertou'] == 1) {
        $respostas_corretas_usuario++;
    }
}
$respostas_erradas_usuario = $total_respostas_usuario - $respostas_corretas_usuario;

$porcentagem_acertos_usuario = ($total_respostas_usuario > 0) ? ($respostas_corretas_usuario / $total_respostas_usuario) * 100 : 0;

// Dados para o gráfico de histórico
$historico_acertos = [];
$labels_historico = [];
$acertos_acumulados = 0;
$erros_acumulados = 0;
foreach ($respostas as $resposta) {
    if ($resposta['acertou'] == 1) {
        $acertos_acumulados++;
    } else {
        $erros_acumulados++;
    }
    // Para simplificar, vamos usar apenas a data da resposta como label
    $data_formatada = (new DateTime($resposta['data_resposta']))->format('d/m/Y');
    if (!in_array($data_formatada, $labels_historico)) {
        $labels_historico[] = $data_formatada;
        $historico_acertos[] = $acertos_acumulados;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Usuário</title>
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="modern-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            max-width: 900px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .dashboard-container h2 {
            margin-bottom: 20px;
        }
        .dashboard-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .info-card {
            background-color: #f0f8ff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            flex-basis: 30%;
        }
        .info-card h3 {
            margin-bottom: 5px;
            color: #333;
        }
        .info-card p {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .dashboard-charts {
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }
        .chart-box {
            width: 45%; /* Ajustado para melhor visualização dos 2 gráficos */
        }
        /* Adicionado para garantir a responsividade do canvas */
        canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
<?php
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
    ['icon' => '👤', 'text' => 'Meu Desempenho', 'link' => 'perfil_usuario.php', 'current' => true]
];
$page_title = 'Painel de Desempenho';
$page_subtitle = 'Acompanhe sua evolução';
include 'header.php';
?>

    <main class="conteudo-principal">
        <div class="dashboard-container">
            <h2>Olá, <?= htmlspecialchars($nome_usuario) ?>. Veja como está seu desempenho!</h2>

            <div class="dashboard-info">
                <div class="info-card">
                    <h3>Resoluções de Questões</h3>
                    <p><?= htmlspecialchars($total_respostas_usuario) ?></p>
                </div>
                <div class="info-card">
                    <h3>Resoluções Corretas</h3>
                    <p><?= htmlspecialchars($respostas_corretas_usuario) ?></p>
                </div>
                <div class="info-card">
                    <h3>Porcentagem de Acertos</h3>
                    <p><?= htmlspecialchars(number_format($porcentagem_acertos_usuario, 0)) ?>%</p>
                </div>
            </div>

            <div class="dashboard-charts">
                <div class="chart-box">
                    <h3>Acertos e Erros</h3>
                    <canvas id="donutChart"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Histórico de Resoluções</h3>
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <div class="actions-right" style="margin-top: 30px;">
                <a href="index.php" class="btn btn-primary">Voltar ao menu</a>
            </div>
        </div>
    </main>

<?php include 'footer.php'; ?>
    
    <script>
        // Dados do PHP
        const acertos = <?= json_encode($respostas_corretas_usuario) ?>;
        const erros = <?= json_encode($respostas_erradas_usuario) ?>;

        // Gráfico de Donut (Acertos e Erros)
        const donutCtx = document.getElementById('donutChart').getContext('2d');
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: ['Acertos', 'Erros'],
                datasets: [{
                    data: [acertos, erros],
                    backgroundColor: ['#4caf50', '#f44336'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });

        // Dados do PHP para o gráfico de linha
        const labelsHistorico = <?= json_encode($labels_historico) ?>;
        const dadosHistorico = <?= json_encode($historico_acertos) ?>;

        // Gráfico de Linha (Histórico)
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: labelsHistorico,
                datasets: [{
                    label: 'Acertos Acumulados',
                    data: dadosHistorico,
                    borderColor: '#007bff',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Data'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Número de Acertos'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>