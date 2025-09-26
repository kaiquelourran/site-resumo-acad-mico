<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Redireciona para o login se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'];
    $id_questao = $_POST['id_questao'];
    $acertou = $_POST['acertou'];

    // Define o fuso horário para garantir que a hora esteja correta
    date_default_timezone_set('America/Sao_Paulo');

    // Pega a data e hora atual no formato do MySQL
    $data_resposta = date("Y-m-d H:i:s");

    try {
        // Inserir a resposta no banco de dados, incluindo a data e hora
        $stmt = $pdo->prepare("INSERT INTO respostas_usuarios (id_usuario, id_questao, acertou, data_resposta) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_usuario, $id_questao, $acertou, $data_resposta]);

        // Resposta de sucesso (opcional, mas bom para depuração)
        echo json_encode(['status' => 'success', 'message' => 'Resposta salva com sucesso.']);
    } catch (PDOException $e) {
        // Resposta de erro
        echo json_encode(['status' => 'error', 'message' => 'Erro ao salvar a resposta: ' . $e->getMessage()]);
    }
} else {
    // Resposta de erro se a requisição não for POST
    echo json_encode(['status' => 'error', 'message' => 'Método de requisição inválido.']);
}
?>