<?php
session_start();
header('Content-Type: application/json');

// Debug completo
$debug_info = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'não definido',
    'session_data' => $_SESSION,
    'validation_results' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_questao = isset($_POST['id_questao']) ? (int)$_POST['id_questao'] : 0;
    $id_alternativa_selecionada = isset($_POST['id_alternativa']) ? (int)$_POST['id_alternativa'] : 0;
    
    $debug_info['validation_results'] = [
        'id_questao_raw' => $_POST['id_questao'] ?? 'não definido',
        'id_alternativa_raw' => $_POST['id_alternativa'] ?? 'não definido',
        'id_questao_int' => $id_questao,
        'id_alternativa_int' => $id_alternativa_selecionada,
        'id_questao_valid' => $id_questao > 0,
        'id_alternativa_valid' => $id_alternativa_selecionada > 0,
        'both_valid' => ($id_questao > 0 && $id_alternativa_selecionada > 0)
    ];
    
    if ($id_questao > 0 && $id_alternativa_selecionada > 0) {
        $debug_info['status'] = 'Dados válidos - processamento seria executado';
    } else {
        $debug_info['status'] = 'Dados inválidos - erro 400 seria retornado';
        $debug_info['error_reason'] = 'id_questao ou id_alternativa são zero ou inválidos';
    }
} else {
    $debug_info['status'] = 'Método não é POST - erro 400 seria retornado';
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>