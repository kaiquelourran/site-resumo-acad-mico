<?php
/**
 * Sistema de Backup Automático
 * Criar backup do banco de dados de forma segura
 * 
 * USO: 
 * - Configurar cron job diário: 0 3 * * * /usr/bin/php /caminho/backup_automatico.php
 * - Ou executar manualmente quando necessário
 */

require_once 'conexao.php';
require_once 'config.php';

// Configurações de backup
$backup_dir = __DIR__ . '/backups';
$max_backups = 7; // Manter apenas os últimos 7 backups
$backup_prefix = 'backup_questoes_';

// Criar diretório de backups se não existir
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    // Proteger pasta com .htaccess
    file_put_contents($backup_dir . '/.htaccess', "Deny from all\n");
}

// Nome do arquivo de backup
$backup_file = $backup_dir . '/' . $backup_prefix . date('Y-m-d_H-i-s') . '.sql';

try {
    // Abrir arquivo para escrita
    $handle = fopen($backup_file, 'w');
    
    if (!$handle) {
        throw new Exception('Não foi possível criar arquivo de backup');
    }
    
    // Cabeçalho do arquivo SQL
    fwrite($handle, "-- Backup Automático do Banco de Dados\n");
    fwrite($handle, "-- Data: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "-- Banco: " . $db . "\n\n");
    fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($handle, "START TRANSACTION;\n");
    fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
    
    // Listar todas as tabelas
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Estrutura da tabela
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        fwrite($handle, "\n-- Estrutura da tabela `$table`\n");
        fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($handle, $create_table['Create Table'] . ";\n\n");
        
        // Dados da tabela
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            fwrite($handle, "-- Dados da tabela `$table`\n");
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                $columns_str = implode('`, `', $columns);
                $values_str = implode("', '", array_map(function($v) use ($pdo) {
                    return $pdo->quote($v);
                }, $values));
                $values_str = str_replace("''", "'", $values_str); // Remover quotes duplas do quote()
                
                fwrite($handle, "INSERT INTO `$table` (`$columns_str`) VALUES ($values_str);\n");
            }
            fwrite($handle, "\n");
        }
    }
    
    fwrite($handle, "COMMIT;\n");
    fclose($handle);
    
    // Limpar backups antigos (manter apenas os últimos N)
    $existing_backups = glob($backup_dir . '/' . $backup_prefix . '*.sql');
    if (count($existing_backups) > $max_backups) {
        // Ordenar por data (mais antigos primeiro)
        usort($existing_backups, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        // Remover os mais antigos
        $to_remove = array_slice($existing_backups, 0, count($existing_backups) - $max_backups);
        foreach ($to_remove as $old_backup) {
            unlink($old_backup);
        }
    }
    
    $backup_size = filesize($backup_file);
    $backup_size_mb = round($backup_size / 1024 / 1024, 2);
    
    $message = "Backup criado com sucesso!\n";
    $message .= "Arquivo: " . basename($backup_file) . "\n";
    $message .= "Tamanho: " . $backup_size_mb . " MB\n";
    $message .= "Backups mantidos: " . min(count($existing_backups), $max_backups) . "\n";
    
    // Log de sucesso
    error_log($message);
    
    // Se executado via navegador, mostrar mensagem
    if (php_sapi_name() !== 'cli') {
        echo "<pre>" . htmlspecialchars($message) . "</pre>";
    }
    
} catch (Exception $e) {
    $error_message = "Erro ao criar backup: " . $e->getMessage();
    error_log($error_message);
    
    if (php_sapi_name() !== 'cli') {
        echo "<pre>" . htmlspecialchars($error_message) . "</pre>";
    }
}
?>

