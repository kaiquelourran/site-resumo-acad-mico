<?php
session_start();
require_once 'conexao.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar mensagens de status
$mensagem_status = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $mensagem_status = '<div class="alert alert-success">✅ Questão excluída com sucesso!</div>';
            break;
        case 'updated':
            $mensagem_status = '<div class="alert alert-success">✅ Questão atualizada com sucesso!</div>';
            break;
        case 'error':
            $mensagem_status = '<div class="alert alert-danger">❌ Erro ao excluir questão!</div>';
            break;
        case 'no_id':
            $mensagem_status = '<div class="alert alert-warning">⚠️ ID da questão não fornecido.</div>';
            break;
        case 'invalid':
            $mensagem_status = '<div class="alert alert-warning">⚠️ Requisição inválida.</div>';
            break;
    }
}

try {
    $stmt_questoes = $pdo->query("SELECT q.id_questao, q.enunciado, q.created_at, a.nome AS nome_assunto, a.id_assunto
                                  FROM questoes q
                                  JOIN assuntos a ON q.id_assunto = a.id_assunto
                                  ORDER BY a.nome ASC, q.created_at DESC");
    $questoes = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar questões por assunto
    $questoes_por_assunto = [];
    foreach ($questoes as $questao) {
        $questoes_por_assunto[$questao['nome_assunto']][] = $questao;
    }
} catch (PDOException $e) {
    $mensagem_status = "Erro ao buscar questões: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Questões - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
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
    .btn-warning { background: linear-gradient(to top, #ffc107, #e0a800); color: #212529; }
    .btn-danger { background: linear-gradient(to top, #dc3545, #c82333); }
    .btn-secondary { background: linear-gradient(to top, #6c757d, #545b62); }
    /* Tabela moderna */
    .table-responsive { overflow-x: auto; }
    .modern-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .modern-table th, .modern-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef; }
    .modern-table th { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); font-weight: 600; color: #495057; }
    .modern-table tr:hover { background: #f8f9fa; }
    .badge { display: inline-block; padding: 4px 8px; font-size: 12px; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 6px; background: linear-gradient(135deg, #0072FF 0%, #00C6FF 100%); color: #fff; }
    /* Alertas */
    .alert { padding: 16px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 8px; }
    .alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
    .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    .alert-warning { color: #856404; background-color: #fff3cd; border-color: #ffeaa7; }
    /* Header estiloso */
    .app-header { background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%); border-radius: 16px; padding: 18px; color: #fff; box-shadow: 0 12px 30px rgba(0,114,255,0.25); margin-bottom: 24px; }
    .app-header .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .app-header .brand { display: flex; align-items: center; gap: 12px; }
    .app-header .logo { font-size: 1.8rem; }
    .app-header .titles .title { margin: 0; color: #fff; font-size: 1.8rem; }
    .app-header .titles .subtitle { margin: 2px 0 0; color: #eaf6ff; font-size: 1rem; }
    .app-header .user-actions { display: flex; align-items: center; gap: 12px; }
    /* Responsividade */
    @media (max-width: 768px) {
        html, body { overflow-x: hidden; }
        .main-container { margin: 16px; padding: 18px; }
        .app-header .header-inner { flex-direction: column; align-items: flex-start; }
        .app-header .user-actions { width: 100%; justify-content: space-between; flex-wrap: wrap; }
        .modern-table { font-size: 14px; }
        .modern-table th, .modern-table td { padding: 8px; }
        .btn { padding: 8px 12px; font-size: 14px; }
    }
    @media (max-width: 480px) {
        .app-header .titles .title { font-size: 1.4rem; }
        .modern-table { font-size: 12px; }
        .btn { padding: 6px 10px; font-size: 12px; }
    }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="app-header">
            <div class="header-inner">
                <div class="brand">
                    <span class="logo">📋</span>
                    <div class="titles">
                        <h1 class="title">Gerenciar Questões</h1>
                        <p class="subtitle">Visualização e administração das questões</p>
                    </div>
                </div>
                <div class="user-actions">
                    <a href="quiz_sem_login.php" class="nav-btn">🎮 Testar Questões</a>
                    <a href="index.php" class="nav-btn secondary">🏠 Menu Principal</a>
                </div>
            </div>
        </div>

        <?php if (!empty($mensagem_status)): ?>
            <?= $mensagem_status ?>
        <?php endif; ?>
        
        <?php if (empty($questoes)): ?>
            <div class="card fade-in" style="text-align: center;">
                <h3 class="card-title">❌ Nenhuma questão encontrada</h3>
                <div class="card-description">
                    <p>Possíveis soluções:</p>
                    <div style="margin: 20px 0;">
                        <a href="quiz_sem_login.php" class="nav-btn secondary" style="margin: 10px;">🎮 Testar Questões</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✅ Questões encontradas:</strong> <?= count($questoes) ?> questão(ões) no banco de dados
            </div>
            
            <?php foreach ($questoes_por_assunto as $nome_assunto => $questoes_assunto): ?>
                <div class="card fade-in" style="margin-bottom: 30px;">
                    <h2 class="card-title">📚 <?= htmlspecialchars($nome_assunto) ?> (<?= count($questoes_assunto) ?> questões)</h2>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data de Criação</th>
                                    <th>Enunciado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questoes_assunto as $questao): ?>
                                    <tr>
                                        <td><span class="badge"><?= htmlspecialchars($questao['id_questao']) ?></span></td>
                                        <td><strong><?= $questao['created_at'] ? date('d/m/Y H:i', strtotime($questao['created_at'])) : 'N/A' ?></strong></td>
                                        <td><?= htmlspecialchars(substr($questao['enunciado'], 0, 100)) ?>...</td>
                                        <td>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <a href="quiz_sem_login.php?questao=<?= $questao['id_questao'] ?>" 
                                                   class="btn btn-primary" target="_blank" title="Visualizar">
                                                   👁️ Ver
                                                </a>
                                                <a href="admin/editar_questao.php?id=<?= $questao['id_questao'] ?>" 
                                                   class="btn btn-warning" title="Editar">
                                                   ✏️ Editar
                                                </a>
                                                <form method="POST" action="admin/deletar_questao.php" style="display: inline;" 
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta questão? Esta ação não pode ser desfeita!')">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="id" value="<?= $questao['id_questao'] ?>">
                                                    <button type="submit" class="btn btn-danger" title="Excluir">
                                                        🗑️ Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 40px 0;">
            <a href="quiz_sem_login.php" class="nav-btn" style="margin: 10px;">🎮 Testar Questões</a>
            <a href="index.php" class="nav-btn secondary" style="margin: 10px;">🏠 Menu Principal</a>
        </div>
    </div>
</body>
</html>