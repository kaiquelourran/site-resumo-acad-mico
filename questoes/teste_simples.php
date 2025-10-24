<?php
require_once 'conexao.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🧪 TESTE SIMPLES - FORÇA BRUTA</h1>";
echo "<hr>";

try {
    // Query SIMPLES sem GROUP BY
    $sql = "SELECT a.id_assunto, a.nome, a.tipo_assunto 
            FROM assuntos a 
            ORDER BY a.tipo_assunto, a.nome";
    
    $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 TODOS OS ASSUNTOS:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>tipo_assunto</th></tr>";
    
    $temas = 0;
    $concursos = 0;
    $profissionais = 0;
    
    foreach ($result as $r) {
        $bg = '';
        if ($r['tipo_assunto'] === 'concurso') {
            $bg = 'background: #ffebee;';
            $concursos++;
        } elseif ($r['tipo_assunto'] === 'profissional') {
            $bg = 'background: #e8f5e8;';
            $profissionais++;
        } else {
            $temas++;
        }
        
        echo "<tr style='$bg'>";
        echo "<td>" . htmlspecialchars($r['id_assunto']) . "</td>";
        echo "<td>" . htmlspecialchars($r['nome']) . "</td>";
        echo "<td><b>" . htmlspecialchars($r['tipo_assunto']) . "</b></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h2>📈 CONTAGEM:</h2>";
    echo "<p><b>Temas:</b> $temas</p>";
    echo "<p><b>Concursos:</b> $concursos</p>";
    echo "<p><b>Profissionais:</b> $profissionais</p>";
    
    if ($concursos > 0) {
        echo "<p style='color: green; font-size: 1.2em; font-weight: bold;'>✅ CONCURSOS ENCONTRADOS! O problema NÃO é no banco de dados.</p>";
    } else {
        echo "<p style='color: red; font-size: 1.2em; font-weight: bold;'>❌ NENHUM CONCURSO ENCONTRADO! O problema É no banco de dados.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
