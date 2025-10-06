<?php
require_once 'conexao.php';

echo "<h1>Debug da Estrutura da Tabela 'alternativas'</h1>";

// Verificar estrutura da tabela
$stmt = $pdo->query("DESCRIBE alternativas");
$colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Estrutura da tabela 'alternativas':</h2>";
echo "<table border='1'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
echo "</table>";

// Buscar uma questão específica para teste
$id_questao = 92;
echo "<h2>Alternativas da questão $id_questao:</h2>";

$stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt->execute([$id_questao]);
$alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($alternativas) {
    echo "<table border='1'>";
    echo "<tr>";
    foreach (array_keys($alternativas[0]) as $campo) {
        echo "<th>$campo</th>";
    }
    echo "</tr>";
    
    foreach ($alternativas as $alt) {
        echo "<tr>";
        foreach ($alt as $valor) {
            echo "<td>" . htmlspecialchars($valor) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhuma alternativa encontrada para a questão $id_questao</p>";
}

// Testar diferentes campos para identificar qual é usado para marcar como correta
echo "<h2>Teste de campos para identificar alternativa correta:</h2>";

$campos_teste = ['eh_correta', 'correta', 'is_correct', 'correct', 'acertou', 'correta_flag', 'flag_correta'];

foreach ($campos_teste as $campo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alternativas WHERE $campo = 1");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Campo '$campo': " . $resultado['total'] . " alternativas marcadas como corretas</p>";
    } catch (Exception $e) {
        echo "<p>Campo '$campo': ERRO - " . $e->getMessage() . "</p>";
    }
}

// Verificar se existe campo 'texto' ou similar
echo "<h2>Verificação de campos de texto:</h2>";
$campos_texto = ['texto', 'alternativa_texto', 'descricao', 'conteudo', 'text'];

foreach ($campos_texto as $campo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alternativas WHERE $campo IS NOT NULL AND $campo != ''");
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Campo '$campo': " . $resultado['total'] . " alternativas com texto</p>";
    } catch (Exception $e) {
        echo "<p>Campo '$campo': ERRO - " . $e->getMessage() . "</p>";
    }
}
?>

