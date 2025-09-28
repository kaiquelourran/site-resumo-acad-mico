<?php
// Quiz sem necessidade de login para testar as questões
require_once __DIR__ . '/conexao.php';

// Simula uma sessão básica
session_start();
$numero_de_questoes_por_quiz = 5;

// Captura parâmetros de filtro para redirecionamento
$filtro_origem = isset($_GET['filtro']) ? $_GET['filtro'] : null;
$id_assunto_origem = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Limpa o feedback se for uma nova questão (não é POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['feedback'])) {
    unset($_SESSION['feedback']);
}

// Inicializa a sessão para o quiz, se ainda não estiver
if (!isset($_SESSION['quiz_progress']) || isset($_GET['novo'])) {
    $_SESSION['quiz_progress'] = [
        'acertos' => 0,
        'respondidas' => [],
        'id_assunto' => isset($_GET['id']) ? (int)$_GET['id'] : 0,
        'filtro_origem' => $filtro_origem,
    ];
}

// Atualiza o id_assunto se fornecido
if (isset($_GET['id'])) {
    $_SESSION['quiz_progress']['id_assunto'] = (int)$_GET['id'];
}

// Atualiza o filtro de origem se fornecido
if ($filtro_origem) {
    $_SESSION['quiz_progress']['filtro_origem'] = $filtro_origem;
}

// Garante que o array 'respondidas' existe
if (!isset($_SESSION['quiz_progress']['respondidas'])) {
    $_SESSION['quiz_progress']['respondidas'] = [];
}

// Redireciona para a página de resultados se o número de questões foi alcançado
if (count($_SESSION['quiz_progress']['respondidas']) >= $numero_de_questoes_por_quiz) {
    $total_questoes = count($_SESSION['quiz_progress']['respondidas']);
    $acertos = $_SESSION['quiz_progress']['acertos'];
    $porcentagem = round(($acertos / $total_questoes) * 100);
    
    echo "<!DOCTYPE html>";
    echo "<html lang='pt-br'>";
    echo "<head>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<title>Resultado das Questões</title>";
    echo "<link rel='stylesheet' href='modern-style.css'>";
    echo "</head>";
    echo "<body>";
    echo "<div class='main-container fade-in'>";
    echo "<div class='header'>";
    echo "<div class='logo'>🎉</div>";
    echo "<h1 class='title'>Questões Finalizadas!</h1>";
    echo "<p class='subtitle'>Parabéns por completar as questões</p>";
    echo "</div>";
    
    echo "<div class='stats-container'>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$total_questoes}</div>";
    echo "<div class='stat-label'>Questões Respondidas</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$acertos}</div>";
    echo "<div class='stat-label'>Acertos</div>";
    echo "</div>";
    echo "<div class='stat-card'>";
    echo "<div class='stat-number'>{$porcentagem}%</div>";
    echo "<div class='stat-label'>Aproveitamento</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin: 40px 0;'>";
    if ($porcentagem >= 80) {
        echo "<div class='alert alert-success'>🏆 Excelente! Você teve um ótimo desempenho!</div>";
    } elseif ($porcentagem >= 60) {
        echo "<div class='alert alert-info'>👍 Bom trabalho! Continue estudando para melhorar ainda mais!</div>";
    } else {
        echo "<div class='alert alert-warning'>📚 Continue estudando! A prática leva à perfeição!</div>";
    }
    echo "</div>";
    
    echo "<div style='text-align: center;'>";
    echo "<a href='?novo=1' class='btn' style='margin: 10px;'>🔄 Fazer Novas Questões</a>";
    echo "<a href='index.php' class='btn btn-secondary' style='margin: 10px;'>🏠 Voltar ao Menu</a>";
    echo "</div>";
    
    echo "</div>";
    echo "</body>";
    echo "</html>";
    exit;
}

// Busca uma questão baseada no filtro ativo
$id_assunto_atual = $_SESSION['quiz_progress']['id_assunto'];
$questoes_respondidas = $_SESSION['quiz_progress']['respondidas'];
$filtro_ativo = $_SESSION['quiz_progress']['filtro_origem'] ?? 'todas';

// Query base com LEFT JOIN para respostas
$sql = "SELECT q.* FROM questoes q 
        LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
        WHERE 1=1";
$params = [];

if ($id_assunto_atual > 0) {
    $sql .= " AND q.id_assunto = ?";
    $params[] = $id_assunto_atual;
}

// Aplicar filtro específico
switch($filtro_ativo) {
    case 'respondidas':
        $sql .= " AND r.id_questao IS NOT NULL";
        break;
    case 'nao-respondidas':
        $sql .= " AND r.id_questao IS NULL";
        break;
    case 'acertadas':
        $sql .= " AND r.acertou = 1";
        break;
    case 'erradas':
        $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
        break;
    // 'todas' não precisa de filtro adicional
}

