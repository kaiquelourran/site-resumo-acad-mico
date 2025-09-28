<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>Teste Simples de Resposta</h1>";

// Simular uma resposta
$id_questao = 76;
$id_alternativa_selecionada = 322; // Esta é a alternativa correta

// Encontra a alternativa correta no banco de dados
$stmt_correta = $pdo->prepare("SELECT id_alternativa FROM alternativas WHERE id_questao = ? AND correta = 1");
$stmt_correta->execute([$id_questao]);
$alternativa_correta = $stmt_correta->fetch(PDO::FETCH_ASSOC);

echo "<p>Questão: $id_questao</p>";
echo "<p>Alternativa selecionada: $id_alternativa_selecionada</p>";
echo "<p>Alternativa correta no banco: " . ($alternativa_correta ? $alternativa_correta['id_alternativa'] : 'Não encontrada') . "</p>";

$resposta_correta = false;
if ($alternativa_correta && $alternativa_correta['id_alternativa'] === $id_alternativa_selecionada) {
    $resposta_correta = true;
}

echo "<p>Resposta está correta: " . ($resposta_correta ? 'SIM' : 'NÃO') . "</p>";

try {
    // Salvar resposta na tabela de tracking
    $stmt_tracking = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou) 
                                   VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE 
                                   id_alternativa = VALUES(id_alternativa), 
                                   acertou = VALUES(acertou), 
                                   data_resposta = CURRENT_TIMESTAMP");
    $stmt_tracking->execute([$id_questao, $id_alternativa_selecionada, $resposta_correta ? 1 : 0]);
    
    echo "<p>✅ Resposta salva com sucesso!</p>";
    
    // Verificar se foi salva
    $stmt_verificar = $pdo->prepare("SELECT * FROM respostas_usuario WHERE id_questao = ?");
    $stmt_verificar->execute([$id_questao]);
    $resultado = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        echo "<h3>Dados salvos:</h3>";
        echo "<pre>" . print_r($resultado, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao salvar: " . $e->getMessage() . "</p>";
}
?>