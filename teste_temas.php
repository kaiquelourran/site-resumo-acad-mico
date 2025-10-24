<?php
// Arquivo de teste para verificar se buscar_temas.php está funcionando
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Teste de Conexão com Banco de Dados</h1>";

try {
    // Testar conexão
    require_once 'questoes/conexao.php';
    echo "<p style='color: green;'>✅ Conexão com banco estabelecida com sucesso!</p>";
    
    // Testar consulta
    $sql = "SELECT COUNT(*) as total FROM assuntos";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p style='color: green;'>✅ Total de assuntos no banco: " . $result['total'] . "</p>";
    
    // Testar buscar_temas.php
    echo "<h2>Testando buscar_temas.php:</h2>";
    $temas = file_get_contents('http://localhost/buscar_temas.php');
    if ($temas) {
        $temas_array = json_decode($temas, true);
        echo "<p style='color: green;'>✅ buscar_temas.php retornou dados:</p>";
        echo "<pre>" . print_r($temas_array, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ buscar_temas.php não retornou dados</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
