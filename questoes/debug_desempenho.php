<?php
session_start();
require_once 'conexao.php';

echo "<h1>🔍 DEBUG - Página de Desempenho</h1>";

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo "<p style='color: red;'>❌ Usuário não está logado</p>";
    exit;
}

echo "<h2>👤 Informações do Usuário:</h2>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'NÃO DEFINIDO') . "</p>";
echo "<p><strong>Logged In:</strong> " . ($_SESSION['logged_in'] ? 'SIM' : 'NÃO') . "</p>";

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<p style='color: red;'>❌ User ID não encontrado na sessão</p>";
    exit;
}

echo "<h2>🗄️ Verificação da Estrutura do Banco:</h2>";

// Verificar se a tabela respostas_usuario existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'respostas_usuario'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ Tabela 'respostas_usuario' existe</p>";
        
        // Mostrar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE respostas_usuario");
        $columns = $stmt->fetchAll();
        
        echo "<h3>📋 Estrutura da tabela 'respostas_usuario':</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar se há dados na tabela
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
        $total_records = $stmt->fetch()['total'];
        echo "<p><strong>Total de registros na tabela:</strong> $total_records</p>";
        
        // Verificar dados específicos do usuário
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user_records = $stmt->fetch()['total'];
        echo "<p><strong>Registros do usuário atual:</strong> $user_records</p>";
        
        if ($user_records > 0) {
            echo "<h3>📊 Dados do usuário:</h3>";
            $stmt = $pdo->prepare("SELECT * FROM respostas_usuario WHERE user_id = ? ORDER BY data_resposta DESC LIMIT 5");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Questão ID</th><th>Alternativa ID</th><th>Resposta Correta</th><th>Data</th></tr>";
            foreach ($user_data as $row) {
                echo "<tr>";
                echo "<td>" . $row['id_resposta'] . "</td>";
                echo "<td>" . $row['user_id'] . "</td>";
                echo "<td>" . $row['id_questao'] . "</td>";
                echo "<td>" . $row['id_alternativa'] . "</td>";
                echo "<td>" . ($row['resposta_correta'] ? 'SIM' : 'NÃO') . "</td>";
                echo "<td>" . $row['data_resposta'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Tabela 'respostas_usuario' NÃO existe</p>";
        
        // Verificar outras tabelas possíveis
        echo "<h3>🔍 Verificando outras tabelas relacionadas:</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        
        echo "<ul>";
        foreach ($tables as $table) {
            $table_name = array_values($table)[0];
            echo "<li>$table_name</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar banco: " . $e->getMessage() . "</p>";
}

echo "<h2>🔍 Verificação de Outras Tabelas Possíveis:</h2>";

// Verificar se existe tabela 'respostas' (sem _usuario)
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'respostas'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p style='color: green;'>✅ Tabela 'respostas' existe</p>";
        
        $stmt = $pdo->query("DESCRIBE respostas");
        $columns = $stmt->fetchAll();
        
        echo "<h3>📋 Estrutura da tabela 'respostas':</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar dados
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas");
        $total_records = $stmt->fetch()['total'];
        echo "<p><strong>Total de registros na tabela 'respostas':</strong> $total_records</p>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ Tabela 'respostas' não existe</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela 'respostas': " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Teste das Consultas da Página de Desempenho:</h2>";

if ($user_id) {
    try {
        // Teste 1: Total de respostas
        echo "<h3>Teste 1: Total de respostas</h3>";
        $stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM respostas_usuario WHERE user_id = ?");
        $stmt_total->execute([$user_id]);
        $total_respostas = $stmt_total->fetch()['total'];
        echo "<p><strong>Resultado:</strong> $total_respostas respostas</p>";
        
        // Teste 2: Respostas corretas
        echo "<h3>Teste 2: Respostas corretas</h3>";
        $stmt_corretas = $pdo->prepare("SELECT COUNT(*) as corretas FROM respostas_usuario WHERE user_id = ? AND resposta_correta = 1");
        $stmt_corretas->execute([$user_id]);
        $respostas_corretas = $stmt_corretas->fetch()['corretas'];
        echo "<p><strong>Resultado:</strong> $respostas_corretas respostas corretas</p>";
        
        // Teste 3: Percentual
        $percentual_acerto = $total_respostas > 0 ? round(($respostas_corretas / $total_respostas) * 100, 1) : 0;
        echo "<p><strong>Percentual de acerto:</strong> $percentual_acerto%</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro nas consultas: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>📝 Variáveis de Sessão Completas:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>🔧 Próximos Passos:</h2>";
echo "<ol>";
echo "<li>Verificar se a tabela correta existe</li>";
echo "<li>Verificar se os dados estão sendo salvos corretamente</li>";
echo "<li>Ajustar as consultas SQL se necessário</li>";
echo "<li>Verificar se o user_id está sendo salvo corretamente</li>";
echo "</ol>";
?>


