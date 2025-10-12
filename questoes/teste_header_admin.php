<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Header Admin - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
<?php
// Configuração do breadcrumb para teste
$breadcrumb_items = [
    ['icon' => '🏠', 'text' => 'Início', 'link' => 'index.php', 'current' => false],
    ['icon' => '👨‍💼', 'text' => 'Dashboard Admin', 'link' => 'admin/dashboard.php', 'current' => false],
    ['icon' => '💬', 'text' => 'Teste Header', 'link' => '', 'current' => true]
];

$page_title = 'Teste Header Admin';
$page_subtitle = 'Verificando se o header está funcionando corretamente';
include 'header.php';
?>

        <!-- Conteúdo de Teste -->
        <div class="card">
            <h2 class="card-title">🧪 Teste do Header</h2>
            <p>Esta é uma página de teste para verificar se o header está funcionando corretamente.</p>
            
            <div class="alert alert-info">
                <strong>Verificações:</strong>
                <ul>
                    <li>✅ Logo e branding</li>
                    <li>✅ Menu de navegação</li>
                    <li>✅ Perfil do usuário</li>
                    <li>✅ Breadcrumb</li>
                    <li>✅ Título da página</li>
                </ul>
            </div>
        </div>

<?php include 'footer.php'; ?>
</body>
</html>
