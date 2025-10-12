<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Captura parâmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Sem embaralhamento - usar ordem original do banco
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
        
        // NÃO EMBARALHAR - usar ordem original do banco
        
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
            
            // Inserir ou atualizar resposta
            $user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 1; // Usar 1 como padrão se não houver usuário
            
            // Verificar se a tabela tem a coluna user_id
            try {
                $stmt_check = $pdo->query("DESCRIBE respostas_usuario");
                $colunas = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                $tem_user_id = in_array('user_id', $colunas);
                
                if ($tem_user_id) {
                    // Usar estrutura com user_id (permitir múltiplas respostas)
                    $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt_resposta->execute([$user_id, $id_questao, $id_alternativa, $acertou]);
                } else {
                    // Usar estrutura sem user_id (permitir múltiplas respostas)
                    $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
                }
            } catch (Exception $e) {
                error_log("ERRO ao verificar estrutura da tabela: " . $e->getMessage());
                // Fallback: tentar estrutura simples (permitir múltiplas respostas)
                $stmt_resposta = $pdo->prepare("
                    INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
            }
            
            // Se for uma requisição AJAX, retornar JSON
            if (isset($_POST['ajax_request'])) {
                // Encontrar a letra da alternativa correta (ordem original do banco)
                $letra_correta = '';
                $letras = ['A', 'B', 'C', 'D', 'E'];
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
                    'alternativa_correta' => $letra_correta, // Retornar a LETRA, não o ID
                    'explicacao' => '', // Explicação não disponível na tabela alternativas
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
                    'message' => 'Erro ao processar resposta: alternativa não encontrada'
                ]);
                exit;
            }
        }
    } catch (Exception $e) {
        // Se for uma requisição AJAX, retornar erro
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

// Construir query SQL baseada no filtro
if ($filtro_ativo === 'nao-respondidas') {
    // Para "não-respondidas", NUNCA carregar dados de resposta
    $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                   q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                   a.nome,
                   'nao-respondida' as status_resposta,
                   NULL as id_alternativa
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
            WHERE 1=1";
} else {
    // Para todos os outros filtros (incluindo "todas"), carregar dados de resposta normalmente
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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questões - <?php echo htmlspecialchars($assunto_nome); ?> - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <link rel="stylesheet" href="alternative-fix.css">
    <style>
        /* Background gradiente azul igual ao da listar_questoes.php */
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
        }

        /* Header da subjects-page idêntico ao da listar_questoes.php */
        .subjects-page .header .breadcrumb .header-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 10px 24px rgba(0,114,255,0.12);
            border-radius: 16px;
            padding: 14px 20px 16px 44px;
            position: relative;
        }
        .subjects-page .header .breadcrumb .header-container::before {
            content: "";
            position: absolute;
            left: 16px;
            top: 12px;
            bottom: 12px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .subjects-page .header .breadcrumb-link,
        .subjects-page .header .breadcrumb-current {
            font-size: 1.08rem;
            font-weight: 800;
            color: #111827;
            padding: 10px 14px;
            border-radius: 10px;
            background-color: #FFFFFF;
            border: 1px solid #CFE8FF;
            box-shadow: 0 1px 3px rgba(0,114,255,0.10);
        }
        .subjects-page .header .breadcrumb-current { color: #0057D9; }
        .subjects-page .header .breadcrumb-link:hover {
            background-color: #F0F7FF;
            color: #0057D9;
            border-color: #BBDDFF;
        }
        .subjects-page .header .breadcrumb-separator { color: #6B7280; font-size: 1rem; }

        .subjects-page .header .user-info {
            background: transparent !important;
            box-shadow: none !important;
            padding: 0 !important;
            border-radius: 0 !important;
            margin-bottom: 0 !important;
            animation: none !important;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .subjects-page .header .user-profile {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 8px;
            background: transparent;
            border: none;
            color: #111827;
            font-weight: 700;
        }
        .subjects-page .header .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 0 3px 8px rgba(0,114,255,0.25);
        }
        .subjects-page .header .user-name {
            font-size: 0.92rem;
            color: #111827;
            margin: 0;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .subjects-page .header .user-name { max-width: 120px; }
        }
        @media (max-width: 480px) {
            .subjects-page .header .user-name { display: none; }
            .subjects-page .header .user-avatar { width: 26px; height: 26px; font-size: 0.85rem; }
        }

        /* Ocultar o botão Entrar na subjects-page */
        .subjects-page .header .header-btn.primary { display: none !important; }

        /* Estilo destacado para o botão Sair */
        .subjects-page .header a.header-btn[href="logout.php"] {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(180deg, #ff4b5a 0%, #dc3545 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(220,53,69,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            letter-spacing: 0;
            font-size: 0.95rem;
        }
        .subjects-page .header a.header-btn[href="logout.php"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(220,53,69,0.40);
            filter: brightness(1.02);
        }
        .subjects-page .header a.header-btn[href="logout.php"]:focus {
            outline: 3px solid rgba(220,53,69,0.45);
            outline-offset: 2px;
        }
        .subjects-page .header a.header-btn[href="logout.php"]::before { content: none; }

        /* Botão 'Ir para o Site' */
        .subjects-page .header a.header-btn.site-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,114,255,0.30);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
            font-size: 0.95rem;
        }
        .subjects-page .header a.header-btn.site-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(0,114,255,0.40);
            filter: brightness(1.02);
        }
        .subjects-page .header a.header-btn.site-link:focus {
            outline: 3px solid rgba(0,114,255,0.35);
            outline-offset: 2px;
        }
        
        /* Garantir que as alternativas sejam clicáveis */
        .alternative {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 1;
        }
        
        /* Garantir que as alternativas sejam clicáveis */
        .alternative {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative !important;
            z-index: 10 !important;
        }
        
        .alternative::before {
            pointer-events: none !important;
            z-index: 0 !important;
        }
        
        .alternative * {
            pointer-events: none;
        }

        /* Destaque para o título e subtítulo */
        .subjects-page .page-header .header-container {
            max-width: 1100px;
            margin: 16px auto 24px;
            background: #FFFFFF;
            border: 2px solid #dbeafe;
            box-shadow: 0 12px 28px rgba(0,114,255,0.14);
            border-radius: 16px;
            padding: 16px 24px 18px 48px;
            position: relative;
        }
        .subjects-page .page-header .header-container::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 14px;
            bottom: 14px;
            width: 6px;
            border-radius: 6px;
            background: linear-gradient(180deg, #00C6FF 0%, #0072FF 100%);
        }
        .subjects-page .page-title {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 800;
            color: #111827;
            letter-spacing: 0.3px;
        }
        .subjects-page .page-subtitle {
            margin-top: 8px;
            color: #6B7280;
            font-size: 1.05rem;
        }
        @media (max-width: 768px) {
            .subjects-page .page-title { font-size: 1.45rem; }
            .subjects-page .page-subtitle { font-size: 0.95rem; }
        }

        .questoes-info {
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.08) 0%, rgba(0, 114, 255, 0.08) 100%);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 30px;
            border: 2px solid rgba(0, 114, 255, 0.15);
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 114, 255, 0.1);
        }

        .questoes-info h3 {
            color: #0072FF;
            margin-bottom: 8px;
            font-size: 1.4em;
            font-weight: 700;
        }

        .questoes-info p {
            color: #555;
            margin: 0;
            font-size: 1.05em;
            font-weight: 600;
        }

        .questions-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .question-card {
            background: white;
            border-radius: 14px;
            padding: 0;
            box-shadow: 0 8px 16px rgba(0,0,0,0.06);
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .question-card::before {
            display: none !important;
            content: none !important;
        }

        .question-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0,114,255,0.2);
            border-color: #00C6FF;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
            padding: 10px 18px;
            background: linear-gradient(to top, #00C6FF, #0072FF);
            border-radius: 14px 14px 0 0;
        }

        .question-number {
            font-weight: 700;
            color: #FFFFFF;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .question-number::before {
            content: '🎯';
            font-size: 1em;
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
            font-size: 1.05em;
            line-height: 1.5;
            color: #333;
            margin-bottom: 0;
            padding: 15px 18px;
            background: #FFFFFF;
            font-weight: 500;
        }

        .alternatives-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 0 18px 18px 18px;
            background: #FFFFFF;
            margin-bottom: 0;
        }

        .alternative {
            background: #FFFFFF;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            gap: 12px;
        }

        .alternative::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to top, #00C6FF, #0072FF);
            transform: scaleY(0);
            transition: transform 0.3s ease;
            border-radius: 10px 0 0 10px;
        }

        .alternative:hover::before {
            transform: scaleY(1);
        }

        .alternative:hover {
            border-color: #00C6FF;
            transform: translateX(5px);
            box-shadow: 0 6px 20px rgba(0, 114, 255, 0.15);
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.03) 0%, rgba(0, 114, 255, 0.03) 100%);
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
            font-size: 1em;
            box-shadow: 0 3px 10px rgba(0, 114, 255, 0.3);
        }

        .alternative-text {
            flex: 1;
            font-size: 1em;
            line-height: 1.5;
            color: #333;
            font-weight: 500;
        }

        /* Estilos para feedback visual - usando as classes corretas */
        .alternative-correct {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
            border-color: #28a745 !important;
            color: #155724 !important;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3) !important;
        }

        .alternative-correct::before {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4) !important;
        }

        .alternative-incorrect-chosen {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
            border-color: #dc3545 !important;
            color: #721c24 !important;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3) !important;
        }

        .alternative-incorrect-chosen::before {
            background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4) !important;
        }

        /* Estilos para painel de estatísticas */
        .stats-toggle-container {
            padding: 15px 18px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
            position: relative;
            z-index: 1;
        }

        .stats-toggle-btn {
            width: 100%;
            padding: 10px 15px;
            background: white;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            font-weight: 600;
            color: #0072FF;
        }

        .stats-toggle-btn:hover {
            background: #f0f7ff;
            border-color: #0072FF;
        }

        .stats-toggle-btn.active .fa-chevron-down {
            transform: rotate(180deg);
        }

        .stats-panel {
            padding: 20px 18px;
            background: #f8f9fa;
            border-top: 1px solid #e1e5e9;
            position: relative;
            z-index: 1;
        }

        .stats-loading {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-size: 1em;
        }

        .stats-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stats-chart-item {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stats-chart-item h4 {
            font-size: 0.9em;
            margin: 0 0 15px 0;
            color: #333;
            text-align: center;
        }

        .stats-chart-item canvas {
            max-height: 200px;
        }

        .stats-history {
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .stats-history h4 {
            font-size: 0.9em;
            margin: 0 0 15px 0;
            color: #333;
        }

        .stats-history-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stats-history-item {
            padding: 10px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9em;
        }

        .stats-history-item.correct {
            background: #d4edda;
            color: #155724;
        }

        .stats-history-item.incorrect {
            background: #f8d7da;
            color: #721c24;
        }

        .stats-history-date {
            font-weight: 500;
        }

        .stats-history-result {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .stats-charts {
                grid-template-columns: 1fr;
            }
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
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.08) 0%, rgba(0, 114, 255, 0.08) 100%);
            border-left: 4px solid #0072FF;
            padding: 16px 20px;
            margin: 16px 20px 20px 20px;
            border-radius: 0 10px 10px 0;
            opacity: 0;
            animation: fadeIn 0.5s ease-in-out forwards;
            box-shadow: 0 4px 12px rgba(0, 114, 255, 0.1);
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

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .explicacao-title {
            color: #0072FF;
            margin-bottom: 8px;
            font-size: 1em;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .explicacao-title::before {
            content: '💡';
            font-size: 1em;
        }

        .explicacao-text {
            color: #333;
            line-height: 1.6;
            margin: 0;
            font-size: 0.95em;
            font-weight: 500;
        }

        .navigation-section {
            text-align: center;
            padding: 25px 0;
            background: transparent;
            border-radius: 0;
            border: none;
            margin-top: 30px;
        }

        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
                padding: 20px;
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
                padding: 12px 16px;
            }
            
            .alternative:hover {
                transform: translateX(5px);
            }
        }
    </style>
