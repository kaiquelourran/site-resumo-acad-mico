<?php
require_once 'conexao.php';

echo "<h2>🎯 Inserção das Questões - Marcos do Desenvolvimento Infantil (CORRIGIDO)</h2>";

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
    // Primeiro remover alternativas das questões do assunto
    $stmt = $pdo->prepare("DELETE a FROM alternativas a 
                          INNER JOIN questoes q ON a.id_questao = q.id_questao 
                          WHERE q.id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $alternativas_removidas = $stmt->rowCount();
    echo "<p style='color: orange;'>🗑️ Alternativas removidas: {$alternativas_removidas}</p>";
    
    // Depois remover as questões
    $stmt = $pdo->prepare("DELETE FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $questoes_removidas = $stmt->rowCount();
    echo "<p style='color: orange;'>🗑️ Questões removidas: {$questoes_removidas}</p>";
    
    // Array com as questões
    $questoes = [
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência em T.O.)</strong><br><br>Um bebê de 6 meses é capaz de sentar com apoio, rolar de bruços para as costas e levar objetos à boca com as duas mãos. De acordo com os marcos do desenvolvimento, qual habilidade motora fina seria a próxima a se desenvolver de forma típica?',
            'alternativas' => [
                ['texto' => 'Pinça superior (preensão com ponta de polegar e indicador).', 'correta' => false],
                ['texto' => 'Transferência de objetos de uma mão para a outra.', 'correta' => true],
                ['texto' => 'Empilhar blocos de forma coordenada.', 'correta' => false],
                ['texto' => 'Segurar o próprio corpo na posição de cócoras.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso para prefeitura)</strong><br><br>Com que idade é esperado que uma criança demonstre a capacidade de caminhar de forma autônoma, sem necessidade de apoio?',
            'alternativas' => [
                ['texto' => '10 meses.', 'correta' => false],
                ['texto' => '12 meses.', 'correta' => false],
                ['texto' => '18 meses.', 'correta' => true],
                ['texto' => '24 meses.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência multiprofissional)</strong><br><br>Em relação aos marcos da linguagem, qual das seguintes habilidades é a última a se desenvolver em uma sequência típica?',
            'alternativas' => [
                ['texto' => 'Balbuciar (repetição de sons como \'ba-ba\' ou \'ma-ma\').', 'correta' => false],
                ['texto' => 'Compreender o próprio nome.', 'correta' => false],
                ['texto' => 'Formular frases com duas palavras.', 'correta' => true],
                ['texto' => 'Responder a gestos como \'tchau\'.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso público)</strong><br><br>Aos 2 anos de idade, uma criança típica deve ser capaz de:',
            'alternativas' => [
                ['texto' => 'Subir e descer escadas alternando os pés.', 'correta' => false],
                ['texto' => 'Correr e parar sem cair.', 'correta' => true],
                ['texto' => 'Pular com os dois pés juntos.', 'correta' => false],
                ['texto' => 'Andar de bicicleta sem rodinhas.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência em pediatria)</strong><br><br>O desenvolvimento da preensão em pinça (polegar e indicador) ocorre tipicamente em que idade?',
            'alternativas' => [
                ['texto' => '6 meses.', 'correta' => false],
                ['texto' => '9 meses.', 'correta' => true],
                ['texto' => '12 meses.', 'correta' => false],
                ['texto' => '15 meses.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso de fisioterapia)</strong><br><br>Em relação ao controle de esfíncteres, qual é a idade típica para o controle diurno da urina?',
            'alternativas' => [
                ['texto' => '18 meses.', 'correta' => false],
                ['texto' => '2-3 anos.', 'correta' => true],
                ['texto' => '4 anos.', 'correta' => false],
                ['texto' => '5 anos.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência multiprofissional)</strong><br><br>Qual marco do desenvolvimento social é esperado aos 12 meses?',
            'alternativas' => [
                ['texto' => 'Brincar cooperativamente com outras crianças.', 'correta' => false],
                ['texto' => 'Demonstrar ansiedade de separação.', 'correta' => true],
                ['texto' => 'Compartilhar brinquedos espontaneamente.', 'correta' => false],
                ['texto' => 'Seguir regras simples de jogos.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso de psicologia)</strong><br><br>O desenvolvimento da permanência do objeto, segundo Piaget, ocorre tipicamente em que período?',
            'alternativas' => [
                ['texto' => '4-6 meses.', 'correta' => false],
                ['texto' => '8-12 meses.', 'correta' => true],
                ['texto' => '15-18 meses.', 'correta' => false],
                ['texto' => '2-3 anos.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de residência em neurologia)</strong><br><br>Em relação aos reflexos primitivos, o reflexo de Moro desaparece tipicamente em que idade?',
            'alternativas' => [
                ['texto' => '2 meses.', 'correta' => false],
                ['texto' => '4-6 meses.', 'correta' => true],
                ['texto' => '8 meses.', 'correta' => false],
                ['texto' => '12 meses.', 'correta' => false]
            ]
        ],
        [
            'enunciado' => '<strong>(Fonte: adaptada de prova de concurso de T.O.)</strong><br><br>Em relação aos marcos da alimentação, com que idade é esperado que uma criança consiga beber de um copo aberto, com derramamento mínimo?',
            'alternativas' => [
                ['texto' => '6-9 meses.', 'correta' => false],
                ['texto' => '12-18 meses.', 'correta' => false],
                ['texto' => '18-24 meses.', 'correta' => true],
                ['texto' => '3-4 anos.', 'correta' => false]
            ]
        ]
    ];
    
    // Inserir as questões
    $stmt_questao = $pdo->prepare("INSERT INTO questoes (id_assunto, enunciado) VALUES (?, ?)");
    $stmt_alternativa = $pdo->prepare("INSERT INTO alternativas (id_questao, texto, correta) VALUES (?, ?, ?)");
    
    $questoes_inseridas = 0;
    foreach ($questoes as $index => $questao) {
        // Inserir a questão
        $stmt_questao->execute([$id_assunto, $questao['enunciado']]);
        $id_questao = $pdo->lastInsertId();
        
        // Inserir as alternativas
        foreach ($questao['alternativas'] as $alternativa) {
            $stmt_alternativa->execute([
                $id_questao,
                $alternativa['texto'],
                $alternativa['correta'] ? 1 : 0
            ]);
        }
        
        $questoes_inseridas++;
        echo "<p style='color: green;'>✅ Questão " . ($index + 1) . " inserida - ID: {$id_questao}</p>";
    }
    
    echo "<h3 style='color: green;'>🎉 Inserção concluída!</h3>";
    echo "<p><strong>Total de questões inseridas:</strong> {$questoes_inseridas}</p>";
    
    // Verificar total de questões do assunto
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM questoes WHERE id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $total = $stmt->fetch()['total'];
    echo "<p><strong>Total de questões no assunto:</strong> {$total}</p>";
    
    // Verificar total de alternativas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM alternativas a 
                          INNER JOIN questoes q ON a.id_questao = q.id_questao 
                          WHERE q.id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $total_alt = $stmt->fetch()['total'];
    echo "<p><strong>Total de alternativas inseridas:</strong> {$total_alt}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="gerenciar_questoes_sem_auth.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📋 Gerenciar Questões</a>
    <a href="quiz_sem_login.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">🧪 Testar Quiz</a>
</div>