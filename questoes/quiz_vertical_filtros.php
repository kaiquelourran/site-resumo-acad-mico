<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Captura parâmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';
$questao_inicial = isset($_GET['questao_inicial']) ? (int)$_GET['questao_inicial'] : 0;

// Busca informações do assunto
$assunto_nome = 'Todas as Questões';
if ($id_assunto > 0) {
    $stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
    $stmt_assunto->execute([$id_assunto]);
    $assunto_nome = $stmt_assunto->fetchColumn() ?: 'Assunto não encontrado';
}

// Processar resposta se enviada via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_questao']) && isset($_POST['alternativa_selecionada'])) {
    $id_questao = (int)$_POST['id_questao'];
    $alternativa_selecionada = $_POST['alternativa_selecionada'];
    
    // Converter letra da alternativa para ID (A=1, B=2, C=3, D=4, E=5)
    $id_alternativa = ord(strtoupper($alternativa_selecionada)) - ord('A') + 1;
    
    // Buscar a questão para verificar a resposta correta
    $stmt_questao = $pdo->prepare("SELECT alternativa_correta, explicacao FROM questoes WHERE id_questao = ?");
    $stmt_questao->execute([$id_questao]);
    $questao_data = $stmt_questao->fetch(PDO::FETCH_ASSOC);
    
    if ($questao_data) {
        $acertou = ($alternativa_selecionada === $questao_data['alternativa_correta']) ? 1 : 0;
        
        // Inserir ou atualizar resposta
        $stmt_resposta = $pdo->prepare("
            INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            id_alternativa = VALUES(id_alternativa), 
            acertou = VALUES(acertou), 
            data_resposta = VALUES(data_resposta)
        ");
        $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
        
        // Se for uma requisição AJAX, retornar JSON
        if (isset($_POST['ajax_request'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'acertou' => (bool)$acertou,
                'alternativa_correta' => $questao_data['alternativa_correta'],
                'explicacao' => $questao_data['explicacao'] ?? '',
                'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
            ]);
            exit;
        }
    } else {
        // Se for uma requisição AJAX, retornar erro
        if (isset($_POST['ajax_request'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Questão não encontrada'
            ]);
            exit;
        }
    }
}

// Construir query SQL baseada no filtro
if ($filtro_ativo === 'todas' || $filtro_ativo === 'nao-respondidas') {
    // Para "todas" e "não-respondidas", NUNCA carregar dados de resposta
    $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                   q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                   a.nome,
                   'nao-respondida' as status_resposta,
                   NULL as id_alternativa
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
            WHERE 1=1";
} else {
    // Para outros filtros, carregar dados de resposta normalmente
    $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                   q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                   a.nome,
                   CASE 
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta,
                   r.id_alternativa
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
            LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
            WHERE 1=1";
}
$params = [];

if ($id_assunto > 0) {
    $sql .= " AND q.id_assunto = ?";
    $params[] = $id_assunto;
}

// Aplicar filtro específico
switch($filtro_ativo) {
    case 'respondidas':
        $sql .= " AND r.id_questao IS NOT NULL";
        break;
    case 'nao-respondidas':
        // Para não-respondidas, não aplicar filtro adicional pois já não carregamos respostas
        break;
    case 'certas':
        $sql .= " AND r.acertou = 1";
        break;
    case 'erradas':
        $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
        break;
    case 'todas':
        // Para todas, não aplicar filtro adicional
        break;
}

$sql .= " ORDER BY q.id_questao";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se uma questão inicial foi especificada, reorganizar array
if ($questao_inicial > 0) {
    $questao_inicial_index = -1;
    foreach ($questoes as $index => $questao) {
        if ($questao['id_questao'] == $questao_inicial) {
            $questao_inicial_index = $index;
            break;
        }
    }
    
    if ($questao_inicial_index >= 0) {
        $questoes_reorganizadas = array_slice($questoes, $questao_inicial_index);
        $questoes_reorganizadas = array_merge($questoes_reorganizadas, array_slice($questoes, 0, $questao_inicial_index));
        $questoes = $questoes_reorganizadas;
    }
}

