<?php
session_start();

// Verifica se o usuário é um administrador logado.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: /admin/login.php');
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
        'alt5' => trim($_POST['alt5'] ?? ''),  // Alternativa E opcional
        'correta' => $_POST['correta'] ?? ''
    ];
    
    $enunciado = $form_data['enunciado'];
    $id_assunto = $form_data['id_assunto'];
    $explicacao = $form_data['explicacao'];
    
    $alternativas = [
        $form_data['alt1'],
        $form_data['alt2'],
        $form_data['alt3'],
        $form_data['alt4'],
        $form_data['alt5']  // Alternativa E opcional
    ];
    $correta_index = (int)$form_data['correta'];

    // Validação básica - apenas campos essenciais para funcionamento
    $errors = [];
    
    // Só validar se pelo menos um campo foi preenchido para evitar inserções completamente vazias
    $has_content = !empty($enunciado) || !empty($explicacao) || array_filter($alternativas);
    
    if (!$has_content) {
        $errors[] = 'Preencha pelo menos um campo para criar a questão';
    }
    
    // Se uma alternativa correta foi selecionada, verificar se ela existe
    if ($correta_index >= 1 && $correta_index <= 5) {
        if (empty(trim($alternativas[$correta_index - 1]))) {
            $letra = chr(64 + $correta_index); // A, B, C, D, E
            $errors[] = "A alternativa {$letra} selecionada como correta está vazia";
        }
    }
    
    if (!empty($errors)) {
        $mensagem_status = 'error';
        $mensagem_texto = implode('<br>', $errors);
    } else {
        // CORREÇÃO: Capturar id_assunto baseado no tipo de conteúdo selecionado
        $tipo_conteudo = $_POST['tipo_conteudo'] ?? '';
        
        if ($tipo_conteudo === 'tema') {
            $id_assunto = $_POST['assunto_tema'] ?? '';
        } elseif ($tipo_conteudo === 'profissional') {
            $id_assunto = $_POST['assunto_profissional'] ?? '';
        } elseif ($tipo_conteudo === 'concurso') {
            // Para concursos, buscar o assunto baseado nas seleções
            $orgao = $_POST['concurso_orgao_sel'] ?? '';
            $banca = $_POST['concurso_banca_sel'] ?? '';
            $ano = $_POST['concurso_ano_sel'] ?? '';
            $prova = $_POST['concurso_prova_sel'] ?? '';
            
            if ($orgao && $banca && $ano && $prova) {
                try {
                    $stmt_concurso = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE concurso_orgao = ? AND concurso_banca = ? AND concurso_ano = ? AND concurso_prova = ?");
                    $stmt_concurso->execute([$orgao, $banca, $ano, $prova]);
                    $assunto_concurso = $stmt_concurso->fetch();
                    
                    if ($assunto_concurso) {
                        $id_assunto = $assunto_concurso['id_assunto'];
                    }
                } catch (Exception $e) {
                    $errors[] = 'Erro ao buscar o concurso selecionado: ' . $e->getMessage();
                }
            }
        }
        
        // Validação final do id_assunto
        if (empty($id_assunto)) {
            $errors[] = 'Erro: Nenhum conteúdo foi selecionado. Por favor, selecione um conteúdo válido.';
            $mensagem_status = 'error';
            $mensagem_texto = implode('<br>', $errors);
        } else {
        try {
            $pdo->beginTransaction();

            // Insere a questão com explicação
            $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, explicacao, id_assunto, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$enunciado, $explicacao, $id_assunto]);
            $id_questao = $pdo->lastInsertId();

            // Insere as alternativas
            $sql_alternativas = "INSERT INTO alternativas (id_questao, texto, eh_correta) VALUES (?, ?, ?)";
            $stmt_alternativas = $pdo->prepare($sql_alternativas);

            foreach ($alternativas as $index => $texto) {
                // Só insere se a alternativa não estiver vazia
                if (!empty(trim($texto))) {
                $eh_correta = ($index + 1 == $correta_index) ? 1 : 0;
                $stmt_alternativas->execute([$id_questao, $texto, $eh_correta]);
                }
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
}

// Busca os assuntos categorizados e estatísticas
try {
    // Verificar se existe campo tipo_assunto
    $stmt_check = $pdo->query("DESCRIBE assuntos");
    $cols = $stmt_check->fetchAll(PDO::FETCH_COLUMN, 0);
    $tem_campo_tipo = in_array('tipo_assunto', $cols);
    
    if ($tem_campo_tipo) {
        $stmt_assuntos = $pdo->query("SELECT id_assunto, nome, tipo_assunto, concurso_ano, concurso_banca, concurso_orgao, concurso_prova FROM assuntos ORDER BY tipo_assunto, nome");
    } else {
        $stmt_assuntos = $pdo->query("SELECT id_assunto, nome, 'tema' as tipo_assunto, NULL as concurso_ano, NULL as concurso_banca, NULL as concurso_orgao, NULL as concurso_prova FROM assuntos ORDER BY nome");
    }
    $assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar assuntos por tipo
    $assuntos_por_tipo = [
        'tema' => [],
        'concurso' => [],
        'profissional' => []
    ];
    
    foreach ($assuntos as $assunto) {
        $tipo = $assunto['tipo_assunto'] ?? 'tema';
        if (isset($assuntos_por_tipo[$tipo])) {
            $assuntos_por_tipo[$tipo][] = $assunto;
        }
    }
    
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
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
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
             background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
             color: white;
             padding: 30px;
             text-align: center;
             position: relative;
         }

         .header-nav {
             position: absolute;
             top: 20px;
             left: 20px;
             z-index: 2;
         }

         .btn-back {
             background: rgba(255, 255, 255, 0.2);
             color: white;
             border: 2px solid rgba(255, 255, 255, 0.3);
             padding: 10px 20px;
             border-radius: 25px;
             font-size: 0.9rem;
             cursor: pointer;
             transition: all 0.3s ease;
             display: flex;
             align-items: center;
             gap: 8px;
             backdrop-filter: blur(10px);
         }

         .btn-back:hover {
             background: rgba(255, 255, 255, 0.3);
             border-color: rgba(255, 255, 255, 0.5);
             transform: translateY(-2px);
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
            color: #00C6FF;
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
            border-color: #00C6FF;
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
            background: #00C6FF;
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
        
        .alt-optional {
            color: #6b7280;
            font-size: 0.75rem;
            font-weight: 400;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 8px;
        }
        
        /* Garantir que os grupos de seleção sejam exibidos corretamente */
        #grupo-concursos,
        #grupo-temas,
        #grupo-profissionais {
            display: none !important;
        }
        
        #grupo-concursos.show,
        #grupo-temas.show,
        #grupo-profissionais.show {
            display: block !important;
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
            accent-color: #00C6FF;
        }

        .radio-item input[type="radio"]:checked + .radio-label {
            color: #00C6FF;
            font-weight: 600;
        }

        .submit-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
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
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
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
                <div class="stat-label">Conteúdos Disponíveis</div>
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
                        <label for="tipo_conteudo">
                            <i class="fas fa-tag"></i> Tipo de Conteúdo
                        </label>
                        <select name="tipo_conteudo" id="tipo_conteudo" class="form-control" onchange="atualizarSeletoresConteudo()">
                            <option value="">Selecione o tipo de conteúdo</option>
                            <option value="tema">📚 Temas</option>
                            <option value="concurso">🏆 Concursos</option>
                            <option value="profissional">💼 Profissionais</option>
                        </select>
                    </div>
                    
                    <!-- Seletor para Temas -->
                    <div class="form-group" id="grupo-temas">
                        <label for="assunto_tema">
                            <i class="fas fa-book"></i> Tema
                        </label>
                        <select id="assunto_tema" class="form-control">
                            <option value="">Selecione um tema</option>
                            <?php foreach ($assuntos_por_tipo['tema'] as $assunto): ?>
                                <option value="<?php echo $assunto['id_assunto']; ?>">
                                    <?php echo htmlspecialchars($assunto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Seletores para Concursos -->
                    <div id="grupo-concursos">
                        <div class="form-group">
                            <label for="concurso_orgao_sel">
                                <i class="fas fa-building"></i> Órgão
                            </label>
                            <select name="concurso_orgao_sel" id="concurso_orgao_sel" class="form-control" onchange="atualizarBancas()">
                                <option value="">Selecione o órgão</option>
                                <?php
                                $orgaos = [];
                                foreach ($assuntos_por_tipo['concurso'] as $assunto) {
                                    if (!empty($assunto['concurso_orgao'])) {
                                        $orgaos[] = $assunto['concurso_orgao'];
                                    }
                                }
                                $orgaos = array_unique($orgaos);
                                foreach ($orgaos as $orgao): ?>
                                    <option value="<?php echo htmlspecialchars($orgao); ?>">
                                        <?php echo htmlspecialchars($orgao); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="concurso_banca_sel">
                                <i class="fas fa-university"></i> Banca
                            </label>
                            <select name="concurso_banca_sel" id="concurso_banca_sel" class="form-control" onchange="atualizarAnos()">
                                <option value="">Selecione a banca</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="concurso_ano_sel">
                                <i class="fas fa-calendar"></i> Ano
                            </label>
                            <select name="concurso_ano_sel" id="concurso_ano_sel" class="form-control" onchange="atualizarProvas()">
                                <option value="">Selecione o ano</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="concurso_prova_sel">
                                <i class="fas fa-file-alt"></i> Prova
                            </label>
                            <select name="concurso_prova_sel" id="concurso_prova_sel" class="form-control" onchange="selecionarAssuntoConcurso()">
                                <option value="">Selecione a prova</option>
                            </select>
                        </div>
                        
                        <input type="hidden" id="id_assunto_concurso" value="">
                    </div>
                    
                    <!-- Campo principal id_assunto (hidden) - ÚNICO campo enviado ao servidor -->
                    <input type="hidden" name="id_assunto" id="id_assunto" value="">
                    
                    <!-- Seletor para Profissionais -->
                    <div class="form-group" id="grupo-profissionais">
                        <label for="assunto_profissional">
                            <i class="fas fa-briefcase"></i> Profissional
                        </label>
                        <select id="assunto_profissional" class="form-control">
                            <option value="">Selecione um profissional</option>
                            <?php foreach ($assuntos_por_tipo['profissional'] as $assunto): ?>
                                <option value="<?php echo $assunto['id_assunto']; ?>">
                                    <?php echo htmlspecialchars($assunto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="validation-message" id="assunto-error"></div>

                    <div class="form-group">
                        <label for="enunciado">
                            <i class="fas fa-question-circle"></i> Enunciado da Questão
                        </label>
                        <div class="input-wrapper">
                            <textarea name="enunciado" id="enunciado" class="form-control"
                                      placeholder="Digite o enunciado da questão de forma clara e objetiva..."
                                      maxlength="1000"><?php echo htmlspecialchars($form_data['enunciado'] ?? ''); ?></textarea>
                            <div class="char-counter" id="enunciado-counter">0/1000</div>
                        </div>
                        <div class="validation-message" id="enunciado-error"></div>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-list"></i> Alternativas
                        </label>
                        <div class="alternatives-grid">
                            <?php 
                            $letters = ['A', 'B', 'C', 'D', 'E'];
                            $alt_names = ['alt1', 'alt2', 'alt3', 'alt4', 'alt5'];
                            for ($i = 0; $i < 5; $i++): 
                            ?>
                                <div class="alternative-item">
                                    <div class="alternative-label">
                                        <span class="alt-letter"><?php echo $letters[$i]; ?></span>
                                        Alternativa <?php echo $letters[$i]; ?>
                                        <?php if ($i == 4): ?>
                                            <span class="alt-optional">(Opcional)</span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="text" name="<?php echo $alt_names[$i]; ?>" 
                                           id="<?php echo $alt_names[$i]; ?>" class="form-control"
                                           placeholder="Digite a alternativa <?php echo $letters[$i]; ?>"
                                           maxlength="500"
                                           value="<?php echo htmlspecialchars($form_data[$alt_names[$i]] ?? ''); ?>"
                                           <?php if ($i == 4): ?>
                                           data-optional="true"
                                           <?php endif; ?>>
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
                            <i class="fas fa-check-circle"></i> Alternativa Correta
                        </label>
                        <div class="radio-group">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <div class="radio-item">
                                    <input type="radio" name="correta" id="correta<?php echo $i; ?>" 
                                           value="<?php echo $i; ?>"
                                           <?php echo (isset($form_data['correta']) && $form_data['correta'] == $i) ? 'checked' : ''; ?>>
                                    <label for="correta<?php echo $i; ?>" class="radio-label">
                                        Alternativa <?php echo chr(64 + $i); ?>
                                        <?php if ($i == 5): ?>
                                            <span class="alt-optional">(Opcional)</span>
                                        <?php endif; ?>
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
            const fields = ['id_assunto', 'enunciado', 'alt1', 'alt2', 'alt3', 'alt4', 'alt5'];
            
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

            // Remover validação obrigatória - agora apenas validações opcionais
            if (field.type === 'select-one' && value && !field.options[field.selectedIndex].value) {
                isValid = false;
                message = 'Selecione uma opção válida';
            }
            
            // Alternativa E (alt5) é opcional - não validar se estiver vazia
            if (field.id === 'alt5' && !value) {
                isValid = true; // Alternativa E pode estar vazia
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

            // Não é mais obrigatório selecionar alternativa correta
            correctaError.classList.remove('show');
            return true;
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
            const alternativas = ['alt1', 'alt2', 'alt3', 'alt4', 'alt5'].map(id => 
                document.getElementById(id).value
            );
            const correta = document.querySelector('input[name="correta"]:checked');
            const explicacao = document.getElementById('explicacao').value;

            let html = '<div style="border: 1px solid #e5e7eb; padding: 20px; border-radius: 8px; background: white;">';
            
            if (assunto.value) {
                html += `<div style="color: #6b7280; font-size: 0.9rem; margin-bottom: 10px;">
                    <strong>Conteúdo:</strong> ${assunto.options[assunto.selectedIndex].text}
                </div>`;
            }
            
            if (enunciado) {
                html += `<div style="font-weight: 600; margin-bottom: 15px; font-size: 1.1rem;">
                    ${enunciado}
                </div>`;
            }

            const letters = ['A', 'B', 'C', 'D', 'E'];
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

        // Limpar formulário após sucesso
        function clearFormAfterSuccess() {
            const form = document.getElementById('questionForm');
            
            // Limpar todos os campos do formulário
            form.reset();
            
            // Limpar mensagens de validação
            document.querySelectorAll('.validation-message').forEach(msg => {
                msg.classList.remove('show');
            });
            
            // Remover classes de erro
            document.querySelectorAll('.form-control').forEach(field => {
                field.classList.remove('error');
            });
            
            // Fechar pré-visualização se estiver aberta
            document.getElementById('preview-section').classList.remove('show');
            
            // Resetar contadores de caracteres
            setupCharCounters();
            
            // Focar no primeiro campo para facilitar nova entrada
            const firstField = document.getElementById('id_assunto');
            if (firstField) {
                firstField.focus();
            }
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

        // Função para voltar à página anterior
        function goBack() {
    // Sempre vai para a página index.php
    window.location.href = '../index.php';
}

        // Auto-save (localStorage)
        function setupAutoSave() {
            const form = document.getElementById('questionForm');
            const fields = ['enunciado', 'alt1', 'alt2', 'alt3', 'alt4', 'alt5', 'explicacao'];
            
            // Carregar dados salvos
            fields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                const saved = localStorage.getItem('questao_' + fieldName);
                if (saved && !field.value) {
                    field.value = saved;
                    }
                }
            });

            // Salvar automaticamente
            fields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                field.addEventListener('input', () => {
                    localStorage.setItem('questao_' + fieldName, field.value);
                });
                }
            });

            // Limpar localStorage após envio bem-sucedido
            <?php if ($mensagem_status === 'success'): ?>
                fields.forEach(fieldName => {
                    localStorage.removeItem('questao_' + fieldName);
                });
                // Limpar todos os campos do formulário
                clearFormAfterSuccess();
            <?php endif; ?>
        }

        // Validação do formulário antes do envio - agora opcional
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            // Debug: verificar se id_assunto está preenchido
            const idAssunto = document.getElementById('id_assunto');
            const tipoConteudo = document.getElementById('tipo_conteudo').value;
            
            console.log('Tipo de conteúdo selecionado:', tipoConteudo);
            console.log('ID Assunto sendo enviado:', idAssunto ? idAssunto.value : 'Elemento não encontrado');
            
            // Debug: verificar todos os campos de assunto
            console.log('assunto_tema value:', document.getElementById('assunto_tema')?.value);
            console.log('assunto_profissional value:', document.getElementById('assunto_profissional')?.value);
            console.log('id_assunto_concurso value:', document.getElementById('id_assunto_concurso')?.value);
            
            // Verificar se algum conteúdo foi selecionado baseado no tipo
            let conteudoSelecionado = false;
            
            if (tipoConteudo === 'tema') {
                const assuntoTema = document.getElementById('assunto_tema');
                if (assuntoTema && assuntoTema.value) {
                    idAssunto.value = assuntoTema.value;
                    conteudoSelecionado = true;
                    console.log('ID Assunto preenchido (tema):', assuntoTema.value);
                }
            } else if (tipoConteudo === 'profissional') {
                const assuntoProfissional = document.getElementById('assunto_profissional');
                if (assuntoProfissional && assuntoProfissional.value) {
                    idAssunto.value = assuntoProfissional.value;
                    conteudoSelecionado = true;
                    console.log('ID Assunto preenchido (profissional):', assuntoProfissional.value);
                }
            } else if (tipoConteudo === 'concurso') {
                const idAssuntoConcurso = document.getElementById('id_assunto_concurso');
                if (idAssuntoConcurso && idAssuntoConcurso.value) {
                    idAssunto.value = idAssuntoConcurso.value;
                    conteudoSelecionado = true;
                    console.log('ID Assunto preenchido (concurso):', idAssuntoConcurso.value);
                }
            }
            
            if (!conteudoSelecionado) {
                e.preventDefault();
                alert('Erro: Nenhum conteúdo foi selecionado. Por favor, selecione um conteúdo válido.');
                return false;
            }
            
            // Remover validação obrigatória - permitir envio com campos vazios
            // Apenas limpar mensagens de erro existentes
            document.querySelectorAll('.validation-message').forEach(msg => {
                msg.classList.remove('show');
            });
        });

        // Inicializar funcionalidades
        document.addEventListener('DOMContentLoaded', function() {
            setupCharCounters();
            setupValidation();
            setupAutoSave();
            
            // Inicializar seletores de conteúdo
            atualizarSeletoresConteudo();
            
            // Atualizar contador de sessão
            const sessionCount = parseInt(localStorage.getItem('session_questions') || '0');
            <?php if ($mensagem_status === 'success'): ?>
                const newCount = sessionCount + 1;
                localStorage.setItem('session_questions', newCount);
                document.getElementById('session-count').textContent = newCount;
            <?php else: ?>
                document.getElementById('session-count').textContent = sessionCount;
            <?php endif; ?>
            
            // Event listeners para atualizar id_assunto
            const assuntoTema = document.getElementById('assunto_tema');
            const assuntoProfissional = document.getElementById('assunto_profissional');
            const idAssunto = document.getElementById('id_assunto');
            
            if (assuntoTema && idAssunto) {
                assuntoTema.addEventListener('change', function() {
                    idAssunto.value = this.value;
                });
            }
            
            if (assuntoProfissional && idAssunto) {
                assuntoProfissional.addEventListener('change', function() {
                    idAssunto.value = this.value;
                });
            }
            
            // Event listeners para seletores de concurso
            const concursoOrgao = document.getElementById('concurso_orgao_sel');
            const concursoBanca = document.getElementById('concurso_banca_sel');
            const concursoAno = document.getElementById('concurso_ano_sel');
            const concursoProva = document.getElementById('concurso_prova_sel');
            
            if (concursoOrgao) {
                concursoOrgao.addEventListener('change', function() {
                    atualizarBancas();
                    atualizarAnos();
                    atualizarProvas();
                    selecionarAssuntoConcurso();
                });
            }
            
            if (concursoBanca) {
                concursoBanca.addEventListener('change', function() {
                    atualizarAnos();
                    atualizarProvas();
                    selecionarAssuntoConcurso();
                });
            }
            
            if (concursoAno) {
                concursoAno.addEventListener('change', function() {
                    atualizarProvas();
                    selecionarAssuntoConcurso();
                });
            }
            
            if (concursoProva) {
                concursoProva.addEventListener('change', function() {
                    selecionarAssuntoConcurso();
                });
            }
        });
        
        // Dados dos assuntos para JavaScript
        const assuntosPorTipo = <?php echo json_encode($assuntos_por_tipo); ?>;
        
        // Debug: Mostrar dados no console
        console.log('Dados carregados:', assuntosPorTipo);
        console.log('Concursos encontrados:', assuntosPorTipo.concurso);
        
        // Debug visual na página
        if (assuntosPorTipo.concurso.length === 0) {
            console.warn('⚠️ Nenhum concurso encontrado! Crie alguns concursos primeiro em add_assunto.php');
        }
        
        // Função para atualizar seletores de conteúdo
        function atualizarSeletoresConteudo() {
            const tipoConteudo = document.getElementById('tipo_conteudo').value;
            console.log('Tipo de conteúdo selecionado:', tipoConteudo);
            
            // Ocultar todos os grupos
            document.getElementById('grupo-temas').classList.remove('show');
            document.getElementById('grupo-concursos').classList.remove('show');
            document.getElementById('grupo-profissionais').classList.remove('show');
            
            // Limpar seleções
            const assuntoTema = document.getElementById('assunto_tema');
            const assuntoProfissional = document.getElementById('assunto_profissional');
            const idAssuntoConcurso = document.getElementById('id_assunto_concurso');
            const idAssunto = document.getElementById('id_assunto');
            
            if (assuntoTema) assuntoTema.value = '';
            if (assuntoProfissional) assuntoProfissional.value = '';
            if (idAssuntoConcurso) idAssuntoConcurso.value = '';
            if (idAssunto) idAssunto.value = ''; // Limpar campo principal também
            
            // Mostrar grupo apropriado
            if (tipoConteudo === 'tema') {
                console.log('Mostrando grupo de temas');
                document.getElementById('grupo-temas').classList.add('show');
            } else if (tipoConteudo === 'concurso') {
                console.log('Mostrando grupo de concursos');
                const grupoConcursos = document.getElementById('grupo-concursos');
                console.log('Elemento grupo-concursos encontrado:', grupoConcursos);
                if (grupoConcursos) {
                    grupoConcursos.classList.add('show');
                    console.log('Grupo de concursos exibido');
                } else {
                    console.error('Elemento grupo-concursos não encontrado!');
                }
            } else if (tipoConteudo === 'profissional') {
                console.log('Mostrando grupo de profissionais');
                document.getElementById('grupo-profissionais').classList.add('show');
            }
        }
        
        // Função para atualizar bancas baseado no órgão selecionado
        function atualizarBancas() {
            const orgaoSelect = document.getElementById('concurso_orgao_sel');
            const bancaSelect = document.getElementById('concurso_banca_sel');
            
            if (!orgaoSelect || !bancaSelect) return;
            
            const orgao = orgaoSelect.value;
            
            // Limpar opções
            bancaSelect.innerHTML = '<option value="">Selecione a banca</option>';
            
            if (orgao) {
                const bancas = [...new Set(assuntosPorTipo.concurso
                    .filter(a => a.concurso_orgao === orgao)
                    .map(a => a.concurso_banca)
                    .filter(b => b))];
                
                bancas.forEach(banca => {
                    const option = document.createElement('option');
                    option.value = banca;
                    option.textContent = banca;
                    bancaSelect.appendChild(option);
                });
            }
            
            // Limpar seleções dependentes
            atualizarAnos();
        }
        
        // Função para atualizar anos baseado na banca selecionada
        function atualizarAnos() {
            const orgaoSelect = document.getElementById('concurso_orgao_sel');
            const bancaSelect = document.getElementById('concurso_banca_sel');
            const anoSelect = document.getElementById('concurso_ano_sel');
            
            if (!orgaoSelect || !bancaSelect || !anoSelect) return;
            
            const orgao = orgaoSelect.value;
            const banca = bancaSelect.value;
            
            // Limpar opções
            anoSelect.innerHTML = '<option value="">Selecione o ano</option>';
            
            if (orgao && banca) {
                const anos = [...new Set(assuntosPorTipo.concurso
                    .filter(a => a.concurso_orgao === orgao && a.concurso_banca === banca)
                    .map(a => a.concurso_ano)
                    .filter(a => a)
                    .sort((a, b) => b - a))]; // Ordenar do mais recente para o mais antigo
                
                anos.forEach(ano => {
                    const option = document.createElement('option');
                    option.value = ano;
                    option.textContent = ano;
                    anoSelect.appendChild(option);
                });
            }
            
            // Limpar seleções dependentes
            atualizarProvas();
        }
        
        // Função para atualizar provas baseado no ano selecionado
        function atualizarProvas() {
            const orgaoSelect = document.getElementById('concurso_orgao_sel');
            const bancaSelect = document.getElementById('concurso_banca_sel');
            const anoSelect = document.getElementById('concurso_ano_sel');
            const provaSelect = document.getElementById('concurso_prova_sel');
            const idAssuntoConcurso = document.getElementById('id_assunto_concurso');
            
            if (!orgaoSelect || !bancaSelect || !anoSelect || !provaSelect) return;
            
            const orgao = orgaoSelect.value;
            const banca = bancaSelect.value;
            const ano = anoSelect.value;
            
            // Limpar opções
            provaSelect.innerHTML = '<option value="">Selecione a prova</option>';
            
            if (orgao && banca && ano) {
                const provas = [...new Set(assuntosPorTipo.concurso
                    .filter(a => a.concurso_orgao === orgao && a.concurso_banca === banca && a.concurso_ano === ano)
                    .map(a => a.concurso_prova)
                    .filter(p => p))];
                
                provas.forEach(prova => {
                    const option = document.createElement('option');
                    option.value = prova;
                    option.textContent = prova;
                    provaSelect.appendChild(option);
                });
            }
            
            // Limpar seleção de assunto
            if (idAssuntoConcurso) idAssuntoConcurso.value = '';
        }
        
        // Função para selecionar o assunto de concurso baseado nas seleções
        function selecionarAssuntoConcurso() {
            const orgaoSelect = document.getElementById('concurso_orgao_sel');
            const bancaSelect = document.getElementById('concurso_banca_sel');
            const anoSelect = document.getElementById('concurso_ano_sel');
            const provaSelect = document.getElementById('concurso_prova_sel');
            const idAssuntoConcurso = document.getElementById('id_assunto_concurso');
            const idAssunto = document.getElementById('id_assunto');
            
            if (!orgaoSelect || !bancaSelect || !anoSelect || !provaSelect) return;
            
            const orgao = orgaoSelect.value;
            const banca = bancaSelect.value;
            const ano = anoSelect.value;
            const prova = provaSelect.value;
            
            if (orgao && banca && ano && prova) {
                const assunto = assuntosPorTipo.concurso.find(a => 
                    a.concurso_orgao === orgao && 
                    a.concurso_banca === banca && 
                    a.concurso_ano === ano && 
                    a.concurso_prova === prova
                );
                
                if (assunto) {
                    if (idAssuntoConcurso) idAssuntoConcurso.value = assunto.id_assunto;
                    if (idAssunto) idAssunto.value = assunto.id_assunto;
                }
            }
        }
    </script>
</body>
</html>