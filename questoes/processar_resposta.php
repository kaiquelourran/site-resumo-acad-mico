<?php
// Configurar para não mostrar erros na saída
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

// Headers CORS para permitir requisições cross-origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Responde a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifica se o usuário está logado; se não estiver, não grava no banco, apenas responde.
$usuario_logado = isset($_SESSION['user_id']);
    $id_usuario = $usuario_logado ? $_SESSION['user_id'] : null;

// Inicializa a sessão de progresso se ainda não existir
if (!isset($_SESSION['quiz_progress'])) {
    $_SESSION['quiz_progress'] = ['acertos' => 0, 'respondidas' => []];
}

// Criar tabela de respostas_usuario se não existir (para o sistema de tracking)
$sql_create_table = "CREATE TABLE IF NOT EXISTS respostas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    id_questao INT NOT NULL,
    id_alternativa INT NOT NULL,
    acertou TINYINT(1) NOT NULL DEFAULT 0,
    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_questao (user_id, id_questao)
)";
$pdo->query($sql_create_table);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se é uma requisição AJAX do quiz_vertical.php
    $is_vertical_quiz = isset($_POST['alternativa_selecionada']) && isset($_POST['id_questao']);
    

    $json_data = file_get_contents('php://input');
    error_log("DEBUG processar_resposta: Raw JSON input: " . $json_data);
    $data = json_decode($json_data, true);
    error_log("DEBUG processar_resposta: Decoded data: " . print_r($data, true));

    // 2. EXTRAIR OS DADOS DO JSON LIDO
    $id_questao = isset($data['id_questao']) ? (int)$data['id_questao'] : 0;
    $id_alternativa_selecionada = isset($data['id_alternativa']) ? (int)$data['id_alternativa'] : 0;

    error_log("DEBUG processar_resposta: id_questao=" . $id_questao . ", id_alternativa=" . $id_alternativa_selecionada . ", user_id=" . $id_usuario);

    if ($id_questao > 0 && $id_alternativa_selecionada > 0) {
        // Encontra a alternativa correta no banco de dados
        $stmt_correta = $pdo->prepare("SELECT id_alternativa FROM alternativas WHERE id_questao = ? AND eh_correta = 1");
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
            // Salvar resposta na tabela de tracking (sempre, mesmo sem login)
            $stmt_tracking = $pdo->prepare("INSERT INTO respostas_usuario (user_id, id_questao, id_alternativa, acertou) \n                                           VALUES (?, ?, ?, ?) \n                                           ON DUPLICATE KEY UPDATE \n                                           id_alternativa = VALUES(id_alternativa), \n                                           acertou = VALUES(acertou), \n                                           data_resposta = CURRENT_TIMESTAMP");
            $stmt_tracking->execute([$id_usuario, $id_questao, $id_alternativa_selecionada, $resposta_correta ? 1 : 0]);
            
            // Adiciona a questão ao array de respondidas na sessão
            $_SESSION['quiz_progress']['respondidas'][] = $id_questao;

            // Retorna a resposta para o JavaScript
            echo json_encode([
                'sucesso' => true,
                'acertou' => $resposta_correta,
                'id_alternativa_selecionada' => $id_alternativa_selecionada,
                'id_alternativa_correta' => $alternativa_correta['id_alternativa'],
                'acertos' => $_SESSION['quiz_progress']['acertos']
            ]);
            exit;
        } catch (Exception $e) {
            // Exibe o erro completo do banco de dados
            error_log("ERRO processar_resposta: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar a resposta. Mensagem: ' . $e->getMessage()]);
            exit;
        }
    } else {
        // Dados inválidos - não logar em produção
    }
}

// Código para lidar com requisições inválidas
http_response_code(400);
echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
?>