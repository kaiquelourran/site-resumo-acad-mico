<?php
require 'conexao.php';

echo "Iniciando atualização de user_id nas respostas existentes...\n";

// Verificar se a coluna user_id existe
try {
    $colunas = $pdo->query("DESCRIBE respostas_usuario")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_id', $colunas)) {
        echo "Erro: A coluna user_id não existe na tabela respostas_usuario.\n";
        exit;
    }
} catch (Exception $e) {
    echo "Erro ao verificar estrutura da tabela: " . $e->getMessage() . "\n";
    exit;
}

// Verificar se há respostas sem user_id
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id IS NULL");
    $total_sem_user_id = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de respostas sem user_id: $total_sem_user_id\n";
} catch (Exception $e) {
    echo "Erro ao contar respostas sem user_id: " . $e->getMessage() . "\n";
    exit;
}

// Verificar se há usuários na tabela usuarios
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de usuários na tabela usuarios: $total_usuarios\n";
    
    if ($total_usuarios > 0) {
        // Obter o primeiro usuário (admin ou outro usuário padrão)
        $stmt = $pdo->query("SELECT id_usuario FROM usuarios ORDER BY id_usuario LIMIT 1");
        $usuario_padrao = $stmt->fetch(PDO::FETCH_ASSOC)['id_usuario'];
        echo "ID do usuário padrão: $usuario_padrao\n";
        
// Atualizar respostas sem user_id para usar o usuário padrão
        echo "Atualizando respostas sem user_id para usar o usuário padrão...\n";
        
        // Obter todas as respostas sem user_id
        $stmt = $pdo->query("SELECT id, id_questao FROM respostas_usuario WHERE user_id IS NULL");
        $respostas_sem_user_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $atualizadas = 0;
        $erros = 0;
        
        // Atualizar cada resposta individualmente
        foreach ($respostas_sem_user_id as $resposta) {
            try {
                // Verificar se já existe uma resposta para este usuário e questão
                $stmt_check = $pdo->prepare("SELECT id FROM respostas_usuario WHERE user_id = ? AND id_questao = ?");
                $stmt_check->execute([$usuario_padrao, $resposta['id_questao']]);
                $existe = $stmt_check->fetch();
                
                if ($existe) {
                    // Se já existe, remover a resposta sem user_id
                    $stmt_delete = $pdo->prepare("DELETE FROM respostas_usuario WHERE id = ?");
                    $stmt_delete->execute([$resposta['id']]);
                    echo "Resposta duplicada removida: id = " . $resposta['id'] . ", id_questao = " . $resposta['id_questao'] . "\n";
                } else {
                    // Se não existe, atualizar o user_id
                    $stmt_update = $pdo->prepare("UPDATE respostas_usuario SET user_id = ? WHERE id = ?");
                    $stmt_update->execute([$usuario_padrao, $resposta['id']]);
                    $atualizadas++;
                }
            } catch (Exception $e) {
                echo "Erro ao processar resposta id = " . $resposta['id'] . ": " . $e->getMessage() . "\n";
                $erros++;
            }
        }
        
        echo "Respostas atualizadas com user_id = $usuario_padrao: $atualizadas\n";
        echo "Erros durante a atualização: $erros\n";
    } else {
        echo "Não há usuários na tabela usuarios. Não é possível atualizar as respostas.\n";
    }
} catch (Exception $e) {
    echo "Erro ao atualizar respostas: " . $e->getMessage() . "\n";
}

// Verificar o resultado final
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id IS NULL");
    $total_sem_user_id = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de respostas sem user_id após atualização: $total_sem_user_id\n";
    
    $stmt = $pdo->query("SELECT user_id, COUNT(*) as total FROM respostas_usuario GROUP BY user_id");
    echo "\nDistribuição de respostas por user_id:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user_id = $row['user_id'] ?? 'NULL';
        $total = $row['total'];
        echo "user_id = $user_id: $total respostas\n";
    }
} catch (Exception $e) {
    echo "Erro ao verificar resultado final: " . $e->getMessage() . "\n";
}

echo "\nAtualização concluída.\n";
?>