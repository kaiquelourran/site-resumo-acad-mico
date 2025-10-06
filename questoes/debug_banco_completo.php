<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG COMPLETO DO BANCO DE DADOS</h1>";

echo "<h2>1. Estrutura da tabela 'questoes':</h2>";
try {
    $stmt = $pdo->query("DESCRIBE questoes");
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

echo "<h2>2. Estrutura da tabela 'alternativas':</h2>";
try {
    $stmt = $pdo->query("DESCRIBE alternativas");
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

echo "<h2>3. Estrutura da tabela 'respostas_usuario':</h2>";
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

echo "<h2>4. Estrutura da tabela 'assuntos':</h2>";
try {
    $stmt = $pdo->query("DESCRIBE assuntos");
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

echo "<h2>5. Dados de exemplo - Questões:</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM questoes LIMIT 3");
    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($questoes)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($questoes[0]) as $coluna) {
            echo "<th>$coluna</th>";
        }
        echo "</tr>";
        foreach ($questoes as $q) {
            echo "<tr>";
            foreach ($q as $valor) {
                echo "<td>" . htmlspecialchars(substr($valor, 0, 50)) . "...</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Dados de exemplo - Alternativas:</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM alternativas LIMIT 5");
    $alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($alternativas)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($alternativas[0]) as $coluna) {
            echo "<th>$coluna</th>";
        }
        echo "</tr>";
        foreach ($alternativas as $alt) {
            echo "<tr>";
            foreach ($alt as $valor) {
                echo "<td>" . htmlspecialchars(substr($valor, 0, 50)) . "...</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Dados de exemplo - Respostas do usuário:</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM respostas_usuario LIMIT 5");
    $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($respostas)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($respostas[0]) as $coluna) {
            echo "<th>$coluna</th>";
        }
        echo "</tr>";
        foreach ($respostas as $resp) {
            echo "<tr>";
            foreach ($resp as $valor) {
                echo "<td>" . htmlspecialchars(substr($valor, 0, 50)) . "...</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma resposta encontrada na tabela</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>8. Teste de embaralhamento real:</h2>";
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
        foreach ($alternativas as $index => $alt) {
            $letra = chr(65 + $index); // A, B, C, D, E
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
            $letra = chr(65 + $index); // A, B, C, D, E
            $correta = isset($alt['eh_correta']) ? ($alt['eh_correta'] ? ' (CORRETA)' : '') : ' (SEM CAMPO eh_correta)';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
