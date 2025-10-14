<?php
session_start();
header('Content-Type: application/json');

// Dependência do Composer removida para ambiente local; validação via endpoint tokeninfo do Google
// require __DIR__ . '/../../vendor/autoload.php'; // Ajuste o caminho conforme necessário

// Incluir o arquivo de conexão com o banco de dados
require_once 'conexao.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aceitar tanto JSON (fetch com application/json) quanto form-encoded ($_POST)
    $id_token = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['id_token'])) {
            $id_token = $data['id_token'];
        }
    }
    if (!$id_token && isset($_POST['id_token'])) {
        $id_token = $_POST['id_token'];
    }

    if ($id_token) {
        // Verificar token via endpoint público do Google para evitar dependência do Google_Client
        $client_id = '483177848191-i85ijikssoaftcnam1kjinhkdvi7lf69.apps.googleusercontent.com';
        $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
        $verifyJson = @file_get_contents($verifyUrl);
        $payload = $verifyJson ? json_decode($verifyJson, true) : null;

        if (is_array($payload) && isset($payload['aud']) && $payload['aud'] === $client_id) {
            $userid = $payload['sub'] ?? null;
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? ($payload['given_name'] ?? 'Usuário');
            $picture = $payload['picture'] ?? null;

            if ($email) {
                // Helpers de schema para lidar com diferentes estruturas da tabela usuarios
                function getUsuariosIdColumn(PDO $pdo): ?string {
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
                        if (in_array('id_usuario', $cols)) return 'id_usuario';
                        if (in_array('id', $cols)) return 'id';
                    } catch (PDOException $e) {
                        error_log("Erro lendo colunas da tabela usuarios: " . $e->getMessage());
                    }
                    return null;
                }
                function ensureGoogleIdColumn(PDO $pdo): void {
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
                        if (!in_array('google_id', $cols)) {
                            $pdo->exec("ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) NULL UNIQUE");
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao tentar adicionar coluna google_id: " . $e->getMessage());
                    }
                }
                // NOVO: Garantir coluna de avatar_url e helper para verificar existência
                function ensureAvatarColumn(PDO $pdo): void {
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
                        if (!in_array('avatar_url', $cols)) {
                            $pdo->exec("ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(512) NULL");
                        }
                    } catch (PDOException $e) {
                        error_log("Erro ao tentar adicionar coluna avatar_url: " . $e->getMessage());
                    }
                }
                function usuariosHasAvatarColumn(PDO $pdo): bool {
                    try {
                        $cols = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
                        return in_array('avatar_url', $cols);
                    } catch (PDOException $e) {
                        error_log("Erro lendo colunas da tabela usuarios: " . $e->getMessage());
                        return false;
                    }
                }
                // Definir coluna de ID dinamicamente e garantir existência de google_id
                $idCol = getUsuariosIdColumn($pdo);
                if (!$idCol) {
                    http_response_code(500);
                    $response['message'] = 'Estrutura da tabela usuarios ausente ou inválida.';
                    $response['error_detail'] = 'Tabela usuarios não encontrada ou sem coluna id/id_usuario.';
                    echo json_encode($response);
                    exit;
                }
                ensureGoogleIdColumn($pdo);
                ensureAvatarColumn($pdo);
                $hasAvatar = usuariosHasAvatarColumn($pdo);
                try {
                    // 1. Verificar se o usuário já existe pelo email
                    $stmt = $pdo->prepare("SELECT {$idCol} as id, nome, email, google_id" . ($hasAvatar ? ", avatar_url" : "") . " FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user) {
                        // Usuário existente: atualizar google_id se necessário e autenticar
                        if (empty($user['google_id'])) {
                            $update_stmt = $pdo->prepare("UPDATE usuarios SET google_id = ? WHERE {$idCol} = ?");
                            $update_stmt->execute([$userid, $user['id']]);
                        }
                        // Atualizar avatar_url se disponível
                        if ($hasAvatar && !empty($picture) && ($user['avatar_url'] ?? '') !== $picture) {
                            $updAvatar = $pdo->prepare("UPDATE usuarios SET avatar_url = ? WHERE {$idCol} = ?");
                            $updAvatar->execute([$picture, $user['id']]);
                        }
                        // Marcar sessão como logada e padronizar chaves
                        session_regenerate_id(true);
                        $_SESSION['logged_in'] = true;
                        $_SESSION['id_usuario'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['nome'];
                        $_SESSION['usuario_nome'] = $user['nome'];
                        $_SESSION['nome_usuario'] = $user['nome'];
                        // Foto/avatar do Google, se disponível
                        $_SESSION['user_avatar'] = $picture;
                        $_SESSION['user_picture'] = $picture;
                        $_SESSION['foto_usuario'] = $picture;
                        // Tipo padrão (normal/usuario)
                        if (!isset($_SESSION['user_type'])) {
                            $_SESSION['user_type'] = 'usuario';
                        }
                        $_SESSION['tipo_usuario'] = $_SESSION['user_type'];
                        $response['message'] = 'Login do Google bem-sucedido (usuário existente)!';
                    } else {
                        // Novo usuário: registrar no banco de dados
                        if ($hasAvatar) {
                            $insert_stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, google_id, avatar_url) VALUES (?, ?, ?, ?)");
                            $insert_stmt->execute([$name, $email, $userid, $picture]);
                        } else {
                            $insert_stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, google_id) VALUES (?, ?, ?)");
                            $insert_stmt->execute([$name, $email, $userid]);
                        }
                        $new_user_id = $pdo->lastInsertId();
                        
                        // Marcar sessão como logada e padronizar chaves
                        session_regenerate_id(true);
                        $_SESSION['logged_in'] = true;
                        $_SESSION['id_usuario'] = $new_user_id;
                        $_SESSION['user_email'] = $email;
                        $_SESSION['user_name'] = $name;
                        $_SESSION['usuario_nome'] = $name;
                        $_SESSION['nome_usuario'] = $name;
                        // Foto/avatar do Google, se disponível
                        $_SESSION['user_avatar'] = $picture;
                        $_SESSION['user_picture'] = $picture;
                        $_SESSION['foto_usuario'] = $picture;
                        // Tipo padrão (normal/usuario)
                        if (!isset($_SESSION['user_type'])) {
                            $_SESSION['user_type'] = 'usuario';
                        }
                        $_SESSION['tipo_usuario'] = $_SESSION['user_type'];
                        
                        $response['message'] = 'Login do Google bem-sucedido (novo usuário registrado)!';
                    }
                    $response['success'] = true;

                } catch (PDOException $e) {
                    http_response_code(500);
                    error_log("Erro no banco de dados durante o login do Google: " . $e->getMessage());
                    $response['message'] = 'Erro interno do servidor durante a autenticação.';
                    $response['error_detail'] = $e->getMessage();
                }
            } else {
                $response['message'] = 'E-mail não encontrado no token.';
            }
        } else {
            $response['message'] = 'Token de ID inválido ou client_id não confere.';
        }

    } else {
        // Sem id_token no corpo da requisição
        http_response_code(400);
        $response['message'] = 'Requisição inválida: id_token não fornecido.';
    }

} else {
    http_response_code(405);
    $response['message'] = 'Requisição inválida.';
}

echo json_encode($response);
?>