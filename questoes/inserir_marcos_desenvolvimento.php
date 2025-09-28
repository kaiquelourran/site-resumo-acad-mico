<?php
require_once 'conexao.php';

echo "<h2>📚 Inserindo Questões: MARCOS DO DESENVOLVIMENTO INFANTIL</h2>";

try {
    // Primeiro, verificar se o assunto existe
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

    // Array com as 10 questões
    $questoes = [
        [
            'enunciado' => 'Qual é a idade típica para uma criança começar a andar independentemente?',
            'alternativas' => [
                '6-8 meses',
                '9-12 meses', 
                '12-18 meses',
                '18-24 meses',
                '24-30 meses'
            ],
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => 'Em que idade a maioria das crianças consegue dizer suas primeiras palavras com significado?',
            'alternativas' => [
                '6-8 meses',
                '9-12 meses',
                '12-15 meses',
                '15-18 meses',
                '18-24 meses'
            ],
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => 'Qual marco do desenvolvimento é esperado aos 6 meses de idade?',
            'alternativas' => [
                'Andar sem apoio',
                'Sentar sem apoio',
                'Falar frases de duas palavras',
                'Controlar esfíncteres',
                'Desenhar círculos'
            ],
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => 'Aos 2 anos de idade, uma criança tipicamente deve ser capaz de:',
            'alternativas' => [
                'Amarrar os sapatos',
                'Escrever o próprio nome',
                'Subir e descer escadas alternando os pés',
                'Formar frases de 2-3 palavras',
                'Ler palavras simples'
            ],
            'resposta_correta' => 'D'
        ],
        [
            'enunciado' => 'O reflexo de Moro (reflexo do susto) normalmente desaparece em qual idade?',
            'alternativas' => [
                '2-3 meses',
                '4-6 meses',
                '6-8 meses',
                '8-10 meses',
                '10-12 meses'
            ],
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => 'Qual habilidade social é típica dos 12 meses de idade?',
            'alternativas' => [
                'Brincar cooperativamente com outras crianças',
                'Compartilhar brinquedos voluntariamente',
                'Balbuciar (repetição de sons como \'ba-ba\' ou \'ma-ma\')',
                'Responder a gestos como \'tchau\'',
                'Seguir regras simples de jogos'
            ],
            'resposta_correta' => 'D'
        ],
        [
            'enunciado' => 'A capacidade de empilhar 2-3 blocos é esperada em qual faixa etária?',
            'alternativas' => [
                '9-12 meses',
                '12-18 meses',
                '18-24 meses',
                '24-30 meses',
                '30-36 meses'
            ],
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => 'Aos 3 anos, uma criança deve ser capaz de:',
            'alternativas' => [
                'Escrever letras do alfabeto',
                'Contar até 100',
                'Pedalar um triciclo',
                'Ler frases simples',
                'Amarrar cadarços'
            ],
            'resposta_correta' => 'C'
        ],
        [
            'enunciado' => 'O desenvolvimento da linguagem aos 18 meses inclui:',
            'alternativas' => [
                'Vocabulário de 5-10 palavras',
                'Vocabulário de 50-100 palavras',
                'Formar frases completas',
                'Compreender e seguir instruções de dois passos (\'pegue o sapato e coloque na caixa\')',
                'Contar histórias detalhadas'
            ],
            'resposta_correta' => 'B'
        ],
        [
            'enunciado' => 'Qual marco do desenvolvimento cognitivo é característico dos 24 meses?',
            'alternativas' => [
                'Resolver quebra-cabeças de 100 peças',
                'Entender conceitos abstratos',
                'Início do brincar funcional (usar objetos para sua função real)',
                'Realizar operações matemáticas simples',
                'Compreender conceitos de tempo complexos'
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
                $letra = chr(65 + $alt_index); // A, B, C, D, E
                $correta = ($letra == $questao['resposta_correta']) ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO alternativas (id_questao, texto, correta) VALUES (?, ?, ?)");
                $stmt->execute([$questao_id, $alternativa, $correta]);
            }
            
            $questoes_inseridas++;
            echo "<p>✅ Questão " . ($index + 1) . " inserida com sucesso (ID: $questao_id)</p>";
            
        } catch (Exception $e) {
            echo "<p>❌ Erro na questão " . ($index + 1) . ": " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Processo Concluído!</h3>";
    echo "<p><strong>Questões inseridas com sucesso:</strong> $questoes_inseridas de " . count($questoes) . "</p>";
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