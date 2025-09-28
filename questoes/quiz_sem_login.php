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
    echo "<link rel='stylesheet' href='../style.css'>";
    echo "<style>";
    echo ".main-container { max-width: 1200px; margin: 0 auto; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }";
    echo ".content-wrapper { background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 40px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1); backdrop-filter: blur(10px); text-align: center; }";
    echo ".result-header { margin-bottom: 40px; }";
    echo ".result-title { font-size: 2.5em; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 10px; }";
    echo ".result-subtitle { color: #666; font-size: 1.2em; font-weight: 300; }";
    echo ".stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 40px 0; }";
    echo ".stat-card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); border: 2px solid #f0f0f0; }";
    echo ".stat-number { font-size: 2.5em; font-weight: 700; color: #667eea; margin-bottom: 10px; }";
    echo ".stat-label { color: #666; font-weight: 500; }";
    echo ".alert { padding: 20px; border-radius: 15px; margin: 30px 0; font-weight: 500; }";
    echo ".alert-success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }";
    echo ".alert-info { background: #d1ecf1; color: #0c5460; border: 2px solid #bee5eb; }";
    echo ".alert-warning { background: #fff3cd; color: #856404; border: 2px solid #ffeaa7; }";
    echo ".nav-buttons { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 30px; }";
    echo ".nav-btn { padding: 15px 30px; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 10px; }";
    echo ".nav-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: 2px solid transparent; }";
    echo ".nav-btn-outline { background: white; color: #667eea; border: 2px solid #667eea; }";
    echo ".nav-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }";
    echo "</style>";
    echo "</head>";
    echo "<body>";
    echo "<div class='main-container'>";
    echo "<div class='content-wrapper'>";
    echo "<div class='result-header'>";
    echo "<h1 class='result-title'>🎉 Questões Finalizadas!</h1>";
    echo "<p class='result-subtitle'>Parabéns por completar as questões</p>";
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
    
    if ($porcentagem >= 80) {
        echo "<div class='alert alert-success'>🏆 Excelente! Você teve um ótimo desempenho!</div>";
    } elseif ($porcentagem >= 60) {
        echo "<div class='alert alert-info'>👍 Bom trabalho! Continue estudando para melhorar ainda mais!</div>";
    } else {
        echo "<div class='alert alert-warning'>📚 Continue estudando! A prática leva à perfeição!</div>";
    }
    
    echo "<div class='nav-buttons'>";
    echo "<a href='?novo=1' class='nav-btn nav-btn-primary'>🔄 Fazer Novas Questões</a>";
    echo "<a href='index.php' class='nav-btn nav-btn-outline'>🏠 Voltar ao Menu</a>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    echo "</body>";
    echo "</html>";
    exit;
}

// Busca uma questão baseada no filtro ativo ou questão específica
$id_assunto_atual = $_SESSION['quiz_progress']['id_assunto'];
$questoes_respondidas = $_SESSION['quiz_progress']['respondidas'];
$filtro_ativo = $_SESSION['quiz_progress']['filtro_origem'] ?? 'todas';

// Verifica se foi solicitada uma questão específica
$questao_especifica = isset($_GET['questao']) ? (int)$_GET['questao'] : null;

if ($questao_especifica) {
    // Busca questão específica
    $sql = "SELECT q.* FROM questoes q WHERE q.id_questao = ?";
    $params = [$questao_especifica];
} else {
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
}

$stmt_questao = $pdo->prepare($sql);
$stmt_questao->execute($params);
$questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    $stmt_alternativas = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY RAND()");
    $stmt_alternativas->execute([$questao['id_questao']]);
    $alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
}

