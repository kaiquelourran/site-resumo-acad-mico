<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>DEBUG QUIZ VERTICAL FILTROS REAL</h1>";

// Gerar seed de sessão para embaralhamento consistente
if (!isset($_SESSION['quiz_seed'])) {
    $_SESSION['quiz_seed'] = rand(1, 10000);
}

echo "<h2>Seed de sessão: " . $_SESSION['quiz_seed'] . "</h2>";

// Simular parâmetros do quiz_vertical_filtros.php
$id_assunto = 8;
$filtro_ativo = 'todas';
$questao_inicial = 92;

echo "<h2>Testando questão #$questao_inicial:</h2>";

try {
    // Buscar questão específica
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = ?");
    $stmt->execute([$questao_inicial]);
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$questao) {
        echo "<p style='color: red;'>❌ Questão não encontrada</p>";
        exit;
    }
    
    echo "<h3>Questão encontrada: #" . $questao['id_questao'] . "</h3>";
    echo "<p>" . htmlspecialchars($questao['enunciado']) . "</p>";
    
    // Simular exatamente a lógica do quiz_vertical_filtros.php
    echo "<h3>1. Simulando exatamente a lógica do quiz_vertical_filtros.php:</h3>";
    
    // Buscar alternativas da tabela 'alternativas'
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao['id_questao']]);
    $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Alternativas ORIGINAIS (ordem do banco):</h4>";
    $letras = ['A', 'B', 'C', 'D', 'E'];
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
    // Embaralhar as alternativas para que a resposta correta apareça em posições diferentes
    // Usar seed baseado na sessão para garantir consistência e embaralhamento
    $seed = $questao['id_questao'] * 1000 + ($_SESSION['quiz_seed'] ?? 0);
    echo "<p>Seed usado: $seed</p>";
    
    srand($seed);
    shuffle($alternativas_questao);
    
    echo "<h4>Alternativas EMBARALHADAS:</h4>";
    foreach ($alternativas_questao as $index => $alt) {
        $letra = $letras[$index] ?? ($index + 1);
        $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
        echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
    }
    
    // Mapear as letras corretas após o embaralhamento
    $letra_correta = '';
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        
        // Identificar qual letra corresponde à resposta correta após embaralhamento
        if ($alternativa['eh_correta'] == 1) {
            $letra_correta = $letra;
        }
    }
    
    echo "<p><strong>Letra correta após embaralhamento: $letra_correta</strong></p>";
    
    // Simular a exibição HTML exatamente como no quiz_vertical_filtros.php
    echo "<h3>2. Simulando exibição HTML exatamente como no quiz_vertical_filtros.php:</h3>";
    
    echo "<div class='alternatives-container'>";
    foreach ($alternativas_questao as $index => $alternativa) {
        $letra = $letras[$index] ?? ($index + 1);
        
        // Identificar alternativa correta
        $is_correct = ($alternativa['eh_correta'] == 1);
        
        // Verificar se esta alternativa foi selecionada pelo usuário
        $is_selected = (!empty($questao['id_alternativa']) && $questao['id_alternativa'] == $alternativa['id_alternativa']);
        
        // Verificar se a questão foi respondida (apenas para filtros que mostram respostas)
        $is_answered = ($filtro_ativo !== 'todas' && $filtro_ativo !== 'nao-respondidas') && !empty($questao['id_alternativa']);
        
        $class = '';
        // NÃO aplicar classes visuais automaticamente - deixar para o JavaScript
        // Isso permite que o usuário clique e responda novamente
        ?>
        <div class="alternative <?php echo $class; ?>" 
             data-alternativa="<?php echo $letra; ?>"
             data-alternativa-id="<?php echo $alternativa['id_alternativa']; ?>"
             data-questao-id="<?php echo $questao['id_questao']; ?>">
            <div class="alternative-letter"><?php echo $letra; ?></div>
            <div class="alternative-text"><?php echo htmlspecialchars($alternativa['texto']); ?></div>
        </div>
        <?php
    }
    echo "</div>";
    
    // Testar múltiplos embaralhamentos
    echo "<h3>3. Testando múltiplos embaralhamentos:</h3>";
    
    for ($i = 1; $i <= 3; $i++) {
        echo "<h4>Teste $i:</h4>";
        
        // Buscar alternativas novamente
        $stmt_alt->execute([$questao['id_questao']]);
        $alt_teste = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        // Usar seed diferente para cada teste
        $seed_teste = $questao['id_questao'] * 1000 + $_SESSION['quiz_seed'] + $i;
        echo "<p>Seed usado: $seed_teste</p>";
        
        srand($seed_teste);
        shuffle($alt_teste);
        
        echo "<p>Alternativas após embaralhamento:</p>";
        foreach ($alt_teste as $index => $alt) {
            $letra = $letras[$index] ?? ($index + 1);
            $correta = $alt['eh_correta'] ? ' (CORRETA)' : '';
            echo "<p>$letra) " . htmlspecialchars($alt['texto']) . $correta . " [ID: " . $alt['id_alternativa'] . "]</p>";
        }
        
        // Encontrar letra correta
        $letra_correta_teste = '';
        foreach ($alt_teste as $index => $alt) {
            if ($alt['eh_correta'] == 1) {
                $letra_correta_teste = $letras[$index] ?? ($index + 1);
                break;
            }
        }
        
        echo "<p><strong>Letra correta: $letra_correta_teste</strong></p>";
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Próximos passos:</h2>";
echo "<p>1. Se o embaralhamento está funcionando, o problema pode estar na exibição</p>";
echo "<p>2. Se não está funcionando, preciso corrigir o código</p>";
echo "<p>3. Verificar se há problema na lógica de verificação</p>";
?>
