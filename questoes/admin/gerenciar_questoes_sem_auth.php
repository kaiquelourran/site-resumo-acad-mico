<?php
session_start();

// Verifica se o usuário está logado E se ele tem o tipo 'admin'
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

// Incluir o arquivo de conexão
require_once __DIR__ . '/../conexao.php';

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar mensagens de status
$mensagem_status = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'deleted':
            $mensagem_status = '<div class="alert alert-success"><strong>✅ Sucesso!</strong> Questão excluída com sucesso!</div>';
            break;
        case 'error':
            $mensagem_status = '<div class="alert alert-danger"><strong>❌ Erro!</strong> Erro ao excluir questão!</div>';
            break;
    }
}

try {
    // Buscar questões com informações completas
    $stmt_questoes = $pdo->query("SELECT q.id_questao, q.enunciado, q.dificuldade, q.created_at, a.nome AS nome_assunto,
                                  COUNT(alt.id_alternativa) as total_alternativas
                                  FROM questoes q
                                  JOIN assuntos a ON q.id_assunto = a.id_assunto
                                  LEFT JOIN alternativas alt ON q.id_questao = alt.id_questao
                                  GROUP BY q.id_questao
                                  ORDER BY q.id_questao DESC");
    $questoes = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $total_questoes = count($questoes);
    $questoes_por_dificuldade = [];
    foreach ($questoes as $q) {
        $dif = $q['dificuldade'] ?? 'não definida';
        $questoes_por_dificuldade[$dif] = ($questoes_por_dificuldade[$dif] ?? 0) + 1;
    }
    
} catch (PDOException $e) {
    $mensagem_status = '<div class="alert alert-danger"><strong>❌ Erro!</strong> ' . $e->getMessage() . '</div>';
    $questoes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Questões - Admin</title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../modern-style.css">

</head>
<body>
<!-- VERIFICACAO_ARQUIVO_GEMINI_20240729 -->
<?php
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => '../index.php', 'current' => false],
    ['icon' => '👨‍💼', 'text' => 'Admin', 'link' => 'dashboard.php', 'current' => false],
    ['icon' => '📝', 'text' => 'Gerenciar Questões', 'link' => 'gerenciar_questoes_sem_auth.php', 'current' => true]
];
$page_title = 'Gerenciar Questões';
$page_subtitle = 'Administre todas as questões do sistema';
include '../header.php';
?>

    <!-- usando o main-container e <main> abertos pelo header.php -->
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-bottom:15px;">
            <a href="dashboard.php" class="btn btn-outline">← Voltar ao Dashboard</a>
            <a href="add_questao.php" class="btn btn-success">+ Adicionar Questão</a>
            <a href="gerenciar_assuntos.php" class="btn btn-primary">Gerenciar Assuntos</a>
        </div>

        <?php if (!empty($mensagem_status)): ?>
            <?= str_replace('alert-danger', 'alert-error', $mensagem_status) ?>
        <?php endif; ?>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total de Questões</h3>
                <div class="stat-number"><?= $total_questoes ?></div>
            </div>
            <?php foreach ($questoes_por_dificuldade as $dif => $count): ?>
                <div class="stat-card">
                    <h3><?= ucfirst($dif) ?></h3>
                    <div class="stat-number"><?= $count ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Buscar questões por assunto ou enunciado...">
        </div>

        <?php if (empty($questoes)): ?>
            <div class="alert alert-warning">
                <strong>Nenhuma questão encontrada!</strong> 
                <a href="add_questao.php">Clique aqui para adicionar a primeira questão</a>.
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table" id="questionsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Assunto</th>
                            <th>Enunciado</th>
                            <th>Dificuldade</th>
                            <th>Alternativas</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questoes as $questao): ?>
                            <tr>
                                <td><strong>#<?= htmlspecialchars($questao['id_questao']) ?></strong></td>
                                <td><span class="badge"><?= htmlspecialchars($questao['nome_assunto']) ?></span></td>
                                 <td>
                                     <div class="question-preview" title="<?= htmlspecialchars($questao['enunciado']) ?>">
                                         <?= htmlspecialchars(substr($questao['enunciado'], 0, 80)) ?><?= strlen($questao['enunciado']) > 80 ? '...' : '' ?>
                                     </div>
                                 </td>
                                 <td>
                                     <?php 
                                     $dif = $questao['dificuldade'] ?? 'não definida';
                                     $style = '';
                                     switch (strtolower($dif)) {
                                         case 'facil':
                                             $style = 'background:#eafaea; color:#2e7d32;';
                                             break;
                                         case 'medio':
                                             $style = 'background:#fff3cd; color:#856404;';
                                             break;
                                         case 'dificil':
                                             $style = 'background:#f8d7da; color:#721c24;';
                                             break;
                                     }
                                     ?>
                                     <span class="badge" style="<?= $style ?>"><?= ucfirst($dif) ?></span>
                                 </td>
                                <td>
                                    <span class="badge"><?= $questao['total_alternativas'] ?> opções</span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($questao['created_at'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="../quiz_sem_login.php?questao=<?= $questao['id_questao'] ?>" 
                                           class="btn btn-primary" target="_blank" title="Visualizar">👁️</a>
                                        <a href="editar_questao.php?id=<?= $questao['id_questao'] ?>" 
                                           class="btn btn-warning" title="Editar">✏️</a>
                                        <a href="deletar_questao.php?id=<?= $questao['id_questao'] ?>" 
                                           class="btn btn-danger" title="Excluir"
                                           onclick="return confirm('Tem certeza que deseja excluir esta questão? Esta ação não pode ser desfeita!')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <script>
            document.getElementById('searchInput')?.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const table = document.getElementById('questionsTable');
                if (!table) return;
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const assunto = row.cells[1].textContent.toLowerCase();
                    const enunciado = row.cells[2].textContent.toLowerCase();
                    row.style.display = (assunto.includes(searchTerm) || enunciado.includes(searchTerm)) ? '' : 'none';
                }
            });
        </script>
    
    <?php include '../footer.php'; ?>
</body>
</html>
</html>