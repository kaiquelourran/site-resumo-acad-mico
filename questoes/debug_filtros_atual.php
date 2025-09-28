<?php
require_once 'conexao.php';

echo "<h2>Debug dos Filtros - Estado Atual</h2>";

// Verificar se a tabela respostas_usuario existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'respostas_usuario'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabela 'respostas_usuario' existe</p>";
        
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE respostas_usuario");
        echo "<h3>Estrutura da tabela respostas_usuario:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar dados na tabela
        $stmt = $pdo->query("SELECT * FROM respostas_usuario");
        $respostas = $stmt->fetchAll();
        echo "<h3>Dados na tabela respostas_usuario (" . count($respostas) . " registros):</h3>";
        if (count($respostas) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID Questão</th><th>ID Alternativa</th><th>Acertou</th><th>Data Resposta</th></tr>";
            foreach ($respostas as $resposta) {
                echo "<tr>";
                echo "<td>" . $resposta['id_questao'] . "</td>";
                echo "<td>" . $resposta['id_alternativa'] . "</td>";
                echo "<td>" . ($resposta['acertou'] ? 'Sim' : 'Não') . "</td>";
                echo "<td>" . $resposta['data_resposta'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ Nenhuma resposta encontrada na tabela</p>";
        }
    } else {
        echo "<p>❌ Tabela 'respostas_usuario' não existe</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

// Testar queries dos filtros para id_assunto = 8
$id_assunto = 8;
echo "<h3>Testando queries dos filtros para id_assunto = $id_assunto:</h3>";

// Query base
$sql_base = "SELECT q.id_questao, q.enunciado, 
             CASE WHEN r.id_questao IS NOT NULL THEN 1 ELSE 0 END as respondida,
             CASE WHEN r.acertou = 1 THEN 1 ELSE 0 END as acertou
             FROM questoes q 
             LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
             WHERE q.id_assunto = ?";

// Testar contadores
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
    
    echo "<h4>Contadores:</h4>";
    echo "<ul>";
    echo "<li>Total de questões: " . $counts['total'] . "</li>";
    echo "<li>Questões respondidas: " . $counts['respondidas'] . "</li>";
    echo "<li>Questões não respondidas: " . $counts['nao_respondidas'] . "</li>";
    echo "<li>Questões acertadas: " . $counts['acertadas'] . "</li>";
    echo "<li>Questões erradas: " . $counts['erradas'] . "</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Erro ao executar query de contadores: " . $e->getMessage() . "</p>";
}

// Testar cada filtro
$filtros = ['todas', 'respondidas', 'nao-respondidas', 'acertadas', 'erradas'];

foreach ($filtros as $filtro) {
    echo "<h4>Testando filtro: $filtro</h4>";
    
    $sql_questoes = $sql_base;
    switch($filtro) {
        case 'respondidas':
            $sql_questoes .= " AND r.id_questao IS NOT NULL";
            break;
        case 'nao-respondidas':
            $sql_questoes .= " AND r.id_questao IS NULL";
            break;
        case 'acertadas':
            $sql_questoes .= " AND r.acertou = 1";
            break;
        case 'erradas':
            $sql_questoes .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
            break;
    }
    $sql_questoes .= " ORDER BY q.id_questao";
    
    try {
        $stmt_questoes = $pdo->prepare($sql_questoes);
        $stmt_questoes->execute([$id_assunto]);
        $result_questoes = $stmt_questoes->fetchAll();
        
        echo "<p>Questões encontradas: " . count($result_questoes) . "</p>";
        if (count($result_questoes) > 0) {
            echo "<ul>";
            foreach ($result_questoes as $questao) {
                $status = $questao['respondida'] ? ($questao['acertou'] ? 'acertada' : 'errada') : 'nao_respondida';
                echo "<li>ID: " . $questao['id_questao'] . " - Status: $status</li>";
            }
            echo "</ul>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Erro ao executar query do filtro $filtro: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Verificação de Sessão:</h3>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Dados da sessão: " . print_r($_SESSION, true) . "</p>";
?>