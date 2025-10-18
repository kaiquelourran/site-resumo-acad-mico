<?php
// Arquivo para atualizar a senha do usuário teste
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Forçar uso da configuração local
$_SERVER['HTTP_HOST'] = 'localhost';

// Incluir conexão com o banco de dados
require_once 'conexao.php';

// Hash bcrypt correto para a senha 'password'
$senha_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

try {
    // Atualizar a senha do usuário teste
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
    $resultado = $stmt->execute([$senha_hash, 'teste@teste.com']);
    
    if ($resultado) {
        echo "<h1>Senha atualizada com sucesso!</h1>";
        echo "<p>A senha do usuário teste@teste.com foi atualizada para o hash correto.</p>";
        echo "<p>Hash da senha: $senha_hash</p>";
        echo "<p>Agora você pode fazer login com:</p>";
        echo "<ul>";
        echo "<li>Email: teste@teste.com</li>";
        echo "<li>Senha: password</li>";
        echo "<li>Tipo: usuario</li>";
        echo "</ul>";
        echo "<p><a href='login.php'>Ir para a página de login</a></p>";
    } else {
        echo "<h1>Erro ao atualizar senha</h1>";
        echo "<p>Não foi possível atualizar a senha do usuário.</p>";
    }
} catch (PDOException $e) {
    echo "<h1>Erro no banco de dados</h1>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
}
?>