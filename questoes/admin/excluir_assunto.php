<?php
session_start();
require_once __DIR__ . '/../conexao.php';

// Redireciona para a página de login se o usuário não estiver logado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é um administrador
if ($_SESSION['tipo_usuario'] !== 'admin') {
    echo "Acesso negado. Você não tem permissão para realizar esta ação.";
    exit();
}

// Verifica se o método da requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. **Verificação CSRF:** Valida o token de segurança enviado pelo formulário
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Se o token for inválido, redireciona para a página principal com mensagem de erro
        header("Location: ../index.php?erro=csrf");
        exit();
    }

    // 2. **Verifica o ID:** Confirma se o ID do assunto foi enviado no formulário
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id_assunto = $_POST['id'];

        try {
            // Prepara e executa a query DELETE para excluir o assunto
            $stmt = $pdo->prepare("DELETE FROM assuntos WHERE id_assunto = ?");
            $stmt->execute([$id_assunto]);

            // Redireciona para a página principal após a exclusão bem-sucedida
            header("Location: ../index.php");
            exit();
        } catch (PDOException $e) {
            // Em caso de erro no banco de dados, exibe uma mensagem
            echo "Erro ao excluir o assunto: " . $e->getMessage();
        }
    } else {
        // Se o ID não foi fornecido no formulário, exibe uma mensagem de erro
        echo "ID do assunto não especificado.";
    }
} else {
    // Se a requisição não foi via POST, exibe uma mensagem de erro
    echo "Acesso negado. Esta ação só pode ser realizada via POST.";
}
?>