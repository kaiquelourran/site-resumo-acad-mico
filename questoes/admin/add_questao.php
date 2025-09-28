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
        $mensagem_status = 'error';
        $mensagem_texto = 'Por favor, preencha o enunciado, a alternativa correta e selecione um assunto.';
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
            $mensagem_status = 'success';
            $mensagem_texto = 'Questão adicionada com sucesso!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem_status = 'error';
            $mensagem_texto = 'Erro ao adicionar a questão: ' . $e->getMessage();
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
    <title>Adicionar Questão - Resumo Acadêmico</title>
    <link rel="stylesheet" href="../modern-style.css">
</head>
<body>
    <div class="main-container fade-in">
        <header class="header">
            <div class="logo">
                <img src="../../fotos/Logotipo_resumo_academico.png" alt="Resumo Acadêmico">
            </div>
            <div class="title-section">
                <h1>Adicionar Nova Questão</h1>
                <p class="subtitle">Preencha os dados da nova questão</p>
            </div>
        </header>

        <div class="user-info">
            <a href="dashboard.php" class="btn btn-outline">Voltar ao Dashboard</a>
        </div>

        <main class="content">
            <?php if (!empty($mensagem_status)): ?>
                <div class="alert alert-<?= $mensagem_status ?> fade-in">
                    <?= htmlspecialchars($mensagem_texto) ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form action="add_questao.php" method="post" class="modern-form">
                    <?php echo csrf_field(); ?>
                    
                    <div class="form-group">
                        <label for="id_assunto">Assunto:</label>
                        <select id="id_assunto" name="id_assunto" required class="form-control">
                            <option value="">Selecione um assunto</option>
                            <?php foreach ($assuntos as $assunto): ?>
                                <option value="<?= htmlspecialchars($assunto['id_assunto']) ?>"><?= htmlspecialchars($assunto['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="enunciado">Enunciado da Questão:</label>
                        <textarea id="enunciado" name="enunciado" required class="form-control" rows="4" placeholder="Digite o enunciado da questão..."></textarea>
                    </div>
                    
                    <div class="alternatives-grid">
                        <div class="form-group">
                            <label for="alt1">Alternativa A:</label>
                            <input type="text" id="alt1" name="alt1" required class="form-control" placeholder="Digite a alternativa A">
                        </div>
                        
                        <div class="form-group">
                            <label for="alt2">Alternativa B:</label>
                            <input type="text" id="alt2" name="alt2" required class="form-control" placeholder="Digite a alternativa B">
                        </div>

                        <div class="form-group">
                            <label for="alt3">Alternativa C:</label>
                            <input type="text" id="alt3" name="alt3" required class="form-control" placeholder="Digite a alternativa C">
                        </div>

                        <div class="form-group">
                            <label for="alt4">Alternativa D:</label>
                            <input type="text" id="alt4" name="alt4" required class="form-control" placeholder="Digite a alternativa D">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="correta">Qual é a alternativa correta?</label>
                        <select id="correta" name="correta" required class="form-control">
                            <option value="">Selecione a alternativa correta</option>
                            <option value="1">A</option>
                            <option value="2">B</option>
                            <option value="3">C</option>
                            <option value="4">D</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-success">Adicionar Questão</button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="footer">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </footer>
    </div>
</body>
</html>