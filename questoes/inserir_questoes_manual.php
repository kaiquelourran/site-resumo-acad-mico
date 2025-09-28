<?php
// Sistema Simplificado para Inserção Manual de Questões
require_once 'conexao.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inserção Manual</title>
    <link rel="stylesheet" href="modern-style.css">
</head>
<body>
    <div class="main-container fade-in">
        <div class="header">
            <div class="logo">📝</div>
            <h1 class="title">Sistema de Inserção Manual</h1>
            <p class="subtitle">Inserção de questões via código</p>
        </div>
        
        <div class="user-info">
            <a href="gerenciar_questoes_sem_auth.php" class="user-link">📋 Gerenciar Questões</a>
            <a href="quiz_sem_login.php" class="user-link">🎮 Questões</a>
            <a href="index.php" class="user-link">🏠 Menu Principal</a>
        </div>

<?php
// Função para inserir questão
function inserirQuestao($assunto_nome, $enunciado, $alternativas, $resposta_correta, $numero = null) {
    global $pdo;
    
    try {
        // Verificar se o assunto já existe
        $stmt = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = ?");
        $stmt->execute([$assunto_nome]);
        $assunto = $stmt->fetch();
        
        if (!$assunto) {
            // Criar novo assunto
            $stmt = $pdo->prepare("INSERT INTO assuntos (nome) VALUES (?)");
            $stmt->execute([$assunto_nome]);
            $id_assunto = $pdo->lastInsertId();
            echo "<div class='alert alert-success'>✅ Novo assunto criado: <strong>$assunto_nome</strong> (ID: $id_assunto)</div>";
        } else {
            $id_assunto = $assunto['id_assunto'];
        }
        
        // Inserir questão
        $stmt = $pdo->prepare("INSERT INTO questoes (enunciado, id_assunto) VALUES (?, ?)");
        $stmt->execute([$enunciado, $id_assunto]);
        $id_questao = $pdo->lastInsertId();
        
        // Inserir alternativas
        foreach ($alternativas as $letra => $texto) {
            $eh_correta = ($letra === $resposta_correta) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO alternativas (id_questao, letra, texto, eh_correta) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_questao, $letra, $texto, $eh_correta]);
        }
        
        $numero_texto = $numero ? "Questão $numero" : "Questão";
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ $numero_texto inserida com sucesso!</h4>";
        echo "<p><strong>ID:</strong> $id_questao | <strong>Resposta correta:</strong> $resposta_correta</p>";
        echo "<p>" . substr($enunciado, 0, 100) . "...</p>";
        echo "</div>";
        
        return $id_questao;
        
    } catch (Exception $e) {
        $numero_texto = $numero ? "Questão $numero" : "Questão";
        echo "<div class='alert alert-error'>";
        echo "<h4>❌ Erro ao inserir $numero_texto</h4>";
        echo "<p>Erro: " . $e->getMessage() . "</p>";
        echo "</div>";
        return false;
    }
}

// Função para inserir múltiplas questões
function inserirMultiplasQuestoes($questoes_array) {
    $sucessos = 0;
    $total = count($questoes_array);
    
    echo "<h2 class='card-title'>🚀 Inserindo $total questões...</h2>";
    
    foreach ($questoes_array as $index => $questao) {
        $numero = $index + 1;
        if (inserirQuestao(
            $questao['assunto'], 
            $questao['enunciado'], 
            $questao['alternativas'], 
            $questao['resposta_correta'], 
            $numero
        )) {
            $sucessos++;
        }
    }
    
    echo "<div class='alert alert-success' style='text-align: center;'>";
    echo "<h2>🎉 INSERÇÃO CONCLUÍDA!</h2>";
    echo "<p style='font-size: 18px;'>$sucessos de $total questões inseridas com sucesso!</p>";
    echo "</div>";
    
    return $sucessos;
}

// Status atual do banco
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
    $total_questoes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assuntos");
    $total_assuntos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas");
    $total_alternativas = $stmt->fetch()['total'];
    
    echo "<div class='stats-container'>";
    echo "<div class='stat-card slide-in-left'>";
    echo "<div class='stat-number'>$total_questoes</div>";
    echo "<div class='stat-label'>📝 Questões</div>";
    echo "</div>";
    echo "<div class='stat-card slide-in-up'>";
    echo "<div class='stat-number'>$total_assuntos</div>";
    echo "<div class='stat-label'>📚 Assuntos</div>";
    echo "</div>";
    echo "<div class='stat-card slide-in-right'>";
    echo "<div class='stat-number'>$total_alternativas</div>";
    echo "<div class='stat-label'>📋 Alternativas</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-error'>❌ Erro ao verificar status: " . $e->getMessage() . "</div>";
}
?>

        <div class="card fade-in">
            <h2 class="card-title">💡 Como Usar Este Sistema</h2>
            <div class="card-description">
                <h3>Para inserir questões, adicione o código PHP aqui:</h3>
                <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 14px; line-height: 1.4;"><?php echo htmlspecialchars('
// Exemplo de inserção de uma questão:
$assunto = "NOME DO ASSUNTO";
$enunciado = "Texto da questão aqui...";
$alternativas = [
    "A" => "Primeira alternativa",
    "B" => "Segunda alternativa", 
    "C" => "Terceira alternativa",
    "D" => "Quarta alternativa"
];
$resposta_correta = "B"; // Letra da resposta correta

inserirQuestao($assunto, $enunciado, $alternativas, $resposta_correta);

