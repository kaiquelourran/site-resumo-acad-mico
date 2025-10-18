<?php
session_start();
// Permitir comunicação via postMessage com o Google Identity Services (evita bloqueio por COOP)
header('Cross-Origin-Opener-Policy: unsafe-none');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
require_once 'conexao.php';

// Verificação de modo de manutenção


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
                // Determinar a coluna ID correta baseada no ambiente
                $id_column = get_id_column($pdo);
                $stmt = $pdo->prepare("SELECT $id_column as id_usuario, nome, email, senha, tipo FROM usuarios WHERE email = ? AND tipo = ?");
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
                    
                    // NOVO: garantir que nome e email estejam na sessão para o sistema de comentários
                    $_SESSION['user_email'] = $usuario['email'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['nome_usuario'] = $usuario['nome'];
                    
                    // NOVO: tentar carregar avatar_url do usuário (se a coluna existir)
                    try {
                        $stmtAvatar = $pdo->prepare("SELECT avatar_url FROM usuarios WHERE $id_column = ?");
                        $stmtAvatar->execute([$usuario['id_usuario']]);
                        $avatarRow = $stmtAvatar->fetch(PDO::FETCH_ASSOC);
                        $avatarUrl = $avatarRow['avatar_url'] ?? null;
                        if (!empty($avatarUrl)) {
                            $_SESSION['user_avatar'] = $avatarUrl;
                            $_SESSION['user_picture'] = $avatarUrl;
                            $_SESSION['foto_usuario'] = $avatarUrl;
                        }
                    } catch (PDOException $e) {
                        // Silenciosamente ignorar casa coluna não exista em alguns ambientes
                    }
                    
                    // Atualizar último login
                    $stmt_update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE $id_column = ?");
                    $stmt_update->execute([$usuario['id_usuario']]);
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Email, senha ou tipo de usuário incorretos.';
                }
            } catch (PDOException $e) {
                if ($is_local && isset($_GET['debug']) && $_GET['debug'] == '1') {
                    $error = 'Erro no sistema: ' . $e->getMessage();
                } else {
                    $error = 'Erro no sistema. Tente novamente.';
                }
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
        
        .login-container {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
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
            border-color: #00C6FF;
            box-shadow: 0 0 0 3px rgba(0, 198, 255, 0.15);
        }
        
        .btn-login {
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
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 114, 255, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .help-section {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
            border: 1px solid #e1e5e9;
        }
        
        /* UX melhorias */
        .btn-login[disabled] {
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
             color: #0072FF;
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
             border-color: #00C6FF;
             background: #F0FBFF;
         }
         
         .type-btn.active {
             background: linear-gradient(to top, #00C6FF, #0072FF);
             color: white;
             border-color: #0072FF;
             box-shadow: 0 4px 15px rgba(0, 114, 255, 0.25);
         }

         .separator {
             display: flex;
             align-items: center;
             text-align: center;
             margin: 25px 0;
             color: #888;
         }

         .separator::before, .separator::after {
             content: '';
             flex: 1;
             border-bottom: 1px solid #e1e5e9;
         }

         .separator:not(:empty)::before {
             margin-right: .25em;
         }

         .separator:not(:empty)::after {
             margin-left: .25em;
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
            <div class="alert alert-success" role="alert" aria-live="polite">
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
                <input type="email" name="email" id="email" required placeholder="seu@email.com" autocomplete="email" inputmode="email">
                <small style="display:block; margin-top:6px; color:#666">Use o e-mail cadastrado no sistema</small>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-with-action">
                    <input type="password" name="password" id="password" required placeholder="Digite sua senha" autocomplete="current-password">
                    <button type="button" class="toggle-password" aria-label="Mostrar/ocultar senha">👁️</button>
                </div>
                <small id="capsLockMsg" class="caps-lock-indicator">Caps Lock está ativado</small>
            </div>
            
            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="separator">
            <span>OU</span>
        </div>

        <div id="g_id_onload"
             data-client_id="483177848191-i85ijikssoaftcnam1kjinhkdvi7lf69.apps.googleusercontent.com"
             data-context="signin"
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
        
        <div class="help-section">
            <div class="help-title">💡 Como fazer login:</div>
            <div class="help-item">
                <strong>Usuário Normal:</strong> Selecione "Usuário Normal" e use suas credenciais
            </div>
            <div class="help-item">
                <strong>Administrador:</strong> Selecione "Administrador" e use suas credenciais de admin
            </div>
            <div class="help-item" style="margin-top: 12px; color: #666;">
                <strong>Não tem conta?</strong> <a href="cadastro.php" style="color: #0072FF; text-decoration: none; font-weight: 600;">Cadastre-se aqui</a>
            </div>
        </div>

        <script src="https://accounts.google.com/gsi/client" async defer></script>
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
            
            // Se a função não existir por algum motivo, definir uma função no escopo global
            if (typeof window.handleGoogleSignIn !== 'function') {
                window.handleGoogleSignIn = function(response) {
                    try {
                        const id_token = response && response.credential ? response.credential : null;
                        if (!id_token) {
                            console.error('ID token ausente no callback do Google.');
                            alert('Não foi possível obter o token do Google. Tente novamente.');
                            return;
                        }
                        // Tenta decodificar o e-mail (opcional). Mesmo sem parseJwt, o fluxo continua normalmente.
                        try {
                            const claims = (typeof parseJwt === 'function') ? parseJwt(id_token) : null;
                            if (claims && claims.email) {
                                try { localStorage.setItem('google_email', claims.email); } catch(e) {}
                            }
                        } catch (e) {}
                        
                        // Envia o token ao servidor para criar sessão e então redireciona automaticamente
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
                            const msg = (data && data.message) ? data.message : 'Falha no login com Google.';
                            alert(`Erro ao fazer login com o Google (${statusInfo}): ${msg}`);
                        })
                        .catch(() => alert('Ocorreu um erro inesperado ao tentar fazer login com o Google.'));
                    } catch (e) {
                        console.error('Erro em handleGoogleSignIn (fallback):', e);
                    }
                }
            }
        </script>
     <script>
        function selectUserType(type) {
            const buttons = document.querySelectorAll('.type-btn');
            buttons.forEach(btn => {
                const isActive = btn.dataset.type === type;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
            const hidden = document.getElementById('user_type');
            if (hidden) hidden.value = type;
            try { localStorage.setItem('user_type', type); } catch (e) {}
        }
        document.addEventListener('DOMContentLoaded', function () {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const togglePasswordBtn = document.querySelector('.toggle-password');
            const capsLockMsg = document.getElementById('capsLockMsg');
            const typeButtons = document.querySelectorAll('.type-btn');
            const userTypeHidden = document.getElementById('user_type');
            
            // Restaurar tipo selecionado
            const savedType = localStorage.getItem('user_type');
            if (savedType) {
                try { selectUserType(savedType); } catch (e) {}
            }
            
            // Navegação por teclado entre tipos
            document.addEventListener('keydown', function(e) {
                if (!typeButtons.length) return;
                const activeIndex = Array.from(typeButtons).findIndex(btn => btn.classList.contains('active'));
                if (e.key === 'ArrowRight') {
                    const next = activeIndex >= 0 ? (activeIndex + 1) % typeButtons.length : 0;
                    typeButtons[next].click();
                    localStorage.setItem('user_type', typeButtons[next].dataset.type);
                } else if (e.key === 'ArrowLeft') {
                    const prev = activeIndex >= 0 ? (activeIndex - 1 + typeButtons.length) % typeButtons.length : 0;
                    typeButtons[prev].click();
                    localStorage.setItem('user_type', typeButtons[prev].dataset.type);
                }
            });
            
            // Alternar visualização de senha
            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function () {
                    const isPass = passwordInput.type === 'password';
                    passwordInput.type = isPass ? 'text' : 'password';
                    togglePasswordBtn.textContent = isPass ? '🙈' : '👁️';
                    passwordInput.focus();
                });
            }
            
            // Indicador de Caps Lock
            if (passwordInput && capsLockMsg) {
                passwordInput.addEventListener('keyup', function(e) {
                    const capsOn = e.getModifierState && e.getModifierState('CapsLock');
                    capsLockMsg.style.display = capsOn ? 'block' : 'none';
                });
            }
            
            // Validação leve de campos (não substitui required)
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                // Limpa estados
                emailInput.classList.remove('input-error');
                passwordInput.classList.remove('input-error');
                
                if (!emailInput.value.trim()) {
                    emailInput.classList.add('input-error');
                    emailInput.focus();
                    e.preventDefault();
                    return;
                }
                if (!passwordInput.value.trim()) {
                    passwordInput.classList.add('input-error');
                    passwordInput.focus();
                    e.preventDefault();
                    return;
                }
            });
        });
     </script>
 </body>
 </html>