<?php
require_once __DIR__ . '/conexao.php';

// Captura parâmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

// Busca informações do assunto
$assunto_nome = 'Todas as Questões';
if ($id_assunto > 0) {
    $stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
    $stmt_assunto->execute([$id_assunto]);
    $assunto_nome = $stmt_assunto->fetchColumn() ?: 'Assunto não encontrado';
}

// Query base com LEFT JOIN para respostas
$sql = "SELECT q.*, a.nome as assunto_nome, 
               CASE 
                   WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                   WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                   WHEN r.id_questao IS NOT NULL THEN 'respondida'
                   ELSE 'nao-respondida'
               END as status_resposta
        FROM questoes q 
        LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
        LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
        WHERE 1=1";
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
        $sql .= " AND r.id_questao IS NULL";
        break;
    case 'certas':
        $sql .= " AND r.acertou = 1";
        break;
    case 'erradas':
        $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
        break;
    // 'todas' não precisa de filtro adicional
}

$sql .= " ORDER BY q.id_questao";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar questões por status usando queries separadas para precisão
$contadores = [
    'todas' => 0,
    'respondidas' => 0,
    'nao-respondidas' => 0,
    'certas' => 0,
    'erradas' => 0
];

// Contar todas as questões do assunto
$sql_count_all = "SELECT COUNT(*) FROM questoes WHERE id_assunto = ?";
$stmt_count_all = $pdo->prepare($sql_count_all);
$stmt_count_all->execute([$id_assunto]);
$contadores['todas'] = $stmt_count_all->fetchColumn();

// Contar questões respondidas
$sql_count_respondidas = "SELECT COUNT(DISTINCT q.id_questao) FROM questoes q 
                          INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                          WHERE q.id_assunto = ?";
$stmt_count_respondidas = $pdo->prepare($sql_count_respondidas);
$stmt_count_respondidas->execute([$id_assunto]);
$contadores['respondidas'] = $stmt_count_respondidas->fetchColumn();

// Contar questões não respondidas
$contadores['nao-respondidas'] = $contadores['todas'] - $contadores['respondidas'];

// Contar questões certas
$sql_count_certas = "SELECT COUNT(DISTINCT q.id_questao) FROM questoes q 
                        INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                        WHERE q.id_assunto = ? AND r.acertou = 1";
$stmt_count_certas = $pdo->prepare($sql_count_certas);
$stmt_count_certas->execute([$id_assunto]);
$contadores['certas'] = $stmt_count_certas->fetchColumn();

// Contar questões erradas
$sql_count_erradas = "SELECT COUNT(DISTINCT q.id_questao) FROM questoes q 
                      INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                      WHERE q.id_assunto = ? AND r.acertou = 0";
