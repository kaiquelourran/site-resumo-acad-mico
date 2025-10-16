<?php
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
require_once __DIR__ . '/../conexao.php';

// Verificar se é admin
if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validate_csrf()) {
        $mensagem_erro = 'Token de segurança inválido. Atualize a página e tente novamente.';
    } else {
        if (isset($_POST['action'])) {
            $id_relatorio = (int)$_POST['id_relatorio'];
            
            switch ($_POST['action']) {
                case 'atualizar_status':
                    $novo_status = $_POST['novo_status'];
                    $stmt = $pdo->prepare("UPDATE relatorios_bugs SET status = ? WHERE id_relatorio = ?");
                    $stmt->execute([$novo_status, $id_relatorio]);
                    break;
                    
                case 'responder':
                    $resposta = trim($_POST['resposta']);
                    if (!empty($resposta)) {
                        $stmt = $pdo->prepare("UPDATE relatorios_bugs SET resposta_admin = ?, status = 'resolvido', usuario_viu_resposta = FALSE WHERE id_relatorio = ?");
                        $stmt->execute([htmlspecialchars($resposta, ENT_QUOTES, 'UTF-8'), $id_relatorio]);
                    }
                    break;
            }
        }
    }
}

// Buscar relatórios
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_prioridade = $_GET['prioridade'] ?? 'todos';

$where_conditions = [];
$params = [];

if ($filtro_status !== 'todos') {
    $where_conditions[] = "status = ?";
    $params[] = $filtro_status;
}

if ($filtro_prioridade !== 'todos') {
    $where_conditions[] = "prioridade = ?";
    $params[] = $filtro_prioridade;
}

