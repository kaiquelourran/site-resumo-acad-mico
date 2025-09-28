<?php
header('Content-Type: application/json');

// Captura todos os dados recebidos
$dados = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'não definido',
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'headers' => getallheaders(),
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($dados, JSON_PRETTY_PRINT);
?>