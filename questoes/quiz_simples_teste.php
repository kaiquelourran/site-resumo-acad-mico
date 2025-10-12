<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Parâmetros
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$filtro_ativo = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

// Buscar questões
$sql = "SELECT * FROM questoes WHERE id_assunto = ? LIMIT 3";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_assunto]);
$questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Simples - Teste</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .questao-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .questao-texto {
            font-size: 16px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .alternativas-container {
            margin-bottom: 20px;
        }
        
        .alternativa {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            border: 2px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .alternativa:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .alternativa.selecionada {
            border-color: #007bff;
            background: #007bff;
            color: white;
        }
        
        .letra-alternativa {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .alternativa.selecionada .letra-alternativa {
            background: rgba(255,255,255,0.3);
        }
        
        .btn-submit {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-submit:not(:disabled):hover {
            background: #218838;
        }
        
        .feedback {
            margin-top: 15px;
            padding: 15px;
            border-radius: 6px;
        }
        
        .feedback.sucesso {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .feedback.erro {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .debug-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Quiz Simples - Teste de Funcionalidade</h1>
    
    <?php if (empty($questoes)): ?>
        <p>Nenhuma questão encontrada.</p>
    <?php else: ?>
        <?php foreach ($questoes as $index => $questao): ?>
            <div class="questao-card">
                <h3>Questão <?php echo $index + 1; ?></h3>
                <div class="questao-texto">
                    <?php echo nl2br(htmlspecialchars($questao['enunciado'])); ?>
                </div>
                
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
                                <div class="letra-alternativa"><?php echo $letra; ?></div>
                                <div class="texto-alternativa">
                                    <?php echo htmlspecialchars($questao[$campo_alternativa]); ?>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    
                    <button type="submit" class="btn-submit" disabled>
                        📝 Responder
                    </button>
                    
                    <div class="debug-info">
                        <strong>Debug:</strong> Questão ID: <?php echo $questao['id_questao']; ?> | 
                        Resposta Correta: <?php echo $questao['resposta_correta']; ?>
                    </div>
                </form>
                
                <div class="resultado-container"></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        console.log('Script iniciado');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM carregado');
            
            const forms = document.querySelectorAll('.form-questao');
            console.log('Formulários encontrados:', forms.length);
            
            forms.forEach((form, formIndex) => {
                console.log(`Configurando formulário ${formIndex}`);
                
                const alternativas = form.querySelectorAll('.alternativa');
                const inputSelecionada = form.querySelector('.alternativa-selecionada');
                const btnSubmit = form.querySelector('.btn-submit');
                const resultadoContainer = form.parentElement.querySelector('.resultado-container');
                
                console.log(`Formulário ${formIndex}:`, {
                    alternativas: alternativas.length,
                    inputSelecionada: !!inputSelecionada,
                    btnSubmit: !!btnSubmit,
                    resultadoContainer: !!resultadoContainer
                });
                
                alternativas.forEach((alternativa, altIndex) => {
                    alternativa.addEventListener('click', function() {
                        console.log(`Alternativa ${altIndex} clicada:`, this.dataset.letra);
                        
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
                    console.log('Dados:', {
                        id_questao: formData.get('id_questao'),
                        alternativa_selecionada: formData.get('alternativa_selecionada')
                    });
                    
                    btnSubmit.disabled = true;
                    btnSubmit.textContent = '⏳ Enviando...';
                    
                    fetch('processar_resposta.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Status da resposta:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Dados recebidos:', data);
                        
                        if (data.success) {
                            const feedbackClass = data.acertou ? 'sucesso' : 'erro';
                            const feedbackText = data.acertou ? '🎉 Correto!' : '❌ Incorreto!';
                            
                            resultadoContainer.innerHTML = `
                                <div class="feedback ${feedbackClass}">
                                    <h4>${feedbackText}</h4>
                                    <p><strong>Resposta correta:</strong> ${data.resposta_correta}</p>
                                    <p><strong>Sua resposta:</strong> ${data.alternativa_selecionada}</p>
                                    ${data.explicacao ? `<p><strong>Explicação:</strong> ${data.explicacao}</p>` : ''}
                                </div>
                            `;
                            
                            // Marcar alternativas
                            alternativas.forEach(alt => {
                                const letra = alt.dataset.letra;
                                if (letra === data.resposta_correta) {
                                    alt.style.background = '#28a745';
                                    alt.style.color = 'white';
                                    alt.style.borderColor = '#28a745';
                                } else if (letra === data.alternativa_selecionada && !data.acertou) {
                                    alt.style.background = '#dc3545';
                                    alt.style.color = 'white';
                                    alt.style.borderColor = '#dc3545';
                                }
                                alt.style.pointerEvents = 'none';
                            });
                            
                        } else {
                            resultadoContainer.innerHTML = `
                                <div class="feedback erro">
                                    <h4>❌ Erro ao processar resposta</h4>
                                    <p>Tente novamente.</p>
                                </div>
                            `;
                            btnSubmit.disabled = false;
                            btnSubmit.textContent = '📝 Responder';
                        }
                    })
                    .catch(error => {
                        console.error('Erro:', error);
                        resultadoContainer.innerHTML = `
                            <div class="feedback erro">
                                <h4>❌ Erro de conexão</h4>
                                <p>${error.message}</p>
                            </div>
                        `;
                        btnSubmit.disabled = false;
                        btnSubmit.textContent = '📝 Responder';
                    });
                });
            });
        });
    </script>
</body>
</html>