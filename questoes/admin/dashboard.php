<?php
session_start();

// Verifica se o usuário está logado E se ele tem o tipo 'admin'
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Incluir o arquivo de conexão
require_once __DIR__ . '/../conexao.php';

// Verificação de modo de manutenção não é necessária para admins


// --- Consultas para métricas gerais do site ---
// Total de usuários cadastrados
$stmt_usuarios = $pdo->query("SELECT COUNT(*) AS total_usuarios FROM usuarios");
$total_usuarios = $stmt_usuarios->fetch(PDO::FETCH_ASSOC)['total_usuarios'];

// Total de questões respondidas por todos os usuários (tabela correta)
try {
    $stmt_respostas_geral = $pdo->query("SELECT COUNT(*) AS total_respostas_geral FROM respostas_usuario");
    $total_respostas_geral = $stmt_respostas_geral->fetch(PDO::FETCH_ASSOC)['total_respostas_geral'];
} catch (Exception $e) {
    $total_respostas_geral = 0;
}

// Contagens de login real (usa coluna ultimo_login se existir; senão, fallback em created_at)
try {
    $hasUltimoLogin = false;
    try {
        $desc = $pdo->query("DESCRIBE usuarios");
        $cols = $desc ? $desc->fetchAll(PDO::FETCH_COLUMN, 0) : [];
        $hasUltimoLogin = in_array('ultimo_login', $cols, true);
    } catch (Exception $e) { /* ignore */ }

    $col = $hasUltimoLogin ? 'ultimo_login' : 'created_at';

    $stmt_usuarios_hoje = $pdo->query("SELECT COUNT(DISTINCT id_usuario) AS usuarios_hoje FROM usuarios WHERE DATE($col) = CURDATE()");
    $usuarios_hoje = (int)$stmt_usuarios_hoje->fetch(PDO::FETCH_ASSOC)['usuarios_hoje'];

    $stmt_usuarios_semana = $pdo->query("SELECT COUNT(DISTINCT id_usuario) AS usuarios_semana FROM usuarios WHERE $col >= (CURDATE() - INTERVAL 7 DAY)");
    $usuarios_semana = (int)$stmt_usuarios_semana->fetch(PDO::FETCH_ASSOC)['usuarios_semana'];

    $stmt_usuarios_mes = $pdo->query("SELECT COUNT(DISTINCT id_usuario) AS usuarios_mes FROM usuarios WHERE $col >= (CURDATE() - INTERVAL 30 DAY)");
    $usuarios_mes = (int)$stmt_usuarios_mes->fetch(PDO::FETCH_ASSOC)['usuarios_mes'];
} catch (Exception $e) {
    $usuarios_hoje = 0;
    $usuarios_semana = 0;
    $usuarios_mes = 0;
}

// Lista de usuários cadastrados (últimos 10), incluindo último login quando houver
try {
    // Detectar colunas reais para compatibilidade com variações (id vs id_usuario, created_at vs data_criacao)
    $desc = $pdo->query("DESCRIBE usuarios");
    $cols = $desc ? $desc->fetchAll(PDO::FETCH_COLUMN, 0) : [];
    $has = function($c) use ($cols) { return in_array($c, $cols, true); };

    $colId = $has('id_usuario') ? 'id_usuario' : ($has('id') ? 'id' : 'id_usuario');
    $colNome = $has('nome') ? 'nome' : ($has('name') ? 'name' : 'nome');
    $colEmail = $has('email') ? 'email' : 'email';
    $colTipo = $has('tipo') ? 'tipo' : ($has('role') ? 'role' : 'tipo');
    $colCreated = $has('created_at') ? 'created_at' : ($has('data_criacao') ? 'data_criacao' : 'created_at');
    $colUltimo = $has('ultimo_login') ? 'ultimo_login' : null;

    $selectUltimo = $colUltimo ? ", $colUltimo AS ultimo_login" : ", NULL AS ultimo_login";
    $sqlUsers = "SELECT $colId AS id_usuario, $colNome AS nome, $colEmail AS email, $colTipo AS tipo, $colCreated AS created_at" . $selectUltimo . " FROM usuarios ORDER BY $colCreated DESC LIMIT 10";
    $stmt_usuarios_lista = $pdo->query($sqlUsers);
    $usuarios_lista = $stmt_usuarios_lista->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    $usuarios_lista = [];
}

