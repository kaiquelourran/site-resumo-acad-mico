<?php
session_start();

// Verifica se o usuário é um administrador logado.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php';

$mensagem_status = '';
$mensagem_texto = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preservar dados do formulário em caso de erro
    $form_data = [
        'enunciado' => trim($_POST['enunciado'] ?? ''),
        'id_assunto' => $_POST['id_assunto'] ?? '',
        'explicacao' => trim($_POST['explicacao'] ?? ''),
        'alt1' => trim($_POST['alt1'] ?? ''),
        'alt2' => trim($_POST['alt2'] ?? ''),
        'alt3' => trim($_POST['alt3'] ?? ''),
        'alt4' => trim($_POST['alt4'] ?? ''),
        'correta' => $_POST['correta'] ?? ''
    ];
    
    $enunciado = $form_data['enunciado'];
    $id_assunto = $form_data['id_assunto'];
    $explicacao = $form_data['explicacao'];
    $alternativas = [
        $form_data['alt1'],
        $form_data['alt2'],
        $form_data['alt3'],
        $form_data['alt4']
    ];
    $correta_index = (int)$form_data['correta'];

    // Validação melhorada
    $errors = [];
    if (empty($enunciado)) $errors[] = 'O enunciado é obrigatório';
    if (empty($id_assunto)) $errors[] = 'Selecione um assunto';
    if ($correta_index < 1 || $correta_index > 4) $errors[] = 'Selecione a alternativa correta';
    
    // Verificar se todas as alternativas estão preenchidas
    foreach ($alternativas as $i => $alt) {
        if (empty($alt)) {
            $letra = chr(65 + $i); // A, B, C, D
            $errors[] = "A alternativa {$letra} é obrigatória";
        }
    }
    
    if (!empty($errors)) {
        $mensagem_status = 'error';
        $mensagem_texto = implode('<br>', $errors);
    } else {
        try {
            $pdo->beginTransaction();

            // Insere a questão com explicação
            $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, explicacao, id_assunto, data_criacao) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$enunciado, $explicacao, $id_assunto]);
            $id_questao = $pdo->lastInsertId();

            // Insere as alternativas
            $sql_alternativas = "INSERT INTO alternativas (id_questao, texto, eh_correta) VALUES (?, ?, ?)";
            $stmt_alternativas = $pdo->prepare($sql_alternativas);

            foreach ($alternativas as $index => $texto) {
                $eh_correta = ($index + 1 == $correta_index) ? 1 : 0;
                $stmt_alternativas->execute([$id_questao, $texto, $eh_correta]);
            }

            $pdo->commit();
            $mensagem_status = 'success';
            $mensagem_texto = "Questão #{$id_questao} adicionada com sucesso!";
            
            // Limpar dados do formulário após sucesso
            $form_data = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_status = 'error';
            $mensagem_texto = 'Erro ao adicionar a questão: ' . $e->getMessage();
        }
    }
}

