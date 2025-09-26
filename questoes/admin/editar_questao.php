<?php
session_start();

// Verifica se o usuário é um administrador logado usando as variáveis corretas.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php';

$mensagem_status = '';
$questao = null;
$alternativas = [];
$assuntos = [];

if (isset($_GET['id'])) {
    $id_questao = (int)$_GET['id'];

    // Busca a questão e suas alternativas
    try {
        $stmt_questao = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
        $stmt_questao->execute([$id_questao]);
        $questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);

        if (!$questao) {
            $mensagem_status = '<p style="color: red;">Questão não encontrada.</p>';
        } else {
            $stmt_alternativas = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
            $stmt_alternativas->execute([$id_questao]);
            $alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
        }

        // Busca todos os assuntos para o dropdown
        $stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
        $assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $mensagem_status = '<p style="color: red;">Erro ao carregar os dados da questão: ' . $e->getMessage() . '</p>';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_questao'])) {
    if (!validate_csrf()) {
        $mensagem_status = '<p style="color:red;">Sessão expirada ou requisição inválida. Atualize a página e tente novamente.</p>';
    } else {
    $id_questao = (int)$_POST['id_questao'];
    $enunciado = trim($_POST['enunciado']);
    $id_assunto = $_POST['id_assunto'];
    $alternativas_post = $_POST['alternativas'];
    $correta_index = (int)$_POST['correta'];

    if (empty($enunciado) || empty($id_assunto) || empty($alternativas_post)) {
        $mensagem_status = '<p style="color:red;">Por favor, preencha todos os campos obrigatórios.</p>';
    } else {
        try {
            $pdo->beginTransaction();

            // Atualiza a questão
            $stmt_update_questao = $pdo->prepare("UPDATE questoes SET enunciado = ?, id_assunto = ? WHERE id_questao = ?");
            $stmt_update_questao->execute([$enunciado, $id_assunto, $id_questao]);

            // Atualiza as alternativas
            $stmt_update_alternativa = $pdo->prepare("UPDATE alternativas SET texto = ?, correta = ? WHERE id_alternativa = ?");
            foreach ($alternativas_post as $id_alternativa => $texto) {
                $correta = ($id_alternativa == $correta_index) ? 1 : 0;
                $stmt_update_alternativa->execute([trim($texto), $correta, $id_alternativa]);
            }

            $pdo->commit();
            $mensagem_status = '<p style="color:green;">Questão atualizada com sucesso!</p>';
            // Recarrega os dados da questão atualizada
            header('Location: editar_questao.php?id=' . $id_questao);
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
    <title>Editar Questão</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .conteudo-principal { max-width: 900px; margin: 173px auto 10px auto; background-color: #FFFFFF; padding: 20px; box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.432); border-radius: 10px; }
        form { text-align: left; }
        label, input, textarea, select { display: block; width: 100%; margin-bottom: 10px; }
        textarea { height: 100px; }
        .alternativa-row { display: flex; gap: 10px; align-items: center; padding: 8px 10px; border: 1px solid #e3e8ef; border-radius: 8px; }
        .alternativa-row input[type="text"] { flex: 1; }
        .alternativa-row .alt-letra { width: 28px; text-align: center; font-weight: 700; color: #0072FF; }
        .alternativa-row input[type="radio"] { width: 20px; height: 20px; accent-color: #0072FF; cursor: pointer; }
        .alternativas-header { display:flex; align-items:center; gap:10px; margin: 8px 0; font-weight: 700; color: #333; }
        .alternativa-row.correta { border-color: #4CAF50; background-color: #f1fbf2; }
        .actions-right { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    </style>
    </head>
<body>
    <header>
        <h1>Editar Questão</h1>
        <p>Atualize os dados da questão selecionada.</p>
    </header>

    <main class="conteudo-principal">
        <?php if (!empty($mensagem_status)): ?>
            <?= $mensagem_status ?>
        <?php endif; ?>

        <?php if (!$questao): ?>
            <p>Selecione uma questão válida a partir de <a href="gerenciar_questoes.php">Gerenciar Questões</a>.</p>
        <?php else: ?>
            <form action="editar_questao.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id_questao" value="<?= htmlspecialchars($questao['id_questao']) ?>">

                <label for="id_assunto">Assunto:</label>
                <select id="id_assunto" name="id_assunto" required>
                    <?php foreach ($assuntos as $assunto): ?>
                        <option value="<?= htmlspecialchars($assunto['id_assunto']) ?>" <?= ($assunto['id_assunto'] == $questao['id_assunto']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($assunto['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="enunciado">Enunciado da Questão:</label>
                <textarea id="enunciado" name="enunciado" required><?= htmlspecialchars($questao['enunciado']) ?></textarea>

                <label>Alternativas (marque a correta):</label>
                <div class="alternativas-header">
                    <span class="alt-letra">#</span>
                    <span>Texto da alternativa</span>
                </div>
                <?php $letras = ['A','B','C','D','E','F']; foreach ($alternativas as $i => $alt): ?>
                    <div class="alternativa-row <?= $alt['correta'] ? 'correta' : '' ?>">
                        <span class="alt-letra"><?= $letras[$i] ?? ($i+1) ?></span>
                        <input type="text" name="alternativas[<?= htmlspecialchars($alt['id_alternativa']) ?>]" value="<?= htmlspecialchars($alt['texto']) ?>" required>
                        <input type="radio" name="correta" value="<?= htmlspecialchars($alt['id_alternativa']) ?>" <?= ($alt['correta'] ? 'checked' : '') ?> title="Marcar <?= $letras[$i] ?? ($i+1) ?> como correta">
                    </div>
                <?php endforeach; ?>

                <div class="actions-right">
                    <a href="gerenciar_questoes.php" class="btn btn-outline">Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>

<script>
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