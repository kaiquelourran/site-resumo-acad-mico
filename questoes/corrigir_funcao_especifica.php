<?php
// Script para corrigir função específica

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "Corrigindo função específica...\n";

// Substituir a função enviarResposta problemática
$funcao_antiga = '        function enviarResposta(questaoId, comentarioPaiId, form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.id_questao = questaoId;
            data.id_comentario_pai = comentarioPaiId;
            data.nome_usuario = \'Usuario Anonimo\'; // Pode ser obtido de um campo oculto';

$funcao_nova = '        function enviarResposta(questaoId, comentarioPaiId, form) {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.id_questao = questaoId;
            data.id_comentario_pai = comentarioPaiId;
            data.nome_usuario = "Usuario Anonimo"; // Pode ser obtido de um campo oculto';

$conteudo = str_replace($funcao_antiga, $funcao_nova, $conteudo);

// Salvar arquivo
file_put_contents($arquivo, $conteudo);

echo "Função corrigida!\n";
echo "Teste agora o quiz!\n";
?>
