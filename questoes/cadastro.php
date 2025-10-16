<?php
session_start();
header('Cross-Origin-Opener-Policy: unsafe-none');
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
                error_log('PDO Error: ' . $e->getMessage());
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
    <link rel="icon" href="../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../fotos/minha-logo-apple.png">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-container {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid transparent;
            background-image: linear-gradient(#FFFFFF, #FFFFFF), linear-gradient(to top, #00C6FF, #0072FF);
            background-origin: border-box;
            background-clip: padding-box, border-box;
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
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }
        
        .form-group {
            margin-bottom: 18px;
            text-align: left;
        }
        
        .input-with-action {
            position: relative;
        }
        
        .input-with-action input {
            padding-right: 44px;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            color: #333;
            padding: 6px;
            border-radius: 6px;
            transition: background 0.2s ease, color 0.2s ease;
        }
        
        .toggle-password:hover {
            background: #F0FBFF;
            color: #0072FF;
        }
        
        /* UX melhorias */
        .btn-register[disabled] {
            opacity: 0.75;
            cursor: not-allowed;
        }
        .input-error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15) !important;
        }
        .caps-lock-indicator {
            display: none;
            margin-top: 6px;
            color: #dc3545;
            font-size: 0.85em;
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
            border-color: #00C6FF;
            box-shadow: 0 0 0 3px rgba(0, 198, 255, 0.15);
        }
        
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to top, #00C6FF, #0072FF);
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
            box-shadow: 0 8px 25px rgba(0, 114, 255, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .login-link {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            color: #333;
            font-size: 0.95em;
        }
        
        .login-link a {
            color: #0072FF;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #FFFFFF;
            border: 1px solid #e1e5e9;
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
        <p class="subtitle" style="color:#333333">Cadastre-se para acessar o sistema</p>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="nome">nome do usuario</label>
                <input type="text" name="nome" id="nome" required placeholder="Digite o nome do usuario" value="<?= htmlspecialchars($nome ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required placeholder="seu@email.com" value="<?= htmlspecialchars($email ?? '') ?>" autocomplete="email" inputmode="email">
                <small style="display:block;margin-top:6px;color:#666">Use o e-mail cadastrado no sistema</small>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-with-action">
                    <input type="password" name="password" id="password" required placeholder="Digite sua senha" minlength="6" autocomplete="new-password">
                    <button type="button" class="toggle-password" aria-label="Mostrar/ocultar senha" title="Mostrar/ocultar senha">👁️</button>
                </div>
                <div id="caps-indicator" class="caps-lock-indicator" aria-live="polite">Caps Lock está ativado</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Senha</label>
                <input type="password" name="confirm_password" id="confirm_password" required placeholder="Digite sua senha novamente" minlength="6">
            </div>
            
            <button type="submit" class="btn-register">Criar Conta</button>
        </form>

        <div class="separator"><span>OU</span></div>

        <div id="g_id_onload"
             data-client_id="483177848191-i85ijikssoaftcnam1kjinhkdvi7lf69.apps.googleusercontent.com"
             data-context="signup"
             data-ux_mode="popup"
             data-callback="handleGoogleSignIn"
             data-auto_prompt="false">
        </div>

        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signin_with"
             data-size="large"
             data-logo_alignment="left">
        </div>
        
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
        
        // UX: Toggle de senha
        (function(){
            const toggleBtn = document.querySelector('.toggle-password');
            const pwdInput = document.getElementById('password');
            if (toggleBtn && pwdInput) {
                toggleBtn.addEventListener('click', function(){
                    const isPwd = pwdInput.getAttribute('type') === 'password';
                    pwdInput.setAttribute('type', isPwd ? 'text' : 'password');
                    this.textContent = isPwd ? '🙈' : '👁️';
                });
            }
            // Caps Lock indicador
            const capsIndicator = document.getElementById('caps-indicator');
            if (pwdInput && capsIndicator) {
                pwdInput.addEventListener('keydown', function(e){
                    const capsOn = e.getModifierState && e.getModifierState('CapsLock');
                    capsIndicator.style.display = capsOn ? 'block' : 'none';
                });
                pwdInput.addEventListener('keyup', function(e){
                    const capsOn = e.getModifierState && e.getModifierState('CapsLock');
                    capsIndicator.style.display = capsOn ? 'block' : 'none';
                });
                pwdInput.addEventListener('blur', function(){
                    capsIndicator.style.display = 'none';
                });
            }
        })();
        
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
<script>
    // Utilitário para decodificar o JWT do Google e obter "claims" (como e-mail)
    if (typeof window.parseJwt !== 'function') {
        window.parseJwt = function(token) {
            try {
                const base64Url = token.split('.')[1];
                const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
                return JSON.parse(jsonPayload);
            } catch (e) {
                return null;
            }
        }
    }

    // Callback do Google Identity para cadastro/login com Google
    if (typeof window.handleGoogleSignIn !== 'function') {
        window.handleGoogleSignIn = function(response) {
            try {
                const id_token = response && response.credential ? response.credential : null;
                if (!id_token) {
                    console.error('ID token ausente no callback do Google.');
                    alert('Não foi possível obter o token do Google. Tente novamente.');
                    return;
                }
                // Decodificar claims opcionalmente
                try {
                    const claims = (typeof parseJwt === 'function') ? parseJwt(id_token) : null;
                    if (claims && claims.email) {
                        try { localStorage.setItem('google_email', claims.email); } catch(e) {}
                    }
                } catch (e) {}

                // Envia o token ao servidor para criar/atualizar usuário e abrir sessão
                fetch('processar_google_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ id_token })
                })
                .then(async (resp) => {
                    let data = {};
                    try { data = await resp.json(); } catch(e) {}
                    if (resp.ok && data && data.success) {
                        window.location.href = 'index.php';
                        return;
                    }
                    const statusInfo = `HTTP ${resp.status}`;
                    const msg = (data && data.message) ? data.message : 'Falha no cadastro/login com Google.';
                    alert(`Erro ao concluir com o Google (${statusInfo}): ${msg}`);
                })
                .catch(() => alert('Ocorreu um erro inesperado ao tentar concluir com o Google.'));
            } catch (e) {
                console.error('Erro em handleGoogleSignIn (cadastro):', e);
            }
        }
    }
</script>
</body>
</html>