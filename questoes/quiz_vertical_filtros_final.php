<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

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
    try {
        $id_questao = (int)$_POST['id_questao'];
        $alternativa_selecionada = $_POST['alternativa_selecionada'];
        
        // Buscar as alternativas da questão para mapear a letra correta
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$id_questao]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapear a letra selecionada para o ID da alternativa
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $id_alternativa = null;
        foreach ($alternativas_questao as $index => $alternativa) {
            $letra = $letras[$index] ?? ($index + 1);
            if (strtoupper($letra) === strtoupper($alternativa_selecionada)) {
                $id_alternativa = $alternativa['id_alternativa'];
                break;
            }
        }
        
        // Buscar a alternativa correta
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        if ($alternativa_correta && $id_alternativa) {
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
            
            // Inserir resposta
            $user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 1;
            
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
                error_log("ERRO ao inserir resposta: " . $e->getMessage());
                $stmt_resposta = $pdo->prepare("
                    INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
            }
            
            // Se for uma requisição AJAX, retornar JSON
            if (isset($_POST['ajax_request'])) {
                // Encontrar a letra da alternativa correta
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
        } else {
            if (isset($_POST['ajax_request'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao processar resposta: alternativa não encontrada'
                ]);
                exit;
            }
        }
    } catch (Exception $e) {
        if (isset($_POST['ajax_request'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro interno: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}

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
if ($filtro_ativo === 'certas') {
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
} elseif ($filtro_ativo === 'erradas') {
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

function getNomeFiltro($filtro) {
    switch ($filtro) {
        case 'certas': return 'Questões Certas';
        case 'erradas': return 'Questões Erradas';
        default: return 'Todas as Questões';
    }
}

$page_title = '🎯 Questões';
$page_subtitle = htmlspecialchars($assunto_nome) . ' - ' . getNomeFiltro($filtro_ativo);
include 'header.php';
?>

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
</style>

<div class="quiz-container">
    <h1 style="text-align: center; margin-bottom: 30px; color: #333;">
        <i class="fas fa-question-circle"></i> Quiz - <?php echo htmlspecialchars($assunto_nome); ?>
    </h1>
    
    <!-- Filtros -->
    <div class="filters">
        <a href="?id=<?php echo $id_assunto; ?>&filtro=todas" class="filter-btn <?php echo $filtro_ativo === 'todas' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> Todas
        </a>
        <a href="?id=<?php echo $id_assunto; ?>&filtro=certas" class="filter-btn <?php echo $filtro_ativo === 'certas' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Certas
        </a>
        <a href="?id=<?php echo $id_assunto; ?>&filtro=erradas" class="filter-btn <?php echo $filtro_ativo === 'erradas' ? 'active' : ''; ?>">
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
        
        <div class="alternatives-container">
            <?php
            $letras = ['A', 'B', 'C', 'D', 'E'];
            foreach ($alternativas_questao as $index => $alternativa):
                $letra = $letras[$index] ?? ($index + 1);
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
    </div>
    
    <!-- Navegação -->
    <div class="navigation">
        <?php if ($indice_atual > 0): ?>
            <a href="?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>&questao_inicial=<?php echo $questoes[$indice_atual - 1]['id_questao']; ?>" class="nav-btn">
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
            <a href="?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>&questao_inicial=<?php echo $questoes[$indice_atual + 1]['id_questao']; ?>" class="nav-btn">
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
// JavaScript SIMPLIFICADO - SEM DUPLICAÇÃO
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando quiz...');
    
    // Configurar alternativas
    const alternativas = document.querySelectorAll('.alternative');
    console.log('📝 Alternativas encontradas:', alternativas.length);
    
    alternativas.forEach((alt, index) => {
        console.log(`⚙️ Configurando alternativa ${index + 1}`);
        
        alt.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const questaoCard = this.closest('.question-card');
            const questaoId = this.dataset.questaoId;
            const alternativaSelecionada = this.dataset.alternativa;
            
            console.log('🎯 Clique detectado:', {
                questaoId,
                alternativaSelecionada,
                questaoCard: questaoCard.id
            });
            
            // Verificar se já foi respondida
            if (questaoCard.dataset.respondida === 'true') {
                console.log('⚠️ Questão já respondida, ignorando...');
                return;
            }
            
            // Marcar como respondida
            questaoCard.dataset.respondida = 'true';
            
            // Desabilitar todas as alternativas
            const todasAlternativas = questaoCard.querySelectorAll('.alternative');
            todasAlternativas.forEach(a => {
                a.style.pointerEvents = 'none';
                a.style.cursor = 'default';
            });
            
            // Destacar a selecionada
            this.style.background = '#e3f2fd';
            this.style.borderColor = '#2196f3';
            
            // Enviar via AJAX
            const formData = new FormData();
            formData.append('id_questao', questaoId);
            formData.append('alternativa_selecionada', alternativaSelecionada);
            formData.append('ajax_request', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                try {
                    const jsonData = JSON.parse(data);
                    console.log('✅ Resposta recebida:', jsonData);
                    
                    if (jsonData.success) {
                        const alternativaCorreta = jsonData.alternativa_correta;
                        const acertou = alternativaSelecionada === alternativaCorreta;
                        
                        // Marcar alternativa correta
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
                        
                        console.log('🎨 Feedback aplicado:', {
                            alternativaSelecionada,
                            alternativaCorreta,
                            acertou,
                            message: jsonData.message
                        });
                    }
                } catch (e) {
                    console.error('❌ Erro ao processar resposta:', e);
                }
            })
            .catch(error => {
                console.error('❌ Erro na requisição:', error);
            });
        });
    });
    
    console.log('✅ Quiz inicializado com sucesso!');
});
</script>

<?php include 'footer.php'; ?>
