<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Captura parâmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'nao-respondidas';
$questao_atual = isset($_GET['questao_atual']) ? (int)$_GET['questao_atual'] : 1;

// Busca informações do assunto
$assunto_nome = 'Todas as Questões';
if ($id_assunto > 0) {
    $stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
    $stmt_assunto->execute([$id_assunto]);
    $assunto_nome = $stmt_assunto->fetchColumn() ?: 'Assunto não encontrado';
}

// Processar resposta individual se enviada via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_questao = $_POST['id_questao'] ?? 0;
    $alternativa_selecionada = $_POST['alternativa_selecionada'] ?? '';
    
    if ($id_questao && $alternativa_selecionada) {
        // Buscar a questão e verificar se a resposta está correta
        $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
        $stmt->execute([$id_questao]);
        $questao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($questao) {
            $acertou = ($alternativa_selecionada === $questao['resposta_correta']) ? 1 : 0;
            
            // Buscar o ID da alternativa baseado na letra e no texto
            $campo_alternativa = 'alternativa_' . strtolower($alternativa_selecionada);
            $texto_alternativa = $questao[$campo_alternativa];
            
            $stmt_alt = $pdo->prepare("SELECT id_alternativa FROM alternativas WHERE id_questao = ? AND texto = ?");
            $stmt_alt->execute([$id_questao, $texto_alternativa]);
            $alternativa = $stmt_alt->fetch(PDO::FETCH_ASSOC);
            
            if ($alternativa) {
                $id_alternativa = $alternativa['id_alternativa'];
                
                // Inserir ou atualizar resposta do usuário
                $stmt_resposta = $pdo->prepare("
                    INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    id_alternativa = VALUES(id_alternativa),
                    acertou = VALUES(acertou),
                    data_resposta = VALUES(data_resposta)
                ");
                $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
                
                // Salvar feedback na sessão
                $_SESSION['feedback_questao'] = [
                    'id_questao' => $id_questao,
                    'acertou' => $acertou,
                    'resposta_correta' => $questao['resposta_correta'],
                    'alternativa_selecionada' => $alternativa_selecionada,
                    'explicacao' => $questao['explicacao'] ?? ''
                ];
            }
        }
    }
    
    // Redirecionar para a mesma página para mostrar feedback
    header("Location: quiz_vertical.php?id=$id_assunto&filtro=$filtro_ativo&questao_atual=$questao_atual");
    exit;
}

// Buscar questões baseadas no filtro
$sql = "SELECT q.*, a.nome as assunto_nome, 
               CASE 
                   WHEN r.acertou = 1 THEN 'acertada'
                   WHEN r.acertou = 0 THEN 'errada'
                   WHEN r.id_questao IS NOT NULL THEN 'respondida'
                   ELSE 'nao-respondida'
               END as status_questao,
               r.id_alternativa as resposta_usuario_id
        FROM questoes q 
        LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
        LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao";

$where_conditions = [];
$params = [];

if ($id_assunto > 0) {
    $where_conditions[] = "q.id_assunto = ?";
    $params[] = $id_assunto;
}

