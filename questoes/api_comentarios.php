<?php
header('Content-Type: application/json');
require_once 'conexao.php';
session_start();

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

// Helpers: buscar avatar_url e nome do usuário por e-mail
function getAvatarByEmail(PDO $pdo, string $email): ?string {
    if (!$email) return null;
    try {
        $stmt = $pdo->prepare("SELECT avatar_url FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['avatar_url'] ?? null;
    } catch (PDOException $e) {
        error_log('Erro buscando avatar_url: ' . $e->getMessage());
        return null;
    }
}
function getNomeByEmail(PDO $pdo, string $email): ?string {
    if (!$email) return null;
    try {
        $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['nome'] ?? null;
    } catch (PDOException $e) {
        error_log('Erro buscando nome do usuário: ' . $e->getMessage());
        return null;
    }
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['get_reported_comments']) && $_GET['get_reported_comments'] === 'true') {
                $tipo = $_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '';
                $isAdmin = ($tipo === 'admin' || $tipo === 'administrador');
                if (!$isAdmin) {
                    $response['success'] = false;
                    $response['message'] = 'Ação restrita ao administrador';
                    break;
                }

                $stmt = $pdo->prepare("\n                    SELECT \n                        c.id_comentario, \n                        c.id_questao, \n                        c.comentario, \n                        c.data_comentario AS data_criacao, \n                        c.ativo, \n                        c.aprovado,\n                        COALESCE(u.nome, c.nome_usuario) AS nome_usuario, \n                        COALESCE(u.email, c.email_usuario) AS email_usuario, \n                        u.avatar_url AS avatar_usuario,\n                        (SELECT COUNT(*) FROM curtidas_comentarios cc WHERE cc.id_comentario = c.id_comentario) AS total_curtidas\n                    FROM \n                        comentarios_questoes c\n                    LEFT JOIN \n                        usuarios u ON u.email = c.email_usuario\n                    WHERE \n                        c.ativo = 0\n                    ORDER BY \n                        c.data_comentario DESC\n                ");
                $stmt->execute();
                $reportedComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $response['success'] = true;
                $response['data'] = $reportedComments;
                $response['message'] = 'Comentários inativos carregados com sucesso';
                break;
            }
            // Buscar comentários de uma questão
            if (isset($_GET['id_questao'])) {
                $id_questao = (int)$_GET['id_questao'];
                $ordenacao = $_GET['ordenacao'] ?? 'data'; // 'data' ou 'curtidas'
                
                // Ordena por total de curtidas (do mais curtido para o menos), e desempata pela data mais recente
                $orderBy = $ordenacao === 'curtidas' ? 'total_curtidas DESC, c.data_comentario DESC' : 'c.data_comentario DESC';
                
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
                $ipUsuario = getUserIP();
                $sessionEmail = $_SESSION['user_email'] ?? $_SESSION['usuario_email'] ?? $_SESSION['email_usuario'] ?? $_SESSION['email'] ?? null;
                $stmtCurtidoEmail = $pdo->prepare("SELECT 1 FROM curtidas_comentarios WHERE id_comentario = ? AND email_usuario = ? LIMIT 1");
                $stmtCurtidoIP = $pdo->prepare("SELECT 1 FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ? LIMIT 1");
                // Enriquecer comentários principais com avatar_url e nome normalizado
                if (!empty($comentarios)) {
                    foreach ($comentarios as &$comentario) {
                        $email_c = $comentario['email_usuario'] ?? '';
                        $avatar_c = getAvatarByEmail($pdo, $email_c);
                        if ($avatar_c) { $comentario['avatar_url'] = $avatar_c; }
                        $nome_c = getNomeByEmail($pdo, $email_c);
                        if ($nome_c) { $comentario['nome_usuario'] = $nome_c; }
                        // Curtido pelo usuário (por e-mail quando logado; caso contrário, por IP)
                        // Ajuste robusto: cálculo de curtido_pelo_usuario para COMENTÁRIOS com fallback se coluna email_usuario não existir
                        if ($sessionEmail) {
                            try {
                                $stmtCurtidoEmail->execute([$comentario['id_comentario'], $sessionEmail]);
                                $comentario['curtido_pelo_usuario'] = $stmtCurtidoEmail->fetchColumn() ? true : false;
                            } catch (PDOException $e) {
                                // Fallback por IP quando a coluna email_usuario não existe
                                $stmtCurtidoIP->execute([$comentario['id_comentario'], $ipUsuario]);
                                $comentario['curtido_pelo_usuario'] = $stmtCurtidoIP->fetchColumn() ? true : false;
                            }
                        } else {
                            $stmtCurtidoIP->execute([$comentario['id_comentario'], $ipUsuario]);
                            $comentario['curtido_pelo_usuario'] = $stmtCurtidoIP->fetchColumn() ? true : false;
                        }
                        // Fallback: se não tiver avatar/nome por email, usar sessão
                        if (empty($comentario['avatar_url']) && (empty($comentario['email_usuario']) || $comentario['email_usuario'] === $sessionEmail)) {
                            $sessAvatar = $_SESSION['user_avatar'] ?? $_SESSION['user_picture'] ?? $_SESSION['foto_usuario'] ?? null;
                            if ($sessAvatar) { $comentario['avatar_url'] = $sessAvatar; }
                        }
                        if (empty($comentario['nome_usuario']) && (empty($comentario['email_usuario']) || $comentario['email_usuario'] === $sessionEmail)) {
                            $sessNome = $_SESSION['usuario_nome'] ?? $_SESSION['nome_usuario'] ?? $_SESSION['user_name'] ?? null;
                            if ($sessNome) { $comentario['nome_usuario'] = $sessNome; }
                        }
                    }
                    unset($comentario);
                }
                
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
                    if (!empty($comentario['respostas'])) {
                        foreach ($comentario['respostas'] as &$resposta) {
                            $email_r = $resposta['email_usuario'] ?? '';
                            $avatar_r = getAvatarByEmail($pdo, $email_r);
                            if ($avatar_r) { $resposta['avatar_url'] = $avatar_r; }
                            $nome_r = getNomeByEmail($pdo, $email_r);
                            if ($nome_r) { $resposta['nome_usuario'] = $nome_r; }
                            // Curtido pelo usuário (por e-mail quando logado; caso contrário, por IP)
                            if ($sessionEmail) {
                                try {
                                    $stmtCurtidoEmail->execute([$resposta['id_comentario'], $sessionEmail]);
                                    $resposta['curtido_pelo_usuario'] = $stmtCurtidoEmail->fetchColumn() ? true : false;
                                } catch (PDOException $e) {
                                    $stmtCurtidoIP->execute([$resposta['id_comentario'], $ipUsuario]);
                                    $resposta['curtido_pelo_usuario'] = $stmtCurtidoIP->fetchColumn() ? true : false;
                                }
                            } else {
                                $stmtCurtidoIP->execute([$resposta['id_comentario'], $ipUsuario]);
                                $resposta['curtido_pelo_usuario'] = $stmtCurtidoIP->fetchColumn() ? true : false;
                            }
                            // Fallback por sessão quando não há email/registro
                            if (empty($resposta['avatar_url']) && (empty($resposta['email_usuario']) || $resposta['email_usuario'] === $sessionEmail)) {
                                $sessAvatar = $_SESSION['user_avatar'] ?? $_SESSION['user_picture'] ?? $_SESSION['foto_usuario'] ?? null;
                                if ($sessAvatar) { $resposta['avatar_url'] = $sessAvatar; }
                            }
                            if (empty($resposta['nome_usuario']) && (empty($resposta['email_usuario']) || $resposta['email_usuario'] === $sessionEmail)) {
                                $sessNome = $_SESSION['usuario_nome'] ?? $_SESSION['nome_usuario'] ?? $_SESSION['user_name'] ?? null;
                                if ($sessNome) { $resposta['nome_usuario'] = $sessNome; }
                            }
                        }
                        unset($resposta);
                    }
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
            $session_name = $_SESSION['usuario_nome'] ?? $_SESSION['nome_usuario'] ?? $_SESSION['user_name'] ?? null;
            $session_email = $_SESSION['user_email'] ?? $_SESSION['usuario_email'] ?? $_SESSION['email_usuario'] ?? $_SESSION['email'] ?? null;
            $nome_usuario = $session_name ? trim($session_name) : (isset($data['nome_usuario']) ? trim($data['nome_usuario']) : 'Usuário Anônimo');
            $email_usuario = $session_email ? trim($session_email) : (isset($data['email_usuario']) ? trim($data['email_usuario']) : '');
            $comentario = trim($data['comentario']);
            $id_comentario_pai = isset($data['id_comentario_pai']) ? (int)$data['id_comentario_pai'] : null;
            
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
            
            // Garantir que o avatar do autor esteja salvo na tabela usuarios para que outros usuários visualizem
            try {
                $sessAvatar = $_SESSION['user_avatar'] ?? $_SESSION['user_picture'] ?? $_SESSION['foto_usuario'] ?? null;
                if (!empty($email_usuario) && !empty($sessAvatar)) {
                    $updAvatar = $pdo->prepare("UPDATE usuarios SET avatar_url = ? WHERE email = ? AND (avatar_url IS NULL OR avatar_url = '')");
                    $updAvatar->execute([$sessAvatar, $email_usuario]);
                }
            } catch (PDOException $e) {
                error_log('Falha ao atualizar avatar_url do autor do comentário: ' . $e->getMessage());
            }
            
            // Inserir comentário
            $stmt = $pdo->prepare("\n                INSERT INTO comentarios_questoes (id_questao, nome_usuario, email_usuario, comentario, id_comentario_pai) \n                VALUES (?, ?, ?, ?, ?)\n            ");
            
            if ($stmt->execute([$id_questao, $nome_usuario, $email_usuario, $comentario, $id_comentario_pai])) {
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
                if ($response['data']) {
                    $postAvatar = getAvatarByEmail($pdo, $response['data']['email_usuario'] ?? '');
                    if ($postAvatar) { $response['data']['avatar_url'] = $postAvatar; }
                    $postNome = getNomeByEmail($pdo, $response['data']['email_usuario'] ?? '');
                    if ($postNome) { $response['data']['nome_usuario'] = $postNome; }
                    // Fallback para avatar/nome da sessão quando o e-mail não estiver disponível no banco
                    $sessionEmail = $_SESSION['user_email'] ?? $_SESSION['usuario_email'] ?? $_SESSION['email_usuario'] ?? $_SESSION['email'] ?? null;
                    if (empty($response['data']['avatar_url']) && (empty($response['data']['email_usuario']) || $response['data']['email_usuario'] === $sessionEmail)) {
                        $sessAvatar = $_SESSION['user_avatar'] ?? $_SESSION['user_picture'] ?? $_SESSION['foto_usuario'] ?? null;
                        if ($sessAvatar) { $response['data']['avatar_url'] = $sessAvatar; }
                    }
                    if (empty($response['data']['nome_usuario']) && (empty($response['data']['email_usuario']) || $response['data']['email_usuario'] === $sessionEmail)) {
                        $sessNome = $_SESSION['usuario_nome'] ?? $_SESSION['nome_usuario'] ?? $_SESSION['user_name'] ?? null;
                        if ($sessNome) { $response['data']['nome_usuario'] = $sessNome; }
                    }
                    if (!isset($response['data']['id_comentario_pai'])) { $response['data']['id_comentario_pai'] = $id_comentario_pai; }
                }
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
            $email_usuario = $_SESSION['user_email'] ?? $_SESSION['usuario_email'] ?? $_SESSION['email_usuario'] ?? $_SESSION['email'] ?? null;

            // Verificar existência prévia de curtida para este usuário (por email e por IP)
            $hasEmailLike = false;
            $hasIPLike = false;
            try {
                if ($email_usuario) {
                    $stmt = $pdo->prepare("SELECT id_curtida FROM curtidas_comentarios WHERE id_comentario = ? AND email_usuario = ? LIMIT 1");
                    $stmt->execute([$id_comentario, $email_usuario]);
                    $hasEmailLike = (bool)$stmt->fetch();
                }
                $stmt = $pdo->prepare("SELECT id_curtida FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ? LIMIT 1");
                $stmt->execute([$id_comentario, $ip_usuario]);
                $hasIPLike = (bool)$stmt->fetch();
            } catch (PDOException $e) {
                // Em ambientes sem coluna email_usuario, apenas IP estará disponível
                $hasEmailLike = false;
                try {
                    $stmt = $pdo->prepare("SELECT id_curtida FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ? LIMIT 1");
                    $stmt->execute([$id_comentario, $ip_usuario]);
                    $hasIPLike = (bool)$stmt->fetch();
                } catch (PDOException $e2) {
                    $hasIPLike = false;
                }
            }
            
            if ($acao === 'curtir') {
                if ($hasEmailLike) {
                    // Já curtiu por email; não duplicar
                    $response['success'] = true;
                    $response['message'] = 'Você já curtiu este comentário';
                } elseif ($email_usuario && $hasIPLike) {
                    // Migração: converter curtida por IP anterior para email do usuário atual
                    try {
                        $stmt = $pdo->prepare("UPDATE curtidas_comentarios SET email_usuario = ?, ip_usuario = NULL WHERE id_comentario = ? AND ip_usuario = ?");
                        $ok = $stmt->execute([$email_usuario, $id_comentario, $ip_usuario]);
                        $response['success'] = (bool)$ok;
                        $response['message'] = $ok ? 'Comentário curtido com sucesso' : 'Erro ao curtir comentário';
                    } catch (PDOException $e) {
                        // Fallback: se não houver coluna email_usuario, manter por IP
                        $response['success'] = true;
                        $response['message'] = 'Comentário curtido com sucesso';
                    }
                } else {
                    // Adicionar curtida nova
                    if ($email_usuario) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO curtidas_comentarios (id_comentario, email_usuario, ip_usuario) VALUES (?, ?, NULL)");
                            $ok = $stmt->execute([$id_comentario, $email_usuario]);
                        } catch (PDOException $e) {
                            // Fallback para IP quando coluna email_usuario não existir
                            $stmt = $pdo->prepare("INSERT INTO curtidas_comentarios (id_comentario, ip_usuario) VALUES (?, ?)");
                            $ok = $stmt->execute([$id_comentario, $ip_usuario]);
                        }
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO curtidas_comentarios (id_comentario, ip_usuario) VALUES (?, ?)");
                        $ok = $stmt->execute([$id_comentario, $ip_usuario]);
                    }
                    $response['success'] = (bool)$ok;
                    $response['message'] = $ok ? 'Comentário curtido com sucesso' : 'Erro ao curtir comentário';
                }
            } elseif ($acao === 'descurtir') {
                // Remover curtida do usuário atual. Primeiro por email, depois por IP (backward compatibility)
                $deleted = false;
                if ($email_usuario && $hasEmailLike) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM curtidas_comentarios WHERE id_comentario = ? AND email_usuario = ?");
                        $stmt->execute([$id_comentario, $email_usuario]);
                        $deleted = ($stmt->rowCount() > 0);
                    } catch (PDOException $e) {
                        // Fallback para IP em ambientes sem coluna email
                        try {
                            $stmt = $pdo->prepare("DELETE FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ?");
                            $stmt->execute([$id_comentario, $ip_usuario]);
                            $deleted = ($stmt->rowCount() > 0);
                        } catch (PDOException $e2) { $deleted = false; }
                    }
                }
                // Somente excluir por IP quando o usuário NÃO estiver logado
                if (!$deleted && !$email_usuario && $hasIPLike) {
                    $stmt = $pdo->prepare("DELETE FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ?");
                    $stmt->execute([$id_comentario, $ip_usuario]);
                    $deleted = ($stmt->rowCount() > 0);
                }
                $response['success'] = $deleted;
                $response['message'] = $deleted ? 'Curtida removida com sucesso' : 'Erro ao remover curtida';
            } elseif ($acao === 'ativar') {
                // Ativar comentário (apenas para administradores)
                if (!isset($_SESSION['tipo_usuario']) || ((($_SESSION['tipo_usuario'] ?? '') !== 'administrador') && (($_SESSION['user_type'] ?? '') !== 'admin'))) {
                    $response['message'] = 'Apenas administradores podem ativar comentários.';
                } else {
                    $stmt = $pdo->prepare("UPDATE comentarios_questoes SET ativo = 1 WHERE id_comentario = ?");
                    if ($stmt->execute([$id_comentario])) {
                        $response['success'] = true;
                        $response['message'] = 'Comentário ativado com sucesso.';
                    } else {
                        $response['message'] = 'Erro ao ativar comentário.';
                    }
                }
            } else {
                $response['message'] = 'Ação inválida';
            }

            // Sempre retornar total de curtidas atualizado e estado do usuário para o comentário
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM curtidas_comentarios WHERE id_comentario = ?");
                $stmt->execute([$id_comentario]);
                $response['total_curtidas'] = (int)$stmt->fetchColumn();
            } catch (PDOException $e) {
                $response['total_curtidas'] = null;
            }
            // Estado curtido_pelo_usuario
            $curtidoAtual = false;
            try {
                if ($email_usuario) {
                    $stmt = $pdo->prepare("SELECT 1 FROM curtidas_comentarios WHERE id_comentario = ? AND email_usuario = ? LIMIT 1");
                    $stmt->execute([$id_comentario, $email_usuario]);
                    $curtidoAtual = (bool)$stmt->fetchColumn();
                } else {
                    $stmt = $pdo->prepare("SELECT 1 FROM curtidas_comentarios WHERE id_comentario = ? AND ip_usuario = ? LIMIT 1");
                    $stmt->execute([$id_comentario, $ip_usuario]);
                    $curtidoAtual = (bool)$stmt->fetchColumn();
                }
            } catch (PDOException $e) { $curtidoAtual = false; }
            $response['curtido_pelo_usuario'] = $curtidoAtual;
            break;
            
        case 'DELETE':
            // Reportar abuso ou apagar comentário (admin)
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id_comentario'])) {
                $response['message'] = 'ID do comentário não fornecido';
                break;
            }
            
            $id_comentario = (int)$data['id_comentario'];
            $acao = isset($data['acao']) ? $data['acao'] : 'reportar';
            
            if ($acao === 'apagar') {
                // Apenas administrador pode apagar (soft delete)
                $isAdmin = (($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'administrador' || ($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'admin');
                if (!$isAdmin) {
                    $response['success'] = false;
                    $response['message'] = 'Apenas administradores podem apagar comentários.';
                    break;
                }

                $stmt = $pdo->prepare("UPDATE comentarios_questoes SET ativo = 0 WHERE id_comentario = ?");
                if ($stmt->execute([$id_comentario])) {
                    $response['success'] = true;
                    $response['message'] = 'Comentário apagado com sucesso.';
                } else {
                    $response['message'] = 'Erro ao apagar comentário.';
                }
            } elseif ($acao === 'excluir_permanente') {
                // Apenas administrador pode excluir permanentemente
                $isAdmin = (($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'administrador' || ($_SESSION['tipo_usuario'] ?? $_SESSION['user_type'] ?? '') === 'admin');
                if (!$isAdmin) {
                    $response['success'] = false;
                    $response['message'] = 'Apenas administradores podem excluir comentários permanentemente.';
                    break;
                }

                // Excluir curtidas associadas primeiro
                $stmt = $pdo->prepare("DELETE FROM curtidas_comentarios WHERE id_comentario = ?");
                $stmt->execute([$id_comentario]);

                // Excluir comentário
                $stmt = $pdo->prepare("DELETE FROM comentarios_questoes WHERE id_comentario = ?");
                if ($stmt->execute([$id_comentario])) {
                    $response['success'] = true;
                    $response['message'] = 'Comentário excluído permanentemente com sucesso.';
                } else {
                    $response['message'] = 'Erro ao excluir comentário permanentemente.';
                }
            } else { // reportar abuso
                // Lógica existente para reportar abuso (soft delete)
                $stmt = $pdo->prepare("UPDATE comentarios_questoes SET ativo = 0 WHERE id_comentario = ?");
                if ($stmt->execute([$id_comentario])) {
                    $response['success'] = true;
                    $response['message'] = 'Comentário reportado com sucesso.';
                } else {
                    $response['message'] = 'Erro ao reportar comentário.';
                }
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
