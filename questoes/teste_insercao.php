<?php
require_once 'conexao.php';

// Função para inserir uma questão
function inserirQuestao($assunto, $enunciado, $alternativas, $resposta_correta) {
    global $pdo;
    
    try {
        // Verificar se o assunto existe
        $stmt = $pdo->prepare("SELECT id FROM assuntos WHERE nome = ?");
        $stmt->execute([$assunto]);
        $assunto_id = $stmt->fetchColumn();
        
        if (!$assunto_id) {
            // Criar o assunto se não existir
            $stmt = $pdo->prepare("INSERT INTO assuntos (nome, descricao) VALUES (?, ?)");
            $stmt->execute([$assunto, 'Assunto criado automaticamente']);
            $assunto_id = $pdo->lastInsertId();
            echo "Assunto '$assunto' criado com ID: $assunto_id<br>";
        } else {
            echo "Assunto '$assunto' encontrado com ID: $assunto_id<br>";
        }
        
        // Inserir a questão
        $stmt = $pdo->prepare("INSERT INTO questoes (assunto_id, enunciado, data_criacao) VALUES (?, ?, NOW())");
        $stmt->execute([$assunto_id, $enunciado]);
        $questao_id = $pdo->lastInsertId();
        echo "Questão inserida com ID: $questao_id<br>";
        
        // Inserir as alternativas
        foreach ($alternativas as $index => $alternativa) {
            $letra = chr(65 + $index); // A, B, C, D, E
            $stmt = $pdo->prepare("INSERT INTO alternativas (questao_id, letra, texto) VALUES (?, ?, ?)");
            $stmt->execute([$questao_id, $letra, $alternativa]);
            echo "Alternativa $letra inserida<br>";
        }
        
        // Marcar a resposta correta
        $stmt = $pdo->prepare("UPDATE questoes SET resposta_correta = ? WHERE id = ?");
        $stmt->execute([$resposta_correta, $questao_id]);
        echo "Resposta correta marcada: $resposta_correta<br><br>";
        
        return true;
    } catch (Exception $e) {
        echo "Erro ao inserir questão: " . $e->getMessage() . "<br><br>";
        return false;
    }
}

// Teste com uma questão simples
echo "<h2>Teste de Inserção de Questão</h2>";

$resultado = inserirQuestao(
    "MARCOS DO DESENVOLVIMENTO INFANTIL",
    "Qual é a idade típica para uma criança começar a andar independentemente?",
    [
        "6-8 meses",
        "9-12 meses", 
        "12-18 meses",
        "18-24 meses",
        "24-30 meses"
    ],
    "C"
);

if ($resultado) {
    echo "<p style='color: green;'>✅ Questão inserida com sucesso!</p>";
} else {
    echo "<p style='color: red;'>❌ Erro na inserção da questão!</p>";
}

// Verificar quantas questões existem agora
try {
    $stmt = $pdo->prepare("
        SELECT a.nome, COUNT(q.id) as total_questoes 
        FROM assuntos a 
        LEFT JOIN questoes q ON a.id = q.assunto_id 
        WHERE a.nome = 'MARCOS DO DESENVOLVIMENTO INFANTIL'
        GROUP BY a.id, a.nome
    ");
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado) {
        echo "<h3>Status do Assunto:</h3>";
        echo "Assunto: " . $resultado['nome'] . "<br>";
        echo "Total de questões: " . $resultado['total_questoes'] . "<br>";
    }
} catch (Exception $e) {
    echo "Erro ao verificar questões: " . $e->getMessage();
}
?>

<br><br>
<a href="gerenciar_questoes_sem_auth.php">📋 Gerenciar Questões</a>