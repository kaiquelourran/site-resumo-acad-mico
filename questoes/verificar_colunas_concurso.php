<?php
require_once 'conexao.php';

echo "<h1>🔍 VERIFICAÇÃO DE COLUNAS DE CONCURSO</h1>";
echo "<hr>";

try {
    // Verificar estrutura atual da tabela assuntos
    echo "<h2>1. Estrutura atual da tabela 'assuntos':</h2>";
    $stmt = $pdo->query("DESCRIBE assuntos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $colunas_existentes = [];
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
        $colunas_existentes[] = $col['Field'];
    }
    echo "</table>";
    
    // Verificar se as colunas de concurso existem
    echo "<h2>2. Verificação das colunas de concurso:</h2>";
    
    $colunas_necessarias = [
        'concurso_ano' => 'VARCHAR(10) NULL',
        'concurso_banca' => 'VARCHAR(100) NULL',
        'concurso_orgao' => 'VARCHAR(100) NULL',
        'concurso_prova' => 'VARCHAR(100) NULL'
    ];
    
    $faltam_colunas = false;
    
    foreach ($colunas_necessarias as $coluna => $tipo) {
        if (in_array($coluna, $colunas_existentes)) {
            echo "<p style='color: green;'>✅ Coluna '$coluna' existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Coluna '$coluna' NÃO existe</p>";
            $faltam_colunas = true;
        }
    }
    
    if ($faltam_colunas) {
        echo "<h2>3. Adicionando colunas faltantes:</h2>";
        
        foreach ($colunas_necessarias as $coluna => $tipo) {
            if (!in_array($coluna, $colunas_existentes)) {
                try {
                    $sql = "ALTER TABLE assuntos ADD COLUMN $coluna $tipo";
                    $pdo->exec($sql);
                    echo "<p style='color: green;'>✅ Coluna '$coluna' adicionada com sucesso!</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erro ao adicionar coluna '$coluna': " . $e->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: green;'>✅ Todas as colunas de concurso já existem!</p>";
    }
    
    // Verificar se existem assuntos de concurso
    echo "<h2>4. Assuntos de concurso existentes:</h2>";
    try {
        $sql = "SELECT id_assunto, nome, tipo_assunto, concurso_ano, concurso_banca, concurso_orgao, concurso_prova 
                FROM assuntos 
                WHERE tipo_assunto = 'concurso'";
        $concursos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($concursos)) {
            echo "<p style='color: orange;'>⚠️ Nenhum assunto de concurso encontrado!</p>";
            echo "<p>Para criar um concurso, acesse: <a href='add_assunto.php'>Adicionar Assunto</a></p>";
        } else {
            echo "<p>Encontrados " . count($concursos) . " concursos:</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Ano</th><th>Banca</th><th>Órgão</th><th>Prova</th></tr>";
            
            foreach ($concursos as $concurso) {
                echo "<tr>";
                echo "<td>" . $concurso['id_assunto'] . "</td>";
                echo "<td>" . htmlspecialchars($concurso['nome']) . "</td>";
                echo "<td>" . ($concurso['concurso_ano'] ?? 'NULL') . "</td>";
                echo "<td>" . ($concurso['concurso_banca'] ?? 'NULL') . "</td>";
                echo "<td>" . ($concurso['concurso_orgao'] ?? 'NULL') . "</td>";
                echo "<td>" . ($concurso['concurso_prova'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro ao buscar concursos: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro geral: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Próximos passos:</strong></p>";
echo "<ol>";
echo "<li>Se faltavam colunas, elas foram adicionadas</li>";
echo "<li>Se não existem concursos, crie um em 'Adicionar Assunto'</li>";
echo "<li>Depois teste adicionar uma questão de concurso</li>";
echo "</ol>";
?>
