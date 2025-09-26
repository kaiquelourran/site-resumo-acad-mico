<?php
session_start();
require_once __DIR__ . '/conexao.php';

$mensagem = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!validate_csrf()) {
        $mensagem = "Sessão expirada ou requisição inválida. Atualize a página e tente novamente.";
    } else if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } else {
        // Verifica se o e-mail já existe
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetchColumn() > 0) {
            $mensagem = "Este e-mail já está cadastrado. Tente fazer login.";
        } else {
            // Criptografa a senha antes de salvar no banco
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // CORREÇÃO: Insere o novo usuário no banco de dados, incluindo o tipo de conta
            // O valor 'usuario' é adicionado automaticamente
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)");
            
            if ($stmt_insert->execute([$nome, $email, $senha_hash, 'usuario'])) {
                $sucesso = true;
                $mensagem = "Cadastro realizado com sucesso! Você já pode fazer login.";
            } else {
                $mensagem = "Ocorreu um erro ao tentar cadastrar. Por favor, tente novamente.";
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
    <title>Cadastro</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .formulario-card {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .formulario-card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .formulario-card .campo-grupo {
            margin-bottom: 15px;
            text-align: left;
        }
        .formulario-card label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .formulario-card input[type="text"],
        .formulario-card input[type="email"],
        .formulario-card input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .formulario-card button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .formulario-card button:hover {
            background-color: #0056b3;
        }
        .formulario-card .link-login {
            display: block;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }
        .formulario-card .link-login:hover {
            text-decoration: underline;
        }
        .mensagem {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
        }
        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <header>
        <h1>Questões Complementares</h1>
        <div class="login-area">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="perfil_usuario.php" class="btn btn-outline btn-sm" aria-label="Ver meu desempenho">Meu Desempenho</a>
                <a href="logout.php" class="btn btn-danger btn-sm" aria-label="Sair da conta">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="cadastro.php" class="btn btn-primary btn-sm">Cadastro</a>
            <?php endif; ?>
        </div>
    </header>
    
    <main class="conteudo-principal">
        <div class="formulario-card">
            <h2>Criar uma Conta</h2>
            
            <?php if (!empty($mensagem)): ?>
                <div class="mensagem <?= $sucesso ? 'sucesso' : 'erro' ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <form action="cadastro.php" method="POST">
                <?= csrf_field() ?>
                <div class="campo-grupo">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="campo-grupo">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="campo-grupo">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn btn-success btn-lg">Cadastrar</button>
            </form>

            <div class="actions-right">
                <a href="index.php" class="btn btn-outline">Voltar ao menu</a>
                <a href="login.php" class="btn btn-primary">Fazer login</a>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>