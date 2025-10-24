<?php
// Script para corrigir a tabela assuntos na Hostinger
// Adiciona colunas específicas para concursos

require_once 'conexao.php';

echo "<h1>Correção da Tabela 'assuntos' - Hostinger</h1>";
echo "<p>Este script irá adicionar as colunas necessárias para concursos.</p>";
echo "<hr>";

try {
    // Verificar se as colunas já existem
    $columns = $pdo->query("SHOW COLUMNS FROM assuntos")->fetchAll(PDO::FETCH_COLUMN);
    
    $colunas_necessarias = [
        'concurso_ano' => 'VARCHAR(10) NULL',
        'concurso_banca' => 'VARCHAR(100) NULL', 
        'concurso_orgao' => 'VARCHAR(100) NULL',
        'concurso_prova' => 'VARCHAR(100) NULL'
    ];
    
    $alteracoes_feitas = false;
    
    foreach ($colunas_necessarias as $coluna => $tipo) {
        if (!in_array($coluna, $columns)) {
            $sql = "ALTER TABLE assuntos ADD COLUMN $coluna $tipo";
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Coluna '$coluna' adicionada com sucesso!</p>";
            $alteracoes_feitas = true;
        } else {
            echo "<p style='color: blue;'>📋 Coluna '$coluna' já existe.</p>";
        }
    }
    
    if (!$alteracoes_feitas) {
        echo "<p style='color: green;'>✅ Todas as colunas necessárias já existem!</p>";
    }
    
    // Verificar estrutura final
    echo "<h2>Estrutura Final da Tabela 'assuntos':</h2>";
    $final_columns = $pdo->query("DESCRIBE assuntos")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($final_columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✅ Correção concluída com sucesso!</p>";
    echo "<p>Agora você pode adicionar concursos normalmente.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
