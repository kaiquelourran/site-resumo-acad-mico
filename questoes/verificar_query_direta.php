<?php
require_once 'conexao.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 VERIFICAÇÃO DIRETA DA QUERY</h1>";
echo "<hr>";

try {
    // 1. Query EXATA do escolher_assunto.php
    echo "<h2>1. Query EXATA (do escolher_assunto.php):</h2>";
    $sql = "SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes 
            FROM assuntos a 
            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
            GROUP BY a.id_assunto, a.nome, a.tipo_assunto 
            ORDER BY a.tipo_assunto, a.nome";
    
    echo "<pre>SQL: " . htmlspecialchars($sql) . "</pre>";
    
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>tipo_assunto (RAW)</th><th>Questões</th></tr>";
    foreach ($result as $r) {
        $bg = $r['tipo_assunto'] === 'concurso' ? 'background: #ffebee;' : '';
        echo "<tr style='$bg'>";
        echo "<td>" . htmlspecialchars($r['id_assunto']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nome']) . "</td>";
        echo "<td><b>" . htmlspecialchars($r['tipo_assunto'] ?? 'NULL') . "</b> (strlen: " . strlen($r['tipo_assunto']) . ")</td>";
        echo "<td>" . htmlspecialchars($r['total_questoes']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><b>Total de linhas retornadas:</b> " . count($result) . "</p>";
    echo "<br>";
    
    // 2. Verificar se ID 12 existe na tabela assuntos
    echo "<h2>2. Verificação Direta do ID 12:</h2>";
    $stmt_12 = $pdo->prepare("SELECT * FROM assuntos WHERE id_assunto = 12");
    $stmt_12->execute();
    $assunto_12 = $stmt_12->fetch(PDO::FETCH_ASSOC);
    
    if ($assunto_12) {
        echo "<p style='color: green;'>✅ ID 12 EXISTE na tabela assuntos</p>";
        echo "<pre>";
        print_r($assunto_12);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ ID 12 NÃO EXISTE na tabela assuntos!</p>";
    }
    echo "<br>";
    
    // 3. Verificar TODOS os IDs da tabela assuntos
    echo "<h2>3. TODOS os IDs da Tabela 'assuntos':</h2>";
    $stmt_all = $pdo->query("SELECT id_assunto, nome, tipo_assunto FROM assuntos ORDER BY id_assunto");
    $all_assuntos = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>tipo_assunto</th></tr>";
    foreach ($all_assuntos as $a) {
        $bg = $a['tipo_assunto'] === 'concurso' ? 'background: #ffebee;' : '';
        echo "<tr style='$bg'>";
        echo "<td>" . htmlspecialchars($a['id_assunto']) . "</td>";
        echo "<td>" . htmlspecialchars($a['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($a['tipo_assunto'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
    
    // 4. Verificar se há problema no GROUP BY
    echo "<h2>4. Query SEM GROUP BY (para comparar):</h2>";
    $sql_no_group = "SELECT a.id_assunto, a.nome, a.tipo_assunto 
                     FROM assuntos a 
                     ORDER BY a.tipo_assunto, a.nome";
    
    $result_no_group = $pdo->query($sql_no_group)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>tipo_assunto</th></tr>";
    foreach ($result_no_group as $r) {
        $bg = $r['tipo_assunto'] === 'concurso' ? 'background: #ffebee;' : '';
        echo "<tr style='$bg'>";
        echo "<td>" . htmlspecialchars($r['id_assunto']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($r['tipo_assunto'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><b>Total de linhas retornadas:</b> " . count($result_no_group) . "</p>";
    echo "<br>";
    
    // 5. Verificar questões do ID 12
    echo "<h2>5. Questões Associadas ao ID 12:</h2>";
    $stmt_q12 = $pdo->prepare("SELECT id_questao, enunciado, id_assunto FROM questoes WHERE id_assunto = 12");
    $stmt_q12->execute();
    $questoes_12 = $stmt_q12->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($questoes_12)) {
        echo "<p style='color: green;'>✅ Questões encontradas: " . count($questoes_12) . "</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID Questão</th><th>Enunciado</th><th>ID Assunto</th></tr>";
        foreach ($questoes_12 as $q) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($q['id_questao']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($q['enunciado'], 0, 100)) . "...</td>";
            echo "<td>" . htmlspecialchars($q['id_assunto']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Nenhuma questão encontrada para id_assunto = 12</p>";
    }
    echo "<br>";
    
    // 6. Diagnóstico final
    echo "<h2>6. 🎯 DIAGNÓSTICO FINAL:</h2>";
    
    $id_12_in_query = false;
    foreach ($result as $r) {
        if ($r['id_assunto'] == 12) {
            $id_12_in_query = true;
            break;
        }
    }
    
    if (!$id_12_in_query && $assunto_12) {
        echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA CRÍTICO: O ID 12 EXISTE na tabela 'assuntos', mas NÃO aparece na query com LEFT JOIN + GROUP BY!</p>";
        echo "<p>Isso pode ser:</p>";
        echo "<ul>";
        echo "<li>Problema de permissões do MySQL</li>";
        echo "<li>Problema de charset/collation</li>";
        echo "<li>Problema de cache do MySQL</li>";
        echo "<li>Registro 'fantasma' ou corrompido</li>";
        echo "</ul>";
    } elseif ($id_12_in_query) {
        echo "<p style='color: green; font-weight: bold;'>✅ ID 12 aparece corretamente na query!</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ ID 12 não existe na tabela 'assuntos'.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