</head>
<body class="subjects-page">
<?php
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
    ['icon' => '📚', 'text' => 'Assuntos', 'link' => 'escolher_assunto.php', 'current' => false],
    ['icon' => '📋', 'text' => 'Lista de Questões', 'link' => 'listar_questoes.php?id=' . $id_assunto . '&filtro=' . $filtro_ativo, 'current' => false],
    ['icon' => '🎯', 'text' => 'Questões', 'link' => '', 'current' => true]
];
$page_title = '🎯 Questões';
$page_subtitle = htmlspecialchars($assunto_nome) . ' - ' . getNomeFiltro($filtro_ativo);
include 'header.php';
?>
    <script>
    // Função para ajustes de header
    function ajustarHeader() {
        if (!document.body.classList.contains('subjects-page')) return;
        const header = document.querySelector('.header');
        if (!header) return;
        const userInfo = header.querySelector('.user-info');
        if (!userInfo) return;
        // garantir botão Sair
        let logoutBtn = header.querySelector('a.header-btn[href="logout.php"]');
        if (!logoutBtn) {
            const a = document.createElement('a');
            a.href = 'logout.php';
            a.className = 'header-btn';
            a.setAttribute('aria-label', 'Sair da sessão');
            a.innerHTML = '<i class="fas fa-sign-out-alt"></i><span>Sair</span>';
            userInfo.appendChild(a);
        }
        // perfil do usuário
        let profile = userInfo.querySelector('.user-profile');
        <?php
        $displayNameSubjects = '';
        foreach ([
            'usuario_nome','usuario','nome','user_name','username','login','nome_usuario','nomeCompleto'
        ] as $k) {
            if (isset($_SESSION[$k]) && trim($_SESSION[$k]) !== '') { $displayNameSubjects = $_SESSION[$k]; break; }
        }
        ?>
        const userName = "<?php echo htmlspecialchars($displayNameSubjects, ENT_QUOTES, 'UTF-8'); ?>";
        if (userName) {
            if (!profile) {
                const p = document.createElement('div');
                p.className = 'user-profile';
                const avatar = document.createElement('div');
                avatar.className = 'user-avatar';
                avatar.textContent = userName.trim().charAt(0).toUpperCase() || '?';
                const nameEl = document.createElement('span');
                nameEl.className = 'user-name';
                nameEl.textContent = userName;
                p.appendChild(avatar);
                p.appendChild(nameEl);
                userInfo.insertBefore(p, userInfo.firstChild);
            }
        }
        const loginBtn = header.querySelector('a.header-btn.primary[href="login.php"]');
        if (loginBtn) loginBtn.style.display = 'none';
        let siteBtn = header.querySelector('a.header-btn.site-link');
        if (!siteBtn) {
            const s = document.createElement('a');
            s.href = '../index.html';
            s.className = 'header-btn site-link';
            s.target = '_blank';
            s.rel = 'noopener';
            s.innerHTML = '<i class="fas fa-globe"></i><span>Ir para o Site</span>';
            userInfo.appendChild(s);
        }
    }
    </script>

            <!-- Informações das Questões -->
            <div class="questoes-info">
                <h3>📊 <?php echo getNomeFiltro($filtro_ativo); ?></h3>
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
                    <?php 
                     foreach ($questoes as $index => $questao): ?>
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
                                            case 'certa':
                                                echo '✅ Acertou';
                                            break;
                                        case 'errada':
                                                echo '❌ Errou';
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
                            
                            <form class="questoes-form" data-questao-id="<?php echo $questao['id_questao']; ?>">
                                <input type="hidden" name="id_questao" value="<?php echo $questao['id_questao']; ?>">
                                
                                <div class="alternatives-container">
                                    <?php
                                    // Buscar alternativas da tabela 'alternativas'
                                    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
                                    $stmt_alt->execute([$questao['id_questao']]);
                                    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // NÃO EMBARALHAR - usar ordem original do banco
                                    
                                    // Mapear as letras corretas (ordem original do banco)
                                    $letras = ['A', 'B', 'C', 'D', 'E'];
                                    $letra_correta = '';
                                    foreach ($alternativas_questao as $index => $alternativa) {
                                        $letra = $letras[$index] ?? ($index + 1);
                                        
                                        // Identificar qual letra corresponde à resposta correta após embaralhamento
                                        if ($alternativa['eh_correta'] == 1) {
                                            $letra_correta = $letra;
                                        }
                                        
                                        // Identificar alternativa correta
                                        $is_correct = ($alternativa['eh_correta'] == 1);
                                        
                                        // Verificar se esta alternativa foi selecionada pelo usuário
                                        $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
                                        
                                        // Verificar se a questão foi respondida (apenas para filtros que mostram respostas)
                                        $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
                                        
                                        $class = '';
                                        // NÃO aplicar classes visuais automaticamente - deixar para o JavaScript
                                        // Isso permite que o usuário clique e responda novamente
                                        ?>
                                        <div class="alternative <?php echo $class; ?>" 
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
                            </form>
                            
                            <?php if (!empty($questao['explicacao']) && !empty($questao['id_alternativa'])): ?>
                                <div class="explicacao-container">
                                    <div class="explicacao-title">💡 Explicação:</div>
                                    <div class="explicacao-text"><?php echo htmlspecialchars($questao['explicacao']); ?></div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Botão de Estatísticas -->
                            <div class="stats-toggle-container">
                                <button class="stats-toggle-btn" data-questao-id="<?php echo $questao['id_questao']; ?>">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Ver Estatísticas</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>

                            <!-- Painel de Estatísticas -->
                            <div class="stats-panel" id="stats-<?php echo $questao['id_questao']; ?>" style="display: none;">
                                <div class="stats-loading">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando estatísticas...
                                </div>
                                <div class="stats-content" style="display: none;">
                                    <div class="stats-charts">
                                        <div class="stats-chart-item">
                                            <h4>Percentual de Rendimento</h4>
                                            <canvas id="chart-pie-<?php echo $questao['id_questao']; ?>"></canvas>
                                        </div>
                                        <div class="stats-chart-item">
                                            <h4>Alternativas mais respondidas</h4>
                                            <canvas id="chart-bar-<?php echo $questao['id_questao']; ?>"></canvas>
                                        </div>
                                    </div>
                                    <div class="stats-history">
                                        <h4>Histórico de Respostas</h4>
                                        <div class="stats-history-list">
                                            <div id="history-<?php echo $questao['id_questao']; ?>"></div>
                                            <div class="history-load-more" style="text-align:center; margin-top:10px;">
                                                <button class="load-more-btn" data-role="history">Carregar mais</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
            console.log('mostrarFeedbackVisual chamada com:', {
                questaoId, alternativaSelecionada, alternativaCorreta, explicacao
            });
            
            const questaoCard = document.querySelector(`#questao-${questaoId}`);
            
            if (!questaoCard) {
                console.error('Questão não encontrada:', questaoId);
                return;
            }
            
            console.log('Questão encontrada:', questaoCard);
            
            const alternativas = questaoCard.querySelectorAll('.alternative');
            console.log('Alternativas encontradas:', alternativas.length);
            
            // Limpar feedback anterior
            alternativas.forEach(alt => {
                alt.classList.remove('alternative-correct', 'alternative-incorrect-chosen');
                alt.style.background = '';
                alt.style.borderColor = '';
            });
            
            // Marcar alternativa correta
            const alternativaCorretaEl = questaoCard.querySelector(`[data-alternativa="${alternativaCorreta}"]`);
            console.log('Alternativa correta encontrada:', alternativaCorretaEl);
            if (alternativaCorretaEl) {
                alternativaCorretaEl.classList.add('alternative-correct');
                console.log('Classe alternative-correct adicionada');
            }
            
            // Marcar alternativa selecionada
            const alternativaSelecionadaEl = questaoCard.querySelector(`[data-alternativa="${alternativaSelecionada}"]`);
            console.log('Alternativa selecionada encontrada:', alternativaSelecionadaEl);
            if (alternativaSelecionadaEl) {
                // Se a alternativa selecionada for a correta, ela já foi marcada como verde acima
                // Se for incorreta, marcar como vermelha
                if (alternativaSelecionada !== alternativaCorreta) {
                    alternativaSelecionadaEl.classList.add('alternative-incorrect-chosen');
                    console.log('Classe alternative-incorrect-chosen adicionada');
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

        // Event listeners para as alternativas - VERSÃO CORRIGIDA
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado, configurando alternativas...');
            
            // Verificar se já foi configurado para evitar duplicação
            if (window.alternativasConfiguradas) {
                console.log('Alternativas já configuradas, pulando...');
                return;
            }
            window.alternativasConfiguradas = true;
            
            // Limpar flag quando a página for recarregada
            window.addEventListener('beforeunload', function() {
                window.alternativasConfiguradas = false;
            });
            
            // Limpar TODOS os event listeners existentes
            const allAlternatives = document.querySelectorAll('.alternative');
            allAlternatives.forEach(alt => {
                // Remover todos os event listeners clonando o elemento
                const newAlt = alt.cloneNode(true);
                alt.parentNode.replaceChild(newAlt, alt);
            });
            
            // Configurar TODAS as alternativas de uma vez
            const todasAlternativas = document.querySelectorAll('.alternative');
            console.log('Total de alternativas encontradas:', todasAlternativas.length);
            
            todasAlternativas.forEach((alternativa, index) => {
                console.log('Configurando alternativa', index + 1);
                
                // Verificar se já tem event listener para evitar duplicação
                if (alternativa.dataset.listenerAdded === 'true') {
                    console.log('Event listener já adicionado, pulando...');
                    return;
                }
                alternativa.dataset.listenerAdded = 'true';
                
                // Verificar se a questão já foi respondida
                const questaoCard = alternativa.closest('.question-card');
                if (questaoCard && questaoCard.dataset.respondida === 'true') {
                    console.log('Questão já respondida, desabilitando alternativas...');
                    alternativa.style.pointerEvents = 'none';
                    alternativa.style.cursor = 'default';
                    return;
                }
                
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
                    
                    // Desabilitar todas as alternativas desta questão para evitar cliques duplos
                    todasAlternativas.forEach(alt => {
                        alt.style.pointerEvents = 'none';
                        alt.style.cursor = 'default';
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
                                
                                // Feedback visual baseado na resposta real
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
                                
                                console.log('Feedback aplicado:', { 
                                    alternativaSelecionada, 
                                    alternativaCorreta, 
                                    acertou,
                                    message: jsonData.message 
                                });
                                
                            } else {
                                console.log('Erro na resposta:', jsonData.message);
                                // Reabilitar cliques em caso de erro
                                questaoCard.dataset.respondida = 'false';
                                todasAlternativas.forEach(alt => {
                                    alt.style.pointerEvents = 'auto';
                                    alt.style.cursor = 'pointer';
                                });
                            }
                        } catch (e) {
                            console.error('Erro ao fazer parse do JSON:', e);
                            console.log('Dados brutos:', data);
                            // Reabilitar cliques em caso de erro
                            questaoCard.dataset.respondida = 'false';
                            todasAlternativas.forEach(alt => {
                                alt.style.pointerEvents = 'auto';
                                alt.style.cursor = 'pointer';
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Erro na requisição:', error);
                        // Reabilitar cliques em caso de erro
                        questaoCard.dataset.respondida = 'false';
                        todasAlternativas.forEach(alt => {
                            alt.style.pointerEvents = 'auto';
                            alt.style.cursor = 'pointer';
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

            // Inicializar estatísticas
            initStats();
            
            // Ajustar header
            ajustarHeader();
        });

        // Load Chart.js library
        const chartScript = document.createElement('script');
        chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        document.head.appendChild(chartScript);

        // Statistics toggle functionality
        function initStats() {
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
        }

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
    </script>

</main>
</div>
    
<?php include 'footer.php'; ?>
</body>
</html>