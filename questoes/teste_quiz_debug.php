<?php
session_start();
require_once 'conexao.php';

// Parâmetros de teste
$id_assunto = 1;
$filtro_ativo = 'todas';

// Buscar uma questão para teste
$sql = "SELECT * FROM questoes WHERE id_assunto = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_assunto]);
$questao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$questao) {
    die("Nenhuma questão encontrada para teste");
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Quiz Debug</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .alternativa { 
            padding: 10px; 
            margin: 5px 0; 
            border: 2px solid #ccc; 
            cursor: pointer; 
            border-radius: 5px;
        }
        .alternativa:hover { background: #f0f0f0; }
        .alternativa.selecionada { 
            background: #007bff; 
            color: white; 
            border-color: #007bff;
        }
        .btn-submit { 
            padding: 10px 20px; 
            background: #28a745; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
        }
        .btn-submit:disabled { 
            background: #ccc; 
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <h1>Teste de Funcionalidade - Quiz</h1>
    
    <div class="questao-card">
        <h3>Questão de Teste</h3>
        <p><?php echo htmlspecialchars($questao['enunciado']); ?></p>
        
        <form method="POST" class="form-questao" data-questao-id="<?php echo $questao['id_questao']; ?>">
            <input type="hidden" name="id_questao" value="<?php echo $questao['id_questao']; ?>">
            <input type="hidden" name="alternativa_selecionada" class="alternativa-selecionada">
            
            <div class="alternativas-container">
                <?php 
                $letras = ['A', 'B', 'C', 'D'];
                foreach ($letras as $letra): 
                    $campo_alternativa = 'alternativa_' . strtolower($letra);
                    if (!empty($questao[$campo_alternativa])):
                ?>
                    <div class="alternativa" data-letra="<?php echo $letra; ?>">
                        <strong><?php echo $letra; ?>)</strong> 
                        <?php echo htmlspecialchars($questao[$campo_alternativa]); ?>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            
            <button type="submit" class="btn-submit" disabled>
                📝 Responder
            </button>
        </form>
        
        <div id="resultado"></div>
    </div>

    <script>
        console.log('Script carregado');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado');
            
            const form = document.querySelector('.form-questao');
            const alternativas = form.querySelectorAll('.alternativa');
            const inputSelecionada = form.querySelector('.alternativa-selecionada');
            const btnSubmit = form.querySelector('.btn-submit');
            
            console.log('Elementos encontrados:', {
                form: !!form,
                alternativas: alternativas.length,
                inputSelecionada: !!inputSelecionada,
                btnSubmit: !!btnSubmit
            });
            
            alternativas.forEach((alternativa, index) => {
                console.log(`Adicionando listener para alternativa ${index}`);
                
                alternativa.addEventListener('click', function() {
                    console.log('Alternativa clicada:', this.dataset.letra);
                    
                    // Remover seleção anterior
                    alternativas.forEach(alt => alt.classList.remove('selecionada'));
                    
                    // Selecionar atual
                    this.classList.add('selecionada');
                    
                    // Atualizar input hidden
                    const letra = this.dataset.letra;
                    inputSelecionada.value = letra;
                    
                    console.log('Input atualizado:', inputSelecionada.value);
                    
                    // Habilitar botão
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        console.log('Botão habilitado');
                    }
                });
            });

            // Envio do formulário
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('Formulário enviado');
                
                const formData = new FormData(this);
                console.log('Dados do formulário:', {
                    id_questao: formData.get('id_questao'),
                    alternativa_selecionada: formData.get('alternativa_selecionada')
                });
                
                btnSubmit.disabled = true;
                btnSubmit.textContent = '⏳ Enviando...';
                
                fetch('debug_processar_resposta.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Resposta recebida:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    document.getElementById('resultado').innerHTML = `
                        <h4>Resultado:</h4>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('resultado').innerHTML = `
                        <h4>Erro:</h4>
                        <p style="color: red;">${error.message}</p>
                    `;
                });
            });
        });
    </script>
</body>
</html>