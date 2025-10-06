<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG FINAL DA PÁGINA DE DESEMPENHO</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;
$user_id = 1;

echo "<h2>1. Verificando estrutura da tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificando dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas na tabela: $total</p>";
    
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
        
        // Verificar respostas do usuário específico
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_usuario = $stmt->fetchColumn();
        echo "<p>Respostas do usuário $user_id: $total_usuario</p>";
        
        if ($total_usuario > 0) {
            $stmt = $pdo->prepare("SELECT * FROM respostas_usuario WHERE user_id = ? LIMIT 3");
            $stmt->execute([$user_id]);
            $respostas_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Respostas do usuário $user_id:</h3>";
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
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma resposta na tabela</p>";
        echo "<p>Para testar a página de desempenho, você precisa responder algumas questões primeiro.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Testando consultas da página de desempenho:</h2>";
try {
    // Total de respostas
    $sql_total = "SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute([$user_id]);
    $total_respostas = $stmt_total->fetch()['total'];
    echo "<p>Total de respostas do usuário: $total_respostas</p>";
    
    if ($total_respostas > 0) {
        // Respostas corretas
        $sql_corretas = "SELECT COUNT(*) as corretas FROM respostas_usuario WHERE user_id = ? AND acertou = 1";
        $stmt_corretas = $pdo->prepare($sql_corretas);
        $stmt_corretas->execute([$user_id]);
        $respostas_corretas = $stmt_corretas->fetch()['corretas'];
        echo "<p>Respostas corretas: $respostas_corretas</p>";
        
        // Percentual de acerto
        $percentual_acerto = round(($respostas_corretas / $total_respostas) * 100, 1);
        echo "<p>Percentual de acerto: $percentual_acerto%</p>";
        
        // Estatísticas por assunto
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
        
        $stmt_assuntos = $pdo->prepare($sql_assuntos);
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
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma resposta do usuário para calcular estatísticas</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro nas consultas: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Verificando se a página desempenho.php existe:</h2>";
$desempenho_file = __DIR__ . '/desempenho.php';
if (file_exists($desempenho_file)) {
    echo "<p>✅ Arquivo desempenho.php existe</p>";
    echo "<p>Tamanho: " . filesize($desempenho_file) . " bytes</p>";
    echo "<p>Última modificação: " . date('Y-m-d H:i:s', filemtime($desempenho_file)) . "</p>";
} else {
    echo "<p>❌ Arquivo desempenho.php NÃO existe</p>";
}

echo "<h2>5. Próximos passos:</h2>";
echo "<p>1. Se não há dados, responda algumas questões no quiz primeiro</p>";
echo "<p>2. Teste: <a href='desempenho.php' target='_blank'>desempenho.php</a></p>";
echo "<p>3. Teste: <a href='teste_embaralhamento_real.php' target='_blank'>teste_embaralhamento_real.php</a></p>";
?>
