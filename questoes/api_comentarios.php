<?php
header('Content-Type: application/json');
require_once 'conexao.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => '', 'data' => []];

// Função para obter IP do usuário
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

try {
    switch ($method) {
        case 'GET':
            // Buscar comentários de uma questão
            if (isset($_GET['id_questao'])) {
                $id_questao = (int)$_GET['id_questao'];
                $ordenacao = $_GET['ordenacao'] ?? 'data'; // 'data' ou 'curtidas'
                
                $orderBy = $ordenacao === 'curtidas' ? 'c.curtidas DESC, c.data_comentario DESC' : 'c.data_comentario DESC';
                
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           DATE_FORMAT(c.data_comentario, '%d de %M de %Y às %H:%i') as data_formatada,
                           (SELECT COUNT(*) FROM curtidas_comentarios cc WHERE cc.id_comentario = c.id_comentario) as total_curtidas,
                           (SELECT COUNT(*) FROM comentarios_questoes cr WHERE cr.id_comentario_pai = c.id_comentario AND cr.ativo = 1) as total_respostas
                    FROM comentarios_questoes c 
                    WHERE c.id_questao = ? AND c.aprovado = 1 AND c.ativo = 1 AND c.id_comentario_pai IS NULL
                    ORDER BY $orderBy
                ");
                $stmt->execute([$id_questao]);
                $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Buscar respostas para cada comentário
                foreach ($comentarios as &$comentario) {
                    $stmt_respostas = $pdo->prepare("
                        SELECT c.*, 
                               DATE_FORMAT(c.data_comentario, '%d de %M de %Y às %H:%i') as data_formatada,
                               (SELECT COUNT(*) FROM curtidas_comentarios cc WHERE cc.id_comentario = c.id_comentario) as total_curtidas
                        FROM comentarios_questoes c 
                        WHERE c.id_comentario_pai = ? AND c.ativo = 1
                        ORDER BY c.data_comentario ASC
                    ");
                    $stmt_respostas->execute([$comentario['id_comentario']]);
                    $comentario['respostas'] = $stmt_respostas->fetchAll(PDO::FETCH_ASSOC);
                }
                
                $response['success'] = true;
                $response['data'] = $comentarios;
                $response['message'] = 'Comentários carregados com sucesso';
            } else {
                $response['message'] = 'ID da questão não fornecido';
            }
            break;
            
        case 'POST':
            // Adicionar novo comentário
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Debug: log dos dados recebidos
            error_log("API Comentários - Dados recebidos: " . $input);
            
            if (!$data || !isset($data['id_questao']) || !isset($data['comentario'])) {
                $response['message'] = 'Dados obrigatórios não fornecidos. Dados recebidos: ' . $input;
                break;
            }
            
            $id_questao = (int)$data['id_questao'];
            $nome_usuario = isset($data['nome_usuario']) ? trim($data['nome_usuario']) : 'Usuário Anônimo';
            $email_usuario = isset($data['email_usuario']) ? trim($data['email_usuario']) : '';
            $comentario = trim($data['comentario']);
            
            // Validações
            if (empty($nome_usuario) || empty($comentario)) {
                $response['message'] = 'Nome e comentário são obrigatórios';
                break;
            }
            
            if (strlen($comentario) < 10) {
                $response['message'] = 'Comentário deve ter pelo menos 10 caracteres';
                break;
            }
            
            if (strlen($comentario) > 500) {
                $response['message'] = 'Comentário deve ter no máximo 500 caracteres';
                break;
            }
            
            // Verificar se a questão existe
            $stmt = $pdo->prepare("SELECT id_questao FROM questoes WHERE id_questao = ?");
            $stmt->execute([$id_questao]);
            if (!$stmt->fetch()) {
                $response['message'] = 'Questão não encontrada';
                break;
            }
            
            // Inserir comentário
            $stmt = $pdo->prepare("
                INSERT INTO comentarios_questoes (id_questao, nome_usuario, email_usuario, comentario) 
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$id_questao, $nome_usuario, $email_usuario, $comentario])) {
                $response['success'] = true;
                $response['message'] = 'Comentário adicionado com sucesso';
                
                // Retornar o comentário recém-criado
                $id_comentario = $pdo->lastInsertId();
                $stmt = $pdo->prepare("
                    SELECT *, 
                           DATE_FORMAT(data_comentario, '%d/%m/%Y às %H:%i') as data_formatada
                    FROM comentarios_questoes 
                    WHERE id_comentario = ?
                ");
                $stmt->execute([$id_comentario]);
                $response['data'] = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $response['message'] = 'Erro ao salvar comentário';
            }
            break;
            
        case 'PUT':
            // Curtir/descurtir comentário
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id_comentario']) || !isset($data['acao'])) {
                $response['message'] = 'Dados obrigatórios não fornecidos';
                break;
            }
            
            $id_comentario = (int)$data['id_comentario'];
            $acao = $data['acao']; // 'curtir' ou 'descurtir'
            $ip_usuario = getUserIP();
            
            if ($acao === 'curtir') {
                // Verificar se já curtiu
                $stmt = $pdo->prepare("SELECT id_curtida FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ?");
                $stmt->execute([$id_comentario, $ip_usuario]);
                
                if ($stmt->fetch()) {
                    $response['message'] = 'Você já curtiu este comentário';
                    break;
                }
                
                // Adicionar curtida
                $stmt = $pdo->prepare("INSERT INTO curtidas_comentarios (id_comentario, ip_usuario) VALUES (?, ?)");
                if ($stmt->execute([$id_comentario, $ip_usuario])) {
                    $response['success'] = true;
                    $response['message'] = 'Comentário curtido com sucesso';
                } else {
                    $response['message'] = 'Erro ao curtir comentário';
                }
            } elseif ($acao === 'descurtir') {
                // Remover curtida
                $stmt = $pdo->prepare("DELETE FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ?");
                if ($stmt->execute([$id_comentario, $ip_usuario])) {
                    $response['success'] = true;
                    $response['message'] = 'Curtida removida com sucesso';
                } else {
                    $response['message'] = 'Erro ao remover curtida';
                }
            } else {
                $response['message'] = 'Ação inválida';
            }
            break;
            
        case 'DELETE':
            // Reportar abuso
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id_comentario'])) {
                $response['message'] = 'ID do comentário não fornecido';
                break;
            }
            
            $id_comentario = (int)$data['id_comentario'];
            
            // Marcar comentário como inativo (simulando remoção por abuso)
            $stmt = $pdo->prepare("UPDATE comentarios_questoes SET ativo = 0 WHERE id_comentario = ?");
            if ($stmt->execute([$id_comentario])) {
                $response['success'] = true;
                $response['message'] = 'Comentário reportado com sucesso';
            } else {
                $response['message'] = 'Erro ao reportar comentário';
            }
            break;
            
        default:
            $response['message'] = 'Método não permitido';
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Erro no banco de dados: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
