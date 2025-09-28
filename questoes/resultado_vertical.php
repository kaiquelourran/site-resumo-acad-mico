<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Captura parâmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'nao-respondidas';

// Busca informações do assunto
$assunto_nome = 'Todas as Questões';
if ($id_assunto > 0) {
    $stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
    $stmt_assunto->execute([$id_assunto]);
    $assunto_nome = $stmt_assunto->fetchColumn() ?: 'Assunto não encontrado';
}

// Recuperar resultados da sessão
$resultados = $_SESSION['resultados_quiz_vertical'] ?? [];
unset($_SESSION['resultados_quiz_vertical']); // Limpar da sessão

if (empty($resultados)) {
    header("Location: listar_questoes.php?id=$id_assunto&filtro=$filtro_ativo");
    exit;
}

// Calcular estatísticas
$total_questoes = count($resultados);
$acertos = array_sum(array_column($resultados, 'acertou'));
$erros = $total_questoes - $acertos;
$percentual = $total_questoes > 0 ? round(($acertos / $total_questoes) * 100, 1) : 0;

// Buscar detalhes das questões para exibir
$ids_questoes = array_keys($resultados);
$placeholders = str_repeat('?,', count($ids_questoes) - 1) . '?';
$sql = "SELECT * FROM questoes WHERE id_questao IN ($placeholders) ORDER BY id_questao";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids_questoes);
$questoes_detalhes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar questões por ID para fácil acesso
$questoes_por_id = [];
foreach ($questoes_detalhes as $questao) {
    $questoes_por_id[$questao['id_questao']] = $questao;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do Quiz Vertical</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .main-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .result-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .result-title {
            color: #333;
            font-size: 2.5em;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .result-subtitle {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .score-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .score-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 2px solid #e0e0e0;
        }

        .score-card.success {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
        }

        .score-card.danger {
            border-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        }

        .score-card.info {
            border-color: #17a2b8;
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
        }

        .score-number {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .score-label {
            font-size: 1.1em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .percentage-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8em;
            font-weight: 700;
            color: white;
        }

        .percentage-excellent {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .percentage-good {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .percentage-poor {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .questions-review {
            margin-top: 40px;
        }

        .review-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }

        .question-result {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #e0e0e0;
        }

        .question-result.correct {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f8fff9, #f0fff4);
        }

        .question-result.incorrect {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff8f8, #fff0f0);
        }

        .question-number-result {
            font-weight: 600;
            color: #666;
            margin-bottom: 10px;
        }

        .question-text-result {
            font-size: 1.1em;
            line-height: 1.6;
            color: #333;
            margin-bottom: 15px;
        }

        .answer-info {
            display: grid;
            gap: 10px;
        }

        .answer-row {
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 500;
        }

        .your-answer {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            color: #1976d2;
        }

        .correct-answer {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            color: #2e7d32;
        }

        .wrong-answer {
            background: #ffebee;
            border: 1px solid #f44336;
            color: #c62828;
        }

        .navigation-buttons {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .btn-nav {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }

        .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .content-wrapper {
                padding: 20px;
            }
            
            .result-title {
                font-size: 2em;
            }
            
            .score-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content-wrapper">
            <div class="result-header">
                <h1 class="result-title">🎯 Resultado do Quiz</h1>
                <p class="result-subtitle"><?php echo htmlspecialchars($assunto_nome); ?></p>
                
                <div class="percentage-circle <?php 
                    if ($percentual >= 80) echo 'percentage-excellent';
                    elseif ($percentual >= 60) echo 'percentage-good';
                    else echo 'percentage-poor';
                ?>">
                    <?php echo $percentual; ?>%
                </div>
            </div>

            <div class="score-summary">
                <div class="score-card info">
                    <div class="score-number"><?php echo $total_questoes; ?></div>
                    <div class="score-label">Total de Questões</div>
                </div>
                
                <div class="score-card success">
                    <div class="score-number"><?php echo $acertos; ?></div>
                    <div class="score-label">Acertos</div>
                </div>
                
                <div class="score-card danger">
                    <div class="score-number"><?php echo $erros; ?></div>
                    <div class="score-label">Erros</div>
                </div>
            </div>

            <div class="questions-review">
                <h2 class="review-title">📋 Revisão das Questões</h2>
                
                <?php foreach ($resultados as $id_questao => $resultado): ?>
                    <?php $questao = $questoes_por_id[$id_questao]; ?>
                    <div class="question-result <?php echo $resultado['acertou'] ? 'correct' : 'incorrect'; ?>">
                        <div class="question-number-result">
                            <?php echo $resultado['acertou'] ? '✅' : '❌'; ?> 
                            Questão #<?php echo $id_questao; ?>
                        </div>
                        
                        <div class="question-text-result">
                            <?php echo htmlspecialchars($questao['enunciado']); ?>
                        </div>
                        
                        <div class="answer-info">
                            <div class="answer-row your-answer">
                                <strong>Sua resposta:</strong> 
                                <?php echo $resultado['alternativa_selecionada']; ?>) 
                                <?php echo htmlspecialchars($questao['alternativa_' . strtolower($resultado['alternativa_selecionada'])]); ?>
                            </div>
                            
                            <?php if (!$resultado['acertou']): ?>
                                <div class="answer-row correct-answer">
                                    <strong>Resposta correta:</strong> 
                                    <?php echo $resultado['resposta_correta']; ?>) 
                                    <?php echo htmlspecialchars($questao['alternativa_' . strtolower($resultado['resposta_correta'])]); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="navigation-buttons">
                <a href="listar_questoes.php?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>" 
                   class="btn-nav">
                    📋 Voltar à Lista
                </a>
                <a href="quiz_vertical.php?id=<?php echo $id_assunto; ?>&filtro=nao-respondidas" 
                   class="btn-nav btn-secondary">
                    🔄 Fazer Novo Quiz
                </a>
                <a href="index.php" class="btn-nav btn-secondary">
                    🏠 Início
                </a>
            </div>
        </div>
    </div>

    <script>
        // Animações suaves
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.score-card, .question-result');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    element.style.transition = 'all 0.5s ease';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>