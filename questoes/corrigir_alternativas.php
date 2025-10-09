<?php
// Script para corrigir o sistema de alternativas

$arquivo = 'quiz_vertical_filtros.php';
$conteudo = file_get_contents($arquivo);

echo "🔧 Corrigindo sistema de alternativas...\n";

// 1. Encontrar e substituir a função de configuração das alternativas
$funcao_antiga = '// Configurar alternativas para esta questão
                const alternativas = questaoCard.querySelectorAll(\'.alternative\');
                console.log(`📊 Configurando ${alternativas.length} alternativas para questão ${questaoId}`);
                
                alternativas.forEach((alt, index) => {
                    // Verificar se já tem listener para evitar duplicação
                    if (alt.dataset.listenerAdded === \'true\') {
                        console.log(`⚠️ Alternativa ${index + 1} já tem listener, pulando...`);
                        return;
                    }
                    
                    // Limpar listeners existentes clonando o elemento
                    if (alt.hasAttribute(\'data-clicked\')) {
                        console.log(`🧹 Limpando alternativa ${index + 1}...`);
                        const novoElemento = alt.cloneNode(true);
                        alt.parentNode.replaceChild(novoElemento, alt);
                        alt = novoElemento;
                    }
                    alternativa.dataset.listenerAdded = \'true\';
                    
                    // Adicionar event listener diretamente
                    alternativa.addEventListener(\'click\', function(e) {';

$funcao_nova = '// Configurar alternativas para esta questão
                const alternativas = questaoCard.querySelectorAll(\'.alternative\');
                console.log(`📊 Configurando ${alternativas.length} alternativas para questão ${questaoId}`);
                
                alternativas.forEach((alt, index) => {
                    // Verificar se já tem listener para evitar duplicação
                    if (alt.dataset.listenerAdded === \'true\') {
                        console.log(`⚠️ Alternativa ${index + 1} já tem listener, pulando...`);
                        return;
                    }
                    
                    // Marcar como configurada
                    alt.dataset.listenerAdded = \'true\';
                    
                    // Adicionar event listener
                    alt.addEventListener(\'click\', function(e) {';

$conteudo = str_replace($funcao_antiga, $funcao_nova, $conteudo);

// 2. Simplificar a lógica de clique
$logica_antiga = 'console.log(\'🔥 CLIQUE DETECTADO!\', this);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest(\'.question-card\');
                    
                    console.log(\'Questão ID:\', questaoId);
                    console.log(\'Alternativa selecionada:\', alternativaSelecionada);
                    console.log(\'Questão card:\', questaoCard);
                    
                    // Verificar se já foi respondida
                    if (questaoCard.dataset.respondida === \'true\') {
                        console.log(\'Questão já respondida, ignorando...\');
                        return;
                    }
                    
                    // Verificar se esta alternativa já foi clicada
                    if (this.dataset.clicked === \'true\') {
                        console.log(\'Alternativa já foi clicada, ignorando...\');
                        return;
                    }
                    
                    // Verificar se já existe uma questão duplicada no DOM ANTES de processar
                    const questoesExistentes = document.querySelectorAll(\'.question-card\');
                    const questoesIds = Array.from(questoesExistentes).map(q => q.id);
                    const questaoAtualId = questaoCard.id;
                    
                    if (questoesIds.filter(id => id === questaoAtualId).length > 1) {
                        console.log(\'Questão duplicada detectada, removendo e cancelando clique...\');
                        const questoesDuplicadas = document.querySelectorAll(`#${questaoAtualId}`);
                        for (let i = 1; i < questoesDuplicadas.length; i++) {
                            questoesDuplicadas[i].remove();
                        }
                        // Executar verificação geral de duplicatas
                        verificarDuplicatas();
                        return;
                    }
                    
                    // Marcar como clicada ANTES de processar
                    this.dataset.clicked = \'true\';';

$logica_nova = 'console.log(\'🔥 CLIQUE DETECTADO!\', this);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const questaoId = this.dataset.questaoId;
                    const alternativaSelecionada = this.dataset.alternativa;
                    const questaoCard = this.closest(\'.question-card\');
                    
                    // Verificar se já foi respondida
                    if (questaoCard.dataset.respondida === \'true\') {
                        console.log(\'Questão já respondida, ignorando...\');
                        return;
                    }
                    
                    // Marcar como respondida
                    questaoCard.dataset.respondida = \'true\';';

$conteudo = str_replace($logica_antiga, $logica_nova, $conteudo);

// 3. Garantir que as alternativas sejam clicáveis
$css_antigo = '/* Garantir que as alternativas sejam clicáveis */
        .alternative {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 10;
        }';

$css_novo = '/* Garantir que as alternativas sejam clicáveis */
        .alternative {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 10;
            user-select: none;
        }
        
        .alternative:hover {
            background: #e3f2fd !important;
            border-color: #2196f3 !important;
            transform: translateY(-1px);
        }';

$conteudo = str_replace($css_antigo, $css_novo, $conteudo);

// 4. Salvar arquivo
file_put_contents($arquivo, $conteudo);

echo "✅ Sistema de alternativas corrigido!\n";
echo "🔧 Melhorias aplicadas:\n";
echo "   - Lógica de clique simplificada\n";
echo "   - Remoção de verificações desnecessárias\n";
echo "   - CSS melhorado para hover\n";
echo "   - Prevenção de duplicação de listeners\n";
echo "\n🚀 Teste agora as alternativas!\n";
?>
