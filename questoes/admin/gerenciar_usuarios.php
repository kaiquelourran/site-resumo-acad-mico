<?php
session_start();

// Apenas admins
if (!isset($_SESSION['id_usuario']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php';

// Parâmetros de busca/paginação
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Detectar colunas reais da tabela `usuarios`
try {
    $cols = $pdo->query('DESCRIBE usuarios')->fetchAll(PDO::FETCH_COLUMN, 0);
} catch (Exception $e) { $cols = []; }
$has = function($c) use ($cols) { return in_array($c, $cols, true); };

$colId = $has('id_usuario') ? 'id_usuario' : ($has('id') ? 'id' : 'id_usuario');
$colNome = $has('nome') ? 'nome' : ($has('name') ? 'name' : 'nome');
$colEmail = $has('email') ? 'email' : 'email';
$colTipo = $has('tipo') ? 'tipo' : ($has('role') ? 'role' : 'tipo');
$colCreated = $has('created_at') ? 'created_at' : ($has('data_criacao') ? 'data_criacao' : 'created_at');
$colUltimo = $has('ultimo_login') ? 'ultimo_login' : null;

$where = 'WHERE 1=1';
$params = [];
if ($q !== '') {
    $where .= " AND ($colNome LIKE ? OR $colEmail LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

// Total
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM usuarios $where");
$stmtTotal->execute($params);
$total = (int)$stmtTotal->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Lista
$selectUltimo = $colUltimo ? ", $colUltimo AS ultimo_login" : ", NULL AS ultimo_login";
$sql = "SELECT $colId AS id_usuario, $colNome AS nome, $colEmail AS email, $colTipo AS tipo, $colCreated AS created_at $selectUltimo
        FROM usuarios
        $where
        ORDER BY $colCreated DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Resumo Acadêmico</title>
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../modern-style.css">
    <style>
        body { background-image: linear-gradient(to top, #00C6FF, #0072FF); margin:0; min-height:100vh; }
        .main-container { max-width:1100px; margin:40px auto; background:#fff; border-radius:16px; box-shadow:0 20px 40px rgba(0,0,0,.1); padding:24px; }
        .header-row { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:16px; }
        .search-box { display:flex; gap:8px; }
        .search-box input { padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; min-width:260px; }
        .btn { padding:10px 14px; border-radius:10px; border:1px solid #cfe2ff; background:linear-gradient(135deg,#0072FF,#00C6FF); color:#fff; text-decoration:none; font-weight:600; }
        .btn.secondary { background:#fff; color:#0f172a; border:1px solid #e5e7eb; }
        .table { width:100%; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; }
        .thead { display:grid; grid-template-columns:2fr 2fr 1fr 1fr 1.5fr; gap:12px; padding:14px 16px; background:linear-gradient(135deg,#0072FF,#00C6FF); color:#fff; font-weight:700; }
        .row { display:grid; grid-template-columns:2fr 2fr 1fr 1fr 1.5fr; gap:12px; padding:12px 16px; border-bottom:1px solid #e9ecef; align-items:center; }
        .row:last-child { border-bottom:none; }
        .name { display:flex; gap:10px; align-items:center; }
        .avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#0072FF,#00C6FF); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .pagination { margin-top:16px; display:flex; justify-content:center; gap:8px; }
        .page { padding:8px 12px; border-radius:8px; border:1px solid #e5e7eb; background:#fff; text-decoration:none; color:#0f172a; }
        .page.active { background:linear-gradient(135deg,#0072FF,#00C6FF); color:#fff; border-color:#cfe2ff; }
        @media (max-width: 768px) {
            .thead, .row { grid-template-columns: 1fr; }
            .thead { display:none; }
            .row { border:1px solid #e9ecef; border-radius:10px; margin-bottom:10px; }
        }
    </style>
    <script>
        function go(page){
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }
    </script>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
    <meta name="robots" content="noindex,nofollow" />
    <meta name="referrer" content="no-referrer" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="color-scheme" content="light" />
    <meta name="theme-color" content="#0072FF" />
</head>
<body>
    <div class="main-container">
        <div class="header-row">
            <h2 style="margin:0;">👥 Gerenciar Usuários</h2>
            <div class="search-box">
                <form method="get" action="" style="display:flex; gap:8px;">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nome ou email...">
                    <button class="btn" type="submit">Buscar</button>
                </form>
                <a href="dashboard.php" class="btn secondary">Voltar</a>
            </div>
        </div>

        <div class="table">
            <div class="thead">
                <div>Nome</div>
                <div>Email</div>
                <div>Tipo</div>
                <div>Cadastro</div>
                <div>Último Login</div>
            </div>
            <div class="tbody">
                <?php if (!empty($usuarios)): ?>
                    <?php foreach ($usuarios as $u): ?>
                        <div class="row">
                            <div class="name">
                                <div class="avatar"><?= strtoupper(substr($u['nome'] ?? '?', 0, 1)) ?></div>
                                <span><?= htmlspecialchars($u['nome'] ?? '—') ?></span>
                            </div>
                            <div><?= htmlspecialchars($u['email'] ?? '—') ?></div>
                            <div><span class="type-badge <?= ($u['tipo'] ?? 'usuario') === 'admin' ? 'admin' : 'user' ?>"><?= ($u['tipo'] ?? 'usuario') === 'admin' ? '👑 Admin' : '👤 Usuário' ?></span></div>
                            <div><?= !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—' ?></div>
                            <div><?= !empty($u['ultimo_login']) ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : 'N/A' ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row"><div style="grid-column: 1/-1; text-align:center; color:#666;">Nenhum usuário encontrado.</div></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="pagination">
            <?php for ($p=1; $p <= $totalPages; $p++): ?>
                <a class="page <?= $p === $page ? 'active' : '' ?>" href="?q=<?= urlencode($q) ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>


