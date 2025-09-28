<?php
// Sistema de Inserção Manual de Questões
require_once 'conexao.php';

// Processar formulário se enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto_nome = trim($_POST['assunto_nome'] ?? '');
    $enunciado = trim($_POST['enunciado'] ?? '');
    $alternativa_a = trim($_POST['alternativa_a'] ?? '');
    $alternativa_b = trim($_POST['alternativa_b'] ?? '');
    $alternativa_c = trim($_POST['alternativa_c'] ?? '');
    $alternativa_d = trim($_POST['alternativa_d'] ?? '');
    $resposta_correta = $_POST['resposta_correta'] ?? '';
    $explicacao = trim($_POST['explicacao'] ?? '');
    
    // Validar campos obrigatórios
    if (empty($assunto_nome) || empty($enunciado) || empty($alternativa_a) || empty($alternativa_b) || empty($alternativa_c) || empty($alternativa_d) || empty($resposta_correta)) {
        $mensagem = 'Todos os campos são obrigatórios!';
        $tipo_mensagem = 'error';
    } else {
        try {
            // Verificar se o assunto já existe
            $stmt = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = ?");
            $stmt->execute([$assunto_nome]);
            $assunto = $stmt->fetch();
            
            if (!$assunto) {
                // Criar novo assunto
                $stmt = $pdo->prepare("INSERT INTO assuntos (nome) VALUES (?)");
                $stmt->execute([$assunto_nome]);
                $id_assunto = $pdo->lastInsertId();
            } else {
                $id_assunto = $assunto['id_assunto'];
            }
            
            // Inserir questão
            $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao, id_assunto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$enunciado, $alternativa_a, $alternativa_b, $alternativa_c, $alternativa_d, $resposta_correta, $explicacao, $id_assunto]);
            
            $id_questao = $pdo->lastInsertId();
            
            // Inserir alternativas na tabela alternativas
            $alternativas = [
                'A' => $alternativa_a,
                'B' => $alternativa_b,
                'C' => $alternativa_c,
                'D' => $alternativa_d
            ];
            
            foreach ($alternativas as $letra => $texto) {
                $eh_correta = ($letra === $resposta_correta) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO alternativas (id_questao, texto, eh_correta) VALUES (?, ?, ?)");
                $stmt->execute([$id_questao, $texto, $eh_correta]);
            }
            
            $mensagem = "Questão inserida com sucesso! ID: $id_questao";
            $tipo_mensagem = 'success';
            
            // Limpar campos após sucesso
            $assunto_nome = $enunciado = $alternativa_a = $alternativa_b = $alternativa_c = $alternativa_d = $explicacao = '';
            $resposta_correta = '';
            
        } catch (Exception $e) {
            $mensagem = 'Erro ao inserir questão: ' . $e->getMessage();
            $tipo_mensagem = 'error';
        }
    }
}

