<?php
session_start();

// Verifica se o usuário é um administrador logado.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php'; // Caminho para o arquivo conexao.php

$mensagem_status = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_assunto = trim($_POST['nome_assunto']);

    if (empty($nome_assunto)) {
        $mensagem_status = 'error';
        $mensagem_texto = 'Por favor, digite o nome do novo assunto.';
    } else {
        try {
            // Insere o novo assunto na tabela 'assuntos'
            $stmt = $pdo->prepare("INSERT INTO assuntos (nome) VALUES (?)");
            $stmt->execute([$nome_assunto]);
            $mensagem_status = 'success';
            $mensagem_texto = 'Assunto "' . htmlspecialchars($nome_assunto) . '" adicionado com sucesso!';
        } catch (Exception $e) {
            $mensagem_status = 'error';
            $mensagem_texto = 'Erro ao adicionar o assunto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Assunto - Resumo Acadêmico</title>
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header {
            position: relative;
        }
        
        .header-nav {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 2;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <header class="header">
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
            <div class="logo">
                <img src="../../fotos/Logotipo_resumo_academico.png" alt="Resumo Acadêmico">
            </div>
            <div class="title-section">
                <h1>Adicionar Novo Assunto</h1>
                <p class="subtitle">Preencha os dados do novo assunto</p>
            </div>
        </header>

        <div class="user-info">
            <a href="dashboard.php" class="btn btn-outline">Voltar ao Dashboard</a>
        </div>

        <main class="content">
            <?php if (!empty($mensagem_status)): ?>
                <div class="alert alert-<?= $mensagem_status ?> fade-in">
                    <?= htmlspecialchars($mensagem_texto) ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form action="add_assunto.php" method="post" class="modern-form">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="nome_assunto">Nome do Assunto:</label>
                        <input type="text" id="nome_assunto" name="nome_assunto" required class="form-control" placeholder="Digite o nome do assunto...">
                    </div>
                    
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Assunto</button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="footer">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
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
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>