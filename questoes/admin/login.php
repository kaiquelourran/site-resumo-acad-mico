<?php
session_start();
require_once __DIR__ . '/../conexao.php'; // Caminho para o arquivo conexao.php

$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validate_csrf()) {
        $mensagem_erro = "Sessão expirada ou requisição inválida. Atualize a página e tente novamente.";
    } else {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $mensagem_erro = "Por favor, preencha todos os campos.";
    } else {
        // CORREÇÃO 1: A consulta SQL agora busca a senha e o tipo de usuário
        $stmt = $pdo->prepare("SELECT id_usuario, nome, senha, tipo FROM usuarios WHERE email = ? AND tipo = 'admin'");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // CORREÇÃO 2: Verifica a senha usando a coluna 'senha' (e não 'senha_hash')
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            // CORREÇÃO 3: As variáveis de sessão agora correspondem às usadas no dashboard
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nome_usuario'] = $usuario['nome'];
            $_SESSION['tipo_usuario'] = $usuario['tipo'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $mensagem_erro = "Email ou senha incorretos, ou você não tem permissão de administrador.";
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Admin</title>
    <link rel="stylesheet" href="../../style.css"> <style>
        /* Estilos adicionais para o formulário de login */
        main {
            text-align: center;
        }
        form {
            display: inline-block;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            box-sizing: border-box;
        }
        button {
            background-color: #0073ff94;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        button:hover {
            background-color: #0056b3;
        }
        .erro {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <h1>Painel de Administração</h1>
        <p>Acesso Restrito</p>
    </header>

    <main>
        <h2>Login</h2>
        <?php if ($mensagem_erro): ?>
            <p class="erro"><?= $mensagem_erro ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <?= csrf_field() ?>
            <label for="email">E-mail:</label><br>
            <input type="email" id="email" name="email" required><br><br>
            <label for="senha">Senha:</label><br>
            <input type="password" id="senha" name="senha" required><br><br>
            <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
        </form>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>