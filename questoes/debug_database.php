<?php
require_once 'conexao.php';

echo "<h2>Debug Database</h2>";

// Verificar se a conexão está funcionando
try {
    $pdo->query("SELECT 1");
    echo "<p>✅ Conexão com banco OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    exit;
}

// Contar total de questões
$stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
$total = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Total de questões:</strong> " . $total['total'] . "</p>";

// Contar questões por assunto
$stmt = $pdo->query("SELECT id_assunto, COUNT(*) as total FROM questoes GROUP BY id_assunto");
$por_assunto = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Questões por assunto:</h3>";
foreach ($por_assunto as $assunto) {
    echo "<p>Assunto {$assunto['id_assunto']}: {$assunto['total']} questões</p>";
}

// Mostrar algumas questões de exemplo
$stmt = $pdo->query("SELECT id_questao, id_assunto, LEFT(enunciado, 100) as enunciado_resumo FROM questoes LIMIT 5");
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Primeiras 5 questões:</h3>";
foreach ($questoes as $q) {
    echo "<p>ID: {$q['id_questao']}, Assunto: {$q['id_assunto']}, Enunciado: {$q['enunciado_resumo']}...</p>";
}

// Verificar se há assuntos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM assuntos");
$total_assuntos = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<p><strong>Total de assuntos:</strong> " . $total_assuntos['total'] . "</p>";

// Mostrar assuntos
$stmt = $pdo->query("SELECT id_assunto, nome FROM assuntos LIMIT 10");
$assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Assuntos disponíveis:</h3>";
foreach ($assuntos as $assunto) {
    echo "<p>ID: {$assunto['id_assunto']}, Nome: {$assunto['nome']}</p>";
}
?>