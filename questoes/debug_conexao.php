<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/conexao.php';
    
    // Testa a conexão
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo json_encode([
        'conexao' => 'sucesso',
        'teste_query' => $result ? 'ok' : 'falhou',
        'pdo_info' => [
            'driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'conexao' => 'erro',
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
}
?>