$where_sql = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT * FROM relatorios_bugs $where_sql ORDER BY data_relatorio DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$relatorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estatísticas
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM relatorios_bugs")->fetchColumn(),
    'abertos' => $pdo->query("SELECT COUNT(*) FROM relatorios_bugs WHERE status = 'aberto'")->fetchColumn(),
    'em_andamento' => $pdo->query("SELECT COUNT(*) FROM relatorios_bugs WHERE status = 'em_andamento'")->fetchColumn(),
    'resolvidos' => $pdo->query("SELECT COUNT(*) FROM relatorios_bugs WHERE status = 'resolvido'")->fetchColumn(),
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Relatórios - Admin</title>
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../modern-style.css">
    <style>
        /* Design padrão do sistema */
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
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .logo {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .header .title {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .user-info a,
        .user-link {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%) !important;
            color: white !important;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 0 10px;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(0,114,255,0.3);
        }
        
        .user-info a:hover,
        .user-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,114,255,0.4);
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,114,255,0.3);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
        }
        
        .relatorio-item {
            border: 1px solid #e1e5e9;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .relatorio-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .relatorio-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .relatorio-titulo {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .relatorio-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .badge-status-aberto { background: #fff3cd; color: #856404; }
        .badge-status-em_andamento { background: #d1ecf1; color: #0c5460; }
        .badge-status-resolvido { background: #d4edda; color: #155724; }
        .badge-status-fechado { background: #f8d7da; color: #721c24; }
        
        .badge-tipo-bug { background: #f8d7da; color: #721c24; }
        .badge-tipo-melhoria { background: #d1ecf1; color: #0c5460; }
        .badge-tipo-duvida { background: #fff3cd; color: #856404; }
        .badge-tipo-outro { background: #e2e3e5; color: #383d41; }
        
        .badge-prioridade-baixa { background: #d4edda; color: #155724; }
        .badge-prioridade-media { background: #fff3cd; color: #856404; }
        .badge-prioridade-alta { background: #f8d7da; color: #721c24; }
        .badge-prioridade-critica { background: #f5c6cb; color: #721c24; }
        
        .relatorio-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .relatorio-descricao {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            white-space: pre-wrap;
        }
        
        .relatorio-acoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9em;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #00C6FF, #0072FF); 
            color: white; 
            box-shadow: 0 4px 15px rgba(0,114,255,0.3);
        }
        .btn-success { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white; 
            box-shadow: 0 4px 15px rgba(40,167,69,0.3);
        }
        .btn-warning { 
            background: linear-gradient(135deg, #ffc107, #fd7e14); 
            color: #212529; 
            box-shadow: 0 4px 15px rgba(255,193,7,0.3);
        }
        .btn-danger { 
            background: linear-gradient(135deg, #dc3545, #e83e8c); 
            color: white; 
            box-shadow: 0 4px 15px rgba(220,53,69,0.3);
        }
        
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .close:hover { color: #000; }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            transition: border-color 0.3s;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #00C6FF;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .main-container {
                margin: 20px;
                padding: 20px;
            }
            
            .header .title {
                font-size: 2rem;
            }
            
            .admin-container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .relatorio-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .relatorio-meta {
                margin-top: 10px;
            }
            
            .relatorio-acoes {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                margin: 5px 0;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group select {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">🐛</div>
            <h1 class="title">Gerenciar Relatórios</h1>
            <p class="subtitle">Painel administrativo para gerenciar relatórios de bugs</p>
        </div>
        
        <div class="user-info">
            <a href="dashboard.php" class="user-link">📊 Dashboard</a>
            <a href="../index.php" class="user-link">🏠 Sistema</a>
            <a href="../logout.php" class="user-link">🚪 Sair</a>
        </div>

        <div class="admin-container">
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div>Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['abertos']; ?></div>
                    <div>Abertos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['em_andamento']; ?></div>
                    <div>Em Andamento</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['resolvidos']; ?></div>
                    <div>Resolvidos</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label>Status:</label>
                        <select name="status">
                            <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                            <option value="aberto" <?php echo $filtro_status === 'aberto' ? 'selected' : ''; ?>>Abertos</option>
                            <option value="em_andamento" <?php echo $filtro_status === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="resolvido" <?php echo $filtro_status === 'resolvido' ? 'selected' : ''; ?>>Resolvidos</option>
                            <option value="fechado" <?php echo $filtro_status === 'fechado' ? 'selected' : ''; ?>>Fechados</option>
                        </select>
                        
                        <label>Prioridade:</label>
                        <select name="prioridade">
                            <option value="todos" <?php echo $filtro_prioridade === 'todos' ? 'selected' : ''; ?>>Todas</option>
                            <option value="baixa" <?php echo $filtro_prioridade === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                            <option value="media" <?php echo $filtro_prioridade === 'media' ? 'selected' : ''; ?>>Média</option>
                            <option value="alta" <?php echo $filtro_prioridade === 'alta' ? 'selected' : ''; ?>>Alta</option>
                            <option value="critica" <?php echo $filtro_prioridade === 'critica' ? 'selected' : ''; ?>>Crítica</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </form>
            </div>

            <!-- Lista de Relatórios -->
            <?php if (empty($relatorios)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <h3>Nenhum relatório encontrado</h3>
                    <p>Não há relatórios que correspondam aos filtros selecionados.</p>
                </div>
            <?php else: ?>
                <?php foreach ($relatorios as $relatorio): ?>
                    <div class="relatorio-item">
                        <div class="relatorio-header">
                            <h3 class="relatorio-titulo"><?php echo htmlspecialchars($relatorio['titulo']); ?></h3>
                            <div class="relatorio-meta">
                                <span class="badge badge-status-<?php echo $relatorio['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $relatorio['status'])); ?>
                                </span>
                                <span class="badge badge-tipo-<?php echo $relatorio['tipo_problema']; ?>">
                                    <?php echo ucfirst($relatorio['tipo_problema']); ?>
                                </span>
                                <span class="badge badge-prioridade-<?php echo $relatorio['prioridade']; ?>">
                                    <?php echo ucfirst($relatorio['prioridade']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="relatorio-info">
                            <strong>Por:</strong> <?php echo htmlspecialchars($relatorio['nome_usuario']); ?> 
                            (<?php echo htmlspecialchars($relatorio['email_usuario']); ?>)<br>
                            <strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($relatorio['data_relatorio'])); ?>
                            <?php if ($relatorio['pagina_erro']): ?>
                                <br><strong>Página:</strong> <?php echo htmlspecialchars($relatorio['pagina_erro']); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="relatorio-descricao">
                            <?php echo htmlspecialchars($relatorio['descricao']); ?>
                        </div>
                        
                        <?php if ($relatorio['resposta_admin']): ?>
                            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                <strong>Resposta do Admin:</strong><br>
                                <?php echo nl2br(htmlspecialchars($relatorio['resposta_admin'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="relatorio-acoes">
                            <button onclick="abrirModalStatus(<?php echo $relatorio['id_relatorio']; ?>, '<?php echo $relatorio['status']; ?>')" 
                                    class="btn btn-warning">Alterar Status</button>
                            
                            <button onclick="abrirModalResposta(<?php echo $relatorio['id_relatorio']; ?>)" 
                                    class="btn btn-success">Responder</button>
                            
                            <a href="mailto:<?php echo htmlspecialchars($relatorio['email_usuario']); ?>?subject=Re: <?php echo urlencode($relatorio['titulo']); ?>" 
                               class="btn btn-primary">Enviar E-mail</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para alterar status -->
    <div id="modalStatus" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Alterar Status</h3>
                <span class="close" onclick="fecharModal('modalStatus')">&times;</span>
            </div>
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="atualizar_status">
                <input type="hidden" name="id_relatorio" id="status_id_relatorio">
                
                <div class="form-group">
                    <label>Novo Status:</label>
                    <select name="novo_status" id="novo_status">
                        <option value="aberto">Aberto</option>
                        <option value="em_andamento">Em Andamento</option>
                        <option value="resolvido">Resolvido</option>
                        <option value="fechado">Fechado</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </form>
        </div>
    </div>

    <!-- Modal para responder -->
    <div id="modalResposta" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Responder Relatório</h3>
                <span class="close" onclick="fecharModal('modalResposta')">&times;</span>
            </div>
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="responder">
                <input type="hidden" name="id_relatorio" id="resposta_id_relatorio">
                
                <div class="form-group">
                    <label>Resposta:</label>
                    <textarea name="resposta" id="resposta_texto" 
                              placeholder="Digite sua resposta para o usuário..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-success">Enviar Resposta</button>
            </form>
        </div>
    </div>

    <script>
        function abrirModalStatus(id, statusAtual) {
            document.getElementById('status_id_relatorio').value = id;
            document.getElementById('novo_status').value = statusAtual;
            document.getElementById('modalStatus').style.display = 'block';
        }
        
        function abrirModalResposta(id) {
            document.getElementById('resposta_id_relatorio').value = id;
            document.getElementById('modalResposta').style.display = 'block';
        }
        
        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fechar modal clicando fora dele
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
