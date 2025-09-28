<?php
require_once __DIR__ . '/conexao.php';
header('Content-Type: application/json');

try {
    $debug = [];
    
    // 1. Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'respostas_usuario'");
    $debug['tabela_existe'] = $stmt->rowCount() > 0;
    
    // 2. Verificar estrutura da tabela
    if ($debug['tabela_existe']) {
        $stmt = $pdo->query("DESCRIBE respostas_usuario");
        $debug['estrutura_tabela'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Contar registros existentes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM respostas_usuario");
        $debug['total_registros'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // 4. Mostrar últimos 5 registros
        $stmt = $pdo->query("SELECT * FROM respostas_usuario ORDER BY data_resposta DESC LIMIT 5");
        $debug['ultimos_registros'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Verificar se existe questão 76
        $stmt = $pdo->query("SELECT COUNT(*) as existe FROM questoes WHERE id_questao = 76");
        $debug['questao_76_existe'] = $stmt->fetch(PDO::FETCH_ASSOC)['existe'] > 0;
        
        // 6. Verificar se existe alternativa 322
        $stmt = $pdo->query("SELECT COUNT(*) as existe FROM alternativas WHERE id_alternativa = 322");
        $debug['alternativa_322_existe'] = $stmt->fetch(PDO::FETCH_ASSOC)['existe'] > 0;
        
        // 7. Verificar se já existe resposta para questão 76
        $stmt = $pdo->query("SELECT * FROM respostas_usuario WHERE id_questao = 76");
        $debug['resposta_questao_76'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 8. Testar inserção manual
        try {
            $stmt = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id_alternativa = VALUES(id_alternativa), acertou = VALUES(acertou), data_resposta = CURRENT_TIMESTAMP");
            $resultado = $stmt->execute([999, 999, 1]); // Valores de teste
            $debug['teste_insercao'] = $resultado;
            
            // Remove o registro de teste
            $pdo->query("DELETE FROM respostas_usuario WHERE id_questao = 999");
        } catch (Exception $e) {
            $debug['erro_teste_insercao'] = $e->getMessage();
        }
    }
    
    $debug['timestamp'] = date('Y-m-d H:i:s');
    echo json_encode($debug, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()], JSON_PRETTY_PRINT);
}
?>