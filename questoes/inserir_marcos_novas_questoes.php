<?php
require_once 'conexao.php';

echo "<h2>🎯 Inserção das Questões - Marcos do Desenvolvimento Infantil</h2>";

try {
    // Verificar se o assunto existe
    $stmt = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = 'MARCOS DO DESENVOLVIMENTO INFANTIL'");
    $stmt->execute();
    $assunto = $stmt->fetch();
    
    if (!$assunto) {
        echo "<p style='color: red;'>❌ Assunto 'MARCOS DO DESENVOLVIMENTO INFANTIL' não encontrado!</p>";
        exit;
    }
    
    $id_assunto = $assunto['id_assunto'];
    echo "<p style='color: green;'>✅ Assunto encontrado - ID: {$id_assunto}</p>";
    
    // Limpar questões existentes do assunto para evitar duplicações
    $stmt = $pdo->prepare("DELETE FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $questoes_removidas = $stmt->rowCount();
    echo "<p style='color: orange;'>🗑️ Questões removidas: {$questoes_removidas}</p>";
    
    // Array com as questões
    $questoes = [
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência em T.O.)</strong><br><br>Um bebê de 6 meses é capaz de sentar com apoio, rolar de bruços para as costas e levar objetos à boca com as duas mãos. De acordo com os marcos do desenvolvimento, qual habilidade motora fina seria a próxima a se desenvolver de forma típica?',
            'alternativa_a' => 'Pinça superior (preensão com ponta de polegar e indicador).',
            'alternativa_b' => 'Transferência de objetos de uma mão para a outra.',
            'alternativa_c' => 'Empilhar blocos de forma coordenada.',
            'alternativa_d' => 'Segurar o próprio corpo na posição de cócoras.',
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso para prefeitura)</strong><br><br>Com que idade é esperado que uma criança demonstre a capacidade de caminhar de forma autônoma, sem necessidade de apoio?',
            'alternativa_a' => '10 meses.',
            'alternativa_b' => '12 meses.',
            'alternativa_c' => '18 meses.',
            'alternativa_d' => '24 meses.',
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência multiprofissional)</strong><br><br>Em relação aos marcos da linguagem, qual das seguintes habilidades é a última a se desenvolver em uma sequência típica?',
            'alternativa_a' => 'Balbuciar (repetição de sons como \'ba-ba\' ou \'ma-ma\').',
            'alternativa_b' => 'Compreender o próprio nome.',
            'alternativa_c' => 'Formular frases com duas palavras.',
            'alternativa_d' => 'Responder a gestos como \'tchau\'.',
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso de T.O.)</strong><br><br>Um terapeuta ocupacional avalia uma criança de 9 meses. A mãe relata que o bebê prefere se arrastar no chão do que engatinhar. Qual das seguintes afirmações seria a mais apropriada para o profissional?',
            'alternativa_a' => 'A criança está com um atraso significativo no desenvolvimento motor, pois já deveria estar engatinhando.',
            'alternativa_b' => 'O terapeuta ocupacional deve intervir imediatamente para corrigir a forma de locomoção da criança.',
            'alternativa_c' => 'O arrastar é uma forma de locomoção típica, e a criança está explorando seu ambiente de maneira esperada para a idade.',
            'alternativa_d' => 'A criança tem uma fraqueza muscular no tronco, que a impede de adotar a posição de engatinhar.',
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência em T.O.)</strong><br><br>Considerando os marcos do desenvolvimento social, com qual idade uma criança geralmente demonstra o medo de estranhos e a ansiedade de separação?',
            'alternativa_a' => '2-4 meses.',
            'alternativa_b' => '6-9 meses.',
            'alternativa_c' => '12-18 meses.',
            'alternativa_d' => '2-3 anos.',
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso para T.O.)</strong><br><br>Um marco cognitivo importante para uma criança de 2 anos é a capacidade de:',
            'alternativa_a' => 'Compreender e seguir instruções de dois passos (\'pegue o sapato e coloque na caixa\').',
            'alternativa_b' => 'Copiar um círculo ou uma cruz com um lápis.',
            'alternativa_c' => 'Nomear pelo menos 10 cores.',
            'alternativa_d' => 'Reconhecer e nomear todas as letras do alfabeto.',
            'resposta_correta' => 'A'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência multiprofissional)</strong><br><br>O desenvolvimento do \'brincar funcional\' (usar objetos de acordo com sua função, como dirigir um carrinho) é um marco típico que surge em qual faixa etária?',
            'alternativa_a' => '4-6 meses.',
            'alternativa_b' => '9-12 meses.',
            'alternativa_c' => '18-24 meses.',
            'alternativa_d' => '3-4 anos.',
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso para prefeitura)</strong><br><br>Qual das seguintes habilidades é a última a ser esperada no desenvolvimento da coordenação motora grossa de um pré-escolar (4-5 anos)?',
            'alternativa_a' => 'Andar de bicicleta com rodinhas.',
            'alternativa_b' => 'Pular em um pé só.',
            'alternativa_c' => 'Pular com os dois pés juntos.',
            'alternativa_d' => 'Correr sem cair.',
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência em T.O.)</strong><br><br>Um terapeuta ocupacional é solicitado a avaliar a preensão de um bebê de 7 meses. Qual tipo de preensão é a mais esperada para essa idade?',
            'alternativa_a' => 'Preensão em pinça inferior (com a lateral do polegar e o dedo indicador).',
            'alternativa_b' => 'Preensão em pinça superior (com a ponta do polegar e o dedo indicador).',
            'alternativa_c' => 'Preensão radial-palmar (segurar o objeto com os dedos e a base do polegar).',
            'alternativa_d' => 'Preensão palmar reflexa (segurar o dedo do adulto ao ser estimulado).',
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso de T.O.)</strong><br><br>Em relação aos marcos da alimentação, com que idade é esperado que uma criança consiga beber de um copo aberto, com derramamento mínimo?',
            'alternativa_a' => '6-9 meses.',
            'alternativa_b' => '12-18 meses.',
            'alternativa_c' => '18-24 meses.',
            'alternativa_d' => '3-4 anos.',
            'resposta_correta' => 'C'
        ]
    ];
    
    // Inserir as questões
    $stmt = $pdo->prepare("INSERT INTO questoes (id_assunto, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $questoes_inseridas = 0;
    foreach ($questoes as $index => $questao) {
        $stmt->execute([
            $id_assunto,
            $questao['enunciado'],
            $questao['alternativa_a'],
            $questao['alternativa_b'],
            $questao['alternativa_c'],
            $questao['alternativa_d'],
            $questao['resposta_correta']
        ]);
        $questoes_inseridas++;
        $id_questao = $pdo->lastInsertId();
        echo "<p style='color: green;'>✅ Questão " . ($index + 1) . " inserida - ID: {$id_questao}</p>";
    }
    
    echo "<h3 style='color: green;'>🎉 Inserção concluída!</h3>";
    echo "<p><strong>Total de questões inseridas:</strong> {$questoes_inseridas}</p>";
    
    // Verificar total de questões do assunto
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $total = $stmt->fetch()['total'];
    echo "<p><strong>Total de questões no assunto:</strong> {$total}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📋 Gerenciar Questões</a>
    <a href="teste_sistema.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Testar Sistema</a>
</div>