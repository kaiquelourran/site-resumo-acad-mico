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
$usuario_logado = isset($_SESSION['id_usuario']);
$id_usuario = $usuario_logado ? $_SESSION['id_usuario'] : null;

// Inicializa a sessão de progresso se ainda não existir
if (!isset($_SESSION['quiz_progress'])) {
    $_SESSION['quiz_progress'] = ['acertos' => 0, 'respondidas' => []];
}

// Criar tabela de respostas_usuario se não existir (para o sistema de tracking)
$sql_create_table = "CREATE TABLE IF NOT EXISTS respostas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_questao INT NOT NULL,
    id_alternativa INT NOT NULL,
    acertou TINYINT(1) NOT NULL DEFAULT 0,
    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_questao (id_questao)
)";
$pdo->query($sql_create_table);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se é uma requisição AJAX do quiz_vertical.php
    $is_vertical_quiz = isset($_POST['alternativa_selecionada']) && isset($_POST['id_questao']);
    
    if ($is_vertical_quiz) {
        // Processar resposta do quiz vertical
        $id_questao = (int)$_POST['id_questao'];
        $alternativa_selecionada = $_POST['alternativa_selecionada'];
        
        if ($id_questao && $alternativa_selecionada) {
            try {
                // Buscar a questão
                $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
                $stmt->execute([$id_questao]);
                $questao = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($questao) {
                    // Verificar se a resposta está correta
                    $acertou = ($alternativa_selecionada === $questao['resposta_correta']) ? 1 : 0;
                    
                    // Para a estrutura atual, vamos usar um ID fictício baseado na letra
                    $letras_para_id = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
                    $id_alternativa = isset($letras_para_id[$alternativa_selecionada]) ? $letras_para_id[$alternativa_selecionada] : 1;
                    
                    // Inserir ou atualizar resposta do usuário
                    $stmt_resposta = $pdo->prepare("
                        INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) 
                        VALUES (?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        id_alternativa = VALUES(id_alternativa),
                        acertou = VALUES(acertou),
                        data_resposta = VALUES(data_resposta)
                    ");
                    $stmt_resposta->execute([$id_questao, $id_alternativa, $acertou]);
                        // Determinar se deve mudar de filtro
                        $filtro_atual = isset($_POST['filtro_atual']) ? $_POST['filtro_atual'] : '';
                        $novo_filtro = $filtro_atual;
                        $mudou_filtro = false;
                        
                        if ($acertou) {
                            // Se acertou e estava em filtro de erradas, mover para acertadas
                            if ($filtro_atual === 'erradas') {
                                $novo_filtro = 'acertadas';
                                $mudou_filtro = true;
                            }
                        } else {
                            // Se errou e estava em filtro de acertadas, mover para erradas
                            if ($filtro_atual === 'acertadas') {
                                $novo_filtro = 'erradas';
                                $mudou_filtro = true;
                            }
                        }
                        
                        // Retornar resposta JSON
                        echo json_encode([
                            'success' => true,
                            'acertou' => (bool)$acertou,
                            'resposta_correta' => $questao['resposta_correta'],
                            'alternativa_selecionada' => $alternativa_selecionada,
                            'explicacao' => $questao['explicacao'] ?? '',
                            'message' => $acertou ? 'Parabéns! Você acertou!' : 'Não foi dessa vez, mas continue tentando!',
                            'mudou_filtro' => $mudou_filtro,
                            'novo_filtro' => $novo_filtro
                        ]);
                        exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Questão não encontrada']);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao processar resposta: ' . $e->getMessage()]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
            exit;
        }
    }
    
    // 1. LER O CORPO BRUTO DA REQUISIÇÃO (JSON) - para o quiz original
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // 2. EXTRAIR OS DADOS DO JSON LIDO
    $id_questao = isset($data['id_questao']) ? (int)$data['id_questao'] : 0;
    $id_alternativa_selecionada = isset($data['id_alternativa']) ? (int)$data['id_alternativa'] : 0;

    // Debug: log dos dados recebidos
    error_log("DEBUG processar_resposta: id_questao=$id_questao, id_alternativa=$id_alternativa_selecionada");

    if ($id_questao > 0 && $id_alternativa_selecionada > 0) {
        // Encontra a alternativa correta no banco de dados
        $stmt_correta = $pdo->prepare("SELECT id_alternativa FROM alternativas WHERE id_questao = ? AND correta = 1");
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
            $stmt_tracking = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) 
                                           VALUES (?, ?, ?) 
                                           ON DUPLICATE KEY UPDATE 
                                           id_alternativa = VALUES(id_alternativa), 
                                           acertou = VALUES(acertou), 
                                           data_resposta = CURRENT_TIMESTAMP");
            $stmt_tracking->execute([$id_questao, $id_alternativa_selecionada, $resposta_correta ? 1 : 0]);
            
            if ($usuario_logado) {
                // Insere a resposta apenas para usuários logados (tabela original)
                $stmt_salvar = $pdo->prepare("INSERT INTO respostas_usuarios (id_usuario, id_questao, acertou, data_resposta) VALUES (?, ?, ?, ?)");
                $stmt_salvar->execute([$id_usuario, $id_questao, $resposta_correta ? 1 : 0, $data_resposta]);
            }

            // Adiciona a questão ao array de respondidas na sessão
            $_SESSION['quiz_progress']['respondidas'][] = $id_questao;

            // Retorna a resposta para o JavaScript
            echo json_encode([
                'sucesso' => true,
                'correta' => $resposta_correta,
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
        // Debug: log quando dados são inválidos
        error_log("DEBUG processar_resposta: Dados inválidos - id_questao=$id_questao, id_alternativa=$id_alternativa_selecionada");
    }
}

// Código para lidar com requisições inválidas
error_log("DEBUG processar_resposta: Chegou ao final - retornando erro 400");
http_response_code(400);
echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida']);
?>