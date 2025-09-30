<?php
require_once __DIR__ . '/conexao.php';

echo "<h2>Verificação de Questões Não Respondidas</h2>";

// Verificar questões não respondidas no assunto 8
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes q LEFT JOIN respostas_usuario ru ON q.id_questao = ru.id_questao WHERE q.id_assunto = 8 AND ru.id IS NULL");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Questões não respondidas no assunto 8:</strong> {$result['total']}</p>";

// Verificar total de questões no assunto 8
$stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = 8");
$stmt_total->execute();
$total_questoes = $stmt_total->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Total de questões no assunto 8:</strong> {$total_questoes['total']}</p>";

// Verificar questões respondidas
$stmt_resp = $pdo->prepare("SELECT COUNT(*) as total FROM questoes q INNER JOIN respostas_usuario ru ON q.id_questao = ru.id_questao WHERE q.id_assunto = 8");
$stmt_resp->execute();
$respondidas = $stmt_resp->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Questões respondidas no assunto 8:</strong> {$respondidas['total']}</p>";

// Listar algumas questões não respondidas
echo "<h3>Algumas questões não respondidas:</h3>";
$stmt_list = $pdo->prepare("SELECT q.id_questao, LEFT(q.enunciado, 100) as enunciado_resumo FROM questoes q LEFT JOIN respostas_usuario ru ON q.id_questao = ru.id_questao WHERE q.id_assunto = 8 AND ru.id IS NULL LIMIT 5");
$stmt_list->execute();
$questoes_nao_respondidas = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

if (count($questoes_nao_respondidas) > 0) {
    foreach ($questoes_nao_respondidas as $questao) {
        echo "<p><strong>ID {$questao['id_questao']}:</strong> " . htmlspecialchars($questao['enunciado_resumo']) . "...</p>";
    }
} else {
    echo "<p>Todas as questões do assunto 8 já foram respondidas!</p>";
    echo "<h3>Solução: Limpar algumas respostas</h3>";
    echo "<p>Para testar o clique, vamos limpar algumas respostas da tabela respostas_usuario.</p>";
}
?>