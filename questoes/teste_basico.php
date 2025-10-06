<?php
echo "<h1>TESTE BÁSICO</h1>";
echo "<p>Se você está vendo isso, o PHP está funcionando!</p>";

// Testar conexão com banco
try {
    require_once __DIR__ . '/conexao.php';
    echo "<p>✅ Conexão com banco funcionando!</p>";
    
    // Testar consulta simples
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
    $total = $stmt->fetchColumn();
    echo "<p>✅ Total de questões no banco: $total</p>";
    
    // Testar consulta de alternativas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas");
    $total_alt = $stmt->fetchColumn();
    echo "<p>✅ Total de alternativas no banco: $total_alt</p>";
    
    // Testar consulta de respostas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total_resp = $stmt->fetchColumn();
    echo "<p>✅ Total de respostas no banco: $total_resp</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>Teste de embaralhamento básico:</h2>";
$array_teste = ['A', 'B', 'C', 'D', 'E'];
echo "<p>Array original: " . implode(', ', $array_teste) . "</p>";

srand(123);
shuffle($array_teste);
echo "<p>Array embaralhado (seed 123): " . implode(', ', $array_teste) . "</p>";

srand(456);
shuffle($array_teste);
echo "<p>Array embaralhado (seed 456): " . implode(', ', $array_teste) . "</p>";

echo "<h2>Links para testar:</h2>";
echo "<p><a href='quiz.php' target='_blank'>quiz.php</a></p>";
echo "<p><a href='quiz_vertical_filtros.php?id=8&filtro=todas' target='_blank'>quiz_vertical_filtros.php</a></p>";
echo "<p><a href='desempenho.php' target='_blank'>desempenho.php</a></p>";
?>
