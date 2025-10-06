<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG DOS FILTROS</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;

$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

echo "<h2>Parâmetros recebidos:</h2>";
echo "<p>ID Assunto: $id_assunto</p>";
echo "<p>Filtro Ativo: $filtro_ativo</p>";

echo "<h2>Testando cada filtro:</h2>";

$filtros_teste = ['todas', 'respondidas', 'nao-respondidas', 'certas', 'erradas'];

foreach ($filtros_teste as $filtro) {
    echo "<h3>Filtro: $filtro</h3>";
    
    // Construir query SQL baseada no filtro
    if ($filtro === 'nao-respondidas') {
        // Para "não-respondidas", NUNCA carregar dados de resposta
        $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                       q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                       a.nome,
                       'nao-respondida' as status_resposta,
                       NULL as id_alternativa
                FROM questoes q 
                LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                WHERE 1=1";
    } else {
        // Para todos os outros filtros (incluindo "todas"), carregar dados de resposta normalmente
        $sql = "SELECT q.id_questao, q.enunciado, q.alternativa_a, q.alternativa_b, 
                       q.alternativa_c, q.alternativa_d, q.alternativa_correta, q.explicacao,
                       a.nome,
                       CASE 
                           WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                           WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                           WHEN r.id_questao IS NOT NULL THEN 'respondida'
                           ELSE 'nao-respondida'
                       END as status_resposta,
                       r.id_alternativa
                FROM questoes q 
                LEFT JOIN assuntos a ON q.id_assunto = a.id_assunto
                LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao
                WHERE 1=1";
    }
    
    $params = [];
    
    if ($id_assunto > 0) {
        $sql .= " AND q.id_assunto = ?";
        $params[] = $id_assunto;
    }
    
    // Aplicar filtro específico
    switch($filtro) {
        case 'respondidas':
            $sql .= " AND r.id_questao IS NOT NULL";
            break;
        case 'nao-respondidas':
            // Para não-respondidas, não aplicar filtro adicional pois já não carregamos respostas
            break;
        case 'certas':
            $sql .= " AND r.acertou = 1";
            break;
        case 'erradas':
            $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
            break;
        case 'todas':
            // Para todas, não aplicar filtro adicional
            break;
    }
    
    $sql .= " ORDER BY q.id_questao";
    
    echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";
    echo "<p>Parâmetros: " . implode(', ', $params) . "</p>";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Questões encontradas: " . count($questoes) . "</p>";
        
        if (count($questoes) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Enunciado</th><th>Status</th><th>ID Alternativa</th></tr>";
            foreach (array_slice($questoes, 0, 5) as $q) {
                echo "<tr>";
                echo "<td>" . $q['id_questao'] . "</td>";
                echo "<td>" . htmlspecialchars(substr($q['enunciado'], 0, 50)) . "...</td>";
                echo "<td>" . $q['status_resposta'] . "</td>";
                echo "<td>" . ($q['id_alternativa'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Testando links dos filtros:</h2>";
echo "<p><a href='?id=$id_assunto&filtro=todas'>Todas</a></p>";
echo "<p><a href='?id=$id_assunto&filtro=respondidas'>Respondidas</a></p>";
echo "<p><a href='?id=$id_assunto&filtro=nao-respondidas'>Não Respondidas</a></p>";
echo "<p><a href='?id=$id_assunto&filtro=certas'>Certas</a></p>";
echo "<p><a href='?id=$id_assunto&filtro=erradas'>Erradas</a></p>";

echo "<h2>Verificando dados na tabela respostas_usuario:</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
    $total = $stmt->fetchColumn();
    echo "<p>Total de respostas: $total</p>";
    
    if ($total > 0) {
        $stmt = $pdo->query("SELECT r.*, q.enunciado FROM respostas_usuario r JOIN questoes q ON r.id_questao = q.id_questao LIMIT 5");
        $respostas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Últimas 5 respostas:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Questão</th><th>Acertou</th><th>Data</th></tr>";
        foreach ($respostas as $resp) {
            echo "<tr>";
            echo "<td>" . $resp['id'] . "</td>";
            echo "<td>" . $resp['user_id'] . "</td>";
            echo "<td>" . $resp['id_questao'] . "</td>";
            echo "<td>" . ($resp['acertou'] ? 'SIM' : 'NÃO') . "</td>";
            echo "<td>" . $resp['data_resposta'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
