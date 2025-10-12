<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

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

// Detectar suporte a user_id na tabela de respostas e obter user_id atual
$tem_user_id = false;
try {
    $stmt_check = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
    $tem_user_id = in_array('user_id', $colunas);
} catch (Exception $e) {
    // Mantém $tem_user_id = false se não conseguir descrever a tabela
}
$user_id = $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;

// Query base com LEFT JOIN para respostas (considerando apenas a última resposta por questão)
if ($tem_user_id && $user_id !== null) {
    // Com coluna user_id: considerar a última resposta do usuário atual por questão
    $sql = "SELECT q.*, a.nome as assunto_nome, 
                   CASE 
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
            LEFT JOIN (
                SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                FROM respostas_usuario ru1
                INNER JOIN (
                    SELECT id_questao, MAX(data_resposta) AS max_data
                    FROM respostas_usuario
                    WHERE user_id = ?
                    GROUP BY id_questao
                ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                WHERE ru1.user_id = ?
            ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
    $params = [$user_id, $user_id];
} else {
    // Sem coluna user_id: considerar a última resposta geral por questão
    $sql = "SELECT q.*, a.nome as assunto_nome, 
                   CASE 
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta
            FROM questoes q 
            LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
            LEFT JOIN (
                SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                FROM respostas_usuario ru1
                INNER JOIN (
                    SELECT id_questao, MAX(data_resposta) AS max_data
                    FROM respostas_usuario
                    GROUP BY id_questao
                ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
            ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
    $params = [];
}

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
        if ($tem_user_id && $user_id !== null) {
            // Modificar a consulta para usar NOT EXISTS para questões não respondidas pelo usuário
            $sql = "SELECT q.*, a.nome as assunto_nome, 'nao-respondida' as status_resposta
                    FROM questoes q 
                    LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                    WHERE NOT EXISTS (
                        SELECT 1 FROM respostas_usuario ru
                        WHERE ru.id_questao = q.id_questao AND ru.user_id = ?
                    )";
            $params = [$user_id];
        } else {
            // Sem coluna user_id: considerar questões sem qualquer resposta
            $sql = "SELECT q.*, a.nome as assunto_nome, 'nao-respondida' as status_resposta
                    FROM questoes q 
                    LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                    WHERE NOT EXISTS (
                        SELECT 1 FROM respostas_usuario ru
                        WHERE ru.id_questao = q.id_questao
                    )";
            $params = [];
        }
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

// Contar questões respondidas (considerando apenas uma resposta por questão)
if ($tem_user_id && $user_id !== null) {
    $sql_count_respondidas = "SELECT COUNT(*) FROM questoes q 
                              WHERE q.id_assunto = ? 
                              AND EXISTS (
                                  SELECT 1 FROM respostas_usuario ru 
                                  WHERE ru.id_questao = q.id_questao 
                                  AND ru.user_id = ?
                              )";
    $stmt_count_respondidas = $pdo->prepare($sql_count_respondidas);
    $stmt_count_respondidas->execute([$id_assunto, $user_id]);
} else {
    $sql_count_respondidas = "SELECT COUNT(*) FROM questoes q 
                              WHERE q.id_assunto = ? 
                              AND EXISTS (
                                  SELECT 1 FROM respostas_usuario ru 
                                  WHERE ru.id_questao = q.id_questao
                              )";
    $stmt_count_respondidas = $pdo->prepare($sql_count_respondidas);
    $stmt_count_respondidas->execute([$id_assunto]);
}
$contadores['respondidas'] = $stmt_count_respondidas->fetchColumn();

// Contar questões não respondidas
$contadores['nao-respondidas'] = $contadores['todas'] - $contadores['respondidas'];

// Contar questões certas (considerando apenas a última resposta por questão)
if ($tem_user_id && $user_id !== null) {
    $sql_count_certas = "SELECT COUNT(*) FROM questoes q 
                          WHERE q.id_assunto = ? 
                          AND EXISTS (
                              SELECT 1 FROM respostas_usuario ru1
                              INNER JOIN (
                                  SELECT id_questao, MAX(data_resposta) AS max_data
                                  FROM respostas_usuario
                                  WHERE user_id = ?
                                  GROUP BY id_questao
                              ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                              WHERE ru1.id_questao = q.id_questao 
                              AND ru1.user_id = ? 
                              AND ru1.acertou = 1
                          )";
    $stmt_count_certas = $pdo->prepare($sql_count_certas);
    $stmt_count_certas->execute([$id_assunto, $user_id, $user_id]);
} else {
    $sql_count_certas = "SELECT COUNT(*) FROM questoes q 
                          WHERE q.id_assunto = ? 
                          AND EXISTS (
                              SELECT 1 FROM respostas_usuario ru1
                              INNER JOIN (
                                  SELECT id_questao, MAX(data_resposta) AS max_data
                                  FROM respostas_usuario
                                  GROUP BY id_questao
                              ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                              WHERE ru1.id_questao = q.id_questao 
                              AND ru1.acertou = 1
                          )";
    $stmt_count_certas = $pdo->prepare($sql_count_certas);
    $stmt_count_certas->execute([$id_assunto]);
}
$contadores['certas'] = $stmt_count_certas->fetchColumn();

// Contar questões erradas (considerando apenas a última resposta por questão)
if ($tem_user_id && $user_id !== null) {
    $sql_count_erradas = "SELECT COUNT(*) FROM questoes q 
                           WHERE q.id_assunto = ? 
                           AND EXISTS (
                               SELECT 1 FROM respostas_usuario ru1
                               INNER JOIN (
                                   SELECT id_questao, MAX(data_resposta) AS max_data
                                   FROM respostas_usuario
                                   WHERE user_id = ?
                                   GROUP BY id_questao
                               ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                               WHERE ru1.id_questao = q.id_questao 
                               AND ru1.user_id = ? 
                               AND ru1.acertou = 0
                           )";
    $stmt_count_erradas = $pdo->prepare($sql_count_erradas);
    $stmt_count_erradas->execute([$id_assunto, $user_id, $user_id]);
} else {
    $sql_count_erradas = "SELECT COUNT(*) FROM questoes q 
                           WHERE q.id_assunto = ? 
                           AND EXISTS (
                               SELECT 1 FROM respostas_usuario ru1
                               INNER JOIN (
                                   SELECT id_questao, MAX(data_resposta) AS max_data
                                   FROM respostas_usuario
                                   GROUP BY id_questao
                               ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                               WHERE ru1.id_questao = q.id_questao 
                               AND ru1.acertou = 0
                           )";
    $stmt_count_erradas = $pdo->prepare($sql_count_erradas);
    $stmt_count_erradas->execute([$id_assunto]);
}
$contadores['erradas'] = $stmt_count_erradas->fetchColumn();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Questões - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        /* Background gradiente azul igual ao da index.php */
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
            margin: 0;
        }
        
        /* Container principal com mesmo estilo da index.php */
        .main-container {
            max-width: 1100px;
            margin: 40px auto;
            background: #FFFFFF;
            border-radius: 16px;
            border: 1px solid transparent;
            background-image: linear-gradient(#FFFFFF, #FFFFFF), linear-gradient(to top, #00C6FF, #0072FF);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        /* Estilos específicos para a página de questões */
        
        /* Header da subjects-page idêntico ao da index-page (COPIADO DE escolher_assunto.php) */
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

        /* Ocultar o botão Entrar na subjects-page para destacar 'Sair' */
        .subjects-page .header .header-btn.primary { display: none !important; }

        /* Estilo destacado para o botão Sair no header da subjects-page (vermelho de ação) */
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

        /* Botão 'Ir para o Site' compacto */
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

        /* Destaque para o título e subtítulo da página de assuntos */
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

        .filters-section {
            margin-bottom: 40px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.05) 0%, rgba(0, 114, 255, 0.05) 100%);
            border-radius: 12px;
            border: 1px solid rgba(0, 114, 255, 0.1);
        }

        .filters-title {
            font-size: 1.8em;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .filters-title::before {
            content: "🔍";
            font-size: 1.2em;
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
            padding: 18px 24px;
            border: 1px solid #e1e5e9;
            border-radius: 12px;
            background: #FFFFFF;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: transform .2s ease, box-shadow .2s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.06);
        }

        .filter-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 30px rgba(0,114,255,0.18);
        }

        .filter-btn.active {
            background: linear-gradient(to top, #00C6FF, #0072FF);
            color: white;
            border-color: #0072FF;
            box-shadow: 0 10px 25px rgba(0,114,255,0.3);
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
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .questions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .questions-grid {
                grid-template-columns: 1fr;
            }
        }

        .question-card {
            background: #FFFFFF !important;
            border-radius: 14px;
            padding: 0;
            box-shadow: 0 10px 20px rgba(0,0,0,0.06);
            border: 1px solid #e1e5e9;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .question-card:nth-child(1) { animation-delay: 0.1s; }
        .question-card:nth-child(2) { animation-delay: 0.2s; }
        .question-card:nth-child(3) { animation-delay: 0.3s; }
        .question-card:nth-child(4) { animation-delay: 0.4s; }
        .question-card:nth-child(5) { animation-delay: 0.5s; }
        
        /* Remover barra lateral roxa */
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
            padding: 12px 16px;
            background: linear-gradient(to top, #00C6FF, #0072FF) !important;
            border-radius: 12px 12px 0 0;
        }

        .question-number {
            font-weight: 700;
            color: #FFFFFF !important;
            font-size: 0.95em;
        }
        
        .question-text {
            padding: 16px 20px;
            background: #FFFFFF;
            font-size: 0.95em;
            line-height: 1.5;
        }
        
        .question-actions {
            padding: 12px 20px 16px 20px;
            background: #FFFFFF;
        }

        .question-status {
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 0.7em;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-nao-respondida {
            background: rgba(255, 255, 255, 0.95) !important;
            color: #333 !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .status-certa {
            background: rgba(76, 175, 80, 0.95) !important;
            color: #FFFFFF !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .status-errada {
            background: rgba(244, 67, 54, 0.95) !important;
            color: #FFFFFF !important;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        /* Removido - estilos consolidados acima */

        .question-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-action {
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .btn-action::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .btn-action:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            border: none;
            box-shadow: 0 6px 15px rgba(0,114,255,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0,114,255,0.45);
        }
        
        .btn-primary:active {
            transform: translateY(-1px) scale(1.02);
        }
        
        .btn-primary:focus {
            outline: 3px solid rgba(0,114,255,0.4);
            outline-offset: 3px;
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
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1em;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .nav-btn::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }

        .nav-btn:hover::before {
            left: 100%;
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: #fff;
            border: none;
            box-shadow: 0 6px 20px rgba(0,114,255,0.3);
        }

        .nav-btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(0,114,255,0.4);
        }

        .nav-btn-primary:active {
            transform: translateY(-1px) scale(1);
        }

        .nav-btn-outline {
            background: #FFFFFF;
            color: #333;
            border: 2px solid #e1e5e9;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .nav-btn-outline:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
            border-color: #00C6FF;
            color: #0072FF;
        }

        .nav-btn i {
            font-size: 1.2em;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,114,255,0.3);
        }
        
        .nav-btn:active {
            transform: translateY(0);
        }
        
        .nav-btn:focus {
            outline: 3px solid rgba(0,114,255,0.35);
            outline-offset: 2px;
        }

        .empty-state {
            text-align: center;
            padding: 80px 40px;
            color: #666;
            background: linear-gradient(135deg, rgba(0, 198, 255, 0.03) 0%, rgba(0, 114, 255, 0.03) 100%);
            border-radius: 12px;
            border: 2px dashed rgba(0, 114, 255, 0.2);
        }

        .empty-state-icon {
            font-size: 5em;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-state-title {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 15px;
            color: #0072FF;
        }

        .empty-state-text {
            font-size: 1.1em;
            line-height: 1.8;
            color: #555;
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
<body class="subjects-page">
<?php
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
    ['icon' => '📚', 'text' => 'Assuntos', 'link' => 'escolher_assunto.php', 'current' => false],
    ['icon' => '📋', 'text' => 'Lista de Questões', 'link' => '', 'current' => true]
];
$page_title = 'Lista de Questões';
$page_subtitle = htmlspecialchars($assunto_nome);
include 'header.php';
?>
    <script>
    // Ajustes de header para subjects-page, espelhando index-page (IGUAL escolher_assunto.php)
    document.addEventListener('DOMContentLoaded', function() {
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
    });
    </script>

            <!-- Filtros -->
            <div class="filters-section">
                <h2 class="filters-title">Filtrar Questões</h2>
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

</main>
</div>
    
<?php include 'footer.php'; ?>
    
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