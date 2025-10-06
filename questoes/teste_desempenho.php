<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DA PÁGINA DE DESEMPENHO</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;
$_SESSION['logged_in'] = true;

echo "<h2>1. Verificando sessão:</h2>";
echo "<p>User ID: " . ($_SESSION['id_usuario'] ?? 'NÃO DEFINIDO') . "</p>";
echo "<p>Logged in: " . ($_SESSION['logged_in'] ? 'SIM' : 'NÃO') . "</p>";

echo "<h2>2. Verificando estrutura da tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificando dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas: $total</p>";
    
    if ($total > 0) {
        $stmt = $pdo->query("SELECT * FROM respostas_usuario LIMIT 5");
        $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimas 5 respostas:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Questão</th><th>Alternativa</th><th>Acertou</th><th>Data</th></tr>";
        foreach ($respostas as $resp) {
            echo "<tr>";
            echo "<td>" . $resp['id'] . "</td>";
            echo "<td>" . $resp['user_id'] . "</td>";
            echo "<td>" . $resp['id_questao'] . "</td>";
            echo "<td>" . $resp['id_alternativa'] . "</td>";
            echo "<td>" . ($resp['acertou'] ? 'SIM' : 'NÃO') . "</td>";
            echo "<td>" . $resp['data_resposta'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testando consultas da página de desempenho:</h2>";

$user_id = $_SESSION['id_usuario'];

try {
    // Total de respostas
    $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?");
    $stmt_total->execute([$user_id]);
    $total_respostas = $stmt_total->fetch()['total'];
    echo "<p>Total de respostas: $total_respostas</p>";
    
    // Respostas corretas
    $stmt_corretas = $pdo->prepare("SELECT COUNT(*) as corretas FROM respostas_usuario WHERE user_id = ? AND acertou = 1");
    $stmt_corretas->execute([$user_id]);
    $respostas_corretas = $stmt_corretas->fetch()['corretas'];
    echo "<p>Respostas corretas: $respostas_corretas</p>";
    
    // Percentual de acerto
    $percentual_acerto = $total_respostas > 0 ? round(($respostas_corretas / $total_respostas) * 100, 1) : 0;
    echo "<p>Percentual de acerto: $percentual_acerto%</p>";
    
    // Estatísticas por assunto
    $stmt_assuntos = $pdo->prepare("
        SELECT 
            a.nome as nome_assunto,
            COUNT(r.id) as total_questoes,
            SUM(r.acertou) as acertos,
            ROUND((SUM(r.acertou) / COUNT(r.id)) * 100, 1) as percentual
        FROM respostas_usuario r
        JOIN questoes q ON r.id_questao = q.id_questao
        JOIN assuntos a ON q.id_assunto = a.id_assunto
        WHERE r.user_id = ?
        GROUP BY a.id_assunto, a.nome
        ORDER BY percentual DESC
    ");
    $stmt_assuntos->execute([$user_id]);
    $stats_assuntos = $stmt_assuntos->fetchAll();
    
    echo "<h3>Estatísticas por assunto:</h3>";
    if (!empty($stats_assuntos)) {
        echo "<table border='1'>";
        echo "<tr><th>Assunto</th><th>Total Questões</th><th>Acertos</th><th>Percentual</th></tr>";
        foreach ($stats_assuntos as $assunto) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($assunto['nome_assunto']) . "</td>";
            echo "<td>" . $assunto['total_questoes'] . "</td>";
            echo "<td>" . $assunto['acertos'] . "</td>";
            echo "<td>" . $assunto['percentual'] . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma estatística por assunto encontrada</p>";
    }
    
    // Últimas atividades
    $stmt_atividades = $pdo->prepare("
        SELECT 
            a.nome as nome_assunto,
            q.enunciado as pergunta,
            r.acertou as resposta_correta,
            r.data_resposta
        FROM respostas_usuario r
        JOIN questoes q ON r.id_questao = q.id_questao
        JOIN assuntos a ON q.id_assunto = a.id_assunto
        WHERE r.user_id = ?
        ORDER BY r.data_resposta DESC
        LIMIT 5
    ");
    $stmt_atividades->execute([$user_id]);
    $atividades_recentes = $stmt_atividades->fetchAll();
    
    echo "<h3>Últimas atividades:</h3>";
    if (!empty($atividades_recentes)) {
        echo "<table border='1'>";
        echo "<tr><th>Assunto</th><th>Pergunta</th><th>Acertou</th><th>Data</th></tr>";
        foreach ($atividades_recentes as $atividade) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($atividade['nome_assunto']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($atividade['pergunta'], 0, 50)) . "...</td>";
            echo "<td>" . ($atividade['resposta_correta'] ? 'SIM' : 'NÃO') . "</td>";
            echo "<td>" . $atividade['data_resposta'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma atividade recente encontrada</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro nas consultas: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Próximos passos:</h2>";
echo "<p>1. Se as consultas funcionaram, a página de desempenho deve estar funcionando</p>";
echo "<p>2. Acesse: <a href='desempenho.php' target='_blank'>desempenho.php</a></p>";
echo "<p>3. Se não funcionar, verifique se há dados na tabela respostas_usuario</p>";
?>
