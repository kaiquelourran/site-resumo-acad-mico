<?php
session_start();
require_once __DIR__ . '/../conexao.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem_status = '';
$questao = null;
$alternativas = [];
$assuntos = [];

// Buscar todos os assuntos para o select
try {
    $stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
    $assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem_status = '<p style="color:red;">Erro ao buscar assuntos: ' . $e->getMessage() . '</p>';
}

// Verificar se foi passado um ID de questão
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_questao = (int)$_GET['id'];
    
    try {
        // Buscar dados da questão
        $stmt_questao = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
        $stmt_questao->execute([$id_questao]);
        $questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);
        
        if ($questao) {
            // Buscar alternativas da questão
            $stmt_alternativas = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
            $stmt_alternativas->execute([$id_questao]);
            $alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $mensagem_status = '<p style="color:red;">Erro ao buscar questão: ' . $e->getMessage() . '</p>';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_questao'])) {
    if (!validate_csrf()) {
        $mensagem_status = '<p style="color:red;">Sessão expirada ou requisição inválida. Atualize a página e tente novamente.</p>';
    } else {
    $id_questao = (int)$_POST['id_questao'];
    $enunciado = trim($_POST['enunciado']);
    $explicacao = trim($_POST['explicacao'] ?? '');
    $id_assunto = $_POST['id_assunto'];
    $alternativas_post = $_POST['alternativas'];
    $correta_index = (int)$_POST['correta'];

    if (empty($enunciado) || empty($id_assunto) || empty($alternativas_post)) {
        $mensagem_status = '<p style="color:red;">Por favor, preencha todos os campos obrigatórios.</p>';
    } else {
        try {
            $pdo->beginTransaction();

            // Atualizar questão
            $stmt_update = $pdo->prepare("UPDATE questoes SET enunciado = ?, explicacao = ?, id_assunto = ? WHERE id_questao = ?");
            $stmt_update->execute([$enunciado, $explicacao, $id_assunto, $id_questao]);

            // Atualizar alternativas
            $stmt_update_alternativa = $pdo->prepare("UPDATE alternativas SET texto = ?, eh_correta = ? WHERE id_alternativa = ?");
            foreach ($alternativas_post as $id_alternativa => $texto) {
                $eh_correta = ($id_alternativa == $correta_index) ? 1 : 0;
                $stmt_update_alternativa->execute([trim($texto), $eh_correta, $id_alternativa]);
            }

            $pdo->commit();
            $mensagem_status = '<p style="color:green;">Questão atualizada com sucesso!</p>';
            // Recarrega os dados da questão atualizada
            header('Location: /admin/gerenciar_questoes_sem_auth.php?status=updated');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_status = '<p style="color:red;">Erro ao atualizar a questão: ' . $e->getMessage() . '</p>';
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
    <title>Editar Questão - Resumo Acadêmico</title>
    <link rel="stylesheet" href="../modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00C6FF;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .alternativas-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        
        .alternativas-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
        }
        
        .alternativa-row {
            display: flex;
            gap: 15px;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            background: white;
            transition: all 0.3s ease;
        }
        
        .alternativa-row:hover {
            border-color: #00C6FF;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }
        
        .alternativa-row.correta {
            border-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        
        .alt-letra {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            color: white;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.1em;
        }
        
        .alternativa-row.correta .alt-letra {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .alternativa-row input[type="text"] {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 1em;
            padding: 8px 12px;
        }
        
        .alternativa-row input[type="text"]:focus {
            background: rgba(102, 126, 234, 0.05);
            border-radius: 6px;
        }
        
        .alternativa-row input[type="radio"] {
            width: 24px;
            height: 24px;
            accent-color: #00C6FF;
            cursor: pointer;
            transform: scale(1.2);
        }
        
        .actions-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
        }
        
        .status-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .status-message.success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .header {
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
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
            <div class="logo">📚</div>
            <h1 class="title">Editar Questão</h1>
            <p class="subtitle">Atualize os dados da questão selecionada</p>
        </div>

        <div class="form-container">
            <?php if (!empty($mensagem_status)): ?>
                <div class="status-message <?= strpos($mensagem_status, 'sucesso') !== false ? 'success' : 'error' ?>">
                    <?= strip_tags($mensagem_status) ?>
                </div>
            <?php endif; ?>

            <?php if (!$questao): ?>
                <div class="status-message error">
                    <p>Selecione uma questão válida a partir de <a href="../gerenciar_questoes_sem_auth.php" class="btn btn-primary">Gerenciar Questões</a>.</p>
                </div>
            <?php else: ?>
                <form action="editar_questao.php" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_questao" value="<?= htmlspecialchars($questao['id_questao']) ?>">

                    <div class="form-group">
                        <label for="id_assunto">📋 Assunto:</label>
                        <select id="id_assunto" name="id_assunto" required>
                            <?php foreach ($assuntos as $assunto): ?>
                                <option value="<?= htmlspecialchars($assunto['id_assunto']) ?>" <?= ($assunto['id_assunto'] == $questao['id_assunto']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($assunto['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="enunciado">❓ Enunciado da Questão:</label>
                        <textarea id="enunciado" name="enunciado" required placeholder="Digite o enunciado da questão..."><?= htmlspecialchars($questao['enunciado']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="explicacao">💡 Explicação (opcional):</label>
                        <textarea id="explicacao" name="explicacao" placeholder="Digite uma explicação para a resposta..."><?= htmlspecialchars($questao['explicacao'] ?? '') ?></textarea>
                    </div>

                    <div class="alternativas-section">
                        <div class="alternativas-header">
                            <span>✅ Alternativas</span>
                            <small style="color: #666;">(marque a alternativa correta)</small>
                        </div>
                        
                        <?php $letras = ['A','B','C','D','E','F']; foreach ($alternativas as $i => $alt): ?>
                            <div class="alternativa-row <?= $alt['eh_correta'] ? 'correta' : '' ?>">
                                <div class="alt-letra"><?= $letras[$i] ?? ($i+1) ?></div>
                                <input type="text" name="alternativas[<?= htmlspecialchars($alt['id_alternativa']) ?>]" value="<?= htmlspecialchars($alt['texto']) ?>" required placeholder="Digite a alternativa <?= $letras[$i] ?? ($i+1) ?>...">
                                <input type="radio" name="correta" value="<?= htmlspecialchars($alt['id_alternativa']) ?>" <?= ($alt['eh_correta'] ? 'checked' : '') ?> title="Marcar <?= $letras[$i] ?? ($i+1) ?> como correta">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="actions-container">
                        <div class="btn-group">
                            <a href="../gerenciar_questoes_sem_auth.php" class="btn btn-outline">
                                ← Voltar para Gerenciar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                💾 Salvar Alterações
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Função para voltar à página anterior
        function goBack() {
            // Verifica se há histórico de navegação
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não há histórico, vai para a página principal
                window.location.href = '../../index.php';
            }
        }
    </script>
</body>
</html>
// Destaque visual da alternativa correta e confirmação ao salvar
document.addEventListener('DOMContentLoaded', function () {
    var radios = document.querySelectorAll('input[type="radio"][name="correta"]');
    function marcarCorreta() {
        document.querySelectorAll('.alternativa-row').forEach(function(row){ row.classList.remove('correta'); });
        var selecionado = document.querySelector('input[type="radio"][name="correta"]:checked');
        if (selecionado) {
            var row = selecionado.closest('.alternativa-row');
            if (row) { row.classList.add('correta'); }
        }
    }
    radios.forEach(function(r){ r.addEventListener('change', marcarCorreta); });
    marcarCorreta();

    var form = document.querySelector('form[action="editar_questao.php"]');
    if (form) {
        form.addEventListener('submit', function(e){
            if(!confirm('Confirmar salvar alterações desta questão?')) {
                e.preventDefault();
            }
        });
    }
});
</script>