// Busca os assuntos e estatísticas
try {
    $stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
    $assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas
    $stmt_stats = $pdo->query("SELECT COUNT(*) as total_questoes FROM questoes");
    $total_questoes = $stmt_stats->fetch()['total_questoes'];
    
    $stmt_stats = $pdo->query("SELECT COUNT(*) as total_assuntos FROM assuntos");
    $total_assuntos = $stmt_stats->fetch()['total_assuntos'];
    
} catch (Exception $e) {
    $assuntos = [];
    $total_questoes = 0;
    $total_assuntos = 0;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Questão - Sistema Quiz</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .stats-bar {
            background: #f8fafc;
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            flex: 1;
            min-width: 120px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4f46e5;
            display: block;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .form-container {
            padding: 40px;
        }

        .message {
            padding: 16px 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        }

        .message.success {
            background: #f0fdf4;
            color: #166534;
            border-left-color: #22c55e;
        }

        .message.error {
            background: #fef2f2;
            color: #dc2626;
            border-left-color: #ef4444;
        }

        .form-grid {
            display: grid;
            gap: 25px;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
        }

        .form-group .required {
            color: #ef4444;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-control.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .char-counter {
            position: absolute;
            right: 12px;
            bottom: 12px;
            font-size: 0.8rem;
            color: #6b7280;
            background: rgba(255, 255, 255, 0.9);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .alternatives-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .alternative-item {
            position: relative;
        }

        .alternative-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }

        .alt-letter {
            background: #4f46e5;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .correct-answer-section {
            background: #f8fafc;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .radio-item:hover {
            background: #f1f5f9;
        }

        .radio-item input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #4f46e5;
        }

        .radio-item input[type="radio"]:checked + .radio-label {
            color: #4f46e5;
            font-weight: 600;
        }

        .submit-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            margin-right: 15px;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .preview-section {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin-top: 30px;
            display: none;
        }

        .preview-section.show {
            display: block;
            animation: slideIn 0.3s ease-out;
        }

        .validation-message {
            color: #ef4444;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .validation-message.show {
            display: block;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .form-container {
                padding: 25px;
            }
            
            .alternatives-grid {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-bar {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-plus-circle"></i> Adicionar Questão</h1>
            <p>Crie novas questões para enriquecer o banco de dados do sistema</p>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_questoes; ?></span>
                <div class="stat-label">Total de Questões</div>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_assuntos; ?></span>
                <div class="stat-label">Assuntos Disponíveis</div>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="session-count">1</span>
                <div class="stat-label">Questões nesta Sessão</div>
            </div>
        </div>

        <div class="form-container">
            <?php if ($mensagem_status): ?>
                <div class="message <?php echo $mensagem_status; ?>">
                    <i class="fas <?php echo $mensagem_status === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                    <?php echo $mensagem_texto; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="questionForm" novalidate>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_assunto">
                            <i class="fas fa-tag"></i> Assunto <span class="required">*</span>
                        </label>
                        <select name="id_assunto" id="id_assunto" class="form-control" required>
                            <option value="">Selecione um assunto</option>
                            <?php foreach ($assuntos as $assunto): ?>
                                <option value="<?php echo $assunto['id_assunto']; ?>" 
                                        <?php echo (isset($form_data['id_assunto']) && $form_data['id_assunto'] == $assunto['id_assunto']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($assunto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="validation-message" id="assunto-error"></div>
                    </div>

                    <div class="form-group">
                        <label for="enunciado">
                            <i class="fas fa-question-circle"></i> Enunciado da Questão <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <textarea name="enunciado" id="enunciado" class="form-control" required 
                                      placeholder="Digite o enunciado da questão de forma clara e objetiva..."
                                      maxlength="1000"><?php echo htmlspecialchars($form_data['enunciado'] ?? ''); ?></textarea>
                            <div class="char-counter" id="enunciado-counter">0/1000</div>
                        </div>
                        <div class="validation-message" id="enunciado-error"></div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-list"></i> Alternativas <span class="required">*</span>
                        </label>
                        <div class="alternatives-grid">
                            <?php 
                            $letters = ['A', 'B', 'C', 'D'];
                            $alt_names = ['alt1', 'alt2', 'alt3', 'alt4'];
                            for ($i = 0; $i < 4; $i++): 
                            ?>
                                <div class="alternative-item">
                                    <div class="alternative-label">
                                        <span class="alt-letter"><?php echo $letters[$i]; ?></span>
                                        Alternativa <?php echo $letters[$i]; ?>
                                    </div>
                                    <input type="text" name="<?php echo $alt_names[$i]; ?>" 
                                           id="<?php echo $alt_names[$i]; ?>" class="form-control" required 
                                           placeholder="Digite a alternativa <?php echo $letters[$i]; ?>"
                                           maxlength="500"
                                           value="<?php echo htmlspecialchars($form_data[$alt_names[$i]] ?? ''); ?>">
                                    <div class="validation-message" id="<?php echo $alt_names[$i]; ?>-error"></div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="explicacao">
                            <i class="fas fa-lightbulb"></i> Explicação (Opcional)
                        </label>
                        <div class="input-wrapper">
                            <textarea name="explicacao" id="explicacao" class="form-control" 
                                      placeholder="Adicione uma explicação detalhada sobre a resposta correta..."
                                      maxlength="2000"><?php echo htmlspecialchars($form_data['explicacao'] ?? ''); ?></textarea>
                            <div class="char-counter" id="explicacao-counter">0/2000</div>
                        </div>
                    </div>

                    <div class="correct-answer-section">
                        <label>
                            <i class="fas fa-check-circle"></i> Alternativa Correta <span class="required">*</span>
                        </label>
                        <div class="radio-group">
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <div class="radio-item">
                                    <input type="radio" name="correta" id="correta<?php echo $i; ?>" 
                                           value="<?php echo $i; ?>" required
                                           <?php echo (isset($form_data['correta']) && $form_data['correta'] == $i) ? 'checked' : ''; ?>>
                                    <label for="correta<?php echo $i; ?>" class="radio-label">
                                        Alternativa <?php echo chr(64 + $i); ?>
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="validation-message" id="correta-error"></div>
                    </div>
                </div>

                <div class="preview-section" id="preview-section">
                    <h3><i class="fas fa-eye"></i> Pré-visualização da Questão</h3>
                    <div id="preview-content"></div>
                </div>

                <div class="submit-section">
                    <button type="button" class="btn-secondary" onclick="togglePreview()">
                        <i class="fas fa-eye"></i> Pré-visualizar
                    </button>
                    <button type="button" class="btn-secondary" onclick="clearForm()">
                        <i class="fas fa-eraser"></i> Limpar
                    </button>
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-plus-circle"></i>
                        Adicionar Questão
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Contadores de caracteres
        function setupCharCounters() {
            const textareas = ['enunciado', 'explicacao'];
            textareas.forEach(id => {
                const textarea = document.getElementById(id);
                const counter = document.getElementById(id + '-counter');
                if (textarea && counter) {
                    const maxLength = textarea.getAttribute('maxlength');
                    
                    function updateCounter() {
                        const current = textarea.value.length;
                        counter.textContent = `${current}/${maxLength}`;
                        counter.style.color = current > maxLength * 0.9 ? '#ef4444' : '#6b7280';
                    }
                    
                    textarea.addEventListener('input', updateCounter);
                    updateCounter(); // Inicializar
                }
            });
        }

        // Validação em tempo real
        function setupValidation() {
            const form = document.getElementById('questionForm');
            const fields = ['id_assunto', 'enunciado', 'alt1', 'alt2', 'alt3', 'alt4'];
            
            fields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                const errorDiv = document.getElementById(fieldName + '-error');
                
                if (field && errorDiv) {
                    field.addEventListener('blur', () => validateField(field, errorDiv));
                    field.addEventListener('input', () => {
                        if (errorDiv.classList.contains('show')) {
                            validateField(field, errorDiv);
                        }
                    });
                }
            });

            // Validação para alternativa correta
            const radioButtons = document.querySelectorAll('input[name="correta"]');
            const correctaError = document.getElementById('correta-error');
            
            radioButtons.forEach(radio => {
                radio.addEventListener('change', () => {
                    if (correctaError.classList.contains('show')) {
                        validateCorrectAnswer();
                    }
                });
            });
        }

        function validateField(field, errorDiv) {
            const value = field.value.trim();
            let isValid = true;
            let message = '';

            if (field.hasAttribute('required') && !value) {
                isValid = false;
                message = 'Este campo é obrigatório';
            } else if (field.type === 'select-one' && !value) {
                isValid = false;
                message = 'Selecione uma opção';
            }

            if (isValid) {
                field.classList.remove('error');
                errorDiv.classList.remove('show');
            } else {
                field.classList.add('error');
                errorDiv.textContent = message;
                errorDiv.classList.add('show');
            }

            return isValid;
        }

        function validateCorrectAnswer() {
            const radioButtons = document.querySelectorAll('input[name="correta"]');
            const correctaError = document.getElementById('correta-error');
            const isChecked = Array.from(radioButtons).some(radio => radio.checked);

            if (!isChecked) {
                correctaError.textContent = 'Selecione a alternativa correta';
                correctaError.classList.add('show');
                return false;
            } else {
                correctaError.classList.remove('show');
                return true;
            }
        }

        // Pré-visualização
        function togglePreview() {
            const previewSection = document.getElementById('preview-section');
            const previewContent = document.getElementById('preview-content');
            
            if (previewSection.classList.contains('show')) {
                previewSection.classList.remove('show');
                return;
            }

            // Gerar conteúdo da pré-visualização
            const assunto = document.getElementById('id_assunto');
            const enunciado = document.getElementById('enunciado').value;
            const alternativas = ['alt1', 'alt2', 'alt3', 'alt4'].map(id => 
                document.getElementById(id).value
            );
            const correta = document.querySelector('input[name="correta"]:checked');
            const explicacao = document.getElementById('explicacao').value;

            let html = '<div style="border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px; background: white;">';
            
            if (assunto.value) {
                html += `<div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">
                    <strong>Assunto:</strong> ${assunto.options[assunto.selectedIndex].text}
                </div>`;
            }
            
            if (enunciado) {
                html += `<div style="font-weight: 600; margin-bottom: 15px; font-size: 1.1rem;">
                    ${enunciado}
                </div>`;
            }

            const letters = ['A', 'B', 'C', 'D'];
            alternativas.forEach((alt, index) => {
                if (alt) {
                    const isCorrect = correta && correta.value == (index + 1);
                    html += `<div style="margin: 8px 0; padding: 10px; border-radius: 6px; 
                        ${isCorrect ? 'background: #f0fdf4; border-left: 3px solid #22c55e;' : 'background: #f8fafc;'}">
                        <strong>${letters[index]})</strong> ${alt}
                        ${isCorrect ? ' <span style="color: #22c55e;">✓</span>' : ''}
                    </div>`;
                }
            });

            if (explicacao) {
                html += `<div style="margin-top: 15px; padding: 15px; background: #fef3c7; border-radius: 6px;">
                    <strong>Explicação:</strong><br>${explicacao}
                </div>`;
            }

            html += '</div>';
            previewContent.innerHTML = html;
            previewSection.classList.add('show');
        }

        // Limpar formulário
        function clearForm() {
            if (confirm('Tem certeza que deseja limpar todos os campos?')) {
                document.getElementById('questionForm').reset();
                document.querySelectorAll('.validation-message').forEach(msg => {
                    msg.classList.remove('show');
                });
                document.querySelectorAll('.form-control').forEach(field => {
                    field.classList.remove('error');
                });
                document.getElementById('preview-section').classList.remove('show');
                setupCharCounters(); // Resetar contadores
            }
        }

        // Auto-save (localStorage)
        function setupAutoSave() {
            const form = document.getElementById('questionForm');
            const fields = ['id_assunto', 'enunciado', 'alt1', 'alt2', 'alt3', 'alt4', 'explicacao'];
            
            // Carregar dados salvos
            fields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                const saved = localStorage.getItem('questao_' + fieldName);
                if (saved && !field.value) {
                    field.value = saved;
                }
            });

            // Salvar automaticamente
            fields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                field.addEventListener('input', () => {
                    localStorage.setItem('questao_' + fieldName, field.value);
                });
            });

            // Limpar localStorage após envio bem-sucedido
            <?php if ($mensagem_status === 'success'): ?>
                fields.forEach(fieldName => {
                    localStorage.removeItem('questao_' + fieldName);
                });
            <?php endif; ?>
        }

        // Validação do formulário antes do envio
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar campos obrigatórios
            const requiredFields = ['id_assunto', 'enunciado', 'alt1', 'alt2', 'alt3', 'alt4'];
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                const errorDiv = document.getElementById(fieldName + '-error');
                if (!validateField(field, errorDiv)) {
                    isValid = false;
                }
            });

            // Validar alternativa correta
            if (!validateCorrectAnswer()) {
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, corrija os erros antes de enviar o formulário.');
            }
        });

        // Inicializar funcionalidades
        document.addEventListener('DOMContentLoaded', function() {
            setupCharCounters();
            setupValidation();
            setupAutoSave();
            
            // Atualizar contador de sessão
            const sessionCount = parseInt(localStorage.getItem('session_questions') || '0');
            <?php if ($mensagem_status === 'success'): ?>
                const newCount = sessionCount + 1;
                localStorage.setItem('session_questions', newCount);
                document.getElementById('session-count').textContent = newCount;
            <?php else: ?>
                document.getElementById('session-count').textContent = sessionCount;
            <?php endif; ?>
        });
    </script>
</body>
</html>