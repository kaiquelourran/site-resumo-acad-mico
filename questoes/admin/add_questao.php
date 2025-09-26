<?php
session_start();

// Verifica se o usuário é um administrador logado.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php'; // Caminho para o arquivo conexao.php

$mensagem_status = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $enunciado = trim($_POST['enunciado']);
    $id_assunto = $_POST['id_assunto'];
    $alternativas = [
        trim($_POST['alt1']),
        trim($_POST['alt2']),
        trim($_POST['alt3']),
        trim($_POST['alt4'])
    ];
    $correta_index = (int)$_POST['correta'];

    if (empty($enunciado) || empty($alternativas[$correta_index - 1]) || empty($id_assunto)) {
        $mensagem_status = '<p style="color:red;">Por favor, preencha o enunciado, a alternativa correta e selecione um assunto.</p>';
    } else {
        try {
            $pdo->beginTransaction();

            // Insere a questão, incluindo o id_assunto
            $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, id_assunto) VALUES (?, ?)");
            $stmt->execute([$enunciado, $id_assunto]);
            $id_questao = $pdo->lastInsertId();

            // Insere as alternativas
            $sql_alternativas = "INSERT INTO alternativas (id_questao, texto, correta) VALUES (?, ?, ?)";
            $stmt_alternativas = $pdo->prepare($sql_alternativas);

            foreach ($alternativas as $index => $texto) {
                $correta = ($index + 1 == $correta_index) ? 1 : 0;
                $stmt_alternativas->execute([$id_questao, $texto, $correta]);
            }

            $pdo->commit();
            $mensagem_status = '<p style="color:green;">Questão adicionada com sucesso!</p>';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_status = '<p style="color:red;">Erro ao adicionar a questão: ' . $e->getMessage() . '</p>';
        }
    }
}

// Busca os assuntos para preencher o campo de seleção
$stmt_assuntos = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY nome");
$assuntos = $stmt_assuntos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Questão</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .conteudo-principal {
            max-width: 900px;
            margin: 173px auto 10px auto;
            background-color: #FFFFFF;
            padding: 20px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.432);
            border-radius: 10px;
        }
        form {
            text-align: left;
        }
        label, input, textarea, select {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
        textarea {
            height: 100px;
        }
        .actions-right { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }
    </style>
</head>
<body>
    <header>
        <h1>Adicionar Nova Questão</h1>
        <p>Preencha os dados da nova questão.</p>
    </header>

    <main class="conteudo-principal">
        <?= $mensagem_status ?>
        <form action="add_questao.php" method="post">
            <?php echo csrf_field(); ?>
            
            <label for="id_assunto">Assunto:</label>
            <select id="id_assunto" name="id_assunto" required>
                <?php foreach ($assuntos as $assunto): ?>
                    <option value="<?= htmlspecialchars($assunto['id_assunto']) ?>"><?= htmlspecialchars($assunto['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="enunciado">Enunciado da Questão:</label>
            <textarea id="enunciado" name="enunciado" required></textarea>
            
            <label for="alt1">Alternativa A:</label>
            <input type="text" id="alt1" name="alt1" required>
            
            <label for="alt2">Alternativa B:</label>
            <input type="text" id="alt2" name="alt2" required>

            <label for="alt3">Alternativa C:</label>
            <input type="text" id="alt3" name="alt3" required>

            <label for="alt4">Alternativa D:</label>
            <input type="text" id="alt4" name="alt4" required>
            
            <label for="correta">Qual é a alternativa correta?</label>
            <select id="correta" name="correta" required>
                <option value="1">A</option>
                <option value="2">B</option>
                <option value="3">C</option>
                <option value="4">D</option>
            </select>
            
            <div class="actions-right">
                <a href="dashboard.php" class="btn btn-outline">Voltar</a>
                <button type="submit" class="btn btn-success btn-lg">Adicionar Questão</button>
            </div>
        </form>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
</body>
</html>