<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>VERIFICAÇÃO RÁPIDA DE DADOS</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;

echo "<h2>1. Verificando dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas: $total</p>";
    
    if ($total > 0) {
        $stmt = $pdo->query("SELECT * FROM respostas_usuario LIMIT 3");
        $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimas 3 respostas:</h3>";
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
        echo "<p style='color: orange;'>⚠️ Nenhuma resposta encontrada na tabela</p>";
        echo "<p>Para testar a página de desempenho, você precisa responder algumas questões primeiro.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificando estrutura da tabela alternativas:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE alternativas");
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

echo "<h2>3. Testando embaralhamento:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM questoes LIMIT 1");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<p>Questão ID: " . $questao['id_questao'] . "</p>";
        
        // Buscar alternativas
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$questao['id_questao']]);
        $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Alternativas ORIGINAIS:</h3>";
        $letras = ['A', 'B', 'C', 'D', 'E'];
        foreach ($alternativas as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = isset($alt['eh_correta']) ? ($alt['eh_correta'] ? ' (CORRETA)' : '') : ' (SEM CAMPO eh_correta)';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
        }
        
        // Testar embaralhamento
        $seed = $questao['id_questao'] + (int)date('Ymd');
        srand($seed);
        $alternativas_embaralhadas = $alternativas;
        shuffle($alternativas_embaralhadas);
        
        echo "<h3>Alternativas EMBARALHADAS (seed: $seed):</h3>";
        foreach ($alternativas_embaralhadas as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = isset($alt['eh_correta']) ? ($alt['eh_correta'] ? ' (CORRETA)' : '') : ' (SEM CAMPO eh_correta)';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
        }
        
        // Verificar se mudou
        $mudou = false;
        for ($i = 0; $i < count($alternativas); $i++) {
            if ($alternativas[$i]['id_alternativa'] !== $alternativas_embaralhadas[$i]['id_alternativa']) {
                $mudou = true;
                break;
            }
        }
        
        echo "<p style='color: " . ($mudou ? 'green' : 'red') . ";'>" . 
             ($mudou ? '✅ Embaralhamento funcionou!' : '❌ Embaralhamento não funcionou!') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Se não há dados na tabela, responda algumas questões no quiz primeiro</p>";
echo "<p>2. Teste o embaralhamento: <a href='quiz.php' target='_blank'>quiz.php</a></p>";
echo "<p>3. Teste a página de desempenho: <a href='desempenho.php' target='_blank'>desempenho.php</a></p>";
?>
