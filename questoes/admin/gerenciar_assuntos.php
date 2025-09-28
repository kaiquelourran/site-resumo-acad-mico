<?php
session_start();

// Verifica se o usuário está logado E se ele tem o tipo 'admin'
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Incluir o arquivo de conexão
require_once __DIR__ . '/../conexao.php';

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Buscar todos os assuntos
$stmt_assuntos = $pdo->query("SELECT a.id_assunto, a.nome, a.descricao, a.created_at, COUNT(q.id_questao) as total_questoes 
                              FROM assuntos a 
                              LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                              GROUP BY a.id_assunto 
                              ORDER BY a.nome");
$assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Assuntos - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .subjects-table th,
        .subjects-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .subjects-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
        }
        
        .subjects-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .delete-form {
            display: inline;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Gerenciar Assuntos</h1>
            <p>Administre os assuntos do sistema</p>
            <div style="margin-top: 15px;">
                <a href="dashboard.php" class="btn btn-secondary">← Voltar ao Dashboard</a>
                <a href="add_assunto.php" class="btn btn-success">+ Adicionar Assunto</a>
            </div>
        </div>

        <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'excluido'): ?>
            <div class="alert alert-success">
                <strong>✅ Sucesso!</strong> Assunto excluído com sucesso.
            </div>
        <?php endif; ?>

        <?php
        $total_assuntos = count($assuntos);
        $total_questoes = array_sum(array_column($assuntos, 'total_questoes'));
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total de Assuntos</h3>
                <div class="stat-number"><?= $total_assuntos ?></div>
            </div>
            <div class="stat-card">
                <h3>Total de Questões</h3>
                <div class="stat-number"><?= $total_questoes ?></div>
            </div>
        </div>

        <?php if (empty($assuntos)): ?>
            <div class="alert alert-warning">
                <strong>Nenhum assunto encontrado!</strong> 
                <a href="add_assunto.php">Clique aqui para adicionar o primeiro assunto</a>.
            </div>
        <?php else: ?>
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Questões</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assuntos as $assunto): ?>
                        <tr>
                            <td><?= htmlspecialchars($assunto['id_assunto']) ?></td>
                            <td><strong><?= htmlspecialchars($assunto['nome']) ?></strong></td>
                            <td><?= htmlspecialchars($assunto['descricao'] ?: 'Sem descrição') ?></td>
                            <td>
                                <span class="badge"><?= $assunto['total_questoes'] ?> questões</span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($assunto['created_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="../listar_questoes.php?id=<?= $assunto['id_assunto'] ?>" 
                                       class="btn btn-primary" target="_blank">Ver Questões</a>
                                    
                                    <?php if ($assunto['total_questoes'] > 0): ?>
                                        <span class="btn btn-secondary" style="opacity: 0.6; cursor: not-allowed;" 
                                              title="Não é possível excluir assunto com questões">
                                            🔒 Excluir
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="excluir_assunto.php" class="delete-form"
                                              onsubmit="return confirm('Tem certeza que deseja excluir o assunto \'<?= htmlspecialchars($assunto['nome']) ?>\'? Esta ação não pode ser desfeita!')">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id" value="<?= $assunto['id_assunto'] ?>">
                                            <button type="submit" class="btn btn-danger">🗑️ Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <style>
        .badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.875em;
            font-weight: 500;
        }
    </style>
</body>
</html>