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
    
    // Buscar alternativas da questão
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    // NÃO EMBARALHAR - usar ordem original do banco
    
    // Mapear letra para ID (ordem original do banco)
    $letras = ['A', 'B', 'C', 'D', 'E'];
    $id_alternativa = null;
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        if (strtoupper($letra) === strtoupper($alternativa_selecionada)) {
            $id_alternativa = $alternativa['id_alternativa'];
            break;
        }
    }
    
    // Buscar alternativa correta (ordem original do banco)
    $alternativa_correta = null;
    foreach ($alternativas_questao as $alt) {
        if ($alt['eh_correta'] == 1) {
            $alternativa_correta = $alt;
            break;
        }
    }
    
    if ($alternativa_correta && $id_alternativa) {
        $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
        
        // Salvar resposta
        $user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 1;
        $stmt_resposta = $pdo->prepare("
            INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            id_alternativa = VALUES(id_alternativa), 
            acertou = VALUES(acertou), 
            data_resposta = VALUES(data_resposta)
        ");
        $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
        
        // Retornar JSON
        if (isset($_POST['ajax_request'])) {
            // Encontrar letra correta (ordem original do banco)
            $letra_correta = '';
            foreach ($alternativas_questao as $index => $alt) {
                if ($alt['id_alternativa'] == $alternativa_correta['id_alternativa']) {
                    $letra_correta = $letras[$index] ?? ($index + 1);
                    break;
                }
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'acertou' => (bool)$acertou,
                'alternativa_correta' => $letra_correta,
                'explicacao' => '',
                'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!'
            ]);
            exit;
        }
    }
}

// Buscar questões
$sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
               q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
               a.nome,
               CASE 
                   WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                   WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                   WHEN r.id_questao IS NOT NULL THEN 'respondida'
                   ELSE 'nao-respondida'
               END as status_resposta,
               r.id_alternativa
        FROM questoes q 
        LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
        LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
        WHERE 1=1";

$params = [];
if ($id_assunto > 0) {
    $sql .= " AND q.id_assunto = ?";
    $params[] = $id_assunto;
}

switch($filtro_ativo) {
    case 'respondidas':
        $sql .= " AND r.id_questao IS NOT NULL";
        break;
    case 'certas':
        $sql .= " AND r.acertou = 1";
        break;
    case 'erradas':
        $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
        break;
}

