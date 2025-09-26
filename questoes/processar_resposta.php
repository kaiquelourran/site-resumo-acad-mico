<?php
session_start();
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json');

// Verifica se o usuário está logado; se não estiver, não grava no banco, apenas responde.
$usuario_logado = isset($_SESSION['id_usuario']);
$id_usuario = $usuario_logado ? $_SESSION['id_usuario'] : null;

// Inicializa a sessão de progresso se ainda não existir
if (!isset($_SESSION['quiz_progress'])) {
    $_SESSION['quiz_progress'] = ['acertos' => 0, 'respondidas' => []];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_questao = isset($_POST['id_questao']) ? (int)$_POST['id_questao'] : 0;
    $id_alternativa_selecionada = isset($_POST['id_alternativa']) ? (int)$_POST['id_alternativa'] : 0;

    if ($id_questao > 0 && $id_alternativa_selecionada > 0) {
        // Encontra a alternativa correta no banco de dados
        $stmt_correta = $pdo->prepare("SELECT id_alternativa FROM alternativas WHERE id_questao = ? AND correta = 1");
        $stmt_correta->execute([$id_questao]);
        $alternativa_correta = $stmt_correta->fetch(PDO::FETCH_ASSOC);

        $resposta_correta = false;
        if ($alternativa_correta && $alternativa_correta['id_alternativa'] === $id_alternativa_selecionada) {
            $resposta_correta = true;
            // Apenas incrementa se o usuário acertou
            $_SESSION['quiz_progress']['acertos']++;
        }

        // Define o fuso horário
        date_default_timezone_set('America/Sao_Paulo');

        // Pega a data e hora atual
        $data_resposta = date("Y-m-d H:i:s");

        try {
            if ($usuario_logado) {
                // Insere a resposta apenas para usuários logados
                $stmt_salvar = $pdo->prepare("INSERT INTO respostas_usuarios (id_usuario, id_questao, acertou, data_resposta) VALUES (?, ?, ?, ?)");
                $stmt_salvar->execute([$id_usuario, $id_questao, $resposta_correta ? 1 : 0, $data_resposta]);
            }

            // Adiciona a questão ao array de respondidas na sessão
            $_SESSION['quiz_progress']['respondidas'][] = $id_questao;

            // Retorna a resposta para o JavaScript
            echo json_encode([
                'sucesso' => true,
                'correta' => $resposta_correta,
                'acertos' => $_SESSION['quiz_progress']['acertos']
            ]);
            exit;
        } catch (PDOException $e) {
            // Exibe o erro completo do banco de dados
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar a resposta. Mensagem: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Código para lidar com requisições inválidas
http_response_code(400);
echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
?>