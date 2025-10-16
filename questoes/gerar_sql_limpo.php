<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Configuração do banco LOCAL (XAMPP)
$host = "localhost";
$db   = "resumo_quiz";
$user = "root";
$pass = "";

echo "<h2>🚀 GERANDO ARQUIVO SQL LIMPO PARA HOSTINGER</h2>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conectado ao banco local: <strong>$db</strong><br><br>";

    // Nome do arquivo SQL (na raiz do projeto)
    $arquivo_sql = "../resumo_quiz_limpo.sql";
    
    // Abrir arquivo para escrita
    $handle = fopen($arquivo_sql, 'w');
    
    if (!$handle) {
        die("❌ Erro ao criar arquivo SQL");
    }

    // Escrever cabeçalho limpo (SEM comandos do phpmyadmin)
    fwrite($handle, "-- Arquivo SQL limpo para Hostinger\n");
    fwrite($handle, "-- Gerado automaticamente - SEM comandos do phpmyadmin\n");
    fwrite($handle, "-- Data: " . date('Y-m-d H:i:s') . "\n\n");
    
    fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($handle, "START TRANSACTION;\n");
    fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
    
    fwrite($handle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
    fwrite($handle, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
    fwrite($handle, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
    fwrite($handle, "/*!40101 SET NAMES utf8mb4 */;\n\n");
    
    fwrite($handle, "--\n");
    fwrite($handle, "-- IMPORTANTE: Este arquivo NÃO contém comandos do phpmyadmin\n");
    fwrite($handle, "-- Selecionando o banco de dados da Hostinger\n");
    fwrite($handle, "--\n");
    fwrite($handle, "USE `u775269467_questoes`;\n\n");

    // Obter lista de tabelas (APENAS tabelas do projeto, não do sistema)
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Filtrar apenas tabelas do projeto (excluir tabelas do sistema)
    $tabelas_sistema = ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin', 'test'];
    $tabelas_projeto = array_filter($tabelas, function($tabela) use ($tabelas_sistema) {
        return !in_array($tabela, $tabelas_sistema);
    });

    echo "📊 Tabelas do sistema ignoradas: " . implode(', ', $tabelas_sistema) . "<br>";
    echo "📊 Tabelas do projeto processadas: " . count($tabelas_projeto) . "<br><br>";

    foreach ($tabelas_projeto as $tabela) {
        echo "📊 Processando tabela: <strong>$tabela</strong><br>";
        
        // Obter estrutura da tabela
        $stmt = $pdo->query("SHOW CREATE TABLE `$tabela`");
        $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
        
        fwrite($handle, "--\n");
        fwrite($handle, "-- Estrutura da tabela `$tabela`\n");
        fwrite($handle, "--\n\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$tabela`;\n");
        fwrite($handle, $create_table['Create Table'] . ";\n\n");
        
        // Obter dados da tabela
        $stmt = $pdo->query("SELECT * FROM `$tabela`");
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($dados)) {
            fwrite($handle, "--\n");
            fwrite($handle, "-- Dados da tabela `$tabela`\n");
            fwrite($handle, "--\n\n");
            
            // Obter nomes das colunas
            $colunas = array_keys($dados[0]);
            $colunas_str = '`' . implode('`, `', $colunas) . '`';
            
            foreach ($dados as $linha) {
                $valores = array();
                foreach ($linha as $valor) {
                    if ($valor === null) {
                        $valores[] = 'NULL';
                    } else {
                        $valores[] = "'" . addslashes($valor) . "'";
                    }
                }
                $valores_str = implode(', ', $valores);
                fwrite($handle, "INSERT INTO `$tabela` ($colunas_str) VALUES ($valores_str);\n");
            }
            fwrite($handle, "\n");
        }
    }

    fwrite($handle, "COMMIT;\n");
    fwrite($handle, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
    fwrite($handle, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
    fwrite($handle, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

    fclose($handle);

    echo "<br>✅ <strong>Arquivo SQL limpo gerado com sucesso!</strong><br>";
    echo "📁 Arquivo: <strong>$arquivo_sql</strong><br>";
    echo "📊 Tabelas processadas: " . count($tabelas_projeto) . " (apenas do projeto)<br>";
    echo "🚫 Tabelas do sistema ignoradas: " . count($tabelas_sistema) . "<br><br>";
    
    echo "<h3>🎯 PRÓXIMOS PASSOS:</h3>";
    echo "<ol>";
    echo "<li>📥 <strong>Baixe o arquivo:</strong> <a href='$arquivo_sql' download>Clique aqui para baixar</a></li>";
    echo "<li>🌐 <strong>Acesse a Hostinger:</strong> Vá no phpMyAdmin da Hostinger</li>";
    echo "<li>📤 <strong>Importe o arquivo:</strong> Use o arquivo <code>$arquivo_sql</code></li>";
    echo "<li>✅ <strong>Teste:</strong> Verifique se as 60 questões foram importadas</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "❌ Erro ao gerar arquivo SQL: " . $e->getMessage() . "<br>";
}
?>
