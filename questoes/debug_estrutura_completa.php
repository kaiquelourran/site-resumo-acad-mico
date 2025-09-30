<?php
require_once 'conexao.php';

echo "<h1>🔍 Debug Completo da Estrutura do Banco</h1>";

try {
    // 1. Verificar todas as tabelas
    echo "<h2>1. Tabelas Existentes</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tabelas as $tabela) {
        echo "<p>📋 $tabela</p>";
    }
    
    // 2. Estrutura da tabela questoes
    echo "<h2>2. Estrutura da Tabela 'questoes'</h2>";
    $stmt = $pdo->query("DESCRIBE questoes");
    $colunas_questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach ($colunas_questoes as $coluna) {
        echo "<tr>";
        echo "<td><strong>{$coluna['Field']}</strong></td>";
        echo "<td>{$coluna['Type']}</td>";
        echo "<td>{$coluna['Null']}</td>";
        echo "<td>{$coluna['Key']}</td>";
        echo "<td>{$coluna['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Verificar se existe tabela alternativas
    if (in_array('alternativas', $tabelas)) {
        echo "<h2>3. ✅ Tabela 'alternativas' EXISTE</h2>";
        $stmt = $pdo->query("DESCRIBE alternativas");
        $colunas_alt = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
        foreach ($colunas_alt as $coluna) {
            echo "<tr>";
            echo "<td><strong>{$coluna['Field']}</strong></td>";
            echo "<td>{$coluna['Type']}</td>";
            echo "<td>{$coluna['Null']}</td>";
            echo "<td>{$coluna['Key']}</td>";
            echo "<td>{$coluna['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar dados da tabela alternativas
        echo "<h3>Dados da tabela alternativas (questão 92):</h3>";
        $stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = 92");
        $stmt->execute();
        $alternativas_92 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($alternativas_92) {
            echo "<p>✅ Encontradas " . count($alternativas_92) . " alternativas para questão 92:</p>";
            foreach ($alternativas_92 as $alt) {
                echo "<p>• <strong>ID {$alt['id_alternativa']}:</strong> " . htmlspecialchars($alt['texto']) . " " . ($alt['correta'] ? '✅ CORRETA' : '') . "</p>";
            }
        } else {
            echo "<p>❌ Nenhuma alternativa encontrada para questão 92</p>";
        }
        
    } else {
        echo "<h2>3. ❌ Tabela 'alternativas' NÃO EXISTE</h2>";
        echo "<p>As alternativas devem estar armazenadas como colunas na tabela questoes</p>";
        
        // Verificar questão 92 na tabela questoes
        echo "<h3>Dados da questão 92 na tabela questoes:</h3>";
        $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = 92");
        $stmt->execute();
        $questao_92 = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($questao_92) {
            echo "<p>✅ Questão 92 encontrada</p>";
            $letras = ['a', 'b', 'c', 'd'];
            foreach ($letras as $letra) {
                $campo = "alternativa_$letra";
                $valor = $questao_92[$campo] ?? 'CAMPO NÃO EXISTE';
                $status = (!empty($valor) && $valor !== 'CAMPO NÃO EXISTE') ? '✅' : '❌';
                echo "<p>$status <strong>Alternativa " . strtoupper($letra) . ":</strong> " . htmlspecialchars($valor) . "</p>";
            }
        } else {
            echo "<p>❌ Questão 92 não encontrada</p>";
        }
    }
    
    // 4. Verificar questões do assunto 8
    echo "<h2>4. Questões do Assunto 8</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = 8");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    echo "<p>📊 Total de questões do assunto 8: <strong>$total</strong></p>";
    
    if ($total > 0) {
        $stmt = $pdo->prepare("SELECT id_questao, enunciado FROM questoes WHERE id_assunto = 8 ORDER BY id_questao LIMIT 5");
        $stmt->execute();
        $questoes_exemplo = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Primeiras 5 questões:</h3>";
        foreach ($questoes_exemplo as $q) {
            echo "<p>• <strong>ID {$q['id_questao']}:</strong> " . substr(htmlspecialchars($q['enunciado']), 0, 100) . "...</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERRO:</strong> " . $e->getMessage() . "</p>";
}
?>