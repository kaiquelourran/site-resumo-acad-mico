<?php
require_once 'conexao.php';

echo "<h2>🧹 Inserção Segura - Marcos do Desenvolvimento Infantil (Questões 1-10)</h2>";

try {
    // Verificar se o assunto já existe
    $stmt = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = ?");
    $stmt->execute(['MARCOS DO DESENVOLVIMENTO INFANTIL']);
    $assunto = $stmt->fetch();
    
    if (!$assunto) {
        // Criar o assunto se não existir
        $stmt = $pdo->prepare("INSERT INTO assuntos (nome) VALUES (?)");
        $stmt->execute(['MARCOS DO DESENVOLVIMENTO INFANTIL']);
        $assunto_id = $pdo->lastInsertId();
        echo "<p>✅ Assunto criado com ID: $assunto_id</p>";
    } else {
        $assunto_id = $assunto['id_assunto'];
        echo "<p>✅ Assunto encontrado com ID: $assunto_id</p>";
    }
    
    // Função para limpar texto de fontes
    function limparFontes($texto) {
        // Remove linhas que começam com "Fonte:"
        $texto = preg_replace('/\s*\(Fonte:.*?\)\s*/i', ' ', $texto);
        $texto = preg_replace('/\s*Fonte:.*$/mi', '', $texto);
        
        // Remove espaços extras e quebras de linha desnecessárias
        $texto = preg_replace('/\s+/', ' ', $texto);
        $texto = trim($texto);
        
        return $texto;
    }
    
    // Questões com fontes que serão removidas automaticamente
    $questoes = [
        [
            'pergunta' => 'Um bebê de 6 meses é capaz de sentar com apoio, rolar de bruços para as costas e levar objetos à boca com as duas mãos. De acordo com os marcos do desenvolvimento, qual habilidade motora fina seria a próxima a se desenvolver de forma típica?',
            'alternativas' => [
                'Pinça superior (preensão com ponta de polegar e indicador).',
                'Transferência de objetos de uma mão para a outra.',
                'Empilhar blocos de forma coordenada.',
                'Segurar o próprio corpo na posição de cócoras.'
            ],
            'resposta_correta' => 1 // B
        ],
        [
            'pergunta' => 'Com que idade é esperado que uma criança demonstre a capacidade de caminhar de forma autônoma, sem necessidade de apoio?',
            'alternativas' => [
                '10 meses.',
                '12 meses.',
                '18 meses.',
                '24 meses.'
            ],
            'resposta_correta' => 2 // C
        ],
        [
            'pergunta' => 'Em relação aos marcos da linguagem, qual das seguintes habilidades é a última a se desenvolver em uma sequência típica?',
            'alternativas' => [
                'Balbuciar (repetição de sons como \'ba-ba\' ou \'ma-ma\').',
                'Compreender o próprio nome.',
                'Formular frases com duas palavras.',
                'Responder a gestos como \'tchau\'.'
            ],
            'resposta_correta' => 2 // C
        ],
        [
            'pergunta' => 'Um terapeuta ocupacional avalia uma criança de 9 meses. A mãe relata que o bebê prefere se arrastar no chão do que engatinhar. Qual das seguintes afirmações seria a mais apropriada para o profissional?',
            'alternativas' => [
                'A criança está com um atraso significativo no desenvolvimento motor, pois já deveria estar engatinhando.',
                'O terapeuta ocupacional deve intervir imediatamente para corrigir a forma de locomoção da criança.',
                'O arrastar é uma forma de locomoção típica, e a criança está explorando seu ambiente de maneira esperada para a idade.',
                'A criança tem uma fraqueza muscular no tronco, que a impede de adotar a posição de engatinhar.'
            ],
            'resposta_correta' => 2 // C
        ],
        [
            'pergunta' => 'Considerando os marcos do desenvolvimento social, com qual idade uma criança geralmente demonstra o medo de estranhos e a ansiedade de separação?',
            'alternativas' => [
                '2-4 meses.',
                '6-9 meses.',
                '12-18 meses.',
                '2-3 anos.'
            ],
            'resposta_correta' => 1 // B
        ],
        [
            'pergunta' => 'Um marco cognitivo importante para uma criança de 2 anos é a capacidade de:',
            'alternativas' => [
                'Compreender e seguir instruções de dois passos (\'pegue o sapato e coloque na caixa\').',
                'Copiar um círculo ou uma cruz com um lápis.',
                'Nomear pelo menos 10 cores.',
                'Reconhecer e nomear todas as letras do alfabeto.'
            ],
            'resposta_correta' => 0 // A
        ],
        [
            'pergunta' => 'O desenvolvimento do \'brincar funcional\' (usar objetos de acordo com sua função, como dirigir um carrinho) é um marco típico que surge em qual faixa etária?',
            'alternativas' => [
                '4-6 meses.',
                '9-12 meses.',
                '18-24 meses.',
                '3-4 anos.'
            ],
            'resposta_correta' => 1 // B
        ],
        [
            'pergunta' => 'Qual das seguintes habilidades é a última a ser esperada no desenvolvimento da coordenação motora grossa de um pré-escolar (4-5 anos)?',
            'alternativas' => [
                'Andar de bicicleta com rodinhas.',
                'Pular em um pé só.',
                'Pular com os dois pés juntos.',
                'Correr sem cair.'
            ],
            'resposta_correta' => 1 // B
        ],
        [
            'pergunta' => 'Um terapeuta ocupacional é solicitado a avaliar a preensão de um bebê de 7 meses. Qual tipo de preensão é a mais esperada para essa idade?',
            'alternativas' => [
                'Preensão em pinça inferior (com a lateral do polegar e o dedo indicador).',
                'Preensão em pinça superior (com a ponta do polegar e o dedo indicador).',
                'Preensão radial-palmar (segurar o objeto com os dedos e a base do polegar).',
                'Preensão palmar reflexa (segurar o dedo do adulto ao ser estimulado).'
            ],
            'resposta_correta' => 2 // C
        ],
        [
            'pergunta' => 'Em relação aos marcos da alimentação, com que idade é esperado que uma criança consiga beber de um copo aberto, com derramamento mínimo?',
            'alternativas' => [
                '6-9 meses.',
                '12-18 meses.',
                '18-24 meses.',
                '3-4 anos.'
            ],
            'resposta_correta' => 2 // C
        ]
    ];
    
    $questoes_inseridas = 0;
    $questoes_duplicadas = 0;
    
    foreach ($questoes as $index => $questao) {
        $numero_questao = $index + 1;
        
        // Limpar a pergunta de qualquer fonte
        $pergunta_limpa = limparFontes($questao['pergunta']);
        
        // Verificar se a questão já existe (comparando os primeiros 50 caracteres da pergunta)
        $stmt = $pdo->prepare("SELECT id_questao FROM questoes WHERE id_assunto = ? AND LEFT(enunciado, 50) = LEFT(?, 50)");
        $stmt->execute([$assunto_id, $pergunta_limpa]);
        $questao_existente = $stmt->fetch();
        
        if ($questao_existente) {
            echo "<p>⚠️ Questão $numero_questao já existe (ID: {$questao_existente['id_questao']}) - PULANDO</p>";
            $questoes_duplicadas++;
            continue;
        }
        
        // Inserir a questão
        $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, id_assunto) VALUES (?, ?)");
        $stmt->execute([$pergunta_limpa, $assunto_id]);
        $questao_id = $pdo->lastInsertId();
        
        // Inserir as alternativas
        foreach ($questao['alternativas'] as $alt_index => $alternativa) {
            $alternativa_limpa = limparFontes($alternativa);
            $stmt = $pdo->prepare("INSERT INTO alternativas (id_questao, texto, correta) VALUES (?, ?, ?)");
            $stmt->execute([$questao_id, $alternativa_limpa, ($alt_index == $questao['resposta_correta']) ? 1 : 0]);
        }
        
        echo "<p>✅ Questão $numero_questao inserida com ID: $questao_id</p>";
        $questoes_inseridas++;
    }
    
    // Verificar total de questões no assunto
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$assunto_id]);
    $total = $stmt->fetch()['total'];
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📊 Resumo da Inserção:</h3>";
    echo "<p><strong>✅ Questões inseridas:</strong> $questoes_inseridas</p>";
    echo "<p><strong>⚠️ Questões duplicadas (puladas):</strong> $questoes_duplicadas</p>";
    echo "<p><strong>📝 Total de questões no assunto:</strong> $total</p>";
    echo "<p><strong>🧹 Todas as fontes foram removidas automaticamente!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📋 Gerenciar Questões</a>
    <a href="teste_sistema.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Testar Sistema</a>
</div>