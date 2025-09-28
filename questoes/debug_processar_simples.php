<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>Debug Processar Resposta</h1>";

try {
    // Verificar se a tabela respostas_usuario existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'respostas_usuario'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "<p>✅ Tabela respostas_usuario existe</p>";
        
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE respostas_usuario");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Estrutura da tabela respostas_usuario:</h3>";
        echo "<pre>" . print_r($columns, true) . "</pre>";
    } else {
        echo "<p>❌ Tabela respostas_usuario não existe</p>";
        
        // Tentar criar a tabela
        echo "<p>Tentando criar a tabela...</p>";
        $sql_create_table = "CREATE TABLE IF NOT EXISTS respostas_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_questao INT NOT NULL,
            id_alternativa INT NOT NULL,
            acertou TINYINT(1) NOT NULL DEFAULT 0,
            data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_questao (id_questao)
        )";
        
        $pdo->query($sql_create_table);
        echo "<p>✅ Tabela criada com sucesso</p>";
    }
    
    // Verificar se existem questões e alternativas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = 8");
    $questoes_count = $stmt->fetch()['total'];
    echo "<p>Total de questões para assunto 8: $questoes_count</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas WHERE id_questao = 76");
    $alternativas_count = $stmt->fetch()['total'];
    echo "<p>Total de alternativas para questão 76: $alternativas_count</p>";
    
    if ($alternativas_count > 0) {
        $stmt = $pdo->query("SELECT * FROM alternativas WHERE id_questao = 76");
        $alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Alternativas da questão 76:</h3>";
        echo "<pre>" . print_r($alternativas, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>