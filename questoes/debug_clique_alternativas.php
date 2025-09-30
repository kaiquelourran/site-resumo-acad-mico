<?php
require_once __DIR__ . '/conexao.php';

echo "<h2>Debug - Clique nas Alternativas</h2>";

// Buscar uma questão específica para testar
$stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = 92");
$stmt->execute();
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$questao) {
    echo "<p>Questão 92 não encontrada!</p>";
    exit;
}

// Buscar alternativas
$stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
$stmt_alt->execute([$questao['id_questao']]);
$alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);

// Verificar se há resposta do usuário
$stmt_resp = $pdo->prepare("SELECT * FROM respostas_usuario WHERE id_questao = ?");
$stmt_resp->execute([$questao['id_questao']]);
$resposta_usuario = $stmt_resp->fetch(PDO::FETCH_ASSOC);

echo "<h3>Questão de Teste (ID: {$questao['id_questao']})</h3>";
echo "<p><strong>Enunciado:</strong> " . htmlspecialchars(substr($questao['enunciado'], 0, 100)) . "...</p>";

if ($resposta_usuario) {
    echo "<p><strong>Status:</strong> JÁ RESPONDIDA (ID Alternativa: {$resposta_usuario['id_alternativa']})</p>";
} else {
    echo "<p><strong>Status:</strong> NÃO RESPONDIDA</p>";
}

echo "<h4>Alternativas:</h4>";
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .alternative {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .alternative:hover {
            background-color: #e9ecef;
            border-color: #007cba;
        }
        .alternative-letter {
            background: #007cba;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .alternative-text {
            flex: 1;
        }
        .alternativa-correta {
            background-color: #d4edda !important;
            border: 2px solid #28a745 !important;
        }
        .alternativa-incorreta {
            background-color: #f8d7da !important;
            border: 2px solid #dc3545 !important;
        }
        .debug-info {
            background: #f0f0f0;
            padding: 10px;
            margin: 5px 0;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>

<?php
$letras = ['A', 'B', 'C', 'D', 'E'];
foreach ($alternativas_questao as $index => $alternativa) {
    $letra = $letras[$index] ?? ($index + 1);
    
    // Verificar se esta alternativa foi selecionada pelo usuário
    $is_selected = ($resposta_usuario && $resposta_usuario['id_alternativa'] == $alternativa['id_alternativa']);
    $is_correct = ($alternativa['eh_correta'] == 1);
    $is_answered = !empty($resposta_usuario);
    
    $class = '';
    if ($is_answered) {
        if ($is_correct) {
            $class = 'alternativa-correta';
        } elseif ($is_selected && !$is_correct) {
            $class = 'alternativa-incorreta';
        }
    }
    
    $pointer_events = $is_answered ? 'pointer-events: none;' : '';
    ?>
    
    <div class="alternative <?php echo $class; ?>" 
         data-alternativa="<?php echo $letra; ?>"
         data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
         data-questao-id="<?php echo $questao['id_questao']; ?>"
         style="<?php echo $pointer_events; ?>">
        <div class="alternative-letter"><?php echo $letra; ?></div>
        <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
    </div>
    
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        - data-alternativa: "<?php echo $letra; ?>"<br>
        - data-alternativa-id: "<?php echo $alternativa['id_alternativa']; ?>"<br>
        - data-questao-id: "<?php echo $questao['id_questao']; ?>"<br>
        - is_answered: <?php echo $is_answered ? 'true' : 'false'; ?><br>
        - is_selected: <?php echo $is_selected ? 'true' : 'false'; ?><br>
        - is_correct: <?php echo $is_correct ? 'true' : 'false'; ?><br>
        - pointer-events: <?php echo $pointer_events ? 'none (BLOQUEADO)' : 'auto (CLICÁVEL)'; ?><br>
    </div>
    
    <?php
}
?>

<script>
console.log('Script carregado!');

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado!');
    
    const alternativas = document.querySelectorAll('.alternative');
    console.log('Alternativas encontradas:', alternativas.length);
    
    alternativas.forEach((alternativa, index) => {
        console.log(`Alternativa ${index}:`, {
            questaoId: alternativa.dataset.questaoId,
            alternativaSelecionada: alternativa.dataset.alternativa,
            alternativaId: alternativa.dataset.alternativaId,
            style: alternativa.style.pointerEvents
        });
        
        alternativa.addEventListener('click', function(e) {
            console.log('Clique detectado!', {
                questaoId: this.dataset.questaoId,
                alternativaSelecionada: this.dataset.alternativa,
                alternativaId: this.dataset.alternativaId,
                pointerEvents: this.style.pointerEvents
            });
            
            if (this.style.pointerEvents === 'none') {
                console.log('Clique bloqueado por pointer-events: none');
                return;
            }
            
            const questaoId = this.dataset.questaoId;
            const alternativaSelecionada = this.dataset.alternativa;
            
            console.log('Enviando resposta:', {questaoId, alternativaSelecionada});
            
            // Simular envio (sem realmente enviar)
            alert(`Clique funcionou!\nQuestão: ${questaoId}\nAlternativa: ${alternativaSelecionada}`);
            
            // Para teste real, descomente as linhas abaixo:
            /*
            const formData = new FormData();
            formData.append('id_questao', questaoId);
            formData.append('alternativa_selecionada', alternativaSelecionada);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Resposta enviada com sucesso');
                window.location.reload();
            })
            .catch(error => {
                console.error('Erro ao enviar resposta:', error);
            });
            */
        });
    });
});
</script>

</body>
</html>