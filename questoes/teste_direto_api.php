<?php
// Teste direto da API sem simulação
require_once 'conexao.php';

echo "🧪 TESTE DIRETO DA API\n";
echo "=====================\n\n";

// Dados de teste
$dados_teste = [
    'id_questao' => 92,
    'nome_usuario' => 'Usuário Teste Direto',
    'email_usuario' => 'teste@example.com',
    'comentario' => 'Este é um teste direto da API de comentários.'
];

try {
    // Inserir comentário diretamente no banco
    $stmt = $pdo->prepare("
        INSERT INTO comentarios_questoes (id_questao, nome_usuario, email_usuario, comentario, aprovado, ativo) 
        VALUES (?, ?, ?, ?, 1, 1)
    ");
    
    $resultado = $stmt->execute([
        $dados_teste['id_questao'],
        $dados_teste['nome_usuario'],
        $dados_teste['email_usuario'],
        $dados_teste['comentario']
    ]);
    
    if ($resultado) {
        echo "✅ Comentário inserido com sucesso!\n";
        echo "   ID: " . $pdo->lastInsertId() . "\n";
        echo "   Texto: " . substr($dados_teste['comentario'], 0, 50) . "...\n";
    } else {
        echo "❌ Erro ao inserir comentário\n";
    }
    
    // Verificar comentários existentes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_questoes WHERE id_questao = ?");
    $stmt->execute([92]);
    $total = $stmt->fetchColumn();
    
    echo "\n📊 Total de comentários para questão 92: $total\n";
    
    // Mostrar últimos comentários
    $stmt = $pdo->prepare("
        SELECT nome_usuario, comentario, data_comentario 
        FROM comentarios_questoes 
        WHERE id_questao = ? 
        ORDER BY data_comentario DESC 
        LIMIT 3
    ");
    $stmt->execute([92]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n💬 Últimos comentários:\n";
    foreach ($comentarios as $comentario) {
        echo "   • " . $comentario['nome_usuario'] . ": " . substr($comentario['comentario'], 0, 30) . "...\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Acesse: quiz_vertical_filtros.php?id=8&filtro=todas&questao_inicial=92\n";
echo "2. Clique no botão 'Comentários'\n";
echo "3. Tente enviar um comentário\n";
echo "4. Verifique o console do navegador (F12) para ver erros\n";
?>
