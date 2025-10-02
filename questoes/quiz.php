<?php
session_start();

require_once __DIR__ . '/conexao.php';

// Define o número de questões no início do arquivo
$numero_de_questoes_por_quiz = 5;

// Sempre atualiza o ID do assunto da sessão com base no que foi passado pela URL
if (isset($_GET['id'])) {
    $_SESSION['quiz_progress']['id_assunto'] = (int)$_GET['id'];
}

// Inicializa a sessão para o quiz, se ainda não estiver
if (!isset($_SESSION['quiz_progress']) || isset($_GET['novo'])) {
    $_SESSION['quiz_progress'] = [
        'acertos' => 0,
        'respondidas' => [],
        'id_assunto' => isset($_GET['id']) ? (int)$_GET['id'] : 0,
    ];
}

// Redireciona para a página de resultados se o número de questões foi alcançado
if (count($_SESSION['quiz_progress']['respondidas']) >= $numero_de_questoes_por_quiz) {
    header('Location: resultado.php');
    exit;
}

// Busca uma questão aleatória que ainda não foi respondida na sessão
$id_assunto_atual = $_SESSION['quiz_progress']['id_assunto'];
$questoes_respondidas = $_SESSION['quiz_progress']['respondidas'];
$sql = "SELECT * FROM questoes WHERE 1=1";
$params = [];

if ($id_assunto_atual > 0) {
    $sql .= " AND id_assunto = ?";
    $params[] = $id_assunto_atual;
}

if (!empty($questoes_respondidas)) {
    $placeholders = implode(',', array_fill(0, count($questoes_respondidas), '?'));
    $sql .= " AND id_questao NOT IN ($placeholders)";
    $params = array_merge($params, $questoes_respondidas);
}

$sql .= " ORDER BY RAND() LIMIT 1";

$stmt_questao = $pdo->prepare($sql);
$stmt_questao->execute($params);
$questao = $stmt_questao->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    $stmt_alternativas = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY RAND()");
    $stmt_alternativas->execute([$questao['id_questao']]);
    $alternativas = $stmt_alternativas->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <h1>Quiz Interativo</h1>
        <div class="login-area">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="perfil_usuario.php" class="btn btn-outline btn-sm" aria-label="Ver meu desempenho">Meu Desempenho</a>
                <a href="logout.php" class="btn btn-danger btn-sm" aria-label="Sair da conta">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm">Login</a>
                <a href="cadastro.php" class="btn btn-primary btn-sm">Cadastro</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="conteudo-principal">
        <?php if ($questao): ?>
            <div class="placar-progresso">
                <p>Pontuação: <span id="placar-pontos"><?= htmlspecialchars($_SESSION['quiz_progress']['acertos']) ?></span></p>
                <p>Questão <span id="questao-atual"><?= count($_SESSION['quiz_progress']['respondidas']) + 1 ?></span> de <span id="total-questoes"><?= $numero_de_questoes_por_quiz ?></span></p>
            </div>
            <div class="barra-progresso">
                <div class="progresso" id="barra-progresso"></div>
            </div>

            <div class="card-questao">
                <h2><?= htmlspecialchars($questao['enunciado']) ?></h2>
                <div class="alternativas-list" data-id-questao="<?= htmlspecialchars($questao['id_questao']) ?>">
                    <?php foreach ($alternativas as $alternativa): ?>
                        <label 
                            for="alt-<?= htmlspecialchars($alternativa['id_alternativa']) ?>"
                            data-id-alternativa="<?= htmlspecialchars($alternativa['id_alternativa']) ?>"
                            data-correta="<?= $alternativa['correta'] == 1 ? 'true' : 'false' ?>">
                            <input type="radio" id="alt-<?= htmlspecialchars($alternativa['id_alternativa']) ?>" name="resposta" value="<?= htmlspecialchars($alternativa['id_alternativa']) ?>">
                            <?= htmlspecialchars($alternativa['texto']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mensagem-status" id="feedback-mensagem"></div>
            <button id="botao-proxima" class="botao-proxima-questao">Próxima Questão</button>

        <?php else: ?>
            <p>Nenhuma questão encontrada para este assunto. Por favor, adicione mais questões ou volte para a página inicial.</p>
        <?php endif; ?>
        
        <div class="actions-right">
            <a href="index.php?novo=1" class="btn btn-outline voltar-link">Voltar aos Assuntos</a>
        </div>
    </main>

    <footer>
        <div class="footer-creditos">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </div>
    </footer>
    
    <script src="quiz.js"></script>
</body>
</html>