<?php
require_once 'init_session.php';

// Verificar se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contato.php');
    exit;
}

// Função para limpar dados
function limparDados($dados) {
    return trim(strip_tags($dados));
}

// Função para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Capturar e limpar dados do formulário
$nome = limparDados($_POST['nome'] ?? '');
$email = limparDados($_POST['email'] ?? '');
$assunto = limparDados($_POST['assunto'] ?? '');
$mensagem = limparDados($_POST['mensagem'] ?? '');

// Array para armazenar erros
$erros = [];

// Validações
if (empty($nome)) {
    $erros[] = 'Nome é obrigatório';
}

if (empty($email)) {
    $erros[] = 'E-mail é obrigatório';
} elseif (!validarEmail($email)) {
    $erros[] = 'E-mail inválido';
}

if (empty($assunto)) {
    $erros[] = 'Assunto é obrigatório';
}

if (empty($mensagem)) {
    $erros[] = 'Mensagem é obrigatória';
}

// Se houver erros, redirecionar de volta
if (!empty($erros)) {
    $_SESSION['mensagem_contato'] = implode('<br>', $erros);
    $_SESSION['mensagem_tipo'] = 'error';
    header('Location: contato.php');
    exit;
}

// Se chegou até aqui, os dados são válidos
// Aqui você pode implementar o envio do email ou salvar no banco de dados

// Por enquanto, vamos simular um sucesso
$assuntos = [
    'duvida' => 'Dúvida sobre o Sistema',
    'sugestao' => 'Sugestão de Melhoria',
    'problema' => 'Reportar Problema',
    'parceria' => 'Proposta de Parceria',
    'outro' => 'Outro'
];

$assunto_formatado = $assuntos[$assunto] ?? 'Outro';

// Log da mensagem (opcional - para debug)
$log_data = [
    'data' => date('Y-m-d H:i:s'),
    'nome' => $nome,
    'email' => $email,
    'assunto' => $assunto_formatado,
    'mensagem' => $mensagem
];

// Salvar em arquivo de log protegido (opcional)
// IMPORTANTE: Este arquivo está na pasta questoes que tem .htaccess de proteção
$log_file = 'questoes/logs/contatos_log.txt';
$log_dir = dirname($log_file);

// Criar diretório de logs se não existir
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
    // Criar .htaccess para proteger a pasta
    file_put_contents($log_dir . '/.htaccess', "Deny from all\n");
}

// Salvar log com hash do email (não expor dados sensíveis)
$email_hash = substr(md5($email), 0, 8);
$log_entry = date('Y-m-d H:i:s') . " | " . $email_hash . " | " . $assunto_formatado . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// Configurar mensagem de sucesso
$_SESSION['mensagem_contato'] = "Obrigado pelo seu contato, $nome! Recebemos sua mensagem sobre '$assunto_formatado' e responderemos em breve.";
$_SESSION['mensagem_tipo'] = 'success';

// Redirecionar de volta para a página de contato
header('Location: contato.php');
exit;
?>
