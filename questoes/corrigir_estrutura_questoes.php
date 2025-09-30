<?php
require_once 'conexao.php';

echo "<h2>Corrigir Estrutura da Tabela Questões</h2>";

try {
    // Verificar se os campos já existem
    $sql_check = "SHOW COLUMNS FROM questoes LIKE 'alternativa_%'";
    $stmt_check = $pdo->query($sql_check);
    $campos_existentes = $stmt_check->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Campos de alternativas existentes:</h3>";
    if (empty($campos_existentes)) {
        echo "<p>❌ Nenhum campo de alternativa encontrado!</p>";
        
        echo "<h3>Adicionando campos das alternativas...</h3>";
        
        // Adicionar campos das alternativas
        $alteracoes = [
            "ALTER TABLE questoes ADD COLUMN alternativa_a TEXT",
            "ALTER TABLE questoes ADD COLUMN alternativa_b TEXT", 
            "ALTER TABLE questoes ADD COLUMN alternativa_c TEXT",
            "ALTER TABLE questoes ADD COLUMN alternativa_d TEXT",
            "ALTER TABLE questoes ADD COLUMN alternativa_e TEXT",
            "ALTER TABLE questoes ADD COLUMN alternativa_correta CHAR(1)"
        ];
        
        foreach ($alteracoes as $sql) {
            try {
                $pdo->exec($sql);
                echo "<p>✅ Executado: $sql</p>";
            } catch (Exception $e) {
                echo "<p>❌ Erro ao executar: $sql - " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>Verificando estrutura após alterações:</h3>";
        $sql_desc = "DESCRIBE questoes";
        $stmt_desc = $pdo->query($sql_desc);
        $colunas = $stmt_desc->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($colunas as $coluna) {
            $destaque = (strpos($coluna['Field'], 'alternativa_') === 0) ? 'style="background-color: #d4edda;"' : '';
            echo "<tr $destaque>";
            echo "<td>" . $coluna['Field'] . "</td>";
            echo "<td>" . $coluna['Type'] . "</td>";
            echo "<td>" . $coluna['Null'] . "</td>";
            echo "<td>" . $coluna['Key'] . "</td>";
            echo "<td>" . $coluna['Default'] . "</td>";
            echo "<td>" . $coluna['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>✅ Campos de alternativas já existem:</p>";
        foreach ($campos_existentes as $campo) {
            echo "<p>- " . $campo['Field'] . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>