// Função para obter nome do filtro
function getNomeFiltro($filtro) {
    switch($filtro) {
        case 'todas': return 'Todas as Questões';
        case 'respondidas': return 'Questões Respondidas';
        case 'nao-respondidas': return 'Questões Não Respondidas';
        case 'certas': return 'Questões Certas';
        case 'erradas': return 'Questões Erradas';
        default: return 'Questões';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Vertical - <?php echo htmlspecialchars($assunto_nome); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .content-wrapper {
            padding: 40px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .page-title {
            font-size: 2.8em;
            font-weight: 300;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .page-subtitle {
            font-size: 1.3em;
            opacity: 0.9;
            font-weight: 400;
        }

        .quiz-info {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            border: 2px solid #f0f0f0;
            text-align: center;
        }

        .quiz-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3em;
        }

        .quiz-info p {
            color: #666;
            margin: 0;
            font-size: 1.1em;
        }

        .questions-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .question-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border: 2px solid #f0f0f0;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .question-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .question-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
            border-color: #667eea;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }

        .question-number {
            font-weight: 700;
            color: #667eea;
            font-size: 1.3em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .question-number::before {
            content: '📝';
            font-size: 1.2em;
        }

        .question-status {
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .status-nao-respondida {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #6c757d;
            border: 2px solid #dee2e6;
        }

        .status-acertada {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-errada {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .question-text {
            font-size: 1.15em;
            line-height: 1.7;
            color: #2c3e50;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .alternatives-container {
            display: flex;
            flex-direction: column;
            gap: 18px;
            margin-bottom: 25px;
        }

        .alternative {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .alternative::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }

        .alternative:hover::before {
            left: 100%;
        }

        .alternative:hover {
            border-color: #667eea;
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .alternative-letter {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 20px;
            flex-shrink: 0;
            font-size: 1.1em;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .alternative-text {
            flex: 1;
            font-size: 1.05em;
            line-height: 1.6;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Estilos para feedback visual */
        .alternativa-correta {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
            border-color: #28a745 !important;
            color: #155724 !important;
            animation: pulse-green 0.8s ease-in-out;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3) !important;
        }

        .alternativa-correta .alternative-letter {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4) !important;
        }

        .alternativa-incorreta {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
            border-color: #dc3545 !important;
            color: #721c24 !important;
            animation: pulse-red 0.8s ease-in-out;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3) !important;
        }

        .alternativa-incorreta .alternative-letter {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4) !important;
        }

        @keyframes pulse-green {
            0% { transform: scale(1) translateX(8px); }
            50% { transform: scale(1.03) translateX(8px); }
            100% { transform: scale(1) translateX(8px); }
        }

        @keyframes pulse-red {
            0% { transform: scale(1) translateX(8px); }
            50% { transform: scale(1.03) translateX(8px); }
            100% { transform: scale(1) translateX(8px); }
        }

        .explicacao-container {
            background: linear-gradient(135deg, #e8f4fd 0%, #f0f8ff 100%);
            border-left: 5px solid #2196f3;
            padding: 25px;
            margin-top: 25px;
            border-radius: 0 15px 15px 0;
            opacity: 0;
            animation: fadeIn 0.8s ease-in-out forwards;
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.15);
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(-15px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        .explicacao-title {
            color: #1976d2;
            margin-bottom: 12px;
            font-size: 1.2em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .explicacao-title::before {
            content: '💡';
            font-size: 1.1em;
        }

        .explicacao-text {
            color: #2c3e50;
            line-height: 1.7;
            margin: 0;
            font-size: 1.05em;
            font-weight: 500;
        }

        .navigation-section {
            text-align: center;
            padding: 35px;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 20px;
            border: 2px solid #f0f0f0;
            margin-top: 30px;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-btn-outline {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
        }

        .progress-info {
            text-align: center;
            color: #667eea;
            font-weight: 600;
            font-size: 1.1em;
            background: white;
            padding: 15px 25px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid #f0f0f0;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .empty-state-icon {
            font-size: 5em;
            margin-bottom: 25px;
            opacity: 0.7;
        }

        .empty-state-title {
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .empty-state-text {
            font-size: 1.2em;
            line-height: 1.6;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                border-radius: 20px;
            }
            
            .content-wrapper {
                padding: 25px;
            }
            
            .page-header {
                 padding: 25px;
             }
            
            .page-title {
                 font-size: 2.2em;
             }
            
            .question-card {
                padding: 25px;
            }
            
            .nav-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-btn {
                width: 100%;
                justify-content: center;
            }
            
            .alternative {
                padding: 20px;
            }
            
            .alternative:hover {
                transform: translateX(5px);
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="content-wrapper">
            <!-- Cabeçalho do Quiz -->
            <div class="page-header">
                <h1 class="page-title">🎯 Quiz Vertical</h1>
                <p class="page-subtitle"><?php echo htmlspecialchars($assunto_nome); ?></p>
            </div>

            <!-- Informações do Quiz -->
            <div class="quiz-info">
                <h3><?php echo getNomeFiltro($filtro_ativo); ?></h3>
                <p><?php echo count($questoes); ?> questão(ões) disponível(eis)</p>
            </div>

            <!-- Container das Questões -->
            <?php if (empty($questoes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3 class="empty-state-title">Nenhuma questão encontrada</h3>
                    <p class="empty-state-text">
                        Não há questões disponíveis para o filtro selecionado.<br>
                        Volte à lista de questões para selecionar outro filtro.
                    </p>
                </div>
            <?php else: ?>
                <div class="questions-container">
                    <?php foreach ($questoes as $index => $questao): ?>
                        <div class="question-card" id="questao-<?php echo $questao['id_questao']; ?>">
                            <div class="question-header">
                                <div class="question-number">
                                    Questão #<?php echo $questao['id_questao']; ?>
                                </div>
                                <div class="question-status status-<?php echo $questao['status_resposta']; ?>">
                                    <?php
                                    switch($questao['status_resposta']) {
                                        case 'nao-respondida':
                                            echo '❓ Não Respondida';
                                            break;
                                        case 'acertada':
                                            echo '✅ Acertada';
                                            break;
                                        case 'errada':
                                            echo '❌ Errada';
                                            break;
                                        default:
                                            echo '✅ Respondida';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="question-text">
                                <?php echo htmlspecialchars($questao['enunciado']); ?>
                            </div>
                            
                            <form class="quiz-form" data-questao-id="<?php echo $questao['id_questao']; ?>">
                                <input type="hidden" name="id_questao" value="<?php echo $questao['id_questao']; ?>">
                                
                                <div class="alternatives-container">
                                    <?php
                                    // Buscar alternativas da tabela 'alternativas'
                                    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
                                    $stmt_alt->execute([$questao['id_questao']]);
                                    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    $letras = ['A', 'B', 'C', 'D', 'E'];
                                    foreach ($alternativas_questao as $index => $alternativa) {
                                        $letra = $letras[$index] ?? ($index + 1);
                                        
                                        // Verificar se esta alternativa foi selecionada pelo usuário
                                        // IMPORTANTE: Para filtros "todas" e "nao-respondidas", NUNCA mostrar como selecionada
                                        $is_selected = false;
                                        if ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') {
                                            $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
                                        }
                                        $is_correct = ($alternativa['eh_correta'] == 1);
                                        // IMPORTANTE: is_answered deve ser false para filtros "todas" e "nao-respondidas"
                                        $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
                                        
                                        $class = '';
                                        // IMPORTANTE: Só aplicar classes visuais nos filtros que mostram respostas
                                        // NUNCA aplicar para "todas" e "nao-respondidas"
                                        if ($is_answered && ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas')) {
                                            if ($is_correct) {
                                                $class = 'alternativa-correta';
                                            } elseif ($is_selected && !$is_correct) {
                                                $class = 'alternativa-incorreta';
                                            }
                                        }
                                        ?>
                                        <div class="alternative <?php echo $class; ?>" 
                                             data-alternativa="<?php echo $letra; ?>"
                                             data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
                                             data-questao-id="<?php echo $questao['id_questao']; ?>">
                                            <div class="alternative-letter"><?php echo $letra; ?></div>
                                            <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </form>
                            
                            <?php if (!empty($questao['explicacao']) && !empty($questao['id_alternativa'])): ?>
                                <div class="explicacao-container">
                                    <div class="explicacao-title">💡 Explicação:</div>
                                    <div class="explicacao-text"><?php echo htmlspecialchars($questao['explicacao']); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Navegação -->
            <div class="navigation-section">
                <div class="nav-buttons">
                    <a href="listar_questoes.php?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>" 
                       class="nav-btn nav-btn-primary">
                        📋 Voltar à Lista
                    </a>
                    <a href="index.php" class="nav-btn nav-btn-outline">
                        🏠 Início
                    </a>
                    <a href="escolher_assunto.php" class="nav-btn nav-btn-outline">
                        📚 Escolher Assunto
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para mostrar feedback visual
        function mostrarFeedbackVisual(questaoId, alternativaSelecionada, alternativaCorreta, explicacao) {
            const questaoCard = document.querySelector(`#questao-${questaoId}`);
            const alternativas = questaoCard.querySelectorAll('.alternative');
            
            // Desabilitar cliques em todas as alternativas
            alternativas.forEach(alt => {
                alt.style.pointerEvents = 'none';
            });
            
            // Marcar alternativa correta como verde
            const alternativaCorretaEl = questaoCard.querySelector(`[data-alternativa="${alternativaCorreta}"]`);
            if (alternativaCorretaEl) {
                alternativaCorretaEl.classList.add('alternativa-correta');
            }
            
            // Se a alternativa selecionada estiver errada, marcar como vermelha
            if (alternativaSelecionada !== alternativaCorreta) {
                const alternativaSelecionadaEl = questaoCard.querySelector(`[data-alternativa="${alternativaSelecionada}"]`);
                if (alternativaSelecionadaEl) {
                    alternativaSelecionadaEl.classList.add('alternativa-incorreta');
                }
            }
            
            // Mostrar explicação após um delay se disponível
            if (explicacao && explicacao.trim() !== '') {
                setTimeout(() => {
                    let explicacaoContainer = questaoCard.querySelector('.explicacao-container');
                    if (!explicacaoContainer) {
                        explicacaoContainer = document.createElement('div');
                        explicacaoContainer.className = 'explicacao-container';
                        explicacaoContainer.innerHTML = `
                            <div class="explicacao-title">💡 Explicação:</div>
                            <div class="explicacao-text">${explicacao}</div>
                        `;
                        questaoCard.appendChild(explicacaoContainer);
                    }
                }, 1000);
            }
        }

        // Event listeners para as alternativas
        document.addEventListener('DOMContentLoaded', function() {
            const alternativas = document.querySelectorAll('.alternative');
            
            alternativas.forEach(alternativa => {
                alternativa.classList.remove('alternativa-correta', 'alternativa-incorreta');
                alternativa.style.pointerEvents = 'auto'; // Reativar cliques
            });

            alternativas.forEach(alternativa => {
                alternativa.addEventListener('click', function() {
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest('.question-card');
                    
                    // Desabilitar cliques em todas as alternativas desta questão
                    const todasAlternativas = questaoCard.querySelectorAll('.alternative');
                    todasAlternativas.forEach(alt => {
                        alt.style.pointerEvents = 'none';
                    });
                    
                    // Enviar resposta via AJAX
                    const formData = new FormData();
                    formData.append('id_questao', questaoId);
                    formData.append('alternativa_selecionada', alternativaSelecionada);
                    formData.append('ajax_request', '1');
                    
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar resultado visual
                            mostrarFeedbackVisual(questaoId, alternativaSelecionada, data.alternativa_correta, data.explicacao);
                            
                            // Verificar se a questão deve ser removida do filtro atual
                            const filtroAtual = new URLSearchParams(window.location.search).get('filtro') || 'todas';
                            let deveRemover = false;
                            
                            switch(filtroAtual) {
                                case 'nao-respondidas':
                                    // Questão respondida deve sair do filtro "não respondidas"
                                    deveRemover = true;
                                    break;
                                case 'certas':
                                    // Se errou, deve sair do filtro "acertadas"
                                    deveRemover = !data.acertou;
                                    break;
                                case 'erradas':
                                    // Se acertou, deve sair do filtro "erradas"
                                    deveRemover = data.acertou;
                                    break;
                            }
                            
                            // Remover questão do DOM após um delay se necessário
                            if (deveRemover) {
                                setTimeout(() => {
                                    questaoCard.style.transition = 'all 0.5s ease';
                                    questaoCard.style.opacity = '0';
                                    questaoCard.style.transform = 'translateX(-100%)';
                                    
                                    setTimeout(() => {
                                        questaoCard.remove();
                                        
                                        // Verificar se ainda há questões
                                        const questoesRestantes = document.querySelectorAll('.question-card');
                                        if (questoesRestantes.length === 0) {
                                            mostrarMensagemFiltroVazio();
                                        }
                                    }, 500);
                                }, 2000);
                            }
                        } else {
                            console.error('Erro ao processar resposta:', data.message);
                            // Reabilitar cliques em caso de erro
                            todasAlternativas.forEach(alt => {
                                alt.style.pointerEvents = 'auto';
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao enviar resposta:', error);
                        // Reabilitar cliques em caso de erro
                        todasAlternativas.forEach(alt => {
                            alt.style.pointerEvents = 'auto';
                        });
                    });
                });
            });

            // Função para mostrar mensagem quando filtro fica vazio
            function mostrarMensagemFiltroVazio() {
                const container = document.querySelector('.questions-container');
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🎉</div>
                        <div class="empty-state-title">Parabéns!</div>
                        <div class="empty-state-text">
                            Você respondeu todas as questões deste filtro!<br>
                            <a href="?id=<?php echo $id_assunto; ?>&filtro=todas" class="nav-btn" style="margin-top: 20px; display: inline-block;">
                                📚 Ver Todas as Questões
                            </a>
                        </div>
                    </div>
                `;
            }

            // Animações de entrada
            const cards = document.querySelectorAll('.question-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>