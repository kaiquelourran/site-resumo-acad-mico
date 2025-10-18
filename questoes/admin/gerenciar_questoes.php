<?php
session_start();

// Verifica se o usuário é um administrador logado usando as variáveis corretas.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php';

// Verificação de modo de manutenção não é necessária para admins


try {
    $stmt_questoes = $pdo->query("SELECT q.id_questao, q.enunciado, a.nome AS nome_assunto
                                 FROM questoes q
                                 JOIN assuntos a ON q.id_assunto = a.id_assunto
                                 ORDER BY q.id_questao DESC");
    $questoes = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="../modern-style.css">
    <style>
        .conteudo-principal {
            max-width: 900px;
            margin: 40px auto;
            background-color: #FFFFFF;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.432);
            border-radius: 10px;
        }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; min-width: 700px; }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .botoes-tabela a { padding: 6px 12px; text-decoration: none; color: white; border-radius: 999px; margin-right: 5px; display:inline-block; }
        .botoes-tabela .editar { background-image: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%); }
        .botoes-tabela .deletar { background-image: linear-gradient(135deg, #FF6B6B 0%, #FF3B3B 100%); }
        .actions-right { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    </style>
</head>
<body>
<?php
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => '../index.php', 'current' => false],
    ['icon' => '👨‍💼', 'text' => 'Admin', 'link' => 'dashboard.php', 'current' => false],
    ['icon' => '📝', 'text' => 'Gerenciar Questões', 'link' => 'gerenciar_questoes.php', 'current' => true]
];
$page_title = 'Gerenciar Questões';
$page_subtitle = 'Edite ou exclua as questões existentes.';
include '../header.php';
?>

    <main class="conteudo-principal">
        <?php if (isset($mensagem_status)): ?>
            <p><?= $mensagem_status ?></p>
        <?php endif; ?>
        
        <?php if (empty($questoes)): ?>
            <p>Nenhuma questão encontrada. <a href="add_questao.php">Adicionar uma nova questão.</a></p>
        <?php else: ?>
            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Assunto</th>
                        <th>Enunciado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questoes as $questao): ?>
                        <tr>
                            <td><?= htmlspecialchars($questao['id_questao']) ?></td>
                            <td><?= htmlspecialchars($questao['nome_assunto']) ?></td>
                            <td><?= htmlspecialchars(substr($questao['enunciado'], 0, 50)) . '...' ?></td>
                            <td class="botoes-tabela">
                                <a href="editar_questao.php?id=<?= htmlspecialchars($questao['id_questao']) ?>" class="editar">Editar</a>
                                <form action="deletar_questao.php" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta questão?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($questao['id_questao']) ?>">
                                    <button type="submit" class="deletar" style="border:none; background:transparent; padding:0 6px; color:#fff; cursor:pointer; border-radius:999px;">Deletar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
        <div class="actions-right"><a href="dashboard.php" class="btn btn-outline">Voltar</a></div>
    </main>

    <?php include '../footer.php'; ?>
</body>
</html>