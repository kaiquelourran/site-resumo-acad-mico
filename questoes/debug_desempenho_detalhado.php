<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG DETALHADO DA PÁGINA DE DESEMPENHO</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;
$_SESSION['logged_in'] = true;

$user_id = $_SESSION['id_usuario'];

echo "<h2>1. Verificando sessão:</h2>";
echo "<p>User ID: $user_id</p>";
echo "<p>Logged in: " . ($_SESSION['logged_in'] ? 'SIM' : 'NÃO') . "</p>";

echo "<h2>2. Verificando se há dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas: $total</p>";
    
    if ($total > 0) {
        $stmt = $pdo->query("SELECT * FROM respostas_usuario WHERE user_id = $user_id LIMIT 5");
        $respostas_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Respostas do usuário $user_id: " . count($respostas_usuario) . "</p>";
        
        if (!empty($respostas_usuario)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Questão</th><th>Alternativa</th><th>Acertou</th><th>Data</th></tr>";
            foreach ($respostas_usuario as $resp) {
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
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testando consultas SQL da página de desempenho:</h2>";

try {
    // Total de respostas
    echo "<h3>3.1 Total de respostas:</h3>";
    $sql_total = "SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?";
    echo "<p>SQL: $sql_total</p>";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute([$user_id]);
    $total_respostas = $stmt_total->fetch()['total'];
    echo "<p>Resultado: $total_respostas</p>";
    
    // Respostas corretas
    echo "<h3>3.2 Respostas corretas:</h3>";
    $sql_corretas = "SELECT COUNT(*) as corretas FROM respostas_usuario WHERE user_id = ? AND acertou = 1";
    echo "<p>SQL: $sql_corretas</p>";
    $stmt_corretas = $pdo->prepare($sql_corretas);
    $stmt_corretas->execute([$user_id]);
    $respostas_corretas = $stmt_corretas->fetch()['corretas'];
    echo "<p>Resultado: $respostas_corretas</p>";
    
    // Percentual de acerto
    $percentual_acerto = $total_respostas > 0 ? round(($respostas_corretas / $total_respostas) * 100, 1) : 0;
    echo "<p>Percentual de acerto: $percentual_acerto%</p>";
    
    // Estatísticas por assunto
    echo "<h3>3.3 Estatísticas por assunto:</h3>";
    $sql_assuntos = "
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
    ";
    echo "<p>SQL: " . str_replace(["\n", "  "], [" ", " "], $sql_assuntos) . "</p>";
    
    $stmt_assuntos = $pdo->prepare($sql_assuntos);
    $stmt_assuntos->execute([$user_id]);
    $stats_assuntos = $stmt_assuntos->fetchAll();
    
    echo "<p>Resultado: " . count($stats_assuntos) . " assuntos encontrados</p>";
    
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
    echo "<h3>3.4 Últimas atividades:</h3>";
    $sql_atividades = "
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
    ";
    echo "<p>SQL: " . str_replace(["\n", "  "], [" ", " "], $sql_atividades) . "</p>";
    
    $stmt_atividades = $pdo->prepare($sql_atividades);
    $stmt_atividades->execute([$user_id]);
    $atividades_recentes = $stmt_atividades->fetchAll();
    
    echo "<p>Resultado: " . count($atividades_recentes) . " atividades encontradas</p>";
    
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
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

echo "<h2>4. Verificando se a página desempenho.php existe e é acessível:</h2>";
$desempenho_file = __DIR__ . '/desempenho.php';
if (file_exists($desempenho_file)) {
    echo "<p>✅ Arquivo desempenho.php existe</p>";
    echo "<p>Tamanho: " . filesize($desempenho_file) . " bytes</p>";
    echo "<p>Última modificação: " . date('Y-m-d H:i:s', filemtime($desempenho_file)) . "</p>";
} else {
    echo "<p>❌ Arquivo desempenho.php NÃO existe</p>";
}

echo "<h2>5. Próximos passos:</h2>";
echo "<p>1. Verifique se há dados na tabela respostas_usuario</p>";
echo "<p>2. Se não houver dados, responda algumas questões no quiz primeiro</p>";
echo "<p>3. Acesse: <a href='desempenho.php' target='_blank'>desempenho.php</a></p>";
?>
