<?php
require_once 'conexao.php';

// Função para mostrar a consulta SQL formatada
function mostrar_sql($sql, $params = []) {
    echo "<div style='background:#f8f9fa;border:1px solid #ddd;margin:10px;padding:10px;'>";
    echo "<h3>Consulta SQL:</h3>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    if (!empty($params)) {
        echo "<h3>Parâmetros:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($params, true)) . "</pre>";
    }
    echo "</div>";
}

// Função para executar e mostrar resultados
function executar_consulta($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div style='background:#e9f7ef;border:1px solid #ddd;margin:10px;padding:10px;'>";
        echo "<h3>Resultados (" . count($resultados) . " registros):</h3>";
        echo "<pre>" . htmlspecialchars(print_r($resultados, true)) . "</pre>";
        echo "</div>";
        
        return $resultados;
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;margin:10px;padding:10px;'>";
        echo "<h3>Erro ao executar consulta:</h3>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "</div>";
        return [];
    }
}

// Verificar se a tabela tem a coluna user_id
$tem_user_id = false;
try {
    $stmt_check = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
    $tem_user_id = in_array('user_id', $colunas);
    
    echo "<h2>Verificação da coluna user_id:</h2>";
    if ($tem_user_id) {
        echo "<p style='color:green;'>A coluna user_id EXISTE na tabela.</p>";
    } else {
        echo "<p style='color:red;'>A coluna user_id NÃO EXISTE na tabela.</p>";
    }
} catch (Exception $e) {
    echo "<h2>Erro ao verificar estrutura:</h2>";
    echo $e->getMessage();
}

// Simular user_id para testes
$user_id = 1; // Usuário de teste
$id_assunto = 8; // Assunto de teste

// Verificar respostas recentes
echo "<h1>Respostas mais recentes</h1>";
$sql = "SELECT ru.id_questao, ru.acertou, ru.data_resposta, ru.user_id
        FROM respostas_usuario ru
        WHERE ru.user_id = ?
        ORDER BY ru.data_resposta DESC
        LIMIT 10";
$params = [$user_id];

mostrar_sql($sql, $params);
$respostas_recentes = executar_consulta($pdo, $sql, $params);

// Verificar se há duplicatas nas respostas
echo "<h1>Verificação de duplicatas nas respostas</h1>";
$sql = "SELECT id_questao, COUNT(*) as total, MAX(data_resposta) as ultima_resposta
        FROM respostas_usuario
        WHERE user_id = ?
        GROUP BY id_questao
        HAVING COUNT(*) > 1
        ORDER BY total DESC";
$params = [$user_id];

mostrar_sql($sql, $params);
$duplicatas = executar_consulta($pdo, $sql, $params);

// Verificar se há questões que estão em ambos os filtros
echo "<h1>Verificação de questões em ambos os filtros</h1>";