// Processa a resposta se foi enviada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê dados JSON do corpo da requisição
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if ($data && isset($data['id_questao']) && isset($data['id_alternativa_selecionada'])) {
        $id_questao = (int)$data['id_questao'];
        $id_alternativa = (int)$data['id_alternativa_selecionada'];
        
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
        
        $next_button = "<div class='next-question-section'><a href='{$redirect_link}' class='nav-btn nav-btn-primary'>➡️ Próxima Questão</a></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Questões - Sem Login</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Estilos específicos para o quiz baseados no index.html */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .content-wrapper {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .quiz-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }

        .quiz-title {
            font-size: 2.5em;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .quiz-subtitle {
            color: #666;
            font-size: 1.2em;
            font-weight: 300;
        }

        .progress-section {
            margin-bottom: 30px;
        }

        .progress-container {
            background: #f0f0f0;
            border-radius: 25px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            border-radius: 25px;
            transition: width 0.5s ease;
        }

        .progress-text {
            text-align: center;
            color: #666;
            font-weight: 500;
            font-size: 1.1em;
        }

        .question-section {
            margin-bottom: 40px;
        }

        .question-header {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid #f0f0f0;
        }

        .question-number {
            font-size: 1.3em;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 10px;
        }

        .question-stats {
            color: #666;
            font-size: 1em;
            font-weight: 500;
        }

        .question-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid #f0f0f0;
            margin-bottom: 30px;
        }

        .question-text {
            font-size: 1.2em;
            line-height: 1.6;
            color: #333;
            font-weight: 500;
        }

        .alternatives-section {
            margin-bottom: 30px;
        }

        .alternatives {
            display: grid;
            gap: 15px;
        }

        .alternative {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .alternative:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .alternative input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: #667eea;
        }

        .alternative span {
            font-size: 1.1em;
            line-height: 1.5;
            color: #333;
            font-weight: 500;
        }

        .alternative::before {
            content: attr(data-letter);
            position: absolute;
            left: -10px;
            top: -10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9em;
        }

        /* Feedback styles */
        .alternative-correct {
            border-color: #28a745;
            background: #d4edda;
        }

        .alternative-correct-chosen {
            border-color: #28a745;
            background: #d4edda;
            box-shadow: 0 0 20px rgba(40, 167, 69, 0.3);
        }

        .alternative-incorrect-chosen {
            border-color: #dc3545;
            background: #f8d7da;
            box-shadow: 0 0 20px rgba(220, 53, 69, 0.3);
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .subjects-section {
            text-align: center;
        }

        .subjects-title {
            font-size: 1.8em;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .subject-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid #f0f0f0;
            transition: all 0.3s ease;
            text-align: center;
        }

        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .subject-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }

        .subject-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .subject-description {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .subject-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .subject-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .next-question-section {
            text-align: center;
            margin: 30px 0;
        }

        .navigation-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 20px;
            border: 2px solid #f0f0f0;
            margin-top: 40px;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid;
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .nav-btn-outline {
            background: white;
            color: #667eea;
            border-color: #667eea;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
            }
            
            .quiz-title {
                font-size: 2em;
            }
            
            .subjects-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content-wrapper">
            <!-- Header -->
            <div class="quiz-header">
                <h1 class="quiz-title">🎯 Teste de Questões</h1>
                <p class="quiz-subtitle">Sistema de Questões - Terapia Ocupacional</p>
            </div>
            
            <?php 
            // Exibir botão de próxima questão se houver feedback
            if (isset($_SESSION['feedback']) && isset($next_button)) {
                echo $next_button;
            }
            ?>
            
            <?php if (!$questao): ?>
                <!-- Seleção de Assunto -->
                <div class="subjects-section">
                    <h2 class="subjects-title">📚 Escolha um Assunto</h2>
                    <div class="subjects-grid">
                        <?php
                        $stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
                        $assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($assuntos as $assunto) {
                            $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE id_assunto = ?");
                            $stmt_count->execute([$assunto['id_assunto']]);
                            $qtd_questoes = $stmt_count->fetchColumn();
                            
                            if ($qtd_questoes > 0) {
                                echo "<div class='subject-card'>";
                                echo "<div class='subject-icon'>📖</div>";
                                echo "<h3 class='subject-title'>{$assunto['nome']}</h3>";
                                echo "<p class='subject-description'>{$qtd_questoes} questões disponíveis</p>";
                                echo "<a href='?id={$assunto['id_assunto']}' class='subject-btn'>Iniciar Questões</a>";
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Quiz -->
                <div class="question-section">
                    <!-- Progress -->
                    <div class="progress-section">
                        <?php 
                        $progress_percentage = (count($_SESSION['quiz_progress']['respondidas']) / $numero_de_questoes_por_quiz) * 100;
                        ?>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                        </div>
                        <div class="progress-text">
                            Progresso: <?php echo count($_SESSION['quiz_progress']['respondidas']); ?> de <?php echo $numero_de_questoes_por_quiz; ?> questões
                        </div>
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
                    
                    <!-- Question -->
                    <div class="question-card">
                        <div class="question-text"><?php echo $questao['enunciado']; ?></div>
                    </div>
                    
                    <!-- Alternatives -->
                    <div class="alternatives-section">
                        <form method="POST" id="quiz-form">
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
                                    <label class="alternative <?php echo $classe_feedback; ?>" data-letter="<?php echo $letters[$index]; ?>">
                                        <input type="radio" name="resposta" value="<?php echo $alternativa['id_alternativa']; ?>" required <?php echo $disabled; ?>>
                                        <span><?php echo htmlspecialchars($alternativa['texto']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (!$mostrar_feedback): ?>
                            <button type="submit" class="btn-submit">
                                ✅ Responder
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Navigation -->
            <div class="navigation-section">
                <div class="nav-buttons">
                    <?php 
                    // Botão para voltar à lista de questões com o filtro ativo
                    if (isset($_SESSION['quiz_progress']['filtro_origem']) && isset($_SESSION['quiz_progress']['id_assunto'])) {
                        $filtro_origem = $_SESSION['quiz_progress']['filtro_origem'];
                        $id_assunto = $_SESSION['quiz_progress']['id_assunto'];
                        echo "<a href='listar_questoes.php?id={$id_assunto}&filtro={$filtro_origem}' class='nav-btn nav-btn-outline'>
                                📋 Voltar à Lista
                              </a>";
                    }
                    ?>
                    <a href="index.php" class="nav-btn nav-btn-outline">
                        🏠 Voltar ao Início
                    </a>
                    <a href="escolher_assunto.php" class="nav-btn nav-btn-outline">
                        📚 Escolher Assunto
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="quiz.js"></script>
</body>
</html>