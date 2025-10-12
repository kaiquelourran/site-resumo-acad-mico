<?php
// Teste direto da API sem simulação
require_once 'conexao.php';

echo "🧪 TESTE DIRETO DA API\n";
echo "=====================\n\n";

// Determinar uma questão válida para o teste
$id_questao = 1;
try {
    $stmtQ = $pdo->query("SELECT id_questao FROM questoes ORDER BY id_questao DESC LIMIT 1");
    $rowQ = $stmtQ->fetch(PDO::FETCH_ASSOC);
    if ($rowQ && !empty($rowQ['id_questao'])) {
        $id_questao = (int)$rowQ['id_questao'];
    }
} catch (PDOException $e) {
    echo "⚠️ Aviso: não foi possível obter id_questao válido: " . $e->getMessage() . "\n";
}

echo "Usando id_questao = {$id_questao} para o teste.\n\n";

// Dados de teste
$dados_teste = [
    'id_questao' => $id_questao,
    'nome_usuario' => 'Usuário Teste Direto',
    'email_usuario' => 'teste@example.com',
    'comentario' => 'Este é um teste direto da API de comentários.'
];

try {
    // Inserir comentário diretamente no banco
    $stmt = $pdo->prepare(
        "\n        INSERT INTO comentarios_questoes (id_questao, nome_usuario, email_usuario, comentario, aprovado, ativo) \n        VALUES (?, ?, ?, ?, 1, 1)\n    "
    );
    
    $resultado = $stmt->execute([
        $dados_teste['id_questao'],
        $dados_teste['nome_usuario'],
        $dados_teste['email_usuario'],
        $dados_teste['comentario']
    ]);
    
    if ($resultado) {
        $lastId = $pdo->lastInsertId();
        echo "✅ Comentário inserido com sucesso!\n";
        echo "   ID: " . $lastId . "\n";
        echo "   Texto: " . substr($dados_teste['comentario'], 0, 50) . "...\n";

        // Marcar como reportado para aparecer no admin
        $stmtReport = $pdo->prepare("UPDATE comentarios_questoes SET reportado = 1 WHERE id_comentario = ?");
        if ($stmtReport->execute([$lastId])) {
            echo "   ⚑ Comentário marcado como REPORTADO (reportado = 1)\n";
        } else {
            echo "   ❌ Falha ao marcar como reportado\n";
        }
    } else {
        echo "❌ Erro ao inserir comentário\n";
    }
    
    // Verificar comentários existentes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_questoes WHERE id_questao = ?");
    $stmt->execute([$id_questao]);
    $total = $stmt->fetchColumn();
    
    echo "\n📊 Total de comentários para questão {$id_questao}: $total\n";
    
    // Mostrar últimos comentários
    $stmt = $pdo->prepare(
        "\n        SELECT nome_usuario, comentario, data_comentario \n        FROM comentarios_questoes \n        WHERE id_questao = ? \n        ORDER BY data_comentario DESC \n        LIMIT 3\n    "
    );
    $stmt->execute([$id_questao]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n💬 Últimos comentários:\n";
    foreach ($comentarios as $comentario) {
        echo "   • " . $comentario['nome_usuario'] . ": " . substr($comentario['comentario'], 0, 30) . "...\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n🔧 PRÓXIMOS PASSOS:\n";
echo "1. Abra a página de administração: /admin/gerenciar_comentarios.php\n";
echo "2. Valide que o comentário reportado aparece na lista de moderados\n";
echo "3. Teste os botões de Ativar/Desativar e Excluir Permanente\n";
echo "1. Acesse: quiz_vertical_filtros.php?id=8&filtro=todas&questao_inicial={$id_questao}\n";
echo "2. Clique no botão 'Comentários'\n";
echo "3. Tente enviar um comentário\n";
echo "4. Verifique o console do navegador (F12) para ver erros\n";
?>
