<?php
require_once 'conexao.php';

echo "<h1>🔍 DEBUG - ESCOLHER ASSUNTO</h1>";
echo "<hr>";

// 1. Verificar se a coluna tipo_assunto existe
echo "<h2>1. Verificando estrutura:</h2>";
try {
    $stmt = $pdo->query("DESCRIBE assuntos");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $tem_campo_tipo_assunto = in_array('tipo_assunto', $cols);
    echo "<p>Campo 'tipo_assunto' existe: " . ($tem_campo_tipo_assunto ? '✅ SIM' : '❌ NÃO') . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    $tem_campo_tipo_assunto = false;
}

// 2. Executar a mesma query do escolher_assunto.php
echo "<h2>2. Query do escolher_assunto.php:</h2>";
if ($tem_campo_tipo_assunto) {
    $sql = "SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes 
            FROM assuntos a 
            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
            GROUP BY a.id_assunto, a.nome, a.tipo_assunto 
            ORDER BY a.tipo_assunto, a.nome";
    
    echo "<p>SQL: <code>" . htmlspecialchars($sql) . "</code></p>";
    
    try {
        $result = $pdo->query($sql)->fetchAll();
        echo "<p>Resultados encontrados: " . count($result) . "</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo Assunto</th><th>Total Questões</th><th>Tipo Mapeado</th></tr>";
        
        foreach ($result as $assunto) {
            // Aplicar a mesma lógica do escolher_assunto.php
            switch ($assunto['tipo_assunto']) {
                case 'concurso':
                    $tipo_mapeado = 'concursos';
                    break;
                case 'profissional':
                    $tipo_mapeado = 'profissionais';
                    break;
                case 'tema':
                default:
                    $tipo_mapeado = 'temas';
                    break;
            }
            
            $cor = '';
            if ($assunto['tipo_assunto'] == 'concurso') {
                $cor = 'background-color: #ffeb3b;'; // Amarelo para concursos
            } elseif ($assunto['tipo_assunto'] == 'profissional') {
                $cor = 'background-color: #4caf50; color: white;'; // Verde para profissionais
            }
            
            echo "<tr style='$cor'>";
            echo "<td>" . $assunto['id_assunto'] . "</td>";
            echo "<td>" . htmlspecialchars($assunto['nome']) . "</td>";
            echo "<td>" . ($assunto['tipo_assunto'] ?? 'NULL') . "</td>";
            echo "<td>" . $assunto['total_questoes'] . "</td>";
            echo "<td>" . $tipo_mapeado . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Erro na query: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Campo 'tipo_assunto' não existe, usando fallback</p>";
}

// 3. Verificar especificamente os concursos
echo "<h2>3. Verificação específica de concursos:</h2>";
try {
    $sql_concurso = "SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes 
                     FROM assuntos a 
                     LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                     WHERE a.tipo_assunto = 'concurso'
                     GROUP BY a.id_assunto, a.nome, a.tipo_assunto 
                     ORDER BY a.nome";
    
    $concursos = $pdo->query($sql_concurso)->fetchAll();
    
    if (empty($concursos)) {
        echo "<p style='color: orange;'>⚠️ Nenhum concurso encontrado!</p>";
    } else {
        echo "<p>Concursos encontrados: " . count($concursos) . "</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo</th><th>Questões</th></tr>";
        
        foreach ($concursos as $concurso) {
            echo "<tr style='background-color: #ffeb3b;'>";
            echo "<td>" . $concurso['id_assunto'] . "</td>";
            echo "<td>" . htmlspecialchars($concurso['nome']) . "</td>";
            echo "<td>" . $concurso['tipo_assunto'] . "</td>";
            echo "<td>" . $concurso['total_questoes'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro ao buscar concursos: " . $e->getMessage() . "</p>";
}

// 4. Simular a categorização final
echo "<h2>4. Categorização final:</h2>";
if ($tem_campo_tipo_assunto && !empty($result)) {
    $categorias = [
        'temas' => [],
        'concursos' => [],
        'profissionais' => []
    ];
    
    foreach ($result as $assunto) {
        switch ($assunto['tipo_assunto']) {
            case 'concurso':
                $assunto['tipo'] = 'concursos';
                break;
            case 'profissional':
                $assunto['tipo'] = 'profissionais';
                break;
            case 'tema':
            default:
                $assunto['tipo'] = 'temas';
                break;
        }
        
        $tipo = $assunto['tipo'] ?? 'temas';
        if (isset($categorias[$tipo])) {
            $categorias[$tipo][] = $assunto;
        }
    }
    
    echo "<p><strong>Temas:</strong> " . count($categorias['temas']) . " itens</p>";
    echo "<p><strong>Concursos:</strong> " . count($categorias['concursos']) . " itens</p>";
    echo "<p><strong>Profissionais:</strong> " . count($categorias['profissionais']) . " itens</p>";
    
    if (!empty($categorias['concursos'])) {
        echo "<h3>Concursos que deveriam aparecer:</h3>";
        foreach ($categorias['concursos'] as $concurso) {
            echo "<p>• " . htmlspecialchars($concurso['nome']) . " (" . $concurso['total_questoes'] . " questões)</p>";
        }
    }
}

echo "<hr>";
echo "<p><strong>Diagnóstico:</strong> Se os concursos aparecem aqui mas não na página escolher_assunto.php, pode ser um problema de cache ou JavaScript.</p>";
?>
