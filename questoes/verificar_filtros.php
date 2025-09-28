<?php
require_once 'conexao.php';

echo "<h2>Verificação dos Filtros - Debug</h2>";

// Verificar se a tabela respostas_usuario existe
echo "<h3>1. Verificando estrutura da tabela respostas_usuario:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($columns) {
        echo "<p style='color: green;'>✓ Tabela respostas_usuario existe!</p>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

// Verificar dados na tabela respostas_usuario
echo "<h3>2. Verificando dados na tabela respostas_usuario:</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total de respostas registradas: <strong>{$total['total']}</strong></p>";
    
    if ($total['total'] > 0) {
        $stmt = $pdo->query("SELECT * FROM respostas_usuario ORDER BY data_resposta DESC LIMIT 10");
        $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>ID Questão</th><th>ID Alternativa</th><th>Acertou</th><th>Data Resposta</th></tr>";
        foreach ($respostas as $resposta) {
            echo "<tr>";
            echo "<td>{$resposta['id']}</td>";
            echo "<td>{$resposta['id_questao']}</td>";
            echo "<td>{$resposta['id_alternativa']}</td>";
            echo "<td>" . ($resposta['acertou'] ? 'Sim' : 'Não') . "</td>";
            echo "<td>{$resposta['data_resposta']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro ao verificar dados: " . $e->getMessage() . "</p>";
}

// Verificar contadores por assunto
echo "<h3>3. Verificando contadores por assunto:</h3>";
try {
    $sql = "SELECT 
                a.id_assunto,
                a.nome as assunto_nome,
                COUNT(q.id_questao) as total_questoes,
                SUM(CASE WHEN r.id_questao IS NOT NULL THEN 1 ELSE 0 END) as respondidas,
                SUM(CASE WHEN r.id_questao IS NULL THEN 1 ELSE 0 END) as nao_respondidas,
                SUM(CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END) as acertadas,
                SUM(CASE WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 1 ELSE 0 END) as erradas
            FROM assuntos a
            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto
            LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
            GROUP BY a.id_assunto, a.nome
            ORDER BY a.id_assunto";
    
    $stmt = $pdo->query($sql);
    $assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Assunto</th><th>Total</th><th>Respondidas</th><th>Não Respondidas</th><th>Acertadas</th><th>Erradas</th></tr>";
    foreach ($assuntos as $assunto) {
        echo "<tr>";
        echo "<td>{$assunto['id_assunto']}</td>";
        echo "<td>{$assunto['assunto_nome']}</td>";
        echo "<td>{$assunto['total_questoes']}</td>";
        echo "<td>{$assunto['respondidas']}</td>";
        echo "<td>{$assunto['nao_respondidas']}</td>";
        echo "<td>{$assunto['acertadas']}</td>";
        echo "<td>{$assunto['erradas']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro ao verificar contadores: " . $e->getMessage() . "</p>";
}

// Testar query específica do filtro
echo "<h3>4. Testando query do filtro 'nao-respondidas' para assunto ID 1:</h3>";
try {
    $id_assunto = 1;
    $sql = "SELECT q.id_questao, q.enunciado, 
             CASE WHEN r.id_questao IS NOT NULL THEN 1 ELSE 0 END as respondida,
             CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END as acertou
             FROM questoes q 
             LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
             WHERE q.id_assunto = ? AND r.id_questao IS NULL
             ORDER BY q.id_questao";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_assunto]);
    $questoes_nao_respondidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Questões não respondidas encontradas: <strong>" . count($questoes_nao_respondidas) . "</strong></p>";
    
    if (count($questoes_nao_respondidas) > 0) {
        echo "<ul>";
        foreach (array_slice($questoes_nao_respondidas, 0, 5) as $questao) {
            echo "<li>ID {$questao['id_questao']}: " . substr($questao['enunciado'], 0, 100) . "...</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro ao testar filtro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='listar_questoes.php?id=1&filtro=todas'>← Voltar para listar questões</a></p>";
?>