<?php
session_start();
require_once 'conexao.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';

// Verificar se já está logado
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token de segurança inválido.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $user_type = $_POST['user_type'];
        
        if (empty($email) || empty($password) || empty($user_type)) {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            try {
                // Buscar usuário no banco de dados
                $stmt = $pdo->prepare("SELECT id_usuario, nome, email, senha, tipo FROM usuarios WHERE email = ? AND tipo = ?");
                $stmt->execute([$email, $user_type]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario && password_verify($password, $usuario['senha'])) {
                    // Login bem-sucedido
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = true;
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['user_name'] = $usuario['nome'];
                    $_SESSION['user_type'] = $usuario['tipo'];
                    $_SESSION['tipo_usuario'] = $usuario['tipo']; // Para compatibilidade com admin
                    
                    // Atualizar último login
                    $stmt_update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id_usuario = ?");
                    $stmt_update->execute([$usuario['id_usuario']]);
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Email, senha ou tipo de usuário incorretos.';
                }
            } catch (PDOException $e) {
                $error = 'Erro no sistema. Tente novamente.';
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
    <title>Login - Resumo Acadêmico</title>
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
        
        .login-container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .logo {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .title {
            color: #333;
            font-size: 1.8em;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #666;
            font-size: 1em;
            margin-bottom: 30px;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cec;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
            font-size: 0.9em;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .help-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
        }
        
        .help-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 0.95em;
        }
        
        .help-item {
            margin-bottom: 8px;
            font-size: 0.85em;
            color: #555;
        }
        
        .help-item strong {
             color: #667eea;
         }
         
         .user-type-buttons {
             display: flex;
             gap: 10px;
             margin-bottom: 25px;
         }
         
         .type-btn {
             flex: 1;
             padding: 16px;
             border: 2px solid #e1e5e9;
             border-radius: 8px;
             background: white;
             color: #333;
             font-size: 1em;
             font-weight: 500;
             cursor: pointer;
             transition: all 0.3s ease;
         }
         
         .type-btn:hover {
             border-color: #667eea;
             background: #f8f9ff;
         }
         
         .type-btn.active {
             background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
             color: white;
             border-color: #667eea;
             box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
         }
         
         @media (max-width: 480px) {
             .login-container {
                 padding: 30px 20px;
             }
             
             .logo {
                 font-size: 2.5em;
             }
             
             .title {
                 font-size: 1.5em;
             }
             
             .user-type-buttons {
                 flex-direction: column;
                 gap: 8px;
             }
         }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">🎓</div>
        <h1 class="title">Resumo Acadêmico</h1>
        <p class="subtitle">Sistema de Questões</p>
        
        <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
            <div class="alert alert-success">
                ✅ Logout realizado com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="user-type-buttons">
             <button type="button" class="type-btn" data-type="usuario" onclick="selectUserType('usuario')">
                 👤 Usuário Normal
             </button>
             <button type="button" class="type-btn" data-type="admin" onclick="selectUserType('admin')">
                 👨‍💼 Administrador
             </button>
         </div>

         <form method="POST" id="loginForm">
             <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
             <input type="hidden" name="user_type" id="user_type" value="" required>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required placeholder="seu@email.com">
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="password" id="password" required placeholder="Digite sua senha">
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>
        
        <div class="help-section">
            <div class="help-title">💡 Como fazer login:</div>
            <div class="help-item">
                <strong>Usuário Normal:</strong> Selecione "Usuário Normal" e use suas credenciais
            </div>
            <div class="help-item">
                <strong>Administrador:</strong> Selecione "Administrador" e use suas credenciais de admin
            </div>
            <div class="help-item" style="margin-top: 12px; color: #666;">
                <strong>Não tem conta?</strong> <a href="cadastro.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Cadastre-se aqui</a>
            </div>
        </div>
     </div>
     
     <script>
         function selectUserType(type) {
             // Remove active class from all buttons
             document.querySelectorAll('.type-btn').forEach(btn => {
                 btn.classList.remove('active');
             });
             
             // Add active class to selected button
             document.querySelector(`[data-type="${type}"]`).classList.add('active');
             
             // Set hidden input value
             document.getElementById('user_type').value = type;
         }
         
         // Prevent form submission if no user type is selected
         document.getElementById('loginForm').addEventListener('submit', function(e) {
             if (!document.getElementById('user_type').value) {
                 e.preventDefault();
                 alert('Por favor, selecione o tipo de usuário.');
             }
         });
     </script>
 </body>
 </html>