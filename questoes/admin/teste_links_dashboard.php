<?php
// Arquivo de teste para verificar se todos os links do dashboard estão funcionando
echo "<h1>Teste de Links do Dashboard</h1>";

$links = [
    'Gerenciar Questões' => '../gerenciar_questoes_sem_auth.php',
    'Gerenciar Assuntos' => 'gerenciar_assuntos.php',
    'Adicionar Questão' => 'add_questao.php',
    'Adicionar Assunto' => 'add_assunto.php',
    'Voltar ao Site' => '../index.php',
    'Meu Desempenho' => '../perfil_usuario.php',
    'Sair' => '../logout.php'
];

echo "<ul>";
foreach ($links as $nome => $caminho) {
    $caminhoCompleto = __DIR__ . '/' . $caminho;
    $existe = file_exists($caminhoCompleto);
    $status = $existe ? '✅' : '❌';
    $cor = $existe ? 'green' : 'red';
    
    echo "<li style='color: $cor;'>";
    echo "$status <strong>$nome</strong>: $caminho ";
    echo $existe ? "(Arquivo existe)" : "(Arquivo NÃO existe)";
    echo "</li>";
}
echo "</ul>";

echo "<h2>Teste de Navegação</h2>";
echo "<p><a href='dashboard.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Voltar ao Dashboard</a></p>";
?>
