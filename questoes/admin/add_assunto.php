<?php
session_start();

// Verifica se o usuário é um administrador logado.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
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
    <link rel="stylesheet" href="../modern-style.css">
</head>
<body>
    <div class="main-container fade-in">
        <header class="header">
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
</body>
</html>