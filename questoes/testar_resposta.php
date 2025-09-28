<?php
require_once 'conexao.php';

echo "<h2>Teste de Salvamento de Resposta</h2>";

// Criar tabela se não existir
$sql_create_table = "CREATE TABLE IF NOT EXISTS respostas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_questao INT NOT NULL,
    id_alternativa INT NOT NULL,
    acertou TINYINT(1) NOT NULL DEFAULT 0,
    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_questao) REFERENCES questoes(id_questao),
    FOREIGN KEY (id_alternativa) REFERENCES alternativas(id_alternativa),
    UNIQUE KEY unique_questao (id_questao)
)";

try {
    $pdo->query($sql_create_table);
    echo "<p style='color: green;'>✓ Tabela criada/verificada com sucesso</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro ao criar tabela: " . $e->getMessage() . "</p>";
}

// Buscar uma questão para testar
$stmt = $pdo->query("SELECT q.id_questao, q.enunciado, a.id_alternativa, a.texto, a.correta 
                     FROM questoes q 
                     JOIN alternativas a ON q.id_questao = a.id_questao 
                     WHERE q.id_assunto = 8 
                     ORDER BY q.id_questao, a.id_alternativa 
                     LIMIT 4");
$dados_teste = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($dados_teste) {
    $questao_teste = $dados_teste[0];
    echo "<h3>Questão de teste:</h3>";
    echo "<p><strong>ID:</strong> {$questao_teste['id_questao']}</p>";
    echo "<p><strong>Enunciado:</strong> " . substr($questao_teste['enunciado'], 0, 100) . "...</p>";
    
    echo "<h4>Alternativas:</h4>";
    foreach ($dados_teste as $alt) {
        $correta_texto = $alt['correta'] ? ' (CORRETA)' : '';
        echo "<p>ID {$alt['id_alternativa']}: {$alt['texto']}{$correta_texto}</p>";
    }
    
    // Tentar salvar uma resposta de teste
    if (isset($_GET['testar'])) {
        $id_questao_teste = $questao_teste['id_questao'];
        $id_alternativa_teste = $dados_teste[0]['id_alternativa']; // Primeira alternativa
        $acertou_teste = $dados_teste[0]['correta'] ? 1 : 0;
        
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) 
                                         VALUES (?, ?, ?) 
                                         ON DUPLICATE KEY UPDATE 
                                         id_alternativa = VALUES(id_alternativa), 
                                         acertou = VALUES(acertou), 
                                         data_resposta = CURRENT_TIMESTAMP");
            $stmt_insert->execute([$id_questao_teste, $id_alternativa_teste, $acertou_teste]);
            
            echo "<p style='color: green;'>✓ Resposta de teste salva com sucesso!</p>";
            echo "<p>Questão: {$id_questao_teste}, Alternativa: {$id_alternativa_teste}, Acertou: {$acertou_teste}</p>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Erro ao salvar resposta: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p><a href='?testar=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Testar Salvamento</a></p>";
    }
}

// Verificar respostas salvas
echo "<h3>Respostas atualmente salvas:</h3>";
$stmt_respostas = $pdo->query("SELECT * FROM respostas_usuario ORDER BY data_resposta DESC");
$respostas = $stmt_respostas->fetchAll(PDO::FETCH_ASSOC);

if ($respostas) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>ID Questão</th><th>ID Alternativa</th><th>Acertou</th><th>Data</th></tr>";
    foreach ($respostas as $resp) {
        echo "<tr>";
        echo "<td>{$resp['id']}</td>";
        echo "<td>{$resp['id_questao']}</td>";
        echo "<td>{$resp['id_alternativa']}</td>";
        echo "<td>" . ($resp['acertou'] ? 'Sim' : 'Não') . "</td>";
        echo "<td>{$resp['data_resposta']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhuma resposta encontrada.</p>";
}

echo "<hr>";
echo "<p><a href='listar_questoes.php?id=8&filtro=todas'>← Voltar para listar questões</a></p>";
?>