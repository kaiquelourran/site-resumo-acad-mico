<?php
// Teste da API de comentários
header('Content-Type: application/json');

// Simular dados de teste
$teste_data = [
    'id_questao' => 92,
    'nome_usuario' => 'Usuário Teste',
    'email_usuario' => 'teste@example.com',
    'comentario' => 'Este é um comentário de teste para verificar se a API está funcionando.'
];

echo "🧪 TESTE DA API DE COMENTÁRIOS\n";
echo "==============================\n\n";

// Teste 1: Verificar se a API existe
if (file_exists('api_comentarios.php')) {
    echo "✅ Arquivo api_comentarios.php existe\n";
} else {
    echo "❌ Arquivo api_comentarios.php NÃO existe\n";
    exit;
}

// Teste 2: Verificar conexão com banco
require_once 'conexao.php';
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Conexão com banco de dados OK\n";
} catch (PDOException $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
    exit;
}

// Teste 3: Verificar se a tabela existe
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM comentarios_questoes");
    $count = $stmt->fetchColumn();
    echo "✅ Tabela comentarios_questoes existe (registros: $count)\n";
} catch (PDOException $e) {
    echo "❌ Erro na tabela: " . $e->getMessage() . "\n";
    exit;
}

// Teste 4: Simular requisição POST
echo "\n🔍 Testando envio de comentário...\n";

// Simular $_POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simular dados JSON
$json_data = json_encode($teste_data);
file_put_contents('php://input', $json_data);

// Capturar output da API
ob_start();
include 'api_comentarios.php';
$api_output = ob_get_clean();

echo "📤 Dados enviados: " . $json_data . "\n";
echo "📥 Resposta da API: " . $api_output . "\n";

// Verificar se o comentário foi inserido
try {
    $stmt = $pdo->prepare("SELECT * FROM comentarios_questoes WHERE nome_usuario = ? ORDER BY data_comentario DESC LIMIT 1");
    $stmt->execute(['Usuário Teste']);
    $ultimo_comentario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ultimo_comentario) {
        echo "✅ Comentário inserido com sucesso!\n";
        echo "   ID: " . $ultimo_comentario['id_comentario'] . "\n";
        echo "   Texto: " . substr($ultimo_comentario['comentario'], 0, 50) . "...\n";
    } else {
        echo "❌ Comentário NÃO foi inserido\n";
    }
} catch (PDOException $e) {
    echo "❌ Erro ao verificar inserção: " . $e->getMessage() . "\n";
}

echo "\n🔧 DIAGNÓSTICO COMPLETO\n";
echo "======================\n";
echo "1. Verifique se o JavaScript está fazendo a requisição corretamente\n";
echo "2. Verifique se há erros no console do navegador\n";
echo "3. Verifique se a URL da API está correta\n";
echo "4. Verifique se os dados estão sendo enviados no formato correto\n";
?>
