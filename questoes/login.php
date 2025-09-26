<?php
session_start();

// Permite trocar de conta: se ?trocar=1, encerra a sessão atual e mostra o formulário
if (isset($_GET['trocar'])) {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
}

// Verifica se o usuário já está logado e o redireciona, exceto quando enviando login (POST) ou trocando de conta
if (isset($_SESSION['id_usuario']) && $_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['trocar'])) {
    if ($_SESSION['tipo_usuario'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// CORREÇÃO: Caminho do arquivo de conexão
require_once __DIR__ . '/conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo_login = isset($_POST['tipo_login']) ? $_POST['tipo_login'] : '';

    if (empty($email) || empty($senha)) {
        $mensagem = "Por favor, preencha todos os campos.";
    } else {
        // Verifica CSRF
        if (!validate_csrf()) {
            $mensagem = "Sessão expirada ou requisição inválida. Atualize a página e tente novamente.";
        } else {
        // A consulta SQL busca o usuário pelo email
        $stmt = $pdo->prepare("SELECT id_usuario, nome, senha, tipo FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            if (password_verify($senha, $usuario['senha'])) {
                if ($usuario['tipo'] === $tipo_login) {
                    // Regera o ID da sessão para evitar reaproveitar sessão antiga ao trocar de conta
                    session_regenerate_id(true);
                    // Limpa progresso de quiz de sessões anteriores
                    unset($_SESSION['quiz_progress']);
                    // SESSÃO ATUALIZADA: Define as variáveis de sessão de forma unificada
                    $_SESSION['id_usuario'] = $usuario['id_usuario'];
                    $_SESSION['nome_usuario'] = $usuario['nome'];
                    $_SESSION['tipo_usuario'] = $usuario['tipo'];
                    
                    // NOVO: Adicionei a atualização da data de login aqui
                    $stmt_update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id_usuario = ?");
                    $stmt_update->execute([$usuario['id_usuario']]);

                    // Redireciona com base no tipo de usuário
                    if ($usuario['tipo'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                } else {
                    $mensagem = "Credenciais de login inválidas para este tipo de acesso.";
                }
            } else {
                $mensagem = "Senha incorreta.";
            }
        } else {
            $mensagem = "Email não encontrado.";
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
    <title>Login - Questões Complementares</title>
    <link rel="icon" href="../fotos/minha-logo-apple.png" type="image/png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-image: linear-gradient(to bottom, #4A90E2, #50E3C2);
            min-height: 100vh;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }

        header {
            min-height: 120px;
            background-image: linear-gradient(to bottom, #0072FF, #00C6FF);
            text-align: center;
            font-size: 1.2em;
            padding-top: 20px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            animation: fadeInDown 0.8s ease;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        main {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 150px auto 30px;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .formulario-card {
            width: 100%;
            padding: 40px 30px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .formulario-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }
        
        .formulario-card h2 {
            margin-bottom: 25px;
            color: #333;
            font-size: 2em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .formulario-card h2:after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, #0072FF, #00C6FF);
            margin: 15px auto 0;
            border-radius: 2px;
        }
        
        .login-opcoes {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .login-opcoes button {
            flex: 1;
            padding: 15px;
            font-size: 1.1em;
            font-weight: bold;
            background-color: #f8f9fa;
            color: #495057;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .login-opcoes button.active {
            background: linear-gradient(to right, #0072FF, #00C6FF);
            color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .login-opcoes button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.15);
        }
        
        .formulario-card .campo-grupo {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .formulario-card label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
            color: #444;
            font-size: 1.1em;
        }
        
        .formulario-card input[type="email"],
        .formulario-card input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #dde1e7;
            border-radius: 10px;
            font-size: 1.1em;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .formulario-card input[type="email"]:focus,
        .formulario-card input[type="password"]:focus {
            border-color: #0072FF;
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 114, 255, 0.2);
            background-color: #fff;
        }

        .formulario-card button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #0072FF, #00C6FF);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .formulario-card button[type="submit"]:hover {
            background: linear-gradient(to right, #005cc8, #00b3e6);
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.25);
        }
        
        .formulario-card .link-cadastro {
            display: inline-block;
            margin-top: 30px;
            color: #0072FF;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            padding: 5px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .formulario-card .link-cadastro:after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #0072FF;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .formulario-card .link-cadastro:hover {
            color: #00C6FF;
        }
        
        .formulario-card .link-cadastro:hover:after {
            transform: scaleX(1);
        }
        
        .mensagem-erro {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            font-weight: bold;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        @media (max-width: 768px) {
            .login-container {
                margin-top: 130px;
                padding: 0 15px;
            }
            
            .formulario-card {
                padding: 30px 20px;
            }
            
            .formulario-card h2 {
                font-size: 1.8em;
            }
            
            .login-opcoes button {
                padding: 12px;
                font-size: 1em;
            }
        }
        
        @media (max-width: 480px) {
            header h1 {
                font-size: 2em;
            }
            
            .login-container {
                margin-top: 110px;
            }
            
            .formulario-card {
                padding: 25px 15px;
            }
            
            .formulario-card h2 {
                font-size: 1.6em;
            }
            
            .login-opcoes {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Questões Complementares</h1>
    </header>

    <main>
        <div class="login-container">
            <div class="formulario-card">
                <h2>Login</h2>
                
                <div class="login-opcoes">
                    <button type="button" class="active" onclick="setTipoLogin('usuario')">Login Usuário</button>
                    <button type="button" onclick="setTipoLogin('admin')">Login Admin</button>
                </div>
                
                <?php if (!empty($mensagem)): ?>
                    <div class="mensagem-erro"><?php echo $mensagem; ?></div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf(); ?>">
                    <input type="hidden" name="tipo_login" id="tipo_login" value="usuario">
                    
                    <div class="campo-grupo">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="campo-grupo">
                        <label for="senha">Senha:</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
                </form>
                
                <a href="cadastro.php" class="link-cadastro">Ainda não tem uma conta? Cadastre-se aqui</a>
            </div>
        </div>
    </main>

    <script>
        function setTipoLogin(tipo) {
            document.getElementById('tipo_login').value = tipo;
            
            // Atualiza os botões
            const botoes = document.querySelectorAll('.login-opcoes button');
            botoes.forEach(botao => {
                botao.classList.remove('active');
            });
            
            if (tipo === 'usuario') {
                botoes[0].classList.add('active');
            } else {
                botoes[1].classList.add('active');
            }
        }
    </script>
</body>
</html>

<?php
// Função para gerar token CSRF
function generate_csrf() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validate_csrf() {
    if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) {
        return false;
    }
    return true;
}
?>