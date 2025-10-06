<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE SIMPLES DA PÁGINA DE DESEMPENHO</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;
$user_id = 1;

echo "<h2>1. Verificando se há dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas na tabela: $total</p>";
    
    if ($total > 0) {
        $stmt = $pdo->query("SELECT * FROM respostas_usuario WHERE user_id = $user_id LIMIT 5");
        $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Respostas do usuário $user_id: " . count($respostas) . "</p>";
        
        if (!empty($respostas)) {
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
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhuma resposta do usuário $user_id encontrada</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma resposta na tabela</p>";
        echo "<p>Para testar a página de desempenho, você precisa responder algumas questões primeiro.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Testando consulta simples de desempenho:</h2>";
try {
    $sql = "SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $total_respostas = $stmt->fetch()['total'];
    echo "<p>Total de respostas do usuário: $total_respostas</p>";
    
    if ($total_respostas > 0) {
        $sql = "SELECT COUNT(*) as corretas FROM respostas_usuario WHERE user_id = ? AND acertou = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $respostas_corretas = $stmt->fetch()['corretas'];
        echo "<p>Respostas corretas: $respostas_corretas</p>";
        
        $percentual = round(($respostas_corretas / $total_respostas) * 100, 1);
        echo "<p>Percentual de acerto: $percentual%</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na consulta: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificando se a página desempenho.php existe:</h2>";
$desempenho_file = __DIR__ . '/desempenho.php';
if (file_exists($desempenho_file)) {
    echo "<p>✅ Arquivo desempenho.php existe</p>";
    echo "<p>Tamanho: " . filesize($desempenho_file) . " bytes</p>";
} else {
    echo "<p>❌ Arquivo desempenho.php NÃO existe</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Se não há dados, responda algumas questões no quiz primeiro</p>";
echo "<p>2. Teste: <a href='desempenho.php' target='_blank'>desempenho.php</a></p>";
echo "<p>3. Teste: <a href='teste_questao_99.php' target='_blank'>teste_questao_99.php</a></p>";
?>
