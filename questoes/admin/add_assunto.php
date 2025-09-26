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
        $mensagem_status = '<p style="color:red;">Por favor, digite o nome do novo assunto.</p>';
    } else {
        try {
            // Insere o novo assunto na tabela 'assuntos'
            $stmt = $pdo->prepare("INSERT INTO assuntos (nome) VALUES (?)");
            $stmt->execute([$nome_assunto]);
            $mensagem_status = '<p style="color:green;">Assunto "' . htmlspecialchars($nome_assunto) . '" adicionado com sucesso!</p>';
        } catch (Exception $e) {
            $mensagem_status = '<p style="color:red;">Erro ao adicionar o assunto: ' . $e->getMessage() . '</p>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Assunto</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .conteudo-principal {
            max-width: 600px;
            margin: 173px auto 10px auto;
            background-color: #FFFFFF;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.432);
            border-radius: 10px;
            text-align: center;
        }
        form {
            text-align: left;
        }
        label, input[type="text"] {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
        .actions-right { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    </style>
</head>
<body>
    <header>
        <h1>Adicionar Novo Assunto</h1>
        <p>Preencha os dados do novo assunto.</p>
    </header>

    <main class="conteudo-principal">
        <?= $mensagem_status ?>
        <form action="add_assunto.php" method="post">
            <?= csrf_field() ?>
            <label for="nome_assunto">Nome do Assunto:</label>
            <input type="text" id="nome_assunto" name="nome_assunto" required>
            <div class="actions-right">
                <a href="dashboard.php" class="btn btn-outline">Voltar</a>
                <button type="submit" class="btn btn-primary">Salvar Assunto</button>
            </div>
        </form>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>