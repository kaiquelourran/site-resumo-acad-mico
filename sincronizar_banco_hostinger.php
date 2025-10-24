<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincronização Banco Hostinger</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .ok { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
        .erro { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        .aviso { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #ffc107; }
        h1 { color: #0072FF; text-align: center; margin-bottom: 30px; }
        h2 { color: #333; margin: 25px 0 15px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 0.9em; }
        .command { background: #0072FF; color: white; padding: 10px 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Sincronização Banco de Dados - Hostinger</h1>
        
        <?php
        require_once 'questoes/conexao.php';
        
        echo '<div class="info">';
        echo '<h2>📊 Status da Conexão</h2>';
        echo '<p><strong>Ambiente:</strong> ' . ($is_local ? 'Local (XAMPP)' : 'Produção (Hostinger)') . '</p>';
        echo '<p><strong>Banco:</strong> ' . $db . '</p>';
        echo '<p><strong>Host:</strong> ' . $host . '</p>';
        echo '</div>';
        
        try {
            // 1. SINCRONIZAR TABELA USUARIOS
            echo '<h2>1. 👥 Sincronizando Tabela USUARIOS</h2>';
            
            // Verificar se tabela existe
            $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
            
            if (empty($tables)) {
                echo '<div class="erro">❌ Tabela usuarios não existe! Criando...</div>';
                
                $sql_create_usuarios = "
                CREATE TABLE usuarios (
                    id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY,
                    nome VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    senha VARCHAR(255) NULL,
                    google_id VARCHAR(255) NULL UNIQUE,
                    avatar_url VARCHAR(512) NULL,
                    tipo ENUM('usuario', 'admin') DEFAULT 'usuario',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                $pdo->exec($sql_create_usuarios);
                echo '<div class="ok">✅ Tabela usuarios criada com estrutura completa!</div>';
            } else {
                echo '<div class="info">📋 Tabela usuarios existe. Verificando estrutura...</div>';
                
                // Verificar colunas existentes
                $columns = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
                
                // Adicionar colunas que faltam
                $alteracoes = [];
                
                if (!in_array('senha', $columns)) {
                    $pdo->exec("ALTER TABLE usuarios ADD COLUMN senha VARCHAR(255) NULL");
                    $alteracoes[] = "✅ Coluna 'senha' adicionada";
                }
                
                if (!in_array('tipo', $columns)) {
                    $pdo->exec("ALTER TABLE usuarios ADD COLUMN tipo ENUM('usuario', 'admin') DEFAULT 'usuario'");
                    $alteracoes[] = "✅ Coluna 'tipo' adicionada";
                }
                
                if (!in_array('google_id', $columns)) {
                    $pdo->exec("ALTER TABLE usuarios ADD COLUMN google_id VARCHAR(255) NULL UNIQUE");
                    $alteracoes[] = "✅ Coluna 'google_id' adicionada";
                }
                
                if (!in_array('avatar_url', $columns)) {
                    $pdo->exec("ALTER TABLE usuarios ADD COLUMN avatar_url VARCHAR(512) NULL");
                    $alteracoes[] = "✅ Coluna 'avatar_url' adicionada";
                }
                
                if (!in_array('updated_at', $columns)) {
                    $pdo->exec("ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                    $alteracoes[] = "✅ Coluna 'updated_at' adicionada";
                }
                
                // Renomear coluna id para id_usuario se necessário
                if (in_array('id', $columns) && !in_array('id_usuario', $columns)) {
                    $pdo->exec("ALTER TABLE usuarios CHANGE id id_usuario INT(11) AUTO_INCREMENT PRIMARY KEY");
                    $alteracoes[] = "✅ Coluna 'id' renomeada para 'id_usuario'";
                }
                
                if (empty($alteracoes)) {
                    echo '<div class="ok">✅ Tabela usuarios já está atualizada!</div>';
                } else {
                    foreach ($alteracoes as $alt) {
                        echo '<div class="ok">' . $alt . '</div>';
                    }
                }
            }
            
            // 2. SINCRONIZAR TABELA COMENTARIOS_QUESTOES
            echo '<h2>2. 💬 Sincronizando Tabela COMENTARIOS_QUESTOES</h2>';
            
            $tables_comentarios = $pdo->query("SHOW TABLES LIKE 'comentarios_questoes'")->fetchAll();
            
            if (empty($tables_comentarios)) {
                echo '<div class="info">📋 Criando tabela comentarios_questoes...</div>';
                
                $sql_create_comentarios = "
                CREATE TABLE comentarios_questoes (
                    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
                    id_questao INT NOT NULL,
                    id_usuario INT NOT NULL,
                    comentario TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_questao) REFERENCES questoes(id_questao) ON DELETE CASCADE,
                    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
                )";
                
                $pdo->exec($sql_create_comentarios);
                echo '<div class="ok">✅ Tabela comentarios_questoes criada!</div>';
            } else {
                echo '<div class="ok">✅ Tabela comentarios_questoes já existe!</div>';
            }
            
            // 3. VERIFICAR OUTRAS TABELAS IMPORTANTES
            echo '<h2>3. 🔍 Verificando Outras Tabelas</h2>';
            
            $tabelas_importantes = ['questoes', 'assuntos', 'alternativas', 'respostas_usuario'];
            $tabelas_existentes = [];
            
            foreach ($tabelas_importantes as $tabela) {
                $existe = $pdo->query("SHOW TABLES LIKE '$tabela'")->fetchAll();
                if (!empty($existe)) {
                    $tabelas_existentes[] = $tabela;
                    echo '<div class="ok">✅ Tabela ' . $tabela . ' existe</div>';
                } else {
                    echo '<div class="erro">❌ Tabela ' . $tabela . ' NÃO existe</div>';
                }
            }
            
            // 4. RELATÓRIO FINAL
            echo '<h2>4. 📊 Relatório Final</h2>';
            
            $total_tabelas = $pdo->query("SHOW TABLES")->fetchAll();
            echo '<div class="info">';
            echo '<p><strong>Total de tabelas no banco:</strong> ' . count($total_tabelas) . '</p>';
            echo '<p><strong>Timestamp da sincronização:</strong> ' . date('Y-m-d H:i:s') . '</p>';
            echo '</div>';
            
            echo '<div class="ok">';
            echo '<h3>🎉 Sincronização Concluída!</h3>';
            echo '<p>O banco de dados da Hostinger foi sincronizado com as alterações do desenvolvimento local.</p>';
            echo '<p><strong>Próximos passos:</strong></p>';
            echo '<ul>';
            echo '<li>✅ Testar o sistema de cadastro</li>';
            echo '<li>✅ Testar o login com Google</li>';
            echo '<li>✅ Testar o sistema de comentários</li>';
            echo '<li>✅ Verificar se todas as funcionalidades estão funcionando</li>';
            echo '</ul>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="erro">';
            echo '<h3>❌ Erro na Sincronização</h3>';
            echo '<p><strong>Erro:</strong> ' . $e->getMessage() . '</p>';
            echo '<p><strong>Arquivo:</strong> ' . $e->getFile() . '</p>';
            echo '<p><strong>Linha:</strong> ' . $e->getLine() . '</p>';
            echo '</div>';
        }
        ?>
        
        <div class="aviso">
            <h3>⚠️ Importante</h3>
            <p><strong>Este script deve ser executado APENAS na Hostinger!</strong></p>
            <p>Após a sincronização, você pode remover este arquivo por segurança.</p>
        </div>
    </div>
</body>
</html>
