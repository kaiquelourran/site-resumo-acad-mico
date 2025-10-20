<?php
require_once 'conexao.php';

echo "<h2>Criando Tabela de Respostas do Usuário</h2>";

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS respostas_usuario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        id_questao INT NOT NULL,
        id_alternativa INT NOT NULL,
        acertou TINYINT(1) NOT NULL DEFAULT 0,
        data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_questao (user_id, id_questao)
    );";

    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Tabela 'respostas_usuario' criada ou já existente!</p>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao criar tabela 'respostas_usuario': " . $e->getMessage() . "</p>";
}
?>