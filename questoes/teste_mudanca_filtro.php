<?php
session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>TESTE DE MUDANÇA DE FILTRO</h1>";

// Simular usuário logado
$_SESSION['id_usuario'] = 1;

echo "<h2>1. Testando lógica de mudança de filtro:</h2>";

// Simular diferentes cenários
$cenarios = [
    ['filtro_atual' => 'certas', 'acertou' => false, 'filtro_esperado' => 'erradas'],
    ['filtro_atual' => 'erradas', 'acertou' => true, 'filtro_esperado' => 'certas'],
    ['filtro_atual' => 'certas', 'acertou' => true, 'filtro_esperado' => 'certas'],
    ['filtro_atual' => 'erradas', 'acertou' => false, 'filtro_esperado' => 'erradas'],
    ['filtro_atual' => 'todas', 'acertou' => true, 'filtro_esperado' => 'todas'],
    ['filtro_atual' => 'todas', 'acertou' => false, 'filtro_esperado' => 'todas'],
];

foreach ($cenarios as $i => $cenario) {
    echo "<h3>Cenário " . ($i + 1) . ":</h3>";
    echo "<p>Filtro atual: " . $cenario['filtro_atual'] . "</p>";
    echo "<p>Acertou: " . ($cenario['acertou'] ? 'SIM' : 'NÃO') . "</p>";
    
    // Aplicar a mesma lógica do código
    $filtro_ativo = $cenario['filtro_atual'];
    $acertou = $cenario['acertou'];
    
    $novo_filtro = $filtro_ativo;
    $mudou_filtro = false;
    
    if ($acertou) {
        // Se acertou e estava em filtro de erradas, mover para acertadas
        if ($filtro_ativo === 'erradas') {
            $novo_filtro = 'certas';
            $mudou_filtro = true;
        }
    } else {
        // Se errou e estava em filtro de acertadas, mover para erradas
        if ($filtro_ativo === 'certas') {
            $novo_filtro = 'erradas';
            $mudou_filtro = true;
        }
    }
    
    echo "<p>Novo filtro: " . $novo_filtro . "</p>";
    echo "<p>Mudou filtro: " . ($mudou_filtro ? 'SIM' : 'NÃO') . "</p>";
    echo "<p>Esperado: " . $cenario['filtro_esperado'] . "</p>";
    
    $correto = ($novo_filtro === $cenario['filtro_esperado']);
    echo "<p style='color: " . ($correto ? 'green' : 'red') . ";'>" . 
         ($correto ? '✅ CORRETO' : '❌ INCORRETO') . "</p>";
    echo "<hr>";
}

echo "<h2>2. Testando com dados reais do banco:</h2>";

try {
    // Buscar uma questão para testar
    $stmt = $pdo->prepare("SELECT * FROM questoes LIMIT 1");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<p>Questão de teste: ID " . $questao['id_questao'] . "</p>";
        echo "<p>Enunciado: " . htmlspecialchars(substr($questao['enunciado'], 0, 100)) . "...</p>";
        
        // Simular resposta incorreta
        echo "<h3>Simulando resposta incorreta:</h3>";
        
        // Simular POST
        $_POST['id_questao'] = $questao['id_questao'];
        $_POST['alternativa_selecionada'] = 'A';
        $_POST['ajax_request'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $id_assunto = 1;
        $filtro_ativo = 'certas'; // Começando no filtro de certas
        
        // Processar resposta (simplificado)
        $id_questao = (int)$_POST['id_questao'];
        $alternativa_selecionada = $_POST['alternativa_selecionada'];
        
        // Buscar alternativas
        $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
        $stmt_alt->execute([$id_questao]);
        $alternativas_questao = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
        
        // Embaralhar
        $seed = $id_questao + (int)date('Ymd');
        srand($seed);
        shuffle($alternativas_questao);
        
        // Mapear letra para ID
        $letras = ['A', 'B', 'C', 'D', 'E'];
        $id_alternativa = null;
        foreach ($alternativas_questao as $index => $alternativa) {
            $letra = $letras[$index] ?? ($index + 1);
            if ($letra === strtoupper($alternativa_selecionada)) {
                $id_alternativa = $alternativa['id_alternativa'];
                break;
            }
        }
        
        // Encontrar alternativa correta
        $alternativa_correta = null;
        foreach ($alternativas_questao as $alt) {
            if ($alt['eh_correta'] == 1) {
                $alternativa_correta = $alt;
                break;
            }
        }
        
        if ($alternativa_correta && $id_alternativa) {
            $acertou = ($id_alternativa == $alternativa_correta['id_alternativa']) ? 1 : 0;
            
            echo "<p>ID selecionado: $id_alternativa</p>";
            echo "<p>ID correto: " . $alternativa_correta['id_alternativa'] . "</p>";
            echo "<p>Acertou: " . ($acertou ? 'SIM' : 'NÃO') . "</p>";
            
            // Aplicar lógica de mudança de filtro
            $novo_filtro = $filtro_ativo;
            $mudou_filtro = false;
            
            if ($acertou) {
                if ($filtro_ativo === 'erradas') {
                    $novo_filtro = 'certas';
                    $mudou_filtro = true;
                }
            } else {
                if ($filtro_ativo === 'certas') {
                    $novo_filtro = 'erradas';
                    $mudou_filtro = true;
                }
            }
            
            echo "<h4>Resultado da lógica de filtro:</h4>";
            echo "<p>Filtro atual: $filtro_ativo</p>";
            echo "<p>Novo filtro: $novo_filtro</p>";
            echo "<p>Mudou filtro: " . ($mudou_filtro ? 'SIM' : 'NÃO') . "</p>";
            
            if ($mudou_filtro) {
                $url_redirecionamento = "quiz_vertical_filtros.php?id=$id_assunto&filtro=$novo_filtro";
                echo "<p>URL de redirecionamento: <a href='$url_redirecionamento' target='_blank'>$url_redirecionamento</a></p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Próximos passos:</h2>";
echo "<p>1. Teste o quiz_vertical_filtros.php</p>";
echo "<p>2. Responda uma questão incorretamente no filtro 'certas'</p>";
echo "<p>3. Deve aparecer uma mensagem e redirecionar para 'erradas'</p>";
echo "<p>4. Responda uma questão corretamente no filtro 'erradas'</p>";
echo "<p>5. Deve aparecer uma mensagem e redirecionar para 'certas'</p>";
?>
