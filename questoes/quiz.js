document.addEventListener('DOMContentLoaded', () => {
    // Referências aos elementos da página
    const quizForm = document.querySelector('.alternativas-list');
    
    // Verifica se os elementos existem antes de continuar
    if (!quizForm) {
        console.log('Elementos do quiz não encontrados na página. Script não será executado.');
        return;
    }
    
    const proximaQuestaoBtn = document.getElementById('botao-proxima');
    const placarPontosSpan = document.getElementById('placar-pontos');
    const feedbackMensagemDiv = document.getElementById('feedback-mensagem');
    const totalQuestoesSpan = document.getElementById('total-questoes');
    const questaoAtualSpan = document.getElementById('questao-atual');

    // Mapeia o ID da alternativa para saber qual é a correta
    const alternativas = quizForm.querySelectorAll('label');
    let alternativaCorretaId;
    alternativas.forEach(alt => {
        if (alt.dataset.correta === 'true') {
            alternativaCorretaId = alt.dataset.idAlternativa;
        }
    });

    // Função para exibir feedback na tela
    function exibirFeedback(mensagem, isCorreta) {
        feedbackMensagemDiv.textContent = mensagem;
        if (isCorreta) {
            feedbackMensagemDiv.style.color = 'green';
        } else {
            feedbackMensagemDiv.style.color = 'red';
        }
        feedbackMensagemDiv.style.display = 'block';

        // Esconde a mensagem após 3 segundos
        setTimeout(() => {
            feedbackMensagemDiv.style.display = 'none';
        }, 3000);
    }

    // Função para desativar as alternativas após a resposta
    function desativarAlternativas() {
        alternativas.forEach(alt => {
            alt.classList.add('desativada');
            alt.querySelector('input').disabled = true;
        });
    }

    // Evento de clique para processar a resposta
    quizForm.addEventListener('click', (event) => {
        if (event.target.tagName === 'INPUT') {
            const alternativaSelecionada = event.target.closest('label');
            const respostaId = alternativaSelecionada.dataset.idAlternativa;
            const idQuestao = quizForm.dataset.idQuestao;
            
            // Verifica se a resposta está correta
            const respostaCorreta = respostaId === alternativaCorretaId;

            // Envia a resposta para o servidor
            fetch('processar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_questao=${idQuestao}&id_alternativa=${respostaId}&acertou=${respostaCorreta ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                // Atualiza a pontuação na tela com base na resposta do servidor
                if (data.sucesso) {
                    placarPontosSpan.textContent = data.acertos;
                    
                    // Atualiza o número da questão atual
                    const questaoAtualNum = parseInt(questaoAtualSpan.textContent);
                    const totalQuestoes = parseInt(totalQuestoesSpan.textContent);
                    
                    if (questaoAtualNum < totalQuestoes) {
                        questaoAtualSpan.textContent = questaoAtualNum + 1;
                    }
                    
                    if (respostaCorreta) {
                        exibirFeedback("Correto! 😄", true);
                    } else {
                        exibirFeedback("Incorreto! 😥", false);
                    }
                } else {
                    console.error('Erro ao salvar resposta:', data.erro);
                    exibirFeedback("Erro ao salvar a resposta.", false);
                }
            })
            .catch(error => {
                console.error('Erro de requisição:', error);
                exibirFeedback("Erro de conexão.", false);
            });

            // Desativa as alternativas e mostra o botão "Próxima Questão"
            desativarAlternativas();
            proximaQuestaoBtn.style.display = 'block';
        }
    });
    
    // Evento para o botão de próxima questão
    proximaQuestaoBtn.addEventListener('click', () => {
        // Verifica se veio de uma questão específica (da lista de questões)
        const urlParams = new URLSearchParams(window.location.search);
        const questaoEspecifica = urlParams.get('questao');
        const idAssunto = urlParams.get('id');
        
        if (questaoEspecifica && idAssunto) {
            // Se veio de uma questão específica, volta para a lista de questões
            // mantendo o filtro ativo
            const filtroAtivo = localStorage.getItem('filtro_ativo') || 'nao-respondidas';
            window.location.href = `listar_questoes.php?id=${idAssunto}&filtro=${filtroAtivo}`;
        } else {
            // Comportamento normal do quiz (recarregar para próxima questão aleatória)
            window.location.reload();
        }
    });
});