$sql .= " ORDER BY q.id_questao";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questões - <?php echo htmlspecialchars($assunto_nome); ?> - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <link rel="stylesheet" href="alternative-fix.css">
    <style>
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
        }
        .questions-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }
        .question-card {
            background: white;
            border-radius: 14px;
            padding: 0;
            box-shadow: 0 10px 20px rgba(0,0,0,0.06);
            border: 1px solid #e1e5e9;
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(to top, #00C6FF, #0072FF);
            border-radius: 14px 14px 0 0;
        }
        .question-number {
            font-weight: 700;
            color: #FFFFFF;
            font-size: 0.95em;
        }
        .question-status {
            padding: 10px 18px;
            border-radius: 25px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .status-nao-respondida {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #6c757d;
            border: 2px solid #dee2e6;
        }
        .status-certa {
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
            font-size: 1em;
            line-height: 1.5;
            color: #333;
            padding: 18px 20px;
            background: #FFFFFF;
            font-weight: 500;
        }
        .alternatives-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 20px 20px 20px;
            background: #FFFFFF;
        }
        .alternative {
            background: #FFFFFF;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alternative:hover {
            border-color: #00C6FF;
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(0, 114, 255, 0.15);
        }
        .alternative-letter {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            flex-shrink: 0;
        }
        .alternative-text {
            flex: 1;
            font-size: 0.95em;
            line-height: 1.5;
            color: #333;
            font-weight: 500;
        }
        .alternative-correct {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
            border-color: #28a745 !important;
            color: #155724 !important;
        }
        .alternative-incorrect-chosen {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
            border-color: #dc3545 !important;
            color: #721c24 !important;
        }
    </style>
</head>
<body>
    <div class="questions-container">
        <h2>📊 <?php echo getNomeFiltro($filtro_ativo); ?></h2>
        <p><?php echo count($questoes); ?> questão(ões) disponível(eis)</p>
        
        <?php if (empty($questoes)): ?>
            <div style="text-align: center; padding: 80px 20px; color: #666; background: white; border-radius: 20px;">
                <h3>Nenhuma questão encontrada</h3>
                <p>Não há questões disponíveis para o filtro selecionado.</p>
            </div>
        <?php else: ?>
            <?php foreach ($questoes as $questao): ?>
                <div class="question-card" id="questao-<?php echo $questao['id_questao']; ?>">
                    <div class="question-header">
                        <div class="question-number">Questão #<?php echo $questao['id_questao']; ?></div>
                        <div class="question-status status-<?php echo $questao['status_resposta']; ?>">
                            <?php
                            switch($questao['status_resposta']) {
                                case 'nao-respondida': echo '❓ Não Respondida'; break;
                                case 'certa': echo '✅ Acertou'; break;
                                case 'errada': echo '❌ Errou'; break;
                                default: echo '✅ Respondida';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="question-text">
                        <?php echo htmlspecialchars($questao['enunciado']); ?>
                    </div>
                    
                    <div class="alternatives-container">
                        <?php
                        // Buscar alternativas da tabela 'alternativas'
                        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
                        $stmt_alt->execute([$questao['id_questao']]);
                        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // NÃO EMBARALHAR - usar ordem original do banco
                        
                        $letras = ['A', 'B', 'C', 'D', 'E'];
                        foreach ($alternativas_questao as $index => $alternativa) {
                            $letra = $letras[$index] ?? ($index + 1);
                            $is_correct = ($alternativa['eh_correta'] == 1);
                            ?>
                            <div class="alternative" 
                                 data-alternativa="<?php echo $letra; ?>"
                                 data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
                                 data-questao-id="<?php echo $questao['id_questao']; ?>"
                                 data-correta="<?php echo $is_correct ? 'true' : 'false'; ?>">
                                <div class="alternative-letter"><?php echo $letra; ?></div>
                                <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function mostrarFeedbackVisual(questaoId, alternativaSelecionada, alternativaCorreta) {
            const questaoCard = document.querySelector(`#questao-${questaoId}`);
            if (!questaoCard) return;
            
            const alternativas = questaoCard.querySelectorAll('.alternative');
            
            // Limpar feedback anterior
            alternativas.forEach(alt => {
                alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
            });
            
            // Marcar alternativa correta
            const alternativaCorretaEl = questaoCard.querySelector(`[data-alternativa="${alternativaCorreta}"]`);
            if (alternativaCorretaEl) {
                alternativaCorretaEl.classList.add('alternative-correct');
            }
            
            // Marcar alternativa selecionada
            const alternativaSelecionadaEl = questaoCard.querySelector(`[data-alternativa="${alternativaSelecionada}"]`);
            if (alternativaSelecionadaEl && alternativaSelecionada !== alternativaCorreta) {
                alternativaSelecionadaEl.classList.add('alternative-incorrect-chosen');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const todasAlternativas = document.querySelectorAll('.alternative');
            
            todasAlternativas.forEach((alternativa) => {
                alternativa.addEventListener('click', function(e) {
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest('.question-card');
                    
                    if (questaoCard.dataset.respondida === 'true') return;
                    
                    // Destacar a alternativa clicada
                    const todasAlternativas = questaoCard.querySelectorAll('.alternative');
                    todasAlternativas.forEach(alt => {
                        alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                    });
                    
                    this.style.background = '#e3f2fd';
                    this.style.borderColor = '#2196f3';
                    
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
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            mostrarFeedbackVisual(questaoId, alternativaSelecionada, data.alternativa_correta);
                            
                            setTimeout(() => {
                                todasAlternativas.forEach(alt => {
                                    alt.style.pointerEvents = 'none';
                                    alt.style.cursor = 'default';
                                });
                            }, 1000);
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        questaoCard.dataset.respondida = 'false';
                    });
                });
            });
        });
    </script>
</body>
</html>
