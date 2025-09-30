<?php
require_once 'conexao.php';

echo "<h2>Verificação de Questões - Assunto 8</h2>";

// Verificar questões não respondidas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes q LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao WHERE q.id_assunto = 8 AND r.id_questao IS NULL");
$stmt->execute();
$nao_respondidas = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Questões não respondidas:</strong> " . $nao_respondidas['total'] . "</p>";

// Verificar questões respondidas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes q INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao WHERE q.id_assunto = 8");
$stmt->execute();
$respondidas = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Questões respondidas:</strong> " . $respondidas['total'] . "</p>";

// Verificar questões acertadas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes q INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao WHERE q.id_assunto = 8 AND r.acertou = 1");
$stmt->execute();
$acertadas = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Questões acertadas:</strong> " . $acertadas['total'] . "</p>";

// Verificar questões erradas
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes q INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao WHERE q.id_assunto = 8 AND r.acertou = 0");
$stmt->execute();
$erradas = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Questões erradas:</strong> " . $erradas['total'] . "</p>";

// Total de questões
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = 8");
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<p><strong>Total de questões:</strong> " . $total['total'] . "</p>";

echo "<hr>";
echo "<h3>Links para testar:</h3>";
echo "<ul>";
echo "<li><a href='quiz_vertical_filtros.php?id=8&filtro=todas'>Todas as questões</a></li>";
echo "<li><a href='quiz_vertical_filtros.php?id=8&filtro=nao-respondidas'>Questões não respondidas</a></li>";
echo "<li><a href='quiz_vertical_filtros.php?id=8&filtro=respondidas'>Questões respondidas</a></li>";
echo "<li><a href='quiz_vertical_filtros.php?id=8&filtro=acertadas'>Questões acertadas</a></li>";
echo "<li><a href='quiz_vertical_filtros.php?id=8&filtro=erradas'>Questões erradas</a></li>";
echo "</ul>";
?>