<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migração de Estatísticas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0072FF;
            margin-bottom: 20px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            margin-bottom: 15px;
        }
        .success {
            color: #28a745;
            padding: 10px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            color: #0056b3;
            padding: 10px;
            background: #cce5ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background: #0072FF;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0072FF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔄 Migração do Banco de Dados - Estatísticas</h1>";

try {
    // 1. Verificar estrutura atual
    echo "<h2>1. Verificando estrutura atual da tabela respostas_usuario:</h2>";
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Verificar índices existentes
    echo "<h2>2. Verificando índices existentes:</h2>";
    $stmt = $pdo->query("SHOW INDEX FROM respostas_usuario");
    $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_unique_questao = false;
    $has_idx_questao = false;
    
    if (!empty($indices)) {
        echo "<table>";
        echo "<tr><th>Nome do Índice</th><th>Coluna</th><th>Único</th></tr>";
        foreach ($indices as $indice) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($indice['Key_name']) . "</td>";
            echo "<td>" . htmlspecialchars($indice['Column_name']) . "</td>";
            echo "<td>" . ($indice['Non_unique'] == 0 ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
            
            if ($indice['Key_name'] === 'unique_questao') {
                $has_unique_questao = true;
            }
            if ($indice['Key_name'] === 'idx_questao') {
                $has_idx_questao = true;
            }
        }
        echo "</table>";
    } else {
        echo "<div class='info'>ℹ️ Nenhum índice encontrado além da chave primária</div>";
    }
    
    // 3. Remover constraint UNIQUE se existir
    echo "<h2>3. Removendo constraint UNIQUE (se existir):</h2>";
    if ($has_unique_questao) {
        try {
            $pdo->exec("ALTER TABLE respostas_usuario DROP INDEX unique_questao");
            echo "<div class='success'>✅ Índice UNIQUE 'unique_questao' removido com sucesso!</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao remover índice: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Índice UNIQUE 'unique_questao' não existe, nada a fazer</div>";
    }
    
    // 4. Adicionar índice normal para performance
    echo "<h2>4. Adicionando índice para performance:</h2>";
    if (!$has_idx_questao) {
        try {
            $pdo->exec("ALTER TABLE respostas_usuario ADD INDEX idx_questao (id_questao)");
            echo "<div class='success'>✅ Índice 'idx_questao' criado com sucesso!</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erro ao criar índice: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Índice 'idx_questao' já existe</div>";
    }
    
    // 5. Verificar dados existentes
    echo "<h2>5. Verificando dados existentes:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetch()['total'];
    echo "<div class='info'>📊 Total de registros na tabela: <strong>{$total}</strong></div>";
    
    // 6. Estrutura final
    echo "<h2>6. Estrutura final da tabela:</h2>";
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas_final = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas_final as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='success'>
        <h3>✅ Migração concluída com sucesso!</h3>
        <p>A tabela respostas_usuario agora permite múltiplas respostas por questão.</p>
        <p>Você pode continuar usando o sistema normalmente.</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erro durante a migração:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<a href='quiz_vertical_filtros.php?id=8' class='btn'>🎯 Ir para o Quiz</a>";
echo "<a href='index.php' class='btn'>🏠 Voltar ao Início</a>";

echo "</div></body></html>";
?>