// Questões sobre Marcos do Desenvolvimento Infantil
$questoes_marcos = [
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Um bebê de 6 meses é capaz de sentar com apoio, rolar de bruços para as costas e levar objetos à boca com as duas mãos. De acordo com os marcos do desenvolvimento, qual habilidade motora fina seria a próxima a se desenvolver de forma típica?",
        "alternativas" => [
            "A" => "Pinça superior (preensão com ponta de polegar e indicador).",
            "B" => "Transferência de objetos de uma mão para a outra.",
            "C" => "Empilhar blocos de forma coordenada.",
            "D" => "Segurar o próprio corpo na posição de cócoras."
        ],
        "resposta_correta" => "B"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Com que idade é esperado que uma criança demonstre a capacidade de caminhar de forma autônoma, sem necessidade de apoio?",
        "alternativas" => [
            "A" => "10 meses.",
            "B" => "12 meses.",
            "C" => "18 meses.",
            "D" => "24 meses."
        ],
        "resposta_correta" => "C"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Em relação aos marcos da linguagem, qual das seguintes habilidades é a última a se desenvolver em uma sequência típica?",
        "alternativas" => [
            "A" => "Balbuciar (repetição de sons como \'ba-ba\' ou \'ma-ma\').",
            "B" => "Compreender o próprio nome.",
            "C" => "Formular frases com duas palavras.",
            "D" => "Responder a gestos como \'tchau\'."
        ],
        "resposta_correta" => "C"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Um terapeuta ocupacional avalia uma criança de 9 meses. A mãe relata que o bebê prefere se arrastar no chão do que engatinhar. Qual das seguintes afirmações seria a mais apropriada para o profissional?",
        "alternativas" => [
            "A" => "A criança está com um atraso significativo no desenvolvimento motor, pois já deveria estar engatinhando.",
            "B" => "O terapeuta ocupacional deve intervir imediatamente para corrigir a forma de locomoção da criança.",
            "C" => "O arrastar é uma forma de locomoção típica, e a criança está explorando seu ambiente de maneira esperada para a idade.",
            "D" => "A criança tem uma fraqueza muscular no tronco, que a impede de adotar a posição de engatinhar."
        ],
        "resposta_correta" => "C"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Considerando os marcos do desenvolvimento social, com qual idade uma criança geralmente demonstra o medo de estranhos e a ansiedade de separação?",
        "alternativas" => [
            "A" => "2-4 meses.",
            "B" => "6-9 meses.",
            "C" => "12-18 meses.",
            "D" => "2-3 anos."
        ],
        "resposta_correta" => "B"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Um marco cognitivo importante para uma criança de 2 anos é a capacidade de:",
        "alternativas" => [
            "A" => "Compreender e seguir instruções de dois passos (\'pegue o sapato e coloque na caixa\').",
            "B" => "Copiar um círculo ou uma cruz com um lápis.",
            "C" => "Nomear pelo menos 10 cores.",
            "D" => "Reconhecer e nomear todas as letras do alfabeto."
        ],
        "resposta_correta" => "A"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "O desenvolvimento do \'brincar funcional\' (usar objetos de acordo com sua função, como dirigir um carrinho) é um marco típico que surge em qual faixa etária?",
        "alternativas" => [
            "A" => "4-6 meses.",
            "B" => "9-12 meses.",
            "C" => "18-24 meses.",
            "D" => "3-4 anos."
        ],
        "resposta_correta" => "B"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Qual das seguintes habilidades é a última a ser esperada no desenvolvimento da coordenação motora grossa de um pré-escolar (4-5 anos)?",
        "alternativas" => [
            "A" => "Andar de bicicleta com rodinhas.",
            "B" => "Pular em um pé só.",
            "C" => "Pular com os dois pés juntos.",
            "D" => "Correr sem cair."
        ],
        "resposta_correta" => "B"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Um terapeuta ocupacional é solicitado a avaliar a preensão de um bebê de 7 meses. Qual tipo de preensão é a mais esperada para essa idade?",
        "alternativas" => [
            "A" => "Preensão em pinça inferior (com a lateral do polegar e o dedo indicador).",
            "B" => "Preensão em pinça superior (com a ponta do polegar e o dedo indicador).",
            "C" => "Preensão radial-palmar (segurar o objeto com os dedos e a base do polegar).",
            "D" => "Preensão palmar reflexa (segurar o dedo do adulto ao ser estimulado)."
        ],
        "resposta_correta" => "C"
    ],
    [
        "assunto" => "MARCOS DO DESENVOLVIMENTO INFANTIL",
        "enunciado" => "Em relação aos marcos da alimentação, com que idade é esperado que uma criança consiga beber de um copo aberto, com derramamento mínimo?",
        "alternativas" => [
            "A" => "6-9 meses.",
            "B" => "12-18 meses.",
            "C" => "18-24 meses.",
            "D" => "3-4 anos."
        ],
        "resposta_correta" => "C"
    ]
];

inserirMultiplasQuestoes($questoes_marcos);
'); ?></pre>
            </div>
        </div>

        <div style="text-align: center; margin: 40px 0;">
            <a href="gerenciar_questoes_sem_auth.php" class="btn" style="margin: 10px;">📋 Gerenciar Questões</a>
            <a href="quiz_sem_login.php" class="btn" style="margin: 10px;">🎮 Fazer Questões</a>
            <a href="index.php" class="btn btn-secondary" style="margin: 10px;">🏠 Menu Principal</a>
        </div>

        <div class="alert alert-success" style="text-align: center;">
            <h2>✅ SISTEMA LIMPO E PRONTO!</h2>
            <p>Todos os arquivos do extrator de PDF foram removidos</p>
            <p>Agora você pode enviar as questões que eu insiro diretamente no código</p>
        </div>
    </div>
</body>
</html>