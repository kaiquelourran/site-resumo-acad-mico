<?php
session_start();
require_once __DIR__ . '/../conexao.php'; // Caminho para o arquivo conexao.php

// Debug: Log para verificar se o arquivo está sendo executado
error_log("deletar_questao.php: Arquivo executado");

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("deletar_questao.php: Requisição POST recebida");
    
    // Debug: Verificar dados POST
    error_log("deletar_questao.php: POST data: " . print_r($_POST, true));
    
    // Verifica CSRF separadamente para melhor tratamento de erro
    if (!validate_csrf()) {
        error_log("deletar_questao.php: Erro de CSRF");
        // Erro de CSRF - redireciona com mensagem específica
        header('Location: ../gerenciar_questoes_sem_auth.php?status=csrf_error');
        exit;
    }
    
    error_log("deletar_questao.php: CSRF válido");
    
    // Verifica se o ID foi fornecido
    if (!isset($_POST['id'])) {
        error_log("deletar_questao.php: ID não fornecido");
        header('Location: ../gerenciar_questoes_sem_auth.php?status=no_id');
        exit;
    }
    
    $id_questao = (int)$_POST['id'];
    error_log("deletar_questao.php: ID da questão: " . $id_questao);
    
    // Inicia uma transação para garantir que a exclusão seja segura
    $pdo->beginTransaction();
    error_log("deletar_questao.php: Transação iniciada");

    try {
        // Primeiro, deleta as respostas dos usuários relacionadas à questão
        $stmt_respostas = $pdo->prepare("DELETE FROM respostas_usuarios WHERE id_questao = ?");
        $stmt_respostas->execute([$id_questao]);
        error_log("deletar_questao.php: Respostas dos usuários deletadas");

        // Segundo, deleta as alternativas da questão
        $stmt_alternativas = $pdo->prepare("DELETE FROM alternativas WHERE id_questao = ?");
        $stmt_alternativas->execute([$id_questao]);
        error_log("deletar_questao.php: Alternativas deletadas");

        // Por último, deleta a questão em si
        $stmt_questao = $pdo->prepare("DELETE FROM questoes WHERE id_questao = ?");
        $stmt_questao->execute([$id_questao]);
        error_log("deletar_questao.php: Questão deletada");

        $pdo->commit();
        error_log("deletar_questao.php: Transação commitada com sucesso");
        
        // Redireciona de volta para a página de gerenciamento com uma mensagem de sucesso
        header('Location: ../gerenciar_questoes_sem_auth.php?status=deleted');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("deletar_questao.php: Erro na transação: " . $e->getMessage());
        // Redireciona com uma mensagem de erro em caso de falha
        header('Location: ../gerenciar_questoes_sem_auth.php?status=error');
        exit;
    }
} else {
    // Requisição inválida
    header('Location: ../gerenciar_questoes_sem_auth.php?status=invalid');
    exit;
}
?>