// Consulta para filtro "certas"
$sql_certas = "SELECT q.id_questao, q.enunciado, 
                   CASE 
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta,
                   r.acertou
            FROM questoes q 
            LEFT JOIN (
                SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                FROM respostas_usuario ru1
                INNER JOIN (
                    SELECT id_questao, MAX(data_resposta) AS max_data
                    FROM respostas_usuario
                    WHERE user_id = ?
                    GROUP BY id_questao
                ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                WHERE ru1.user_id = ?
            ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
$params_certas = [$user_id, $user_id];

if ($id_assunto > 0) {
    $sql_certas .= " AND q.id_assunto = ?";
    $params_certas[] = $id_assunto;
}

$sql_certas .= " AND r.acertou = 1";

echo "<h2>Consulta para filtro 'certas':</h2>";
mostrar_sql($sql_certas, $params_certas);
$resultados_certas = executar_consulta($pdo, $sql_certas, $params_certas);

// Consulta para filtro "erradas"
$sql_erradas = "SELECT q.id_questao, q.enunciado, 
                   CASE 
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta,
                   r.acertou
            FROM questoes q 
            LEFT JOIN (
                SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                FROM respostas_usuario ru1
                INNER JOIN (
                    SELECT id_questao, MAX(data_resposta) AS max_data
                    FROM respostas_usuario
                    WHERE user_id = ?
                    GROUP BY id_questao
                ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                WHERE ru1.user_id = ?
            ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
$params_erradas = [$user_id, $user_id];

if ($id_assunto > 0) {
    $sql_erradas .= " AND q.id_assunto = ?";
    $params_erradas[] = $id_assunto;
}

$sql_erradas .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";

echo "<h2>Consulta para filtro 'erradas':</h2>";
mostrar_sql($sql_erradas, $params_erradas);
$resultados_erradas = executar_consulta($pdo, $sql_erradas, $params_erradas);

// Verificar se há questões que estão em ambos os filtros
if (!empty($resultados_certas) && !empty($resultados_erradas)) {
    $questoes_certas = array_column($resultados_certas, 'id_questao');
    $questoes_erradas = array_column($resultados_erradas, 'id_questao');
    
    $intersecao = array_intersect($questoes_certas, $questoes_erradas);
    
    if (!empty($intersecao)) {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;margin:10px;padding:10px;'>";
        echo "<h3>PROBLEMA: Questões que aparecem em ambos os filtros:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($intersecao, true)) . "</pre>";
        echo "</div>";
        
        // Verificar detalhes dessas questões
        foreach ($intersecao as $id_questao) {
            $sql = "SELECT ru.id_questao, ru.acertou, ru.data_resposta, ru.user_id
                    FROM respostas_usuario ru
                    WHERE ru.id_questao = ? AND ru.user_id = ?
                    ORDER BY ru.data_resposta DESC";
            $params = [$id_questao, $user_id];
            
            echo "<h3>Detalhes da questão $id_questao:</h3>";
            mostrar_sql($sql, $params);
            executar_consulta($pdo, $sql, $params);
        }
    } else {
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;margin:10px;padding:10px;'>";
        echo "<h3>OK: Não há questões que aparecem em ambos os filtros.</h3>";
        echo "</div>";
    }
}

// Verificar se há questões que não estão em nenhum filtro
echo "<h1>Verificação de questões que não estão em nenhum filtro</h1>";

// Consulta para todas as questões respondidas
$sql_todas = "SELECT q.id_questao, q.enunciado, 
                   CASE 
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 1 THEN 'certa'
                       WHEN r.id_questao IS NOT NULL AND r.acertou = 0 THEN 'errada'
                       WHEN r.id_questao IS NOT NULL THEN 'respondida'
                       ELSE 'nao-respondida'
                   END as status_resposta,
                   r.acertou
            FROM questoes q 
            LEFT JOIN (
                SELECT ru1.id_questao, ru1.id_alternativa, ru1.acertou, ru1.data_resposta
                FROM respostas_usuario ru1
                INNER JOIN (
                    SELECT id_questao, MAX(data_resposta) AS max_data
                    FROM respostas_usuario
                    WHERE user_id = ?
                    GROUP BY id_questao
                ) ru2 ON ru1.id_questao = ru2.id_questao AND ru1.data_resposta = ru2.max_data
                WHERE ru1.user_id = ?
            ) r ON q.id_questao = r.id_questao
            WHERE 1=1";
$params_todas = [$user_id, $user_id];

if ($id_assunto > 0) {
    $sql_todas .= " AND q.id_assunto = ?";
    $params_todas[] = $id_assunto;
}

$sql_todas .= " AND r.id_questao IS NOT NULL";

echo "<h2>Consulta para todas as questões respondidas:</h2>";
mostrar_sql($sql_todas, $params_todas);
$resultados_todas = executar_consulta($pdo, $sql_todas, $params_todas);

if (!empty($resultados_todas)) {
    $questoes_todas = array_column($resultados_todas, 'id_questao');
    $questoes_certas_erradas = array_merge($questoes_certas, $questoes_erradas);
    
    $diferenca = array_diff($questoes_todas, $questoes_certas_erradas);
    
    if (!empty($diferenca)) {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;margin:10px;padding:10px;'>";
        echo "<h3>PROBLEMA: Questões respondidas que não estão em nenhum filtro (certas ou erradas):</h3>";
        echo "<pre>" . htmlspecialchars(print_r($diferenca, true)) . "</pre>";
        echo "</div>";
        
        // Verificar detalhes dessas questões
        foreach ($diferenca as $id_questao) {
            $sql = "SELECT ru.id_questao, ru.acertou, ru.data_resposta, ru.user_id
                    FROM respostas_usuario ru
                    WHERE ru.id_questao = ? AND ru.user_id = ?
                    ORDER BY ru.data_resposta DESC";
            $params = [$id_questao, $user_id];
            
            echo "<h3>Detalhes da questão $id_questao:</h3>";
            mostrar_sql($sql, $params);
            executar_consulta($pdo, $sql, $params);
        }
    } else {
        echo "<div style='background:#d4edda;border:1px solid #c3e6cb;margin:10px;padding:10px;'>";
        echo "<h3>OK: Todas as questões respondidas estão em algum filtro (certas ou erradas).</h3>";
        echo "</div>";
    }
}

// Verificar se há questões com respostas inconsistentes
echo "<h1>Verificação de questões com respostas inconsistentes</h1>";

$sql = "SELECT ru.id_questao, 
               COUNT(DISTINCT ru.acertou) as diferentes_acertos,
               GROUP_CONCAT(DISTINCT ru.acertou) as valores_acertou,
               MAX(ru.data_resposta) as ultima_resposta
        FROM respostas_usuario ru
        WHERE ru.user_id = ?
        GROUP BY ru.id_questao
        HAVING COUNT(DISTINCT ru.acertou) > 1
        ORDER BY ultima_resposta DESC";
$params = [$user_id];

mostrar_sql($sql, $params);
$inconsistentes = executar_consulta($pdo, $sql, $params);

if (!empty($inconsistentes)) {
    echo "<div style='background:#fff3cd;border:1px solid #ffeeba;margin:10px;padding:10px;'>";
    echo "<h3>ATENÇÃO: Questões com respostas inconsistentes (tanto certas quanto erradas):</h3>";
    echo "<p>Isso é normal se o usuário respondeu a mesma questão várias vezes com resultados diferentes.</p>";
    echo "<p>O importante é que a consulta SQL esteja usando a resposta mais recente para determinar o filtro.</p>";
    echo "</div>";
    
    // Verificar se a última resposta está sendo usada corretamente
    foreach ($inconsistentes as $questao) {
        $id_questao = $questao['id_questao'];
        
        echo "<h3>Verificação da última resposta para a questão $id_questao:</h3>";
        
        // Consulta para obter todas as respostas da questão
        $sql = "SELECT ru.id_questao, ru.acertou, ru.data_resposta, ru.user_id
                FROM respostas_usuario ru
                WHERE ru.id_questao = ? AND ru.user_id = ?
                ORDER BY ru.data_resposta DESC";
        $params = [$id_questao, $user_id];
        
        mostrar_sql($sql, $params);
        $respostas = executar_consulta($pdo, $sql, $params);
        
        if (!empty($respostas)) {
            $ultima_resposta = $respostas[0];
            $acertou = $ultima_resposta['acertou'];
            
            echo "<div style='background:#e9f7ef;border:1px solid #ddd;margin:10px;padding:10px;'>";
            echo "<h4>Última resposta:</h4>";
            echo "<pre>" . htmlspecialchars(print_r($ultima_resposta, true)) . "</pre>";
            echo "<p>Acertou: " . ($acertou ? "Sim" : "Não") . "</p>";
            echo "<p>Deveria estar no filtro: " . ($acertou ? "certas" : "erradas") . "</p>";
            echo "</div>";
            
            // Verificar se a questão está no filtro correto
            $filtro_correto = $acertou ? 'certas' : 'erradas';
            $esta_no_filtro_correto = false;
            
            if ($acertou && in_array($id_questao, $questoes_certas)) {
                $esta_no_filtro_correto = true;
            } elseif (!$acertou && in_array($id_questao, $questoes_erradas)) {
                $esta_no_filtro_correto = true;
            }
            
            if ($esta_no_filtro_correto) {
                echo "<div style='background:#d4edda;border:1px solid #c3e6cb;margin:10px;padding:10px;'>";
                echo "<h4>OK: A questão está no filtro correto ($filtro_correto).</h4>";
                echo "</div>";
            } else {
                echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;margin:10px;padding:10px;'>";
                echo "<h4>PROBLEMA: A questão NÃO está no filtro correto ($filtro_correto).</h4>";
                echo "</div>";
            }
        }
    }
} else {
    echo "<div style='background:#d4edda;border:1px solid #c3e6cb;margin:10px;padding:10px;'>";
    echo "<h3>OK: Não há questões com respostas inconsistentes.</h3>";
    echo "</div>";
}
?>