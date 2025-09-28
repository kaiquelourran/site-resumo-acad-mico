<?php
session_start();
require_once 'conexao.php';

echo "<h1>Debug Completo dos Filtros</h1>";

// Verificar se a tabela existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'respostas_usuario'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Tabela respostas_usuario existe</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabela respostas_usuario NÃO existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

// Verificar estrutura da tabela
echo "<h2>Estrutura da Tabela</h2>";
try {
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar estrutura: " . $e->getMessage() . "</p>";
}

// Verificar dados na tabela
echo "<h2>Dados na Tabela</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM respostas_usuario ORDER BY data_resposta DESC");
    $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($respostas) > 0) {
        echo "<p>Total de respostas: " . count($respostas) . "</p>";
        echo "<table border='1'><tr><th>ID</th><th>ID Questão</th><th>ID Alternativa</th><th>Acertou</th><th>Data</th></tr>";
        foreach ($respostas as $resp) {
            echo "<tr>";
            echo "<td>" . $resp['id'] . "</td>";
            echo "<td>" . $resp['id_questao'] . "</td>";
            echo "<td>" . $resp['id_alternativa'] . "</td>";
            echo "<td>" . ($resp['acertou'] ? 'Sim' : 'Não') . "</td>";
            echo "<td>" . $resp['data_resposta'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>Nenhuma resposta encontrada na tabela</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar dados: " . $e->getMessage() . "</p>";
}

// Testar cada filtro para o assunto ID 8
$id_assunto = 8;
echo "<h2>Teste dos Filtros para Assunto ID $id_assunto</h2>";

// Total de questões
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Total de questões:</strong> $total</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao contar total: " . $e->getMessage() . "</p>";
}

// Questões respondidas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT q.id_questao) as respondidas 
        FROM questoes q 
        INNER JOIN respostas_usuario ru ON q.id_questao = ru.id_questao 
        WHERE q.id_assunto = ?
    ");
    $stmt->execute([$id_assunto]);
    $respondidas = $stmt->fetch(PDO::FETCH_ASSOC)['respondidas'];
    echo "<p><strong>Questões respondidas:</strong> $respondidas</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao contar respondidas: " . $e->getMessage() . "</p>";
}

// Questões não respondidas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as nao_respondidas 
        FROM questoes q 
        LEFT JOIN respostas_usuario ru ON q.id_questao = ru.id_questao 
        WHERE q.id_assunto = ? AND ru.id_questao IS NULL
    ");
    $stmt->execute([$id_assunto]);
    $nao_respondidas = $stmt->fetch(PDO::FETCH_ASSOC)['nao_respondidas'];
    echo "<p><strong>Questões não respondidas:</strong> $nao_respondidas</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao contar não respondidas: " . $e->getMessage() . "</p>";
}

// Questões acertadas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT q.id_questao) as acertadas 
        FROM questoes q 
        INNER JOIN respostas_usuario ru ON q.id_questao = ru.id_questao 
        WHERE q.id_assunto = ? AND ru.acertou = 1
    ");
    $stmt->execute([$id_assunto]);
    $acertadas = $stmt->fetch(PDO::FETCH_ASSOC)['acertadas'];
    echo "<p><strong>Questões acertadas:</strong> $acertadas</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao contar acertadas: " . $e->getMessage() . "</p>";
}

// Questões erradas
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT q.id_questao) as erradas 
        FROM questoes q 
        INNER JOIN respostas_usuario ru ON q.id_questao = ru.id_questao 
        WHERE q.id_assunto = ? AND ru.acertou = 0
    ");
    $stmt->execute([$id_assunto]);
    $erradas = $stmt->fetch(PDO::FETCH_ASSOC)['erradas'];
    echo "<p><strong>Questões erradas:</strong> $erradas</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao contar erradas: " . $e->getMessage() . "</p>";
}

// Testar query específica do filtro 'nao-respondidas'
echo "<h2>Teste da Query do Filtro 'Não Respondidas'</h2>";
try {
    $stmt = $pdo->prepare("
        SELECT q.*, 
               CASE 
                   WHEN ru.acertou = 1 THEN 'acertada'
                   WHEN ru.acertou = 0 THEN 'errada'
                   ELSE 'nao_respondida'
               END as status_resposta
        FROM questoes q 
        LEFT JOIN respostas_usuario ru ON q.id_questao = ru.id_questao 
        WHERE q.id_assunto = ? AND ru.id_questao IS NULL
        ORDER BY q.id_questao
    ");
    $stmt->execute([$id_assunto]);
    $questoes_nao_respondidas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Questões encontradas: " . count($questoes_nao_respondidas) . "</p>";
    if (count($questoes_nao_respondidas) > 0) {
        echo "<ul>";
        foreach ($questoes_nao_respondidas as $q) {
            echo "<li>ID: {$q['id_questao']} - Status: {$q['status_resposta']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro na query não respondidas: " . $e->getMessage() . "</p>";
}

// Verificar se há questões duplicadas nas respostas
echo "<h2>Verificar Duplicações</h2>";
try {
    $stmt = $pdo->query("
        SELECT id_questao, COUNT(*) as count 
        FROM respostas_usuario 
        GROUP BY id_questao 
        HAVING COUNT(*) > 1
    ");
    $duplicadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicadas) > 0) {
        echo "<p style='color: orange;'>Questões com respostas duplicadas:</p>";
        foreach ($duplicadas as $dup) {
            echo "<p>Questão ID {$dup['id_questao']}: {$dup['count']} respostas</p>";
        }
    } else {
        echo "<p style='color: green;'>✓ Nenhuma duplicação encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao verificar duplicações: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='listar_questoes.php?id=8&filtro=todas'>← Voltar para listar questões</a></p>";
?>