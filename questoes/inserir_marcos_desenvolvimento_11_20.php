<?php
require_once 'conexao.php';

echo "<h2>📚 Inserindo Questões 11-20: MARCOS DO DESENVOLVIMENTO INFANTIL</h2>";

try {
    // Verificar se o assunto existe
    $stmt = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = ?");
    $stmt->execute(['MARCOS DO DESENVOLVIMENTO INFANTIL']);
    $assunto_id = $stmt->fetchColumn();
    
    if (!$assunto_id) {
        // Criar o assunto se não existir
        $stmt = $pdo->prepare("INSERT INTO assuntos (nome, descricao) VALUES (?, ?)");
        $stmt->execute(['MARCOS DO DESENVOLVIMENTO INFANTIL', 'Questões sobre marcos do desenvolvimento infantil']);
        $assunto_id = $pdo->lastInsertId();
        echo "<p>✅ Assunto criado com ID: $assunto_id</p>";
    } else {
        echo "<p>✅ Assunto encontrado com ID: $assunto_id</p>";
    }

    // Array com as questões 11-20
    $questoes = [
        [
            'enunciado' => 'Um bebê de 10 meses demonstra o \'olhar de referência social\', buscando a face do cuidador para verificar a reação dele antes de se aproximar de um objeto desconhecido. Qual das seguintes afirmações melhor descreve este comportamento?',
            'alternativas' => [
                'É um sinal de medo e de um possível atraso no desenvolvimento social da criança.',
                'É um reflexo arcaico de sobrevivência que tende a desaparecer após os 12 meses de idade.',
                'Indica a incapacidade da criança de tomar decisões autônomas, precisando sempre da aprovação do cuidador.',
                'É um marco do desenvolvimento social e emocional, demonstrando que a criança está formando vínculos e usando as emoções do cuidador como guia.'
            ],
            'resposta_correta' => 'D'
        ],
        [
            'enunciado' => 'Em qual idade um bebê é tipicamente capaz de rolar da posição de costas para a de bruços?',
            'alternativas' => [
                '2 meses',
                '4 meses',
                '6 meses',
                '8 meses'
            ],
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => 'Qual das seguintes características é esperada no brincar de uma criança de 3 anos?',
            'alternativas' => [
                'Brincar predominantemente exploratório (levar objetos à boca).',
                'Brincar de faz-de-conta complexo, com papéis definidos (médico e paciente).',
                'Brincar solitário, ignorando outras crianças.',
                'Brincar em grupo, compartilhando e negociando papéis.'
            ],
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => 'Um bebê de 4 meses demonstra qual dos seguintes reflexos primitivos que ainda não desapareceram?',
            'alternativas' => [
                'Reflexo de Moro.',
                'Reflexo de preensão palmar.',
                'Reflexo de busca.',
                'Nenhuma das alternativas, todos já deveriam ter desaparecido.'
            ],
            'resposta_correta' => 'A'
        ],
        [
            'enunciado' => 'A capacidade de um terapeuta ocupacional é solicitada para um bebê de 12 meses. Qual é o marco motor esperado na locomoção dessa idade?',
            'alternativas' => [
                'Engatinhar de forma coordenada.',
                'Caminhar com ajuda, segurando-se em móveis.',
                'Correr de forma independente.',
                'Subir escadas sem apoio.'
            ],
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => 'Qual das seguintes habilidades de autonomia é esperada de uma criança de 4 anos?',
            'alternativas' => [
                'Vestir-se completamente, incluindo fechos e botões.',
                'Amarrar os próprios cadarços.',
                'Usar talheres para cortar alimentos.',
                'Escovar os dentes sem supervisão.'
            ],
            'resposta_correta' => 'A'
        ],
        [
            'enunciado' => 'O \'brincar paralelo\', onde a criança brinca ao lado de outras crianças, mas sem interação direta, é típico de qual faixa etária?',
            'alternativas' => [
                '12-18 meses',
                '2-3 anos',
                '4-5 anos',
                '6-7 anos'
            ],
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => 'A capacidade de construir uma torre de 6 blocos é um marco motor fino esperado para qual idade?',
            'alternativas' => [
                '12 meses',
                '18 meses',
                '24 meses',
                '36 meses'
            ],
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => 'Qual marco da linguagem receptiva é esperado de um bebê de 9 meses?',
            'alternativas' => [
                'Apontar para objetos nomeados.',
                'Compreender o significado de frases curtas.',
                'Seguir instruções simples de um passo (como \'dê-me o brinquedo\').',
                'Responder a gestos como \'tchau\' e \'não\'.'
            ],
            'resposta_correta' => 'D'
        ],
        [
            'enunciado' => 'Um terapeuta ocupacional avalia o desempenho de uma criança de 5 anos para atividades de vida diária. Qual das seguintes habilidades é a mais esperada para essa idade?',
            'alternativas' => [
                'Amarrar os cadarços de forma independente.',
                'Cortar alimentos macios com faca e garfo.',
                'Limpar a si mesma após ir ao banheiro, com supervisão mínima.',
                'Escovar os dentes de forma totalmente autônoma.'
            ],
            'resposta_correta' => 'C'
        ]
    ];

    $questoes_inseridas = 0;
    
    foreach ($questoes as $index => $questao) {
        try {
            // Inserir a questão
            $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, id_assunto) VALUES (?, ?)");
            $stmt->execute([$questao['enunciado'], $assunto_id]);
            $questao_id = $pdo->lastInsertId();
            
            // Inserir as alternativas
            foreach ($questao['alternativas'] as $alt_index => $alternativa) {
                $letra = chr(65 + $alt_index); // A, B, C, D
                $correta = ($letra == $questao['resposta_correta']) ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO alternativas (id_questao, texto, correta) VALUES (?, ?, ?)");
                $stmt->execute([$questao_id, $alternativa, $correta]);
            }
            
            $questoes_inseridas++;
            echo "<p>✅ Questão " . ($index + 11) . " inserida com sucesso (ID: $questao_id)</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Erro na questão " . ($index + 11) . ": " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Processo Concluído!</h3>";
    echo "<p><strong>Questões 11-20 inseridas com sucesso:</strong> $questoes_inseridas de " . count($questoes) . "</p>";
    echo "</div>";
    
    // Verificar o resultado final
    $stmt = $pdo->prepare("
        SELECT a.nome, COUNT(q.id_questao) as total_questoes 
        FROM assuntos a 
        LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
        WHERE a.nome = 'MARCOS DO DESENVOLVIMENTO INFANTIL'
        GROUP BY a.id_assunto, a.nome
    ");
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>📊 Status Final do Assunto</h3>";
        echo "<p><strong>Assunto:</strong> " . $resultado['nome'] . "</p>";
        echo "<p><strong>Total de questões:</strong> " . $resultado['total_questoes'] . "</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro geral: " . $e->getMessage() . "</p>";
}
?>

<div style="text-align: center; margin: 30px 0;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">📋 Gerenciar Questões</a>
    <a href="quiz_sem_login.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px;">🎮 Testar Questões</a>
</div>