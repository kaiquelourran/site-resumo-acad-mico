<?php
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
require_once 'conexao.php';

$mensagem = '';
$tipo_mensagem = '';

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!validate_csrf()) {
        $mensagem = 'Token de segurança inválido. Atualize a página e tente novamente.';
        $tipo_mensagem = 'error';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tipo_problema = $_POST['tipo_problema'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $pagina_erro = trim($_POST['pagina_erro'] ?? '');
        
        // Validação
        if (empty($nome) || empty($email) || empty($tipo_problema) || empty($titulo) || empty($descricao)) {
            $mensagem = 'Por favor, preencha todos os campos obrigatórios.';
            $tipo_mensagem = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem = 'Por favor, insira um email válido.';
            $tipo_mensagem = 'error';
        } else {
            try {
                // Inserir relatório
                $stmt = $pdo->prepare("
                    INSERT INTO relatorios_bugs (id_usuario, nome_usuario, email_usuario, tipo_problema, titulo, descricao, pagina_erro) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : null;
                
                $stmt->execute([
                    $id_usuario,
                    htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($tipo_problema, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($pagina_erro, ENT_QUOTES, 'UTF-8')
                ]);
                
                $mensagem = 'Relatório enviado com sucesso! Entraremos em contato em breve.';
                $tipo_mensagem = 'success';
                
                // Limpar formulário
                $nome = $email = $titulo = $descricao = $pagina_erro = '';
                
            } catch (Exception $e) {
                $mensagem = 'Erro ao enviar relatório. Tente novamente.';
                $tipo_mensagem = 'error';
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
    <title>Relatar Problema - Resumo Acadêmico</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        /* Design padrão do sistema */
        body {
            background-image: linear-gradient(to top, #00C6FF, #0072FF);
            min-height: 100vh;
            margin: 0;
        }
        
        .main-container {
            max-width: 1100px;
            margin: 40px auto;
            background: #FFFFFF;
            border-radius: 16px;
            border: 1px solid transparent;
            background-image: linear-gradient(#FFFFFF, #FFFFFF), linear-gradient(to top, #00C6FF, #0072FF);
            background-origin: border-box;
            background-clip: padding-box, border-box;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header .logo {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .header .title {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .user-info a,
        .user-link {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%) !important;
            color: white !important;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin: 0 10px;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(0,114,255,0.3);
        }
        
        .user-info a:hover,
        .user-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,114,255,0.4);
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #00C6FF, #0072FF);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 8px 25px rgba(0,114,255,0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0,114,255,0.4);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tipo-problema-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .tipo-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tipo-option:hover {
            border-color: #4CAF50;
            background: #f8f9fa;
        }
        
        .tipo-option input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .tipo-option input[type="radio"]:checked + .tipo-label {
            color: #4CAF50;
            font-weight: 600;
        }
        
        .tipo-option.selected {
            border-color: #4CAF50;
            background: #e8f5e8;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .main-container {
                margin: 20px;
                padding: 20px;
            }
            
            .header .title {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .tipo-problema-grid {
                grid-template-columns: 1fr;
            }
            
            .user-info a {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">🐛</div>
            <h1 class="title">Relatar Problema</h1>
            <p class="subtitle">Ajude-nos a melhorar o sistema reportando bugs e sugestões</p>
        </div>
        
        <div class="user-info">
            <a href="index.php" class="user-link">🏠 Voltar ao Sistema</a>
        </div>

        <div class="form-container">
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="nome">
                        Nome Completo <span class="required">*</span>
                    </label>
                    <input type="text" id="nome" name="nome" 
                           value="<?php echo htmlspecialchars($nome ?? ''); ?>" 
                           placeholder="Seu nome completo" required>
                </div>

                <div class="form-group">
                    <label for="email">
                        E-mail <span class="required">*</span>
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                           placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label>Tipo de Problema <span class="required">*</span></label>
                    <div class="tipo-problema-grid">
                        <div class="tipo-option">
                            <input type="radio" id="bug" name="tipo_problema" value="bug" 
                                   <?php echo ($tipo_problema ?? '') === 'bug' ? 'checked' : ''; ?> required>
                            <label for="bug" class="tipo-label">🐛 Bug</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" id="melhoria" name="tipo_problema" value="melhoria" 
                                   <?php echo ($tipo_problema ?? '') === 'melhoria' ? 'checked' : ''; ?> required>
                            <label for="melhoria" class="tipo-label">💡 Melhoria</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" id="duvida" name="tipo_problema" value="duvida" 
                                   <?php echo ($tipo_problema ?? '') === 'duvida' ? 'checked' : ''; ?> required>
                            <label for="duvida" class="tipo-label">❓ Dúvida</label>
                        </div>
                        <div class="tipo-option">
                            <input type="radio" id="outro" name="tipo_problema" value="outro" 
                                   <?php echo ($tipo_problema ?? '') === 'outro' ? 'checked' : ''; ?> required>
                            <label for="outro" class="tipo-label">📝 Outro</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="titulo">
                        Título do Problema <span class="required">*</span>
                    </label>
                    <input type="text" id="titulo" name="titulo" 
                           value="<?php echo htmlspecialchars($titulo ?? ''); ?>" 
                           placeholder="Descreva brevemente o problema" required>
                </div>

                <div class="form-group">
                    <label for="descricao">
                        Descrição Detalhada <span class="required">*</span>
                    </label>
                    <textarea id="descricao" name="descricao" 
                              placeholder="Descreva o problema em detalhes. Inclua passos para reproduzir, mensagens de erro, etc." 
                              required><?php echo htmlspecialchars($descricao ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="pagina_erro">
                        Página onde ocorreu o problema (opcional)
                    </label>
                    <input type="text" id="pagina_erro" name="pagina_erro" 
                           value="<?php echo htmlspecialchars($pagina_erro ?? ''); ?>" 
                           placeholder="Ex: quiz_vertical_filtros.php?id=1&filtro=todas">
                </div>

                <button type="submit" class="submit-btn">
                    📤 Enviar Relatório
                </button>
            </form>
        </div>
    </div>

    <script>
        // Efeito visual nos radio buttons
        document.querySelectorAll('input[name="tipo_problema"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.tipo-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.tipo-option').classList.add('selected');
            });
        });
        
        // Auto-focus no primeiro campo
        document.getElementById('nome').focus();
    </script>
</body>
</html>
