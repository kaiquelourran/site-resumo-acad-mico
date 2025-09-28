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
            $mensagem_status = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">Questão excluída com sucesso!</div>';
            break;
        case 'updated':
            $mensagem_status = '<div style="color: green; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;">Questão atualizada com sucesso!</div>';
            break;
        case 'error':
            $mensagem_status = '<div style="color: red; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;">Erro ao excluir questão!</div>';
            break;
        case 'no_id':
            $mensagem_status = '<div style="color: orange; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin-bottom: 20px;">ID da questão não fornecido.</div>';
            break;
        case 'invalid':
            $mensagem_status = '<div style="color: orange; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin-bottom: 20px;">Requisição inválida.</div>';
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
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Questões</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">📋</div>
            <h1 class="title">Gerenciar Questões</h1>
            <p class="subtitle">Visualização e administração das questões</p>
        </div>
        
        <div class="user-info">
            <a href="quiz_sem_login.php" class="user-link">🎮 Testar Questões</a>
            <a href="index.php" class="user-link">🏠 Menu Principal</a>
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
                        <a href="quiz_sem_login.php" class="btn btn-secondary" style="margin: 10px;">🎮 Testar Questões</a>
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
                                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                                <a href="quiz_sem_login.php?questao=<?= $questao['id_questao'] ?>" 
                                                   class="btn btn-primary" target="_blank" title="Visualizar" 
                                                   style="padding: 5px 10px; font-size: 12px; text-decoration: none; background: #007bff; color: white; border-radius: 4px;">
                                                   👁️ Ver
                                                </a>
                                                <a href="admin/editar_questao.php?id=<?= $questao['id_questao'] ?>" 
                                                   class="btn btn-warning" title="Editar" 
                                                   style="padding: 5px 10px; font-size: 12px; text-decoration: none; background: #ffc107; color: #212529; border-radius: 4px;">
                                                   ✏️ Editar
                                                </a>
                                                <form method="POST" action="admin/deletar_questao.php" style="display: inline;" 
                                                      onsubmit="return confirm('Tem certeza que deseja excluir esta questão? Esta ação não pode ser desfeita!')">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                    <input type="hidden" name="id" value="<?= $questao['id_questao'] ?>">
                                                    <button type="submit" class="btn btn-danger" title="Excluir" 
                                                            style="padding: 5px 10px; font-size: 12px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
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
            <a href="quiz_sem_login.php" class="btn" style="margin: 10px;">🎮 Testar Questões</a>
            <a href="index.php" class="btn btn-secondary" style="margin: 10px;">🏠 Menu Principal</a>
        </div>
    </div>
</body>
</html>