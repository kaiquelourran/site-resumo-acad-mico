<?php
require_once 'conexao.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 DEBUG DETALHADO DA QUESTÃO 162</h1>";
echo "<hr>";

try {
    // 1. Verificar a questão 162
    echo "<h2>1. Dados da Questão 162:</h2>";
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = 162");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($questao);
    echo "</pre>";
    echo "<br>";
    
    // 2. Verificar o assunto associado
    echo "<h2>2. Assunto Associado (id_assunto = {$questao['id_assunto']}):</h2>";
    $stmt_assunto = $pdo->prepare("SELECT * FROM assuntos WHERE id_assunto = ?");
    $stmt_assunto->execute([$questao['id_assunto']]);
    $assunto = $stmt_assunto->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($assunto);
    echo "</pre>";
    echo "<br>";
    
    // 3. Verificar todas as questões deste assunto
    echo "<h2>3. Todas as Questões do Assunto '{$assunto['nome']}':</h2>";
    $stmt_todas = $pdo->prepare("SELECT id_questao, enunciado, id_assunto FROM questoes WHERE id_assunto = ?");
    $stmt_todas->execute([$questao['id_assunto']]);
    $todas_questoes = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID Questão</th><th>Enunciado</th><th>ID Assunto</th></tr>";
    foreach ($todas_questoes as $q) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($q['id_questao']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($q['enunciado'], 0, 100)) . "...</td>";
        echo "<td>" . htmlspecialchars($q['id_assunto']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
    
    // 4. Executar a query exata do escolher_assunto.php
    echo "<h2>4. Query Exata do escolher_assunto.php:</h2>";
    
    $sql = "SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes 
            FROM assuntos a 
            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
            GROUP BY a.id_assunto, a.nome, a.tipo_assunto 
            ORDER BY a.tipo_assunto, a.nome";
    
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Tipo Assunto</th><th>Total Questões</th></tr>";
    foreach ($result as $r) {
        $style = '';
        if ($r['tipo_assunto'] === 'concurso') {
            $style = 'background: #ffebee;';
        }
        echo "<tr style='$style'>";
        echo "<td>" . htmlspecialchars($r['id_assunto']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($r['tipo_assunto']) . "</td>";
        echo "<td><b>" . htmlspecialchars($r['total_questoes']) . "</b></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
    
    // 5. Categorização
    echo "<h2>5. Categorização (Como o escolher_assunto.php faz):</h2>";
    
    $categorias = [
        'temas' => [],
        'concursos' => [],
        'profissionais' => []
    ];
    
    foreach ($result as $assunto_item) {
        switch ($assunto_item['tipo_assunto']) {
            case 'concurso':
                $assunto_item['tipo'] = 'concursos';
                break;
            case 'profissional':
                $assunto_item['tipo'] = 'profissionais';
                break;
            case 'tema':
            default:
                $assunto_item['tipo'] = 'temas';
                break;
        }
        
        $tipo = $assunto_item['tipo'] ?? 'temas';
        if (isset($categorias[$tipo])) {
            $categorias[$tipo][] = $assunto_item;
        }
    }
    
    echo "<h3>📚 Temas (" . count($categorias['temas']) . "):</h3>";
    foreach ($categorias['temas'] as $t) {
        echo "<p>- " . htmlspecialchars($t['nome']) . " (" . $t['total_questoes'] . " questões)</p>";
    }
    
    echo "<h3>🏆 Concursos (" . count($categorias['concursos']) . "):</h3>";
    if (empty($categorias['concursos'])) {
        echo "<p style='color: red;'>❌ NENHUM CONCURSO ENCONTRADO!</p>";
    } else {
        foreach ($categorias['concursos'] as $c) {
            echo "<p>- " . htmlspecialchars($c['nome']) . " (" . $c['total_questoes'] . " questões)</p>";
        }
    }
    
    echo "<h3>💼 Profissionais (" . count($categorias['profissionais']) . "):</h3>";
    foreach ($categorias['profissionais'] as $p) {
        echo "<p>- " . htmlspecialchars($p['nome']) . " (" . $p['total_questoes'] . " questões)</p>";
    }
    
    // 6. Verificar se há diferença de tipo_assunto
    echo "<br><h2>6. DIAGNÓSTICO FINAL:</h2>";
    
    if ($assunto['tipo_assunto'] === 'concurso' && count($categorias['concursos']) === 0) {
        echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA IDENTIFICADO: O assunto tem tipo_assunto = 'concurso', mas não está sendo categorizado como concurso!</p>";
        echo "<p>Verifique se há problema de cache ou se o arquivo escolher_assunto.php está realmente atualizado na Hostinger.</p>";
    } elseif (count($categorias['concursos']) > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Concursos foram encontrados e categorizados corretamente!</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Nenhum assunto de concurso encontrado no banco.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Erro no debug: " . $e->getMessage());
}
?>

