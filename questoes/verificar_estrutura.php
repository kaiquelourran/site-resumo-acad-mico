<?php
require_once 'conexao.php';

echo "<h2>🔍 Estrutura das Tabelas</h2>";

try {
    // Verificar estrutura da tabela assuntos
    echo "<h3>Tabela: assuntos</h3>";
    $stmt = $pdo->query("DESCRIBE assuntos");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Verificar estrutura da tabela questoes
    echo "<h3>Tabela: questoes</h3>";
    $stmt = $pdo->query("DESCRIBE questoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Verificar estrutura da tabela alternativas
    echo "<h3>Tabela: alternativas</h3>";
    $stmt = $pdo->query("DESCRIBE alternativas");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";

    // Verificar dados existentes
    echo "<h3>Dados Existentes</h3>";
    
    $stmt = $pdo->query("SELECT * FROM assuntos");
    $assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Assuntos:</strong> " . count($assuntos) . "</p>";
    foreach ($assuntos as $assunto) {
        echo "<p>- " . htmlspecialchars($assunto['nome']) . " (ID: " . $assunto['id_assunto'] . ")</p>";
    }

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
    $total_questoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Total de questões:</strong> $total_questoes</p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas");
    $total_alternativas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Total de alternativas:</strong> $total_alternativas</p>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>

<br><br>
<a href="gerenciar_questoes_sem_auth.php">📋 Gerenciar Questões</a>