// Excluir questões já respondidas na sessão atual
if (!empty($questoes_respondidas)) {
    $placeholders = implode(',', array_fill(0, count($questoes_respondidas), '?'));
    $sql .= " AND q.id_questao NOT IN ($placeholders)";
    $params = array_merge($params, $questoes_respondidas);
}

$sql .= " ORDER BY q.id_questao LIMIT 1";

$stmt_questao = $pdo->prepare($sql);
$stmt_questao->execute($params);
$questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    $stmt_alternativas = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY RAND()");
    $stmt_alternativas->execute([$questao['id_questao']]);
    $alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
}

// Processa a resposta se foi enviada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resposta'])) {
    $id_questao = (int)$_POST['id_questao'];
    $id_alternativa = (int)$_POST['resposta'];
    
    // Verifica se a resposta está correta
    $stmt_verifica = $pdo->prepare("SELECT correta FROM alternativas WHERE id_alternativa = ?");
    $stmt_verifica->execute([$id_alternativa]);
    $correta = $stmt_verifica->fetchColumn();
    
    // Busca todas as alternativas da questão para mostrar o feedback
    $stmt_todas_alternativas = $pdo->prepare("SELECT id_alternativa, texto, correta FROM alternativas WHERE id_questao = ?");
    $stmt_todas_alternativas->execute([$id_questao]);
    $todas_alternativas = $stmt_todas_alternativas->fetchAll(PDO::FETCH_ASSOC);
    
    // Adiciona a questão às respondidas
    $_SESSION['quiz_progress']['respondidas'][] = $id_questao;
    
    if ($correta) {
        $_SESSION['quiz_progress']['acertos']++;
    }
    
    // Armazena informações para mostrar o feedback
    $_SESSION['feedback'] = [
        'id_questao' => $id_questao,
        'id_alternativa_escolhida' => $id_alternativa,
        'alternativas' => $todas_alternativas,
        'acertou' => $correta
    ];
    
    // Criar tabela de respostas se não existir (sem foreign keys para evitar problemas)
    $sql_create_table = "CREATE TABLE IF NOT EXISTS respostas_usuario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_questao INT NOT NULL,
        id_alternativa INT NOT NULL,
        acertou TINYINT(1) NOT NULL DEFAULT 0,
        data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_questao (id_questao)
    )";
    
    try {
        $pdo->query($sql_create_table);
    } catch (Exception $e) {
        // Ignora erros de criação de tabela
    }
    
    // Salvar resposta na tabela de tracking
    try {
        $stmt_tracking = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE 
                                       id_alternativa = VALUES(id_alternativa), 
                                       acertou = VALUES(acertou), 
                                       data_resposta = CURRENT_TIMESTAMP");
        $resultado = $stmt_tracking->execute([$id_questao, $id_alternativa, $correta ? 1 : 0]);
        
        // Debug: verificar se salvou
        if ($resultado) {
            error_log("Resposta salva: questao={$id_questao}, alternativa={$id_alternativa}, acertou=" . ($correta ? 1 : 0));
        }
    } catch (Exception $e) {
        // Log do erro para debug
        error_log("Erro ao salvar resposta: " . $e->getMessage());
    }
    
    // Determina o link de redirecionamento baseado no filtro de origem
    $redirect_link = "?";
    if (isset($_SESSION['quiz_progress']['filtro_origem']) && isset($_SESSION['quiz_progress']['id_assunto'])) {
        $filtro_origem = $_SESSION['quiz_progress']['filtro_origem'];
        $id_assunto = $_SESSION['quiz_progress']['id_assunto'];
        
        // Mantém o filtro ativo para continuar navegando dentro dele
        $redirect_link = "?id={$id_assunto}&filtro={$filtro_origem}";
    }
    
    $next_button = "<div style='text-align: center; margin: 30px 0;'><a href='{$redirect_link}' class='btn' style='display: inline-block; width: auto; padding: 15px 40px;'>➡️ Próxima Questão</a></div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Questões - Sem Login</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">🎯</div>
            <h1 class="title">Teste de Questões</h1>
            <p class="subtitle">Sistema de Questões - Terapia Ocupacional</p>
        </div>
        
        <?php 
        // Exibir botão de próxima questão se houver feedback
        if (isset($_SESSION['feedback']) && isset($next_button)) {
            echo $next_button;
        }
        ?>
        
        <?php if (!$questao): ?>
            <h2 style="text-align: center; color: #333; margin-bottom: 30px; font-size: 1.8em;">📚 Escolha um Assunto</h2>
            <div class="cards-container">
                <?php
                $stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
                $assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($assuntos as $assunto) {
                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE id_assunto = ?");
                    $stmt_count->execute([$assunto['id_assunto']]);
                    $qtd_questoes = $stmt_count->fetchColumn();
                    
                    if ($qtd_questoes > 0) {
                        echo "<div class='card fade-in'>";
                        echo "<span class='card-icon'>📖</span>";
                        echo "<h3 class='card-title'>{$assunto['nome']}</h3>";
                        echo "<p class='card-description'>{$qtd_questoes} questões disponíveis</p>";
                        echo "<a href='?id={$assunto['id_assunto']}' class='btn'>Iniciar Questões</a>";
                        echo "</div>";
                    }
                }
                ?>
            </div>
        <?php else: ?>
            <div class="questoes-container fade-in-up">
                <!-- Progress Indicator -->
                <div class="progress-container">
                    <?php 
                    $progress_percentage = (count($_SESSION['quiz_progress']['respondidas']) / $numero_de_questoes_por_quiz) * 100;
                    ?>
                    <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <div class="progress-text">
                    Progresso: <?php echo count($_SESSION['quiz_progress']['respondidas']); ?> de <?php echo $numero_de_questoes_por_quiz; ?> questões
                </div>

                <!-- Question Header -->
                <div class="question-header">
                    <div class="question-number">
                        🎯 Questão <?php echo count($_SESSION['quiz_progress']['respondidas']) + 1; ?> de <?php echo $numero_de_questoes_por_quiz; ?>
                    </div>
                    <div class="question-stats">
                        ✅ Acertos: <?php echo $_SESSION['quiz_progress']['acertos']; ?> | 
                        📊 Aproveitamento: <?php echo count($_SESSION['quiz_progress']['respondidas']) > 0 ? round(($_SESSION['quiz_progress']['acertos'] / count($_SESSION['quiz_progress']['respondidas'])) * 100) : 0; ?>%
                    </div>
                </div>
                
                <div class="question-card fade-in-up">
                    <div class="question-text"><?php echo $questao['enunciado']; ?></div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="id_questao" value="<?php echo $questao['id_questao']; ?>">
                    
                    <div class="alternatives">
                        <?php 
                        $letters = ['A', 'B', 'C', 'D', 'E'];
                        
                        // Verifica se há feedback para mostrar
                        $mostrar_feedback = isset($_SESSION['feedback']) && $_SESSION['feedback']['id_questao'] == $questao['id_questao'];
                        
                        foreach ($alternativas as $index => $alternativa): 
                            $classe_feedback = '';
                            $disabled = '';
                            
                            if ($mostrar_feedback) {
                                $disabled = 'disabled';
                                // Se esta alternativa foi escolhida pelo usuário
                                if ($alternativa['id_alternativa'] == $_SESSION['feedback']['id_alternativa_escolhida']) {
                                    if ($alternativa['correta']) {
                                        $classe_feedback = 'alternative-correct-chosen';
                                    } else {
                                        $classe_feedback = 'alternative-incorrect-chosen';
                                    }
                                }
                                // Se esta é a alternativa correta (mas não foi escolhida)
                                else if ($alternativa['correta']) {
                                    $classe_feedback = 'alternative-correct';
                                }
                            }
                        ?>
                            <label class="alternative slide-in-left <?php echo $classe_feedback; ?>" style="animation-delay: <?php echo $index * 0.1; ?>s;" data-letter="<?php echo $letters[$index]; ?>">
                                <input type="radio" name="resposta" value="<?php echo $alternativa['id_alternativa']; ?>" required <?php echo $disabled; ?>>
                                <span><?php echo htmlspecialchars($alternativa['texto']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!$mostrar_feedback): ?>
                    <button type="submit" class="btn-submit">
                        <span style="position: relative; z-index: 1;">✅ Responder</span>
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 40px; padding: 30px; border-top: 2px solid #f0f0f0; background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%); border-radius: 16px;">
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <?php 
                // Botão para voltar à lista de questões com o filtro ativo
                if (isset($_SESSION['quiz_progress']['filtro_origem']) && isset($_SESSION['quiz_progress']['id_assunto'])) {
                    $filtro_origem = $_SESSION['quiz_progress']['filtro_origem'];
                    $id_assunto = $_SESSION['quiz_progress']['id_assunto'];
                    echo "<a href='listar_questoes.php?id={$id_assunto}&filtro={$filtro_origem}' style='color: #667eea; text-decoration: none; padding: 12px 24px; border: 2px solid #667eea; border-radius: 25px; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;'>
                            📋 Voltar à Lista
                          </a>";
                }
                ?>
                <a href="index.php" style="color: #667eea; text-decoration: none; padding: 12px 24px; border: 2px solid #667eea; border-radius: 25px; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">
                    🏠 Voltar ao Início
                </a>
                <a href="escolher_assunto.php" style="color: #764ba2; text-decoration: none; padding: 12px 24px; border: 2px solid #764ba2; border-radius: 25px; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;">
                    📋 Escolher Assunto
                </a>
            </div>
        </div>
    </div>
    <script src="quiz.js"></script>
</body>
</html>