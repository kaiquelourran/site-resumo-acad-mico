<?php
session_start();
require_once 'conexao.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Token de segurança inválido.';
    } else {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($nome) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = 'Por favor, preencha todos os campos.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'As senhas não coincidem.';
        } elseif (strlen($password) < 6) {
            $error_message = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                // Verificar se o email já existe
                $stmt_check = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
                $stmt_check->execute([$email]);
                
                if ($stmt_check->fetch()) {
                    $error_message = 'Este e-mail já está cadastrado.';
                } else {
                    // Inserir novo usuário
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo, created_at) VALUES (?, ?, ?, 'usuario', NOW())");
                    
                    if ($stmt_insert->execute([$nome, $email, $hashed_password])) {
                        $success_message = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                        // Limpar campos após sucesso
                        $nome = $email = '';
                    } else {
                        $error_message = 'Erro ao criar conta. Tente novamente.';
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Erro no banco de dados. Tente novamente.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Resumo Acadêmico</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }
        
        .logo {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .title {
            color: #333;
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            font-size: 1em;
            margin-bottom: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.95em;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .login-link {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.95em;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
            font-size: 0.85em;
        }
        
        .password-requirements h4 {
            color: #333;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .password-requirements ul {
            margin-left: 15px;
            color: #666;
        }
        
        .password-requirements li {
            margin-bottom: 4px;
        }
        
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }
            
            .logo {
                font-size: 2.5em;
            }
            
            .title {
                font-size: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">📚</div>
        <h1 class="title">Criar Conta</h1>
        <p class="subtitle">Cadastre-se para acessar o sistema</p>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" name="nome" id="nome" required placeholder="Digite seu nome completo" value="<?= htmlspecialchars($nome ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required placeholder="seu@email.com" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="password" id="password" required placeholder="Digite sua senha" minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Senha</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Digite sua senha novamente" minlength="6">
            </div>
            
            <button type="submit" class="btn-register">Criar Conta</button>
        </form>
        
        <div class="password-requirements">
            <h4>📋 Requisitos da senha:</h4>
            <ul>
                <li>Mínimo de 6 caracteres</li>
                <li>Use uma senha segura e única</li>
                <li>Não compartilhe sua senha</li>
            </ul>
        </div>
        
        <div class="login-link">
            Já tem uma conta? <a href="login.php">Fazer login</a>
        </div>
    </div>
    
    <script>
        // Validação de confirmação de senha em tempo real
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Validação do formulário
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('As senhas não coincidem.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres.');
                return false;
            }
        });
    </script>
</body>
</html>