// Métricas adicionais de conteúdo e desempenho
// Acerto por assunto
$sql_acerto_por_assunto = "
    SELECT a.nome AS assunto,
           SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) AS acertos,
           COUNT(*) AS total,
           ROUND(SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 0) AS taxa
    FROM respostas_usuario r
    JOIN questoes q ON q.id_questao = r.id_questao
    JOIN assuntos a ON a.id_assunto = q.id_assunto
    GROUP BY a.id_assunto
    ORDER BY taxa ASC
    LIMIT 5";
try {
    $assuntos_mais_dificeis = $pdo->query($sql_acerto_por_assunto)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $assuntos_mais_dificeis = [];
}

// Questões mais erradas (top 5)
$sql_mais_erradas = "
    SELECT q.id_questao, LEFT(q.enunciado, 80) AS enunciado,
           SUM(CASE WHEN r.acertou = 0 THEN 1 ELSE 0 END) AS erros,
           COUNT(*) AS total,
           ROUND(SUM(CASE WHEN r.acertou = 0 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 0) AS taxa_erro
    FROM respostas_usuario r
    JOIN questoes q ON q.id_questao = r.id_questao
    GROUP BY q.id_questao
    HAVING total >= 3
    ORDER BY taxa_erro DESC, erros DESC
    LIMIT 5";
try {
    $questoes_mais_erradas = $pdo->query($sql_mais_erradas)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $questoes_mais_erradas = [];
}

// Buckets de dificuldade por taxa de acerto
$sql_buckets = "
    SELECT
      SUM(CASE WHEN sub.taxa < 40 THEN 1 ELSE 0 END) AS dificeis,
      SUM(CASE WHEN sub.taxa BETWEEN 40 AND 70 THEN 1 ELSE 0 END) AS medias,
      SUM(CASE WHEN sub.taxa > 70 THEN 1 ELSE 0 END) AS faceis
    FROM (
      SELECT q.id_questao,
             ROUND(AVG(r.acertou)*100,0) AS taxa
      FROM respostas_usuario r
      JOIN questoes q ON q.id_questao = r.id_questao
      GROUP BY q.id_questao
    ) sub";
try {
    $buckets = $pdo->query($sql_buckets)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $buckets = ['dificeis' => 0, 'medias' => 0, 'faceis' => 0];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Resumo Acadêmico</title>
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    /* Padrão visual alinhado ao index.php */
    body {
        background-image: linear-gradient(to top, #00C6FF, #0072FF);
        min-height: 100vh;
        margin: 0;
    }
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
    .user-info a { text-decoration: none; font-weight: 600; }
    .user-info a:hover { text-decoration: none; }
    /* Botões de navegação aprimorados */
    .nav-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 10px; background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%); color: #fff; border: 1px solid #bfe0ff; font-weight: 800; text-decoration: none; box-shadow: 0 8px 18px rgba(0,114,255,0.28); transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; letter-spacing: .2px; }
    .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(0,114,255,0.32); filter: brightness(1.03); }
    .nav-btn:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
    .nav-btn.secondary { background: linear-gradient(180deg, #6c757d 0%, #495057 100%); border-color: #adb5bd; box-shadow: 0 8px 18px rgba(108,117,125,0.28); }
    .nav-btn.secondary:hover { box-shadow: 0 12px 26px rgba(108,117,125,0.32); }
    .nav-btn.danger { background: linear-gradient(180deg, #dc3545 0%, #c82333 100%); border-color: #dc3545; box-shadow: 0 8px 18px rgba(220,53,69,0.28); }
    .nav-btn.danger:hover { box-shadow: 0 12px 26px rgba(220,53,69,0.32); }
    /* Cards */
    .card { background: #FFFFFF; border: 1px solid #e1e5e9; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .card-title { color: #333333; margin-bottom: 16px; }
    .card-description { color: #666666; }
    .btn { display: inline-block; padding: 12px 18px; border-radius: 8px; background: linear-gradient(to top, #00C6FF, #0072FF); color: #fff; border: none; font-weight: 600; text-decoration: none; transition: transform .2s ease, box-shadow .2s ease; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,114,255,0.3); }
    .btn:active { transform: translateY(0); }
    .btn:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
    .btn[aria-busy="true"] { cursor: wait; opacity: .8; }
    .btn-primary { background: linear-gradient(to top, #007bff, #0056b3); }
    .btn-success { background: linear-gradient(to top, #28a745, #1e7e34); }
    .btn-warning { background: linear-gradient(to top, #ffc107, #e0a800); color: #212529; }
    .btn-danger { background: linear-gradient(to top, #dc3545, #c82333); }
    .btn-secondary { background: linear-gradient(to top, #6c757d, #545b62); }
    .btn-outline { background: transparent; border: 2px solid #0072FF; color: #0072FF; }
    .btn-outline:hover { background: #0072FF; color: #fff; }
    /* Garantir que os botões sejam clicáveis */
    .btn, .nav-btn { 
        cursor: pointer; 
        user-select: none; 
            position: relative;
        z-index: 10;
        display: inline-block;
        text-decoration: none;
    }
    .btn:disabled, .nav-btn:disabled { opacity: 0.6; cursor: not-allowed; }
    .btn:disabled:hover, .nav-btn:disabled:hover { transform: none; box-shadow: none; }
    /* Garantir que os links não tenham problemas de sobreposição */
    .buttons-grid a, .user-actions a {
        position: relative;
        z-index: 10;
        pointer-events: auto;
    }
    /* Estatísticas */
    .stats-container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
    .stat-card { background: #FFFFFF; border: 1px solid #e1e5e9; border-radius: 12px; padding: 20px; min-width: 200px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.06); }
    .stat-number { color: #0072FF; font-weight: 700; font-size: 2rem; }
    .stat-label { color: #333; }
    .stat-card h3 { color: #333; margin-bottom: 10px; font-size: 1rem; }
    /* Seções */
    .admin-section, .metrics-section, .analytics-section { margin-bottom: 40px; }
    .admin-section h2, .metrics-section h2 { color: #333; margin-bottom: 20px; font-size: 1.5rem; }
    .buttons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
    .cards-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
    .analytics-list { list-style: none; padding: 0; margin: 0; }
    .analytics-list li { padding: 8px 0; border-bottom: 1px solid #f0f0f0; color: #666; }
    .analytics-list li:last-child { border-bottom: none; }
    /* Header estiloso */
    .app-header { background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%); border-radius: 16px; padding: 18px; color: #fff; box-shadow: 0 12px 30px rgba(0,114,255,0.25); margin-bottom: 24px; position: relative; }
    .app-header .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .app-header .brand { display: flex; align-items: center; gap: 12px; }
    .app-header .logo { font-size: 1.8rem; }
    .app-header .titles .title { margin: 0; color: #fff; font-size: 1.8rem; }
    .app-header .titles .subtitle { margin: 2px 0 0; color: #eaf6ff; font-size: 1rem; }
    .app-header .user-actions { display: flex; align-items: center; gap: 12px; }
    .header-nav { position: absolute; top: 18px; left: 18px; z-index: 2; }
    .btn-back { background: rgba(255, 255, 255, 0.2); color: white; border: 2px solid rgba(255, 255, 255, 0.3); padding: 10px 20px; border-radius: 25px; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(10px); }
    .btn-back:hover { background: rgba(255, 255, 255, 0.3); border-color: rgba(255, 255, 255, 0.5); transform: translateY(-2px); }
    /* Footer */
    .footer { text-align: center; padding: 20px; color: #666; border-top: 1px solid #e9ecef; margin-top: 40px; }
    /* Responsividade */
    @media (max-width: 768px) {
        html, body { overflow-x: hidden; }
        .main-container { margin: 16px auto; padding: 18px; max-width: calc(100% - 32px); }
        .app-header .header-inner { flex-direction: column; align-items: flex-start; }
        .app-header .user-actions { width: 100%; justify-content: space-between; flex-wrap: wrap; }
        .stats-container { flex-direction: column; gap: 12px; }
        .stat-card { width: 100%; }
        .buttons-grid { grid-template-columns: 1fr; }
        .cards-container { grid-template-columns: 1fr; }
        .header-nav { position: static; margin-bottom: 16px; }
    }
    @media (max-width: 480px) {
        .app-header .titles .title { font-size: 1.4rem; }
        .stat-number { font-size: 1.6rem; }
        }

    /* Estilos para seção de usuários */
    .users-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .users-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .users-header p {
        color: #666;
        margin: 0;
        font-size: 14px;
    }

    .users-table {
        background: #f8f9fa;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e9ecef;
    }

    .table-header {
        display: grid;
        grid-template-columns: 2fr 2fr 1fr 1fr 1.5fr;
        gap: 15px;
        padding: 15px 20px;
        background: linear-gradient(135deg, #0072FF, #00C6FF);
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    .table-body {
        max-height: 400px;
        overflow-y: auto;
    }

    .table-row {
        display: grid;
        grid-template-columns: 2fr 2fr 1fr 1fr 1.5fr;
        gap: 15px;
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.2s ease;
        align-items: center;
    }

    .table-row:hover {
        background: #f0f7ff;
    }

    .table-row:last-child {
        border-bottom: none;
    }

    .col-name {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .user-avatar-small {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0072FF, #00C6FF);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .col-email {
        color: #666;
        font-size: 14px;
    }

    .type-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .type-badge.admin {
        background: #ff6b6b;
        color: white;
    }

    .type-badge.user {
        background: #4ecdc4;
        color: white;
    }

    .col-date, .col-login {
        font-size: 13px;
        color: #666;
    }

    .never-logged {
        color: #999;
        font-style: italic;
    }

    .no-users {
        text-align: center;
        padding: 40px;
        color: #666;
    }

    /* Responsividade para tabela de usuários */
    @media (max-width: 768px) {
        .table-header, .table-row {
            grid-template-columns: 1fr;
            gap: 8px;
        }
        
        .table-header {
            display: none;
        }
        
        .table-row {
            display: block;
            padding: 15px;
            border: 1px solid #e9ecef;
            margin-bottom: 10px;
            border-radius: 8px;
        }
        
        .col-name, .col-email, .col-type, .col-date, .col-login {
            margin-bottom: 8px;
        }
        
        .col-name::before {
            content: "👤 ";
        }
        
        .col-email::before {
            content: "📧 ";
        }
        
        .col-type::before {
            content: "🏷️ ";
        }
        
        .col-date::before {
            content: "📅 ";
        }
        
        .col-login::before {
            content: "🕐 ";
        }
    }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="app-header">
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
            <div class="header-inner">
                <div class="brand">
                    <span class="logo">👨‍💼</span>
                    <div class="titles">
                        <h1 class="title">Painel de Administração</h1>
                        <p class="subtitle">Bem-vindo, <?= htmlspecialchars($_SESSION['nome_usuario']) ?>!</p>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="../perfil_usuario.php" class="nav-btn secondary">📊 Meu Desempenho</a>
                    <a href="../logout.php" class="nav-btn danger">🚪 Sair</a>
            </div>
            </div>
        </div>

        <main class="content">
            <section class="admin-section">
                <h2>Gerenciamento de Conteúdo</h2>
                <div class="buttons-grid">
                    <a href="../gerenciar_questoes_sem_auth.php" class="btn btn-primary">📋 Gerenciar Questões</a>
                    <a href="gerenciar_assuntos.php" class="btn btn-primary">📚 Gerenciar Assuntos</a>
                    <a href="gerenciar_comentarios.php" class="btn btn-primary">💬 Gerenciar Comentários</a>
                    <a href="add_questao.php" class="btn btn-success">➕ Adicionar Questão</a>
                    <a href="add_assunto.php" class="btn btn-secondary">📝 Adicionar Assunto</a>

                    <a href="../index.php" class="btn btn-outline">🏠 Voltar ao Site</a>
                </div>
            </section>
            
            <section class="metrics-section">
                <h2>📊 Métricas Gerais do Site</h2>
                <div class="stats-container">
                    <div class="stat-card slide-in">
                        <h3>👥 Usuários Cadastrados</h3>
                        <div class="stat-number"><?= htmlspecialchars($total_usuarios) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>🎯 Questões Respondidas</h3>
                        <div class="stat-number"><?= htmlspecialchars($total_respostas_geral) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>📅 Logins Hoje</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_hoje) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>📆 Logins Última Semana</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_semana) ?></div>
                    </div>
                    <div class="stat-card slide-in">
                        <h3>📈 Logins Último Mês</h3>
                        <div class="stat-number"><?= htmlspecialchars($usuarios_mes) ?></div>
                    </div>
                </div>
            </section>

            <section class="users-section">
                <h2>👥 Usuários Cadastrados</h2>
                <div class="users-container">
                    <div class="users-header">
                        <p>Últimos 10 usuários cadastrados no sistema</p>
                        <a href="gerenciar_usuarios.php" class="btn btn-outline">Ver Todos</a>
                    </div>
                    <div class="users-table">
                        <div class="table-header">
                            <div class="col-name">Nome</div>
                            <div class="col-email">Email</div>
                            <div class="col-type">Tipo</div>
                            <div class="col-date">Cadastro</div>
                            <div class="col-login">Último Login</div>
                        </div>
                        <div class="table-body">
                            <?php if (!empty($usuarios_lista)): ?>
                                <?php foreach ($usuarios_lista as $usuario): ?>
                                    <div class="table-row">
                                        <div class="col-name">
                                            <div class="user-avatar-small">
                                                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($usuario['nome']) ?></span>
                                        </div>
                                        <div class="col-email"><?= htmlspecialchars($usuario['email']) ?></div>
                                        <div class="col-type">
                                            <span class="type-badge <?= $usuario['tipo'] === 'admin' ? 'admin' : 'user' ?>">
                                                <?= $usuario['tipo'] === 'admin' ? '👑 Admin' : '👤 Usuário' ?>
                                            </span>
                                        </div>
                                        <div class="col-date">
                                            <?= date('d/m/Y', strtotime($usuario['created_at'])) ?>
                                        </div>
                                        <div class="col-login">
                                            <span class="never-logged">N/A</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-users">
                                    <p>Nenhum usuário cadastrado ainda.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="analytics-section">
                <h2>📈 Análises e Relatórios</h2>
                <div class="cards-container">
                    <div class="card">
                        <h4>🎯 Assuntos mais difíceis (taxa de acerto)</h4>
                        <ul class="analytics-list">
                            <?php if (!empty($assuntos_mais_dificeis)): foreach ($assuntos_mais_dificeis as $row): ?>
                                <li><?= htmlspecialchars($row['assunto']) ?> — <strong><?= htmlspecialchars($row['taxa']) ?>%</strong></li>
                            <?php endforeach; else: ?>
                                <li>Sem dados suficientes.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card">
                        <h4>📊 Distribuição de dificuldade</h4>
                        <ul class="analytics-list">
                            <li>🔴 Difíceis (< 40%): <strong><?= htmlspecialchars($buckets['dificeis'] ?? 0) ?></strong></li>
                            <li>🟡 Médias (40–70%): <strong><?= htmlspecialchars($buckets['medias'] ?? 0) ?></strong></li>
                            <li>🟢 Fáceis (> 70%): <strong><?= htmlspecialchars($buckets['faceis'] ?? 0) ?></strong></li>
                        </ul>
                    </div>
                    <div class="card">
                        <h4>❌ Questões mais erradas</h4>
                        <ul class="analytics-list">
                            <?php if (!empty($questoes_mais_erradas)): foreach ($questoes_mais_erradas as $q): ?>
                                <li>#<?= htmlspecialchars($q['id_questao']) ?> — <strong><?= htmlspecialchars($q['taxa_erro']) ?>%</strong> erros</li>
                            <?php endforeach; else: ?>
                                <li>Sem dados suficientes.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p>🚀 Desenvolvido por <strong>Resumo Acadêmico</strong> &copy; 2025</p>
        </footer>
    </div>

    <script>
        // Função para voltar à página anterior
        function goBack() {
            // Verifica se há histórico de navegação
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não há histórico, vai para a página principal
                window.location.href = '../index.php';
            }
        }


    </script>
</body>
</html>