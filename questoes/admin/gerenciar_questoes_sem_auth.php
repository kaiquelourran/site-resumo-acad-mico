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
    <link rel="stylesheet" href="../style.css">
    <style>
        .container {
            max-width: 1400px;
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
            font-size: 14px;
        }
        
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { background: #5a6268; }
        
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        
        .questions-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .questions-table th,
        .questions-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .questions-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #495057;
            position: sticky;
            top: 0;
        }
        
        .questions-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
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
        
        .difficulty-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.875em;
            font-weight: 500;
        }
        
        .difficulty-facil { background: #d4edda; color: #155724; }
        .difficulty-medio { background: #fff3cd; color: #856404; }
        .difficulty-dificil { background: #f8d7da; color: #721c24; }
        
        .question-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Gerenciar Questões</h1>
            <p>Administre todas as questões do sistema</p>
            <div style="margin-top: 15px;">
                <a href="dashboard.php" class="btn btn-secondary">← Voltar ao Dashboard</a>
                <a href="add_questao.php" class="btn btn-success">+ Adicionar Questão</a>
                <a href="gerenciar_assuntos.php" class="btn btn-primary">Gerenciar Assuntos</a>
            </div>
        </div>

        <?php if (!empty($mensagem_status)): ?>
            <?= $mensagem_status ?>
        <?php endif; ?>

        <div class="stats">
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
            <div class="table-responsive">
                <table class="questions-table" id="questionsTable">
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
                                    $class = 'difficulty-' . strtolower($dif);
                                    ?>
                                    <span class="difficulty-badge <?= $class ?>"><?= ucfirst($dif) ?></span>
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
    </div>

    <script>
        // Funcionalidade de busca
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('questionsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const assunto = row.cells[1].textContent.toLowerCase();
                const enunciado = row.cells[2].textContent.toLowerCase();
                
                if (assunto.includes(searchTerm) || enunciado.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    </script>

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