// Buscar estatísticas
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
    $total_questoes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assuntos");
    $total_assuntos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT nome FROM assuntos ORDER BY nome");
    $assuntos_existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (Exception $e) {
    $total_questoes = $total_assuntos = 0;
    $assuntos_existentes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserir Questões Manualmente</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4CAF50;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .alternatives-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }
        
        .alternative-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alternative-letter {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }
        
        .alternative-item input {
            flex: 1;
            margin: 0;
        }
        
        .correct-answer {
            display: flex;
            gap: 15px;
            align-items: center;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .correct-answer label {
            margin: 0;
            font-weight: 600;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
        }
        
        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-item input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
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
        
        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .existing-subjects {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .existing-subjects h4 {
            margin: 0 0 10px 0;
            color: #666;
        }
        
        .subjects-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .subject-tag {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">📝</div>
            <h1 class="title">Inserir Questões Manualmente</h1>
            <p class="subtitle">Sistema de inserção manual com interface amigável</p>
        </div>
        
        <div class="user-info">
            <a href="gerenciar_questoes_sem_auth.php" class="user-link">📋 Gerenciar Questões</a>
            <a href="quiz_sem_login.php" class="user-link">🎮 Questões</a>
            <a href="index.php" class="user-link">🏠 Menu Principal</a>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_questoes; ?></div>
                <div class="stat-label">📝 Questões</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_assuntos; ?></div>
                <div class="stat-label">📚 Assuntos</div>
            </div>
        </div>

        <div class="form-container">
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="assunto_nome">Nome do Assunto:</label>
                    <?php if (!empty($assuntos_existentes)): ?>
                        <div class="existing-subjects">
                            <h4>Assuntos existentes:</h4>
                            <div class="subjects-list">
                                <?php foreach ($assuntos_existentes as $assunto): ?>
                                    <span class="subject-tag"><?php echo htmlspecialchars($assunto); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="text" id="assunto_nome" name="assunto_nome" 
                           value="<?php echo htmlspecialchars($assunto_nome ?? ''); ?>" 
                           placeholder="Digite o nome do assunto" required>
                </div>

                <div class="form-group">
                    <label for="enunciado">Enunciado da Questão:</label>
                    <textarea id="enunciado" name="enunciado" 
                              placeholder="Digite o enunciado completo da questão..." required><?php echo htmlspecialchars($enunciado ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Alternativas:</label>
                    <div class="alternatives-grid">
                        <div class="alternative-item">
                            <div class="alternative-letter">A</div>
                            <input type="text" name="alternativa_a" 
                                   value="<?php echo htmlspecialchars($alternativa_a ?? ''); ?>" 
                                   placeholder="Alternativa A" required>
                        </div>
                        <div class="alternative-item">
                            <div class="alternative-letter">B</div>
                            <input type="text" name="alternativa_b" 
                                   value="<?php echo htmlspecialchars($alternativa_b ?? ''); ?>" 
                                   placeholder="Alternativa B" required>
                        </div>
                        <div class="alternative-item">
                            <div class="alternative-letter">C</div>
                            <input type="text" name="alternativa_c" 
                                   value="<?php echo htmlspecialchars($alternativa_c ?? ''); ?>" 
                                   placeholder="Alternativa C" required>
                        </div>
                        <div class="alternative-item">
                            <div class="alternative-letter">D</div>
                            <input type="text" name="alternativa_d" 
                                   value="<?php echo htmlspecialchars($alternativa_d ?? ''); ?)" 
                                   placeholder="Alternativa D" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="correct-answer">
                        <label>Resposta Correta:</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" id="resp_a" name="resposta_correta" value="A" 
                                       <?php echo ($resposta_correta ?? '') === 'A' ? 'checked' : ''; ?> required>
                                <label for="resp_a">A</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="resp_b" name="resposta_correta" value="B" 
                                       <?php echo ($resposta_correta ?? '') === 'B' ? 'checked' : ''; ?> required>
                                <label for="resp_b">B</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="resp_c" name="resposta_correta" value="C" 
                                       <?php echo ($resposta_correta ?? '') === 'C' ? 'checked' : ''; ?> required>
                                <label for="resp_c">C</label>
                            </div>
                            <div class="radio-item">
                                <input type="radio" id="resp_d" name="resposta_correta" value="D" 
                                       <?php echo ($resposta_correta ?? '') === 'D' ? 'checked' : ''; ?> required>
                                <label for="resp_d">D</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="explicacao">Explicação (opcional):</label>
                    <textarea id="explicacao" name="explicacao" 
                              placeholder="Digite uma explicação para a resposta correta..."><?php echo htmlspecialchars($explicacao ?? ''); ?></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    ✅ Inserir Questão
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto-focus no primeiro campo
        document.getElementById('assunto_nome').focus();
        
        // Adicionar efeito visual nos radio buttons
        document.querySelectorAll('input[name="resposta_correta"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.radio-item').forEach(item => {
                    item.style.background = '';
                    item.style.borderRadius = '';
                    item.style.padding = '';
                });
                
                this.closest('.radio-item').style.background = '#e8f5e8';
                this.closest('.radio-item').style.borderRadius = '6px';
                this.closest('.radio-item').style.padding = '8px';
            });
        });
    </script>
</body>
</html>