<?php
require_once 'conexao.php';

echo "<h1>🔧 SINCRONIZAÇÃO ESTRUTURA HOSTINGER</h1>";
echo "<hr>";

try {
    // 1. Verificar estrutura atual
    echo "<h2>1. Estrutura atual da tabela 'assuntos':</h2>";
    $stmt = $pdo->query("DESCRIBE assuntos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $colunas_existentes = [];
    foreach ($columns as $col) {
        $colunas_existentes[] = $col['Field'];
    }
    
    echo "<p>Colunas existentes: " . implode(', ', $colunas_existentes) . "</p>";
    
    // 2. Verificar se tipo_assunto existe
    $tem_tipo_assunto = in_array('tipo_assunto', $colunas_existentes);
    echo "<p>Campo 'tipo_assunto' existe: " . ($tem_tipo_assunto ? '✅ SIM' : '❌ NÃO') . "</p>";
    
    // 3. Adicionar colunas faltantes
    echo "<h2>2. Adicionando colunas faltantes:</h2>";
    
    $colunas_necessarias = [
        'tipo_assunto' => "ENUM('tema', 'concurso', 'profissional') DEFAULT 'tema'",
        'concurso_ano' => 'VARCHAR(10) NULL',
        'concurso_banca' => 'VARCHAR(100) NULL',
        'concurso_orgao' => 'VARCHAR(100) NULL',
        'concurso_prova' => 'VARCHAR(100) NULL'
    ];
    
    $alteracoes_feitas = false;
    
    foreach ($colunas_necessarias as $coluna => $tipo) {
        if (!in_array($coluna, $colunas_existentes)) {
            try {
                $sql = "ALTER TABLE assuntos ADD COLUMN $coluna $tipo";
                $pdo->exec($sql);
                echo "<p style='color: green;'>✅ Coluna '$coluna' adicionada com sucesso!</p>";
                $alteracoes_feitas = true;
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Erro ao adicionar coluna '$coluna': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>📋 Coluna '$coluna' já existe</p>";
        }
    }
    
    // 4. Atualizar registros existentes
    echo "<h2>3. Atualizando registros existentes:</h2>";
    
    // Verificar se há registros sem tipo_assunto
    $sql_check = "SELECT COUNT(*) as total FROM assuntos WHERE tipo_assunto IS NULL OR tipo_assunto = ''";
    $result_check = $pdo->query($sql_check)->fetch();
    $total_sem_tipo = $result_check['total'];
    
    if ($total_sem_tipo > 0) {
        echo "<p>Encontrados $total_sem_tipo registros sem tipo_assunto definido.</p>";
        
        // Categorizar baseado no nome
        $sql_update = "UPDATE assuntos SET tipo_assunto = CASE 
                       WHEN LOWER(nome) LIKE '%concurso%' OR LOWER(nome) LIKE '%prova%' OR LOWER(nome) LIKE '%edital%' THEN 'concurso'
                       WHEN LOWER(nome) LIKE '%profissional%' OR LOWER(nome) LIKE '%carreira%' OR LOWER(nome) LIKE '%trabalho%' THEN 'profissional'
                       ELSE 'tema'
                       END
                       WHERE tipo_assunto IS NULL OR tipo_assunto = ''";
        
        $pdo->exec($sql_update);
        echo "<p style='color: green;'>✅ Registros atualizados com tipo_assunto baseado no nome!</p>";
    } else {
        echo "<p style='color: blue;'>📋 Todos os registros já têm tipo_assunto definido</p>";
    }
    
    // 5. Verificar resultado final
    echo "<h2>4. Verificação final:</h2>";
    
    // Contar por tipo
    $sql_count = "SELECT tipo_assunto, COUNT(*) as total FROM assuntos GROUP BY tipo_assunto";
    $result_count = $pdo->query($sql_count)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Tipo</th><th>Quantidade</th></tr>";
    
    foreach ($result_count as $row) {
        $tipo = $row['tipo_assunto'] ?? 'NULL';
        $total = $row['total'];
        echo "<tr>";
        echo "<td>" . $tipo . "</td>";
        echo "<td>" . $total . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 6. Mostrar concursos específicos
    echo "<h2>5. Concursos encontrados:</h2>";
    $sql_concursos = "SELECT id_assunto, nome, tipo_assunto, concurso_ano, concurso_banca, concurso_orgao, concurso_prova 
                      FROM assuntos 
                      WHERE tipo_assunto = 'concurso' 
                      ORDER BY nome";
    
    $concursos = $pdo->query($sql_concursos)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($concursos)) {
        echo "<p style='color: orange;'>⚠️ Nenhum concurso encontrado!</p>";
        echo "<p>Para criar concursos, acesse: <a href='add_assunto.php'>Adicionar Assunto</a></p>";
    } else {
        echo "<p>Encontrados " . count($concursos) . " concursos:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Ano</th><th>Banca</th><th>Órgão</th><th>Prova</th></tr>";
        
        foreach ($concursos as $concurso) {
            echo "<tr style='background-color: #ffeb3b;'>";
            echo "<td>" . $concurso['id_assunto'] . "</td>";
            echo "<td>" . htmlspecialchars($concurso['nome']) . "</td>";
            echo "<td>" . $concurso['tipo_assunto'] . "</td>";
            echo "<td>" . ($concurso['concurso_ano'] ?? 'NULL') . "</td>";
            echo "<td>" . ($concurso['concurso_banca'] ?? 'NULL') . "</td>";
            echo "<td>" . ($concurso['concurso_orgao'] ?? 'NULL') . "</td>";
            echo "<td>" . ($concurso['concurso_prova'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<h2>✅ SINCRONIZAÇÃO CONCLUÍDA!</h2>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Teste acessar <a href='escolher_assunto.php'>Escolher Assunto</a></li>";
    echo "<li>Verifique se os concursos aparecem na aba 'Concursos'</li>";
    echo "<li>Se ainda não aparecer, limpe o cache do navegador (Ctrl+F5)</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro geral: " . $e->getMessage() . "</p>";
}
?>
