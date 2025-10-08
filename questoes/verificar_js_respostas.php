<?php
// Cabeçalho HTML
echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação do JavaScript</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .code-block { background: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin: 15px 0; overflow: auto; }
        pre { margin: 0; }
        .test-button { background: #4CAF50; color: white; border: none; padding: 10px 15px; cursor: pointer; margin: 10px 0; }
        .result-area { background: #e9f7ef; border: 1px solid #ddd; padding: 15px; margin: 15px 0; min-height: 100px; }
        h2 { margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Verificação do JavaScript de Processamento de Respostas</h1>
    
    <h2>1. Simulação de Resposta AJAX</h2>
    <div class="code-block">
        <pre>
// Simulação de resposta AJAX
const mockResponse = {
    success: true,
    acertou: true,
    alternativa_correta: "A",
    message: "Parabéns! Você acertou!"
};

// Função para processar resposta
function processarResposta(response) {
    console.log("Processando resposta:", response);
    
    if (response.success) {
        // Mostrar feedback
        const resultado = document.getElementById("resultado");
        resultado.innerHTML = `
            <p><strong>Status:</strong> ${response.success ? "Sucesso" : "Erro"}</p>
            <p><strong>Acertou:</strong> ${response.acertou ? "Sim" : "Não"}</p>
            <p><strong>Alternativa correta:</strong> ${response.alternativa_correta}</p>
            <p><strong>Mensagem:</strong> ${response.message}</p>
        `;
        
        // Simular redirecionamento após 1.5 segundos
        setTimeout(() => {
            resultado.innerHTML += `<p><em>Redirecionando em 1.5 segundos...</em></p>`;
        }, 500);
    } else {
        document.getElementById("resultado").innerHTML = `<p>Erro: ${response.message}</p>`;
    }
}
        </pre>
    </div>
    
    <button id="testar-resposta" class="test-button">Testar Processamento de Resposta</button>
    <div id="resultado" class="result-area"></div>
    
    <h2>2. Simulação de Clique em Alternativa</h2>
    <div class="code-block">
        <pre>
// Criar uma questão de exemplo
function criarQuestaoExemplo() {
    const container = document.getElementById("questao-container");
    container.innerHTML = `
        <div class="question-card" id="questao-123" data-respondida="false">
            <div class="question-header">Questão 123</div>
            <div class="question-content">
                <p>Qual é a capital do Brasil?</p>
                <div class="alternatives">
                    <div class="alternative" data-alternativa="A" data-questao-id="123">A) Brasília</div>
                    <div class="alternative" data-alternativa="B" data-questao-id="123">B) Rio de Janeiro</div>
                    <div class="alternative" data-alternativa="C" data-questao-id="123">C) São Paulo</div>
                    <div class="alternative" data-alternativa="D" data-questao-id="123">D) Salvador</div>
                </div>
            </div>
        </div>
    `;
    
    // Adicionar event listeners
    const alternativas = document.querySelectorAll(".alternative");
    alternativas.forEach(alternativa => {
        alternativa.addEventListener("click", function() {
            const questaoId = this.dataset.questaoId;
            const alternativaSelecionada = this.dataset.alternativa;
            const questaoCard = this.closest(".question-card");
            
            console.log("Clique detectado:");
            console.log("- Questão ID:", questaoId);
            console.log("- Alternativa selecionada:", alternativaSelecionada);
            
            // Verificar se já foi respondida
            if (questaoCard.dataset.respondida === "true") {
                console.log("Questão já respondida, ignorando...");
                return;
            }
            
            // Marcar como respondida
            questaoCard.dataset.respondida = "true";
            
            // Desabilitar todas as alternativas
            alternativas.forEach(alt => {
                alt.style.pointerEvents = "none";
                alt.style.cursor = "default";
            });
            
            // Simular resposta do servidor
            setTimeout(() => {
                const resposta = {
                    success: true,
                    acertou: alternativaSelecionada === "A",
                    alternativa_correta: "A",
                    message: alternativaSelecionada === "A" ? 
                        "Parabéns! Você acertou!" : 
                        "Não foi dessa vez, mas continue tentando!"
                };
                
                // Processar resposta
                processarRespostaQuestao(resposta, questaoId, alternativaSelecionada, questaoCard);
            }, 500);
        });
    });
}

// Função para processar resposta de questão
function processarRespostaQuestao(resposta, questaoId, alternativaSelecionada, questaoCard) {
    console.log("Processando resposta da questão:", resposta);
    
    const alternativas = questaoCard.querySelectorAll(".alternative");
    
    if (resposta.success) {
        // Marcar alternativa correta
        const alternativaCorreta = questaoCard.querySelector(`[data-alternativa="${resposta.alternativa_correta}"]`);
        if (alternativaCorreta) {
            alternativaCorreta.classList.add("alternative-correct");
        }
        
        // Marcar alternativa selecionada se for incorreta
        if (!resposta.acertou) {
            const alternativaSelecionadaEl = questaoCard.querySelector(`[data-alternativa="${alternativaSelecionada}"]`);
            if (alternativaSelecionadaEl) {
                alternativaSelecionadaEl.classList.add("alternative-incorrect-chosen");
            }
        }
        
        // Mostrar mensagem
        const mensagem = document.createElement("div");
        mensagem.className = "mensagem-sucesso";
        mensagem.style.position = "fixed";
        mensagem.style.top = "20px";
        mensagem.style.left = "50%";
        mensagem.style.transform = "translateX(-50%)";
        mensagem.style.background = resposta.acertou ? "#4CAF50" : "#F44336";
        mensagem.style.color = "white";
        mensagem.style.padding = "10px 20px";
        mensagem.style.borderRadius = "5px";
        mensagem.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
        mensagem.style.zIndex = "1000";
        mensagem.innerHTML = resposta.acertou ? 
            "✅ Resposta correta! Atualizando filtros..." : 
            "❌ Resposta incorreta! Atualizando filtros...";
        document.body.appendChild(mensagem);
        
        // Simular redirecionamento
        document.getElementById("resultado-questao").innerHTML = `
            <p><strong>Status:</strong> ${resposta.success ? "Sucesso" : "Erro"}</p>
            <p><strong>Acertou:</strong> ${resposta.acertou ? "Sim" : "Não"}</p>
            <p><strong>Alternativa correta:</strong> ${resposta.alternativa_correta}</p>
            <p><strong>Mensagem:</strong> ${resposta.message}</p>
            <p><em>Redirecionando em 1.5 segundos...</em></p>
        `;
    }
}
        </pre>
    </div>
    
    <button id="criar-questao" class="test-button">Criar Questão de Exemplo</button>
    <div id="questao-container"></div>
    <div id="resultado-questao" class="result-area"></div>
    
    <h2>3. Verificação do Redirecionamento</h2>
    <div class="code-block">
        <pre>
// Código atual de redirecionamento
setTimeout(() => {
    window.location.reload();
}, 1500);

// Alternativa: redirecionamento para filtro específico
function redirecionarParaFiltro(acertou) {
    const filtroAtual = "certas"; // Exemplo: filtro atual
    const novoFiltro = acertou ? "certas" : "erradas";
    
    // Se o filtro atual for diferente do novo filtro, redirecionar
    if (filtroAtual !== novoFiltro) {
        const url = new URL(window.location.href);
        url.searchParams.set("filtro", novoFiltro);
        
        setTimeout(() => {
            window.location.href = url.toString();
        }, 1500);
    } else {
        // Apenas recarregar a página
        setTimeout(() => {
            window.location.reload();
        }, 1500);
    }
}
        </pre>
    </div>
    
    <button id="testar-redirecionamento-certo" class="test-button">Testar Redirecionamento (Acertou)</button>
    <button id="testar-redirecionamento-errado" class="test-button">Testar Redirecionamento (Errou)</button>
    <div id="resultado-redirecionamento" class="result-area"></div>
    
    <script>
        // Implementação das funções de teste
        document.getElementById("testar-resposta").addEventListener("click", function() {
            const mockResponse = {
                success: true,
                acertou: true,
                alternativa_correta: "A",
                message: "Parabéns! Você acertou!"
            };
            
            processarResposta(mockResponse);
        });
        
        document.getElementById("criar-questao").addEventListener("click", function() {
            criarQuestaoExemplo();
        });
        
        document.getElementById("testar-redirecionamento-certo").addEventListener("click", function() {
            const resultado = document.getElementById("resultado-redirecionamento");
            resultado.innerHTML = "<p>Simulando redirecionamento para filtro 'certas'...</p>";
            
            // Mostrar URL atual
            const urlAtual = new URL(window.location.href);
            resultado.innerHTML += `<p>URL atual: ${urlAtual.toString()}</p>`;
            
            // Mostrar URL de destino
            const urlDestino = new URL(window.location.href);
            urlDestino.searchParams.set("filtro", "certas");
            resultado.innerHTML += `<p>URL de destino: ${urlDestino.toString()}</p>`;
            
            // Simular redirecionamento
            resultado.innerHTML += "<p><em>Redirecionamento simulado em 1.5 segundos...</em></p>";
        });
        
        document.getElementById("testar-redirecionamento-errado").addEventListener("click", function() {
            const resultado = document.getElementById("resultado-redirecionamento");
            resultado.innerHTML = "<p>Simulando redirecionamento para filtro 'erradas'...</p>";
            
            // Mostrar URL atual
            const urlAtual = new URL(window.location.href);
            resultado.innerHTML += `<p>URL atual: ${urlAtual.toString()}</p>`;
            
            // Mostrar URL de destino
            const urlDestino = new URL(window.location.href);
            urlDestino.searchParams.set("filtro", "erradas");
            resultado.innerHTML += `<p>URL de destino: ${urlDestino.toString()}</p>`;
            
            // Simular redirecionamento
            resultado.innerHTML += "<p><em>Redirecionamento simulado em 1.5 segundos...</em></p>";
        });
        
        // Implementação das funções simuladas
        function processarResposta(response) {
            console.log("Processando resposta:", response);
            
            if (response.success) {
                // Mostrar feedback
                const resultado = document.getElementById("resultado");
                resultado.innerHTML = `
                    <p><strong>Status:</strong> ${response.success ? "Sucesso" : "Erro"}</p>
                    <p><strong>Acertou:</strong> ${response.acertou ? "Sim" : "Não"}</p>
                    <p><strong>Alternativa correta:</strong> ${response.alternativa_correta}</p>
                    <p><strong>Mensagem:</strong> ${response.message}</p>
                `;
                
                // Simular redirecionamento após 1.5 segundos
                setTimeout(() => {
                    resultado.innerHTML += `<p><em>Redirecionando em 1.5 segundos...</em></p>`;
                }, 500);
            } else {
                document.getElementById("resultado").innerHTML = `<p>Erro: ${response.message}</p>`;
            }
        }
        
        function criarQuestaoExemplo() {
            const container = document.getElementById("questao-container");
            container.innerHTML = `
                <div class="question-card" id="questao-123" data-respondida="false">
                    <div class="question-header">Questão 123</div>
                    <div class="question-content">
                        <p>Qual é a capital do Brasil?</p>
                        <div class="alternatives">
                            <div class="alternative" data-alternativa="A" data-questao-id="123">A) Brasília</div>
                            <div class="alternative" data-alternativa="B" data-questao-id="123">B) Rio de Janeiro</div>
                            <div class="alternative" data-alternativa="C" data-questao-id="123">C) São Paulo</div>
                            <div class="alternative" data-alternativa="D" data-questao-id="123">D) Salvador</div>
                        </div>
                    </div>
                </div>
            `;
            
            // Adicionar event listeners
            const alternativas = document.querySelectorAll(".alternative");
            alternativas.forEach(alternativa => {
                alternativa.addEventListener("click", function() {
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest(".question-card");
                    
                    console.log("Clique detectado:");
                    console.log("- Questão ID:", questaoId);
                    console.log("- Alternativa selecionada:", alternativaSelecionada);
                    
                    // Verificar se já foi respondida
                    if (questaoCard.dataset.respondida === "true") {
                        console.log("Questão já respondida, ignorando...");
                        return;
                    }
                    
                    // Marcar como respondida
                    questaoCard.dataset.respondida = "true";
                    
                    // Desabilitar todas as alternativas
                    alternativas.forEach(alt => {
                        alt.style.pointerEvents = "none";
                        alt.style.cursor = "default";
                    });
                    
                    // Simular resposta do servidor
                    setTimeout(() => {
                        const resposta = {
                            success: true,
                            acertou: alternativaSelecionada === "A",
                            alternativa_correta: "A",
                            message: alternativaSelecionada === "A" ? 
                                "Parabéns! Você acertou!" : 
                                "Não foi dessa vez, mas continue tentando!"
                        };
                        
                        // Processar resposta
                        processarRespostaQuestao(resposta, questaoId, alternativaSelecionada, questaoCard);
                    }, 500);
                });
            });
        }
        
        function processarRespostaQuestao(resposta, questaoId, alternativaSelecionada, questaoCard) {
            console.log("Processando resposta da questão:", resposta);
            
            const alternativas = questaoCard.querySelectorAll(".alternative");
            
            if (resposta.success) {
                // Marcar alternativa correta
                const alternativaCorreta = questaoCard.querySelector(`[data-alternativa="${resposta.alternativa_correta}"]`);
                if (alternativaCorreta) {
                    alternativaCorreta.classList.add("alternative-correct");
                    alternativaCorreta.style.background = "#d4edda";
                    alternativaCorreta.style.borderColor = "#c3e6cb";
                }
                
                // Marcar alternativa selecionada se for incorreta
                if (!resposta.acertou) {
                    const alternativaSelecionadaEl = questaoCard.querySelector(`[data-alternativa="${alternativaSelecionada}"]`);
                    if (alternativaSelecionadaEl) {
                        alternativaSelecionadaEl.classList.add("alternative-incorrect-chosen");
                        alternativaSelecionadaEl.style.background = "#f8d7da";
                        alternativaSelecionadaEl.style.borderColor = "#f5c6cb";
                    }
                }
                
                // Mostrar mensagem
                const mensagem = document.createElement("div");
                mensagem.className = "mensagem-sucesso";
                mensagem.style.position = "fixed";
                mensagem.style.top = "20px";
                mensagem.style.left = "50%";
                mensagem.style.transform = "translateX(-50%)";
                mensagem.style.background = resposta.acertou ? "#4CAF50" : "#F44336";
                mensagem.style.color = "white";
                mensagem.style.padding = "10px 20px";
                mensagem.style.borderRadius = "5px";
                mensagem.style.boxShadow = "0 2px 5px rgba(0,0,0,0.2)";
                mensagem.style.zIndex = "1000";
                mensagem.innerHTML = resposta.acertou ? 
                    "✅ Resposta correta! Atualizando filtros..." : 
                    "❌ Resposta incorreta! Atualizando filtros...";
                document.body.appendChild(mensagem);
                
                // Simular redirecionamento
                document.getElementById("resultado-questao").innerHTML = `
                    <p><strong>Status:</strong> ${resposta.success ? "Sucesso" : "Erro"}</p>
                    <p><strong>Acertou:</strong> ${resposta.acertou ? "Sim" : "Não"}</p>
                    <p><strong>Alternativa correta:</strong> ${resposta.alternativa_correta}</p>
                    <p><strong>Mensagem:</strong> ${resposta.message}</p>
                    <p><em>Redirecionando em 1.5 segundos...</em></p>
                `;
            }
        }
    </script>
</body>
</html>';
?>