$stmt_count_erradas = $pdo->prepare($sql_count_erradas);
$stmt_count_erradas->execute([$id_assunto]);
$contadores['erradas'] = $stmt_count_erradas->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Questões - <?php echo htmlspecialchars($assunto_nome); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Estilos específicos para a página de questões baseados no index.html */
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

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        .page-header .breadcrumb { display: flex; align-items: center; gap: 10px; justify-content: center; margin-bottom: 14px; }
        .page-header .breadcrumb-link { color: #111827; text-decoration: none; font-weight: 700; padding: 8px 12px; border-radius: 10px; background-color: #FFFFFF; border: 1px solid #E5E7EB; box-shadow: 0 1px 2px rgba(17,24,39,0.06); }
        .page-header .breadcrumb-link:hover { background-color: #F0F7FF; color: #0057D9; border-color: #CFE8FF; }
        .page-header .breadcrumb-current { color: #0057D9; font-weight: 800; }
        .page-header .breadcrumb-separator { color: #6B7280; font-size: 0.95rem; }

        .page-title {
            font-size: 2.5em;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.2em;
            font-weight: 300;
        }

        .filters-section {
            margin-bottom: 40px;
        }

        .filters-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .filter-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            background: white;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .filter-label {
            font-size: 1em;
            font-weight: 600;
        }

        .filter-count {
            background: rgba(255, 255, 255, 0.2);
            color: inherit;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }

        .filter-btn.active .filter-count {
            background: rgba(255, 255, 255, 0.3);
        }

        .questions-section {
            margin-bottom: 40px;
        }

        .questions-grid {
            display: grid;
            gap: 20px;
        }

        .question-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 2px solid #f0f0f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-number {
            font-weight: 600;
            color: #667eea;
            font-size: 1.1em;
        }

        .question-status {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-nao-respondida {
            background: #f8f9fa;
            color: #6c757d;
            border: 1px solid #dee2e6;
        }

        .status-certa {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-errada {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .question-text {
            color: #333;
            line-height: 1.6;
            font-size: 1.05em;
            margin-bottom: 20px;
        }

        .question-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .navigation-section {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 20px;
            border: 2px solid #f0f0f0;
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
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state-text {
            font-size: 1.1em;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 20px;
            }
            
            .page-title {
                font-size: 2em;
            }
            
            .filters-grid {
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
            <div class="page-header">
                <nav class="breadcrumb">
                    <a href="index.php" class="breadcrumb-link">🏠 Início</a>
                    <span class="breadcrumb-separator">›</span>
                    <a href="escolher_assunto.php" class="breadcrumb-link">📚 Assuntos</a>
                    <span class="breadcrumb-separator">›</span>
                    <span class="breadcrumb-current">📋 Lista de Questões</span>
                </nav>
                <h1 class="page-title">📚 Lista de Questões</h1>
                <p class="page-subtitle"><?php echo htmlspecialchars($assunto_nome); ?></p>
            </div>

            <!-- Filtros -->
            <div class="filters-section">
                <h2 class="filters-title">🔍 Filtrar Questões</h2>
                <div class="filters-grid">
                    <a href="?id=<?php echo $id_assunto; ?>&filtro=todas" 
                       class="filter-btn <?php echo $filtro_ativo === 'todas' ? 'active' : ''; ?>">
                        <span class="filter-label">📋 Todas</span>
                        <span class="filter-count"><?php echo $contadores['todas']; ?></span>
                    </a>
                    
                    <a href="?id=<?php echo $id_assunto; ?>&filtro=nao-respondidas" 
                       class="filter-btn <?php echo $filtro_ativo === 'nao-respondidas' ? 'active' : ''; ?>">
                        <span class="filter-label">❓ Não Respondidas</span>
                        <span class="filter-count"><?php echo $contadores['nao-respondidas']; ?></span>
                    </a>
                    
                    <a href="?id=<?php echo $id_assunto; ?>&filtro=respondidas" 
                       class="filter-btn <?php echo $filtro_ativo === 'respondidas' ? 'active' : ''; ?>">
                        <span class="filter-label">✅ Respondidas</span>
                        <span class="filter-count"><?php echo $contadores['respondidas']; ?></span>
                    </a>
                    
                    <a href="?id=<?php echo $id_assunto; ?>&filtro=certas" 
                       class="filter-btn <?php echo $filtro_ativo === 'certas' ? 'active' : ''; ?>">
                        <span class="filter-label">🎯 Certas</span>
                        <span class="filter-count"><?php echo $contadores['certas']; ?></span>
                    </a>
                    
                    <a href="?id=<?php echo $id_assunto; ?>&filtro=erradas" 
                       class="filter-btn <?php echo $filtro_ativo === 'erradas' ? 'active' : ''; ?>">
                        <span class="filter-label">❌ Erradas</span>
                        <span class="filter-count"><?php echo $contadores['erradas']; ?></span>
                    </a>
                </div>
            </div>

            <!-- Lista de Questões -->
            <div class="questions-section">
                <?php if (empty($questoes)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <h3 class="empty-state-title">Nenhuma questão encontrada</h3>
                        <p class="empty-state-text">
                            Não há questões disponíveis para o filtro selecionado.<br>
                            Tente selecionar um filtro diferente ou escolha outro assunto.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="questions-grid">
                        <?php foreach ($questoes as $questao): ?>
                            <div class="question-card">
                                <div class="question-header">
                                    <div class="question-number">
                                        🎯 Questão #<?php echo $questao['id_questao']; ?>
                                    </div>
                                    <div class="question-status status-<?php echo $questao['status_resposta']; ?>">
                                        <?php
                                        switch($questao['status_resposta']) {
                                            case 'nao-respondida':
                                                echo '❓ Não Respondida';
                                                break;
                                            case 'acertada':
                                                echo '✅ Certa';
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
                                
                                <div class="question-actions">
                    <a href="quiz_vertical_filtros.php?id=<?php echo $id_assunto; ?>&filtro=<?php echo $filtro_ativo; ?>&questao_inicial=<?php echo $questao['id_questao']; ?>" 
                       class="btn-action btn-primary">
                        🎯 Responder
                    </a>

                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Navegação -->
            <div class="navigation-section">
                <div class="nav-buttons">

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

    <script>
        // Salvar filtro ativo no localStorage
        const filtroAtivo = '<?php echo $filtro_ativo; ?>';
        localStorage.setItem('filtro_ativo', filtroAtivo);
        
        // Animações suaves
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.question-card');
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