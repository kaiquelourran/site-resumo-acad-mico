<?php
/**
 * Este arquivo força o uso da configuração online (Hostinger) para o banco de dados
 * Inclua este arquivo antes de incluir o conexao.php
 */

// Define uma variável global para forçar o uso da configuração online
$_SERVER['HTTP_HOST'] = 'resumoacademico.com.br';

// Mensagem de log para debug
error_log("Forçando uso da configuração online do banco de dados (Hostinger)");
?>