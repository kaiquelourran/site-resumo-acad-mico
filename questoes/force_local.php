<?php
/**
 * Este arquivo força o uso da configuração local para o banco de dados
 * Inclua este arquivo antes de incluir o conexao.php
 */

// Define uma variável global para forçar o uso da configuração local
$_SERVER['HTTP_HOST'] = 'localhost';

// Mensagem de log para debug
error_log("Forçando uso da configuração local do banco de dados");
?>