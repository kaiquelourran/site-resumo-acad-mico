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

// Testar filtro "certas"
echo "<h1>Teste do filtro 'certas'</h1>";
$filtro_ativo = 'certas';

if ($tem_user_id && $user_id !== null) {
    // Com coluna user_id: considerar a última resposta do usuário atual por questão
    $sql = "SELECT q.id_questao, q.enunciado, 
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
    $params = [$user_id, $user_id];
    
    if ($id_assunto > 0) {
        $sql .= " AND q.id_assunto = ?";
        $params[] = $id_assunto;
    }
    
    // Aplicar filtro específico
    $sql .= " AND r.acertou = 1";
    
    $sql .= " ORDER BY q.id_questao";
    
    mostrar_sql($sql, $params);
    $resultados_certas = executar_consulta($pdo, $sql, $params);
}

// Testar filtro "erradas"
echo "<h1>Teste do filtro 'erradas'</h1>";
$filtro_ativo = 'erradas';

if ($tem_user_id && $user_id !== null) {
    // Com coluna user_id: considerar a última resposta do usuário atual por questão
    $sql = "SELECT q.id_questao, q.enunciado, 
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
    $params = [$user_id, $user_id];
    
    if ($id_assunto > 0) {
        $sql .= " AND q.id_assunto = ?";
        $params[] = $id_assunto;
    }
    
    // Aplicar filtro específico
    $sql .= " AND r.id_questao IS NOT NULL AND r.acertou = 0";
    
    $sql .= " ORDER BY q.id_questao";
    
    mostrar_sql($sql, $params);
    $resultados_erradas = executar_consulta($pdo, $sql, $params);
}

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
if (!empty($resultados_certas) && !empty($resultados_erradas)) {
    echo "<h1>Verificação de questões em ambos os filtros</h1>";
    
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
?>