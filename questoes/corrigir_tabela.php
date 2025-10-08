<?php
require 'conexao.php';

echo "Iniciando correção da tabela respostas_usuario...\n";

try {
    // Remover o índice único antigo
    $pdo->exec('ALTER TABLE respostas_usuario DROP INDEX unique_questao');
    echo "Índice removido com sucesso.\n";
} catch (Exception $e) {
    echo "Erro ao remover índice: " . $e->getMessage() . "\n";
}

try {
    // Verificar se a coluna user_id já existe
    $colunas = $pdo->query("DESCRIBE respostas_usuario")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('user_id', $colunas)) {
        // Adicionar a coluna user_id
        $pdo->exec('ALTER TABLE respostas_usuario ADD COLUMN user_id INT NULL AFTER id');
        echo "Coluna user_id adicionada com sucesso.\n";
    } else {
        echo "Coluna user_id já existe.\n";
    }
} catch (Exception $e) {
    echo "Erro ao adicionar coluna: " . $e->getMessage() . "\n";
}

try {
    // Adicionar o novo índice único
    $pdo->exec('ALTER TABLE respostas_usuario ADD UNIQUE KEY unique_user_questao (user_id, id_questao)');
    echo "Novo índice adicionado com sucesso.\n";
} catch (Exception $e) {
    echo "Erro ao adicionar novo índice: " . $e->getMessage() . "\n";
}

echo "Correção concluída.\n";

// Verificar a estrutura final da tabela
echo "\nEstrutura final da tabela respostas_usuario:\n";
foreach($pdo->query("DESCRIBE respostas_usuario")->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Key'] . ' - ' . $col['Default'] . "\n";
}
?>