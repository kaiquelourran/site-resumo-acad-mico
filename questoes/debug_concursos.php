<?php
require_once 'conexao.php';

echo "<h1>🔍 DEBUG - CONCURSOS</h1>";
echo "<hr>";

// 1. Verificar se a coluna tipo_assunto existe
echo "<h2>1. Verificando estrutura da tabela:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE assuntos");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "<p>Colunas encontradas: " . implode(', ', $cols) . "</p>";
    
    $tem_campo_tipo_assunto = in_array('tipo_assunto', $cols);
    echo "<p>Campo 'tipo_assunto' existe: " . ($tem_campo_tipo_assunto ? '✅ SIM' : '❌ NÃO') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

// 2. Verificar todos os assuntos
echo "<h2>2. Todos os assuntos no banco:</h2>";
try {
    $sql = "SELECT id_assunto, nome, tipo_assunto, concurso_ano, concurso_banca, concurso_orgao, concurso_prova FROM assuntos ORDER BY id_assunto";
    $assuntos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Ano</th><th>Banca</th><th>Órgão</th><th>Prova</th></tr>";
    
    foreach ($assuntos as $assunto) {
        echo "<tr>";
        echo "<td>" . $assunto['id_assunto'] . "</td>";
        echo "<td>" . htmlspecialchars($assunto['nome']) . "</td>";
        echo "<td>" . ($assunto['tipo_assunto'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assunto['concurso_ano'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assunto['concurso_banca'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assunto['concurso_orgao'] ?? 'NULL') . "</td>";
        echo "<td>" . ($assunto['concurso_prova'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

// 3. Verificar assuntos de concurso especificamente
echo "<h2>3. Assuntos de concurso:</h2>";
try {
    $sql = "SELECT id_assunto, nome, tipo_assunto, concurso_ano, concurso_banca, concurso_orgao, concurso_prova 
            FROM assuntos 
            WHERE tipo_assunto = 'concurso' 
            ORDER BY id_assunto";
    $concursos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($concursos)) {
        echo "<p style='color: orange;'>⚠️ Nenhum assunto de concurso encontrado!</p>";
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
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

// 4. Verificar questões associadas aos concursos
echo "<h2>4. Questões associadas aos concursos:</h2>";
try {
    $sql = "SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes
            FROM assuntos a 
            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
            WHERE a.tipo_assunto = 'concurso'
            GROUP BY a.id_assunto, a.nome, a.tipo_assunto 
            ORDER BY a.nome";
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($result)) {
        echo "<p style='color: orange;'>⚠️ Nenhum resultado encontrado para concursos!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Total Questões</th></tr>";
        
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . $row['id_assunto'] . "</td>";
            echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
            echo "<td>" . $row['tipo_assunto'] . "</td>";
            echo "<td>" . $row['total_questoes'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Conclusão:</strong> Execute este script para ver o que está acontecendo com os concursos.</p>";
?>
