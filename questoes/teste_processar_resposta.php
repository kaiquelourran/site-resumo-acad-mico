<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>Teste do Processar Resposta</h1>";

// Simular dados de POST
$_POST['id_questao'] = 76;
$_POST['id_alternativa'] = 301;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Incluir o arquivo processar_resposta.php
ob_start();
include 'processar_resposta.php';
$output = ob_get_clean();

echo "<h2>Resultado:</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verificar se a resposta foi salva
echo "<h2>Verificação no Banco:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM respostas_usuario WHERE id_questao = ?");
    $stmt->execute([76]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        echo "<p>✅ Resposta encontrada no banco:</p>";
        echo "<pre>" . print_r($resultado, true) . "</pre>";
    } else {
        echo "<p>❌ Nenhuma resposta encontrada no banco para a questão 76</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro ao verificar banco: " . $e->getMessage() . "</p>";
}
?>