<?php
session_start();

// Resetar o seed de sessão
unset($_SESSION['quiz_seed']);

echo "<h1>Seed de sessão resetado!</h1>";
echo "<p>O embaralhamento será regenerado na próxima vez que você acessar o quiz.</p>";
echo "<p><a href='quiz_vertical_filtros.php?id=8&filtro=todas'>Voltar para o quiz</a></p>";
?>
