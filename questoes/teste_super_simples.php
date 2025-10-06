<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TESTE SUPER SIMPLES</h1>";

// Testar conexão
try {
    require_once __DIR__ . '/conexao.php';
    echo "<p style='color: green;'>✅ Conexão com banco OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
    exit;
}

// Testar se a questão 92 existe
try {
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = 92");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<p style='color: green;'>✅ Questão 92 encontrada: " . htmlspecialchars($questao['enunciado']) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Questão 92 não encontrada</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar questão: " . $e->getMessage() . "</p>";
    exit;
}

// Testar se existem alternativas para a questão 92
try {
    $stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = 92 ORDER BY id_alternativa");
    $stmt->execute();
    $alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($alternativas) {
        echo "<p style='color: green;'>✅ " . count($alternativas) . " alternativas encontradas para questão 92</p>";
        
        echo "<h3>Alternativas:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Texto</th><th>eh_correta</th></tr>";
        foreach ($alternativas as $alt) {
            echo "<tr>";
            echo "<td>" . $alt['id_alternativa'] . "</td>";
            echo "<td>" . htmlspecialchars($alt['texto']) . "</td>";
            echo "<td>" . $alt['eh_correta'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Encontrar qual é a correta
        $correta = null;
        foreach ($alternativas as $alt) {
            if ($alt['eh_correta'] == 1) {
                $correta = $alt;
                break;
            }
        }
        
        if ($correta) {
            echo "<p style='color: green;'>✅ Alternativa correta: ID " . $correta['id_alternativa'] . " - " . htmlspecialchars($correta['texto']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Nenhuma alternativa marcada como correta!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nenhuma alternativa encontrada para questão 92</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar alternativas: " . $e->getMessage() . "</p>";
    exit;
}

// Testar embaralhamento
echo "<h3>Teste de Embaralhamento:</h3>";
$seed = 92 + (int)date('Ymd');
srand($seed);
shuffle($alternativas);

$letras = ['A', 'B', 'C', 'D', 'E'];
echo "<p>Seed usado: $seed</p>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Posição</th><th>Letra</th><th>ID</th><th>Texto</th><th>eh_correta</th></tr>";
foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    echo "<tr>";
    echo "<td>$index</td>";
    echo "<td>$letra</td>";
    echo "<td>" . $alt['id_alternativa'] . "</td>";
    echo "<td>" . htmlspecialchars($alt['texto']) . "</td>";
    echo "<td>" . $alt['eh_correta'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Testar mapeamento de letra para ID
echo "<h3>Teste de Mapeamento:</h3>";
foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    echo "<p>Letra $letra → ID " . $alt['id_alternativa'] . " (eh_correta: " . $alt['eh_correta'] . ")</p>";
}

// Testar se a tabela respostas_usuario existe
echo "<h3>Teste da Tabela respostas_usuario:</h3>";
try {
    $stmt = $pdo->prepare("DESCRIBE respostas_usuario");
    $stmt->execute();
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Tabela respostas_usuario existe</p>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th></tr>";
    foreach ($colunas as $col) {
        echo "<tr><td>" . $col['Field'] . "</td><td>" . $col['Type'] . "</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela respostas_usuario: " . $e->getMessage() . "</p>";
}

echo "<h3>Teste de Inserção:</h3>";
try {
    // Tentar inserir uma resposta de teste
    $stmt = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) VALUES (?, ?, ?, NOW())");
    $stmt->execute([92, $alternativas[0]['id_alternativa'], 1]);
    echo "<p style='color: green;'>✅ Inserção de teste funcionou</p>";
    
    // Remover o registro de teste
    $stmt = $pdo->prepare("DELETE FROM respostas_usuario WHERE id_questao = 92 AND data_resposta > NOW() - INTERVAL 1 MINUTE");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Limpeza de teste funcionou</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na inserção de teste: " . $e->getMessage() . "</p>";
}

echo "<h3>Teste JSON:</h3>";
$teste_json = [
    'success' => true,
    'acertou' => true,
    'alternativa_correta' => 123,
    'explicacao' => '',
    'message' => 'Teste OK'
];

echo "<p>JSON de teste:</p>";
echo "<pre>" . json_encode($teste_json, JSON_PRETTY_PRINT) . "</pre>";

echo "<h3>Teste de Requisição AJAX:</h3>";
echo "<button onclick='testarAjax()'>Testar AJAX</button>";
echo "<div id='resultado'></div>";

?>
<script>
function testarAjax() {
    const formData = new FormData();
    formData.append('id_questao', '92');
    formData.append('alternativa_selecionada', 'C');
    
    document.getElementById('resultado').innerHTML = 'Enviando...';
    
    fetch('teste_ajax_simples.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Status:', response.status);
        return response.text();
    })
    .then(data => {
        document.getElementById('resultado').innerHTML = '<pre>' + data + '</pre>';
    })
    .catch(error => {
        document.getElementById('resultado').innerHTML = 'Erro: ' + error;
    });
}
</script>