// Aplicar filtro
switch ($filtro_ativo) {
    case 'acertadas':
        $where_conditions[] = "r.acertou = 1";
        break;
    case 'erradas':
        $where_conditions[] = "r.acertou = 0";
        break;
    case 'respondidas':
        $where_conditions[] = "r.id_questao IS NOT NULL";
        break;
    case 'nao-respondidas':
    default:
        $where_conditions[] = "r.id_questao IS NULL";
        break;
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY q.id_questao";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recuperar feedback da sessão se existir
$feedback = $_SESSION['feedback_questao'] ?? null;
unset($_SESSION['feedback_questao']); // Limpar da sessão após usar
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Vertical - Questões Não Respondidas</title>
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

        .quiz-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .quiz-title {
            color: #333;
            font-size: 2.2em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .quiz-subtitle {
            color: #666;
            font-size: 1.1em;
            margin-bottom: 15px;
        }

        .questions-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            font-weight: 600;
        }

        .question-vertical {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .question-vertical:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .question-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .question-text {
            font-size: 1.1em;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alternatives {
            display: grid;
            gap: 12px;
        }

        .alternative {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            position: relative;
            overflow: hidden;
        }

        .alternative::before {
            content: attr(data-letter);
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9em;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .alternative span {
            flex: 1;
            font-size: 1.05em;
            line-height: 1.5;
            color: #2d3748;
            margin-left: 50px;
            font-weight: 500;
        }

        .alternative:hover {
            border-color: #667eea;
            background: #f0f4ff;
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .alternative:hover::before {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .alternative.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
            transform: translateX(12px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }

        .alternative.selected::before {
            background: linear-gradient(135deg, #4c51bf 0%, #553c9a 100%);
            transform: translateY(-50%) scale(1.15);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .alternative.selected span {
            color: #667eea;
            font-weight: 600;
        }

        .navigation-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 15px;
            gap: 15px;
        }

        .btn-nav {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-prev {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .btn-next {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .feedback-section {
            margin: 20px 0;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease;
        }

        .feedback-correct {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
        }

        .feedback-incorrect {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid #dc3545;
        }

        .feedback-icon {
            font-size: 2rem;
        }

        .feedback-text {
            flex: 1;
            font-size: 1.1rem;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-acertada {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-errada {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .status-respondida {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border: 2px solid #17a2b8;
        }

        .status-nao-respondida {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #495057;
            border: 2px solid #6c757d;
        }

        .alternative-correct {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            color: #155724;
        }

        .alternative-incorrect {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid #dc3545;
            color: #721c24;
        }

        .selected-mark {
            color: #007bff;
            font-weight: bold;
        }

        .correct-mark {
            color: #28a745;
            font-weight: bold;
        }

        .answer-form {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .answer-select {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
            min-width: 300px;
            background: white;
        }

        .answer-select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        }

        .btn-answer {
            padding: 10px 20px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-answer:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,123,255,0.3);
        }

        .answered-info {
            margin-top: 15px;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .status-correct {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-incorrect {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .question-feedback {
            margin-top: 15px;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .question-vertical {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .question-vertical:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-submit:not(:disabled) {
            opacity: 1;
            cursor: pointer;
        }

        .btn-submit:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 20px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }

        .empty-state-title {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: #333;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .content-wrapper {
                padding: 20px;
            }
            
            .quiz-title {
                font-size: 1.8em;
            }
            
            .question-vertical {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content-wrapper">
            <div class="quiz-header">
                <h1 class="quiz-title">📝 Quiz Vertical</h1>
                <p class="quiz-subtitle"><?php echo htmlspecialchars($assunto_nome); ?></p>
                <div class="questions-count">
                    📊 <?php echo count($questoes); ?> questões - Filtro: <?php echo ucfirst(str_replace('-', ' ', $filtro_ativo)); ?>
                </div>
                
                <?php if ($feedback): ?>
                    <div class="feedback-section <?php echo $feedback['acertou'] ? 'feedback-correct' : 'feedback-incorrect'; ?>">
                        <div class="feedback-icon">
                            <?php echo $feedback['acertou'] ? '✅' : '❌'; ?>
                        </div>
                        <div class="feedback-text">
                            <?php if ($feedback['acertou']): ?>
                                <strong>Parabéns! Resposta correta!</strong>
                            <?php else: ?>
                                <strong>Resposta incorreta.</strong><br>
                                A resposta correta é: <strong><?php echo $feedback['resposta_correta']; ?></strong>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($questoes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3 class="empty-state-title">Nenhuma questão encontrada</h3>
                    <p>Não há questões para o filtro selecionado neste assunto.</p>
                </div>
            <?php else: ?>
                <?php foreach ($questoes as $index => $questao): ?>
                    <div class="question-vertical" data-question-id="<?php echo $questao['id_questao']; ?>">
                        <div class="question-number">
                            Questão <?php echo $index + 1; ?> de <?php echo count($questoes); ?> - ID #<?php echo $questao['id_questao']; ?>
                            <span class="question-status status-<?php echo $questao['status_questao']; ?>">
                                <?php
                                switch ($questao['status_questao']) {
                                    case 'acertada':
                                        echo '✅ Acertada';
                                        break;
                                    case 'errada':
                                        echo '❌ Errada';
                                        break;
                                    case 'respondida':
                                        echo '📝 Respondida';
                                        break;
                                    default:
                                        echo '⭕ Não respondida';
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="question-text">
                            <?php echo htmlspecialchars($questao['enunciado']); ?>
                        </div>
                        
                        <div class="alternatives">
                            <?php
                            // Buscar alternativas da questão atual
                            $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
                            $stmt_alt->execute([$questao['id_questao']]);
                            $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
                            
                            $letras = ['A', 'B', 'C', 'D'];
                            foreach ($alternativas_questao as $index => $alternativa) {
                                $letra = $letras[$index] ?? ($index + 1);
                                
                                // Verificar se esta alternativa foi selecionada pelo usuário
                                $is_selected = ($questao['resposta_usuario_id'] == $alternativa['id_alternativa']);
                                $is_correct = ($alternativa['eh_correta'] == 1);
                                $class = '';
                                $clickable = '';
                                
                                if ($questao['status_questao'] != 'nao-respondida') {
                                    if ($is_correct) {
                                        $class = 'alternative-correct';
                                    } elseif ($is_selected && !$is_correct) {
                                        $class = 'alternative-incorrect';
                                    }
                                } else {
                                    $clickable = 'clickable-alternative';
                                }
                                
                                echo '<div class="alternative ' . $class . ' ' . $clickable . '" data-letter="' . $letra . '" data-question-id="' . $questao['id_questao'] . '" data-alternative-letter="' . $letra . '">';
                                echo '<span>' . htmlspecialchars($alternativa['texto']) . '</span>';
                                if ($is_selected) echo ' <span class="selected-mark">👈 Sua resposta</span>';
                                if ($is_correct && $questao['status_questao'] != 'nao-respondida') echo ' <span class="correct-mark">✓ Correta</span>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        
                        <div class="submit-section">
                            <?php if ($questao['status_questao'] == 'nao-respondida'): ?>
                                <form method="POST" class="answer-form" id="form-<?php echo $questao['id_questao']; ?>" style="display: inline;">
                                    <input type="hidden" name="id_questao" value="<?php echo $questao['id_questao']; ?>">
                                    <input type="hidden" name="alternativa_selecionada" id="selected-<?php echo $questao['id_questao']; ?>" value="">
                                    <button type="submit" class="btn-submit" id="btn-<?php echo $questao['id_questao']; ?>" disabled>
                                        🎯 Responder
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="answered-info">
                                    <?php if ($questao['status_questao'] == 'acertada'): ?>
                                        <span class="status-badge status-correct">✅ Você acertou!</span>
                                    <?php elseif ($questao['status_questao'] == 'errada'): ?>
                                        <span class="status-badge status-incorrect">❌ Você errou</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Mostrar feedback se for para esta questão -->
                        <?php if ($feedback && $feedback['id_questao'] == $questao['id_questao']): ?>
                            <div class="feedback-section <?php echo $feedback['acertou'] ? 'feedback-correct' : 'feedback-incorrect'; ?>">
                                <div class="feedback-icon">
                                    <?php echo $feedback['acertou'] ? '✅' : '❌'; ?>
                                </div>
                                <div class="feedback-text">
                                    <?php if ($feedback['acertou']): ?>
                                        <strong>Parabéns! Resposta correta!</strong>
                                    <?php else: ?>
                                        <strong>Resposta incorreta.</strong><br>
                                        A resposta correta é: <strong><?php echo $feedback['resposta_correta']; ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="navigation-section">
                    <a href="listar_questoes.php?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>" 
                       class="btn-back">
                        ← Voltar à Lista
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Animações suaves
        document.addEventListener('DOMContentLoaded', function() {
            const questions = document.querySelectorAll('.question-vertical');
            questions.forEach((question, index) => {
                question.style.opacity = '0';
                question.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    question.style.transition = 'all 0.6s ease';
                    question.style.opacity = '1';
                    question.style.transform = 'translateY(0)';
                }, index * 150);
            });

            // Interceptar envio de formulários para usar AJAX
            const forms = document.querySelectorAll('.answer-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const button = form.querySelector('.btn-submit');
                    const hiddenInput = form.querySelector('input[name="alternativa_selecionada"]');
                    const questionId = form.querySelector('input[name="id_questao"]').value;
                    
                    // Verificar se uma alternativa foi selecionada
                    if (!hiddenInput.value) {
                        alert('Por favor, selecione uma alternativa antes de responder.');
                        return;
                    }
                    
                    // Desabilitar botão durante o envio
                    button.disabled = true;
                    button.textContent = '⏳ Enviando...';
                    
                    // Adicionar parâmetro para indicar que é uma requisição AJAX
                    formData.append('ajax', '1');
                    
                    // Enviar via AJAX
                    fetch('processar_resposta.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar resultado na própria página
                            showAnswerResult(questionId, data);
                        } else {
                            throw new Error(data.message || 'Erro ao processar resposta');
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        alert('Erro ao enviar resposta. Tente novamente.');
                        
                        // Reabilitar botão em caso de erro
                        button.disabled = false;
                        button.textContent = '🎯 Responder';
                    });
                });
            });
            
            // Função para mostrar o resultado da resposta
            function showAnswerResult(questionId, data) {
                const questionDiv = document.querySelector(`[data-question-id="${questionId}"]`).closest('.question-card');
                const alternativesDiv = questionDiv.querySelector('.alternatives');
                const submitSection = questionDiv.querySelector('.submit-section');
                
                // Atualizar as alternativas para mostrar qual é a correta
                const alternatives = alternativesDiv.querySelectorAll('.alternative');
                alternatives.forEach(alt => {
                    const letter = alt.getAttribute('data-letter');
                    alt.classList.remove('clickable-alternative', 'selected');
                    
                    if (letter === data.resposta_correta) {
                        alt.classList.add('alternative-correct');
                        alt.innerHTML += ' <span class="correct-mark">✓ Correta</span>';
                    } else if (letter === data.alternativa_selecionada && !data.acertou) {
                        alt.classList.add('alternative-incorrect');
                        alt.innerHTML += ' <span class="selected-mark">👈 Sua resposta</span>';
                    }
                });
                
                // Atualizar a seção de submit para mostrar o resultado
                const statusBadge = data.acertou ? 
                    '<span class="status-badge status-correct">✅ Você acertou!</span>' :
                    '<span class="status-badge status-incorrect">❌ Você errou</span>';
                
                submitSection.innerHTML = `
                    <div class="answered-info">
                        ${statusBadge}
                    </div>
                `;
                
                // Mostrar explicação se disponível
                if (data.explicacao) {
                    const feedbackDiv = document.createElement('div');
                    feedbackDiv.className = `feedback-section ${data.acertou ? 'feedback-correct' : 'feedback-incorrect'}`;
                    feedbackDiv.innerHTML = `
                        <div class="feedback-icon">
                            ${data.acertou ? '✅' : '❌'}
                        </div>
                        <div class="feedback-text">
                            <strong>${data.acertou ? 'Parabéns!' : 'Não foi dessa vez!'}</strong><br>
                            ${data.explicacao}
                        </div>
                    `;
                    questionDiv.appendChild(feedbackDiv);
                }
            }
        });

        // Funcionalidade de clique nas alternativas
        document.addEventListener('DOMContentLoaded', function() {
            // Adicionar evento de clique para alternativas clicáveis
            document.querySelectorAll('.clickable-alternative').forEach(function(alternative) {
                alternative.addEventListener('click', function() {
                    const questionId = this.getAttribute('data-question-id');
                    const alternativeLetter = this.getAttribute('data-alternative-letter');
                    
                    // Remover seleção anterior da mesma questão
                    document.querySelectorAll(`[data-question-id="${questionId}"].clickable-alternative`).forEach(function(alt) {
                        alt.classList.remove('selected');
                    });
                    
                    // Adicionar seleção à alternativa clicada
                    this.classList.add('selected');
                    
                    // Atualizar campo hidden
                    const hiddenInput = document.getElementById(`selected-${questionId}`);
                    if (hiddenInput) {
                        hiddenInput.value = alternativeLetter;
                    }
                    
                    // Habilitar botão de responder
                    const submitBtn = document.getElementById(`btn-${questionId}`);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.cursor = 'pointer';
                    }
                });
            });
        });

        // Confirmação antes de enviar
        document.getElementById('quizForm')?.addEventListener('submit', function(e) {
            const totalQuestions = <?php echo count($questoes); ?>;
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            
            if (answeredQuestions < totalQuestions) {
                if (!confirm(`Você respondeu ${answeredQuestions} de ${totalQuestions} questões. Deseja continuar mesmo assim?`)) {
                    e.preventDefault();
                }
            } else {
                if (!confirm(`Tem certeza que deseja finalizar o quiz com ${totalQuestions} questões?`)) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>