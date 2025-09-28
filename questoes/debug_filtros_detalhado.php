<?php
session_start();
require_once 'conexao.php';

echo "<h1>Debug Detalhado dos Filtros</h1>";

$id_assunto = 8;
$filtro = 'nao-respondidas';

echo "<h2>Parâmetros:</h2>";
echo "ID Assunto: " . $id_assunto . "<br>";
echo "Filtro: " . $filtro . "<br><br>";

// Verificar se a tabela respostas_usuario existe
echo "<h2>1. Verificando tabela respostas_usuario:</h2>";
try {
    $sql_check = "SHOW TABLES LIKE 'respostas_usuario'";
    $result = $pdo->query($sql_check);
    if ($result->rowCount() > 0) {
        echo "✅ Tabela respostas_usuario existe<br>";
        
        // Mostrar estrutura da tabela
        $sql_structure = "DESCRIBE respostas_usuario";
        $structure = $pdo->query($sql_structure)->fetchAll();
        echo "<strong>Estrutura da tabela:</strong><br>";
        foreach ($structure as $column) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
        }
        
        // Mostrar dados da tabela
        echo "<br><strong>Dados na tabela:</strong><br>";
        $sql_data = "SELECT * FROM respostas_usuario WHERE id_questao IN (SELECT id_questao FROM questoes WHERE id_assunto = ?)";
        $stmt_data = $pdo->prepare($sql_data);
        $stmt_data->execute([$id_assunto]);
        $data = $stmt_data->fetchAll();
        
        if (count($data) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>ID Questão</th><th>ID Alternativa</th><th>Acertou</th><th>Data</th></tr>";
            foreach ($data as $row) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['id_questao'] . "</td>";
                echo "<td>" . $row['id_alternativa'] . "</td>";
                echo "<td>" . ($row['acertou'] ? 'Sim' : 'Não') . "</td>";
                echo "<td>" . $row['data_resposta'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "Nenhum dado encontrado para o assunto " . $id_assunto;
        }
        
    } else {
        echo "❌ Tabela respostas_usuario NÃO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar tabela: " . $e->getMessage() . "<br>";
}

echo "<br><h2>2. Testando Query do Filtro 'nao-respondidas':</h2>";

$sql_base = "SELECT q.id_questao, q.enunciado, 
             CASE WHEN r.id_questao IS NOT NULL THEN 1 ELSE 0 END as respondida,
             CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END as acertou
             FROM questoes q 
             LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
             WHERE q.id_assunto = ?";

$sql_questoes = $sql_base . " AND r.id_questao IS NULL ORDER BY q.id_questao";

echo "<strong>Query executada:</strong><br>";
echo "<code>" . str_replace('?', $id_assunto, $sql_questoes) . "</code><br><br>";

try {
    $stmt_questoes = $pdo->prepare($sql_questoes);
    $stmt_questoes->execute([$id_assunto]);
    $result_questoes = $stmt_questoes->fetchAll();
    
    echo "<strong>Resultado:</strong><br>";
    echo "Número de questões não respondidas encontradas: " . count($result_questoes) . "<br><br>";
    
    if (count($result_questoes) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID Questão</th><th>Enunciado (primeiros 100 chars)</th><th>Respondida</th><th>Acertou</th></tr>";
        foreach ($result_questoes as $questao) {
            echo "<tr>";
            echo "<td>" . $questao['id_questao'] . "</td>";
            echo "<td>" . substr(strip_tags($questao['enunciado']), 0, 100) . "...</td>";
            echo "<td>" . ($questao['respondida'] ? 'Sim' : 'Não') . "</td>";
            echo "<td>" . ($questao['acertou'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao executar query: " . $e->getMessage() . "<br>";
}

echo "<br><h2>3. Testando Contadores:</h2>";

$sql_counts = "SELECT 
               COUNT(*) as total,
               SUM(CASE WHEN r.id_questao IS NOT NULL THEN 1 ELSE 0 END) as respondidas,
               SUM(CASE WHEN r.id_questao IS NULL THEN 1 ELSE 0 END) as nao_respondidas,
               SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) as acertadas,
               SUM(CASE WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 1 ELSE 0 END) as erradas
               FROM questoes q 
               LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
               WHERE q.id_assunto = ?";

try {
    $stmt_counts = $pdo->prepare($sql_counts);
    $stmt_counts->execute([$id_assunto]);
    $counts = $stmt_counts->fetch();
    
    echo "<strong>Contadores:</strong><br>";
    echo "Total: " . $counts['total'] . "<br>";
    echo "Respondidas: " . $counts['respondidas'] . "<br>";
    echo "Não Respondidas: " . $counts['nao_respondidas'] . "<br>";
    echo "Acertadas: " . $counts['acertadas'] . "<br>";
    echo "Erradas: " . $counts['erradas'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erro ao executar contadores: " . $e->getMessage() . "<br>";
}

echo "<br><h2>4. Informações da Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<br><h2>5. Informações do GET:</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";
?>