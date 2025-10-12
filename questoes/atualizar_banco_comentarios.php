<?php
require_once 'conexao.php';

try {
    // Adicionar colunas (cada operação com try/catch para ser idempotente)
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD COLUMN curtidas INT DEFAULT 0"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD COLUMN id_comentario_pai INT NULL"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD COLUMN ativo BOOLEAN DEFAULT TRUE"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD COLUMN reportado BOOLEAN DEFAULT FALSE"); } catch (PDOException $e) { /* coluna já existe */ }
    echo "✅ Colunas adicionadas com sucesso!<br>";

    // Adicionar índices/foreign key (idempotentes)
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD INDEX idx_curtidas (curtidas)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD INDEX idx_pai (id_comentario_pai)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD INDEX idx_reportado (reportado)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE comentarios_questoes ADD CONSTRAINT fk_comentario_pai FOREIGN KEY (id_comentario_pai) REFERENCES comentarios_questoes(id_comentario) ON DELETE CASCADE"); } catch (PDOException $e) { /* constraint já existe */ }
    
    // Criar tabela de curtidas de usuários
    $sql = "CREATE TABLE IF NOT EXISTS curtidas_comentarios (
        id_curtida INT AUTO_INCREMENT PRIMARY KEY,
        id_comentario INT NOT NULL,
        email_usuario VARCHAR(255) NULL,
        ip_usuario VARCHAR(45) NULL,
        data_curtida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_curtida_email (id_comentario, email_usuario),
        UNIQUE KEY unique_curtida_ip (id_comentario, ip_usuario),
        INDEX idx_email_usuario (email_usuario),
        INDEX idx_ip_usuario (ip_usuario),
        FOREIGN KEY (id_comentario) REFERENCES comentarios_questoes(id_comentario) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "✅ Tabela de curtidas criada!<br>";

    // Migração: garantir colunas/índices quando a tabela já existe
    try { $pdo->exec("ALTER TABLE curtidas_comentarios ADD COLUMN email_usuario VARCHAR(255) NULL AFTER id_comentario"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE curtidas_comentarios MODIFY COLUMN ip_usuario VARCHAR(45) NULL"); } catch (PDOException $e) { /* já nulo ou erro compatível */ }

    // Adicionar índices/uniques se ainda não existirem (tentativas idempotentes)
    try { $pdo->exec("ALTER TABLE curtidas_comentarios ADD UNIQUE KEY unique_curtida_email (id_comentario, email_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE curtidas_comentarios ADD UNIQUE KEY unique_curtida_ip (id_comentario, ip_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE curtidas_comentarios ADD INDEX idx_email_usuario (email_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE curtidas_comentarios ADD INDEX idx_ip_usuario (ip_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    echo "✅ Migração de curtidas aplicada!<br>";
    
    // Criar tabela de denúncias de comentários
    $sqlDenuncias = "CREATE TABLE IF NOT EXISTS denuncias_comentarios (
        id_denuncia INT AUTO_INCREMENT PRIMARY KEY,
        id_comentario INT NOT NULL,
        email_usuario VARCHAR(255) NULL,
        ip_usuario VARCHAR(45) NULL,
        motivo TEXT NULL,
        tipo VARCHAR(50) NULL,
        data_denuncia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_denuncia_email (id_comentario, email_usuario),
        UNIQUE KEY unique_denuncia_ip (id_comentario, ip_usuario),
        INDEX idx_denuncia_email (email_usuario),
        INDEX idx_denuncia_ip (ip_usuario),
        FOREIGN KEY (id_comentario) REFERENCES comentarios_questoes(id_comentario) ON DELETE CASCADE
    )";
    $pdo->exec($sqlDenuncias);
    echo "✅ Tabela de denúncias criada!<br>";

    // Migração idempotente para denuncias_comentarios
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD COLUMN email_usuario VARCHAR(255) NULL AFTER id_comentario"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios MODIFY COLUMN ip_usuario VARCHAR(45) NULL"); } catch (PDOException $e) { /* já nulo ou erro compatível */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD COLUMN motivo TEXT NULL AFTER ip_usuario"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD COLUMN tipo VARCHAR(50) NULL AFTER motivo"); } catch (PDOException $e) { /* coluna já existe */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD UNIQUE KEY unique_denuncia_email (id_comentario, email_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD UNIQUE KEY unique_denuncia_ip (id_comentario, ip_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD INDEX idx_denuncia_email (email_usuario)"); } catch (PDOException $e) { /* índice já existe */ }
    try { $pdo->exec("ALTER TABLE denuncias_comentarios ADD INDEX idx_denuncia_ip (ip_usuario)"); } catch (PDOException $e) { /* índice já existe */ }

    
    // Verificar estrutura atualizada
    $stmt = $pdo->query("DESCRIBE comentarios_questoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Estrutura atualizada da tabela comentarios_questoes:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "</tr>";
    }
    echo "<h3>Estrutura atualizada da tabela denuncias_comentarios:</h3>";
    $stmt3 = $pdo->query("DESCRIBE denuncias_comentarios");
    $colunas3 = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($colunas3 as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . ($coluna['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Verificar estrutura de curtidas_comentarios
    $stmt2 = $pdo->query("DESCRIBE curtidas_comentarios");
    $colunas2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Estrutura atualizada da tabela curtidas_comentarios:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($colunas2 as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . ($coluna['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
