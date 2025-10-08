<?php
require 'conexao.php';

echo "Estrutura da tabela respostas_usuario:\n";
try {
    $cols = $pdo->query("DESCRIBE respostas_usuario")->fetchAll(PDO::FETCH_ASSOC);
    foreach($cols as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . ' - ' . $col['Key'] . ' - ' . $col['Default'] . "\n";
    }
} catch (Exception $e) {
    echo "Erro ao verificar tabela: " . $e->getMessage() . "\n";
}

echo "\nDados na tabela respostas_usuario:\n";
try {
    $rows = $pdo->query("SELECT * FROM respostas_usuario LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
        foreach($rows as $row) {
            echo "ID: " . $row['id'] . " | ";
            echo "user_id: " . ($row['user_id'] ?? 'NULL') . " | ";
            echo "id_questao: " . $row['id_questao'] . " | ";
            echo "data_resposta: " . $row['data_resposta'] . "\n";
        }
    } else {
        echo "Nenhum registro encontrado.\n";
    }
} catch (Exception $e) {
    echo "Erro ao buscar dados: " . $e->getMessage() . "\n";
}

echo "\nDados na tabela respostas_usuarios:\n";
try {
    $rows = $pdo->query("SELECT * FROM respostas_usuarios LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > 0) {
        foreach($rows as $row) {
            echo "ID: " . $row['id'] . " | ";
            echo "id_usuario: " . ($row['id_usuario'] ?? 'NULL') . " | ";
            echo "id_questao: " . $row['id_questao'] . " | ";
            echo "data_resposta: " . $row['data_resposta'] . "\n";
        }
    } else {
        echo "Nenhum registro encontrado.\n";
    }
} catch (Exception $e) {
    echo "Erro ao buscar dados: " . $e->getMessage() . "\n";
}

echo "\nInformações da sessão atual:\n";
echo "SESSION['id_usuario']: " . ($_SESSION['id_usuario'] ?? 'não definido') . "\n";
echo "SESSION['user_id']: " . ($_SESSION['user_id'] ?? 'não definido') . "\n";
echo "SESSION['user_name']: " . ($_SESSION['user_name'] ?? 'não definido') . "\n";
echo "SESSION['user_type']: " . ($_SESSION['user_type'] ?? 'não definido') . "\n";
?>