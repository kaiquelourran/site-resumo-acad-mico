<?php
require_once 'conexao.php';

// Buscar uma questão de teste
$id_questao = 92;
$stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
$stmt->execute([$id_questao]);
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if ($questao) {
    // Buscar alternativas
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$id_questao]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    // Embaralhar
    $seed = $id_questao + (int)date('Ymd');
    srand($seed);
    shuffle($alternativas_questao);
    
    $letras = ['A', 'B', 'C', 'D', 'E'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste HTML</title>
    <style>
        .question-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
        }
        .alternative {
            background: #f5f5f5;
            border: 1px solid #ccc;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
        }
        .alternative:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <h1>Teste de Estrutura HTML</h1>
    
    <div class="question-card" id="questao-<?php echo $questao['id_questao']; ?>">
        <h3>Questão #<?php echo $questao['id_questao']; ?></h3>
        <p><?php echo htmlspecialchars($questao['enunciado']); ?></p>
        
        <div class="alternatives-container">
            <?php foreach ($alternativas_questao as $index => $alternativa): ?>
                <?php $letra = $letras[$index] ?? ($index + 1); ?>
                <div class="alternative" 
                     data-alternativa="<?php echo $letra; ?>"
                     data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
                     data-questao-id="<?php echo $questao['id_questao']; ?>">
                    <div class="alternative-letter"><?php echo $letra; ?></div>
                    <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== TESTE DE ESTRUTURA HTML ===');
            
            // Testar seletor global
            const todasAlternativas = document.querySelectorAll('.alternative');
            console.log('Todas as alternativas encontradas:', todasAlternativas.length);
            
            // Testar seletor por questão
            const questoes = document.querySelectorAll('.question-card');
            console.log('Questões encontradas:', questoes.length);
            
            questoes.forEach((questao, questaoIndex) => {
                const alternativas = questao.querySelectorAll('.alternative');
                console.log('Questão', questaoIndex, 'tem', alternativas.length, 'alternativas');
                
                alternativas.forEach((alt, index) => {
                    console.log('  Alternativa', index, ':', {
                        letra: alt.dataset.alternativa,
                        id: alt.dataset.alternativaId,
                        questaoId: alt.dataset.questaoId
                    });
                    
                    alt.addEventListener('click', function() {
                        console.log('CLIQUE na questão', questaoIndex, 'alternativa', index, 'letra', this.dataset.alternativa);
                    });
                });
            });
        });
    </script>
</body>
</html>
<?php
} else {
    echo "Questão não encontrada!";
}
?>

