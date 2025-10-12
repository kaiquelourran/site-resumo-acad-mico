<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/conexao.php';

echo "<h1>🔍 DIAGNÓSTICO COMPLETO - Problema das Alternativas</h1>";

// 1. TESTAR CONEXÃO
echo "<h2>1. ✅ Teste de Conexão</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✅ Conexão com banco OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
    exit;
}

// 2. TESTAR QUESTÃO 92
echo "<h2>2. ✅ Teste da Questão 92</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM questoes WHERE id_questao = 92");
    $stmt->execute();
    $questao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($questao) {
        echo "<p style='color: green;'>✅ Questão 92 encontrada</p>";
        echo "<p><strong>Enunciado:</strong> " . htmlspecialchars(substr($questao['enunciado'], 0, 100)) . "...</p>";
    } else {
        echo "<p style='color: red;'>❌ Questão 92 não encontrada</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar questão: " . $e->getMessage() . "</p>";
    exit;
}

// 3. TESTAR ALTERNATIVAS
echo "<h2>3. ✅ Teste das Alternativas</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = 92 ORDER BY id_alternativa");
    $stmt->execute();
    $alternativas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($alternativas) {
        echo "<p style='color: green;'>✅ " . count($alternativas) . " alternativas encontradas</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Texto</th><th>eh_correta</th><th>correta</th><th>is_correct</th><th>correct</th><th>acertou</th></tr>";
        
        $alternativa_correta = null;
        foreach ($alternativas as $alt) {
            echo "<tr>";
            echo "<td>" . $alt['id_alternativa'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($alt['texto'], 0, 50)) . "...</td>";
            echo "<td>" . ($alt['eh_correta'] ?? 'NULL') . "</td>";
            echo "<td>" . ($alt['correta'] ?? 'NULL') . "</td>";
            echo "<td>" . ($alt['is_correct'] ?? 'NULL') . "</td>";
            echo "<td>" . ($alt['correct'] ?? 'NULL') . "</td>";
            echo "<td>" . ($alt['acertou'] ?? 'NULL') . "</td>";
            echo "</tr>";
            
            // Identificar qual é a correta
            if (($alt['eh_correta'] ?? 0) == 1) {
                $alternativa_correta = $alt;
            }
        }
        echo "</table>";
        
        if ($alternativa_correta) {
            echo "<p style='color: green;'>✅ Alternativa correta identificada: ID " . $alternativa_correta['id_alternativa'] . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Nenhuma alternativa marcada como correta!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Nenhuma alternativa encontrada</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar alternativas: " . $e->getMessage() . "</p>";
    exit;
}

// 4. TESTAR EMBARALHAMENTO
echo "<h2>4. ✅ Teste de Embaralhamento</h2>";
$seed = 92 + (int)date('Ymd');
srand($seed);
shuffle($alternativas);

echo "<p><strong>Seed usado:</strong> $seed</p>";

$letras = ['A', 'B', 'C', 'D', 'E'];
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f8f9fa;'><th>Posição</th><th>Letra</th><th>ID</th><th>Texto</th><th>eh_correta</th></tr>";

foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $is_correct = ($alt['eh_correta'] ?? 0) == 1;
    $style = $is_correct ? "background: #d4edda;" : "";
    
    echo "<tr style='$style'>";
    echo "<td>$index</td>";
    echo "<td><strong>$letra</strong></td>";
    echo "<td>" . $alt['id_alternativa'] . "</td>";
    echo "<td>" . htmlspecialchars(substr($alt['texto'], 0, 50)) . "...</td>";
    echo "<td>" . ($alt['eh_correta'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// 5. TESTAR MAPEAMENTO
echo "<h2>5. ✅ Teste de Mapeamento Letra → ID</h2>";
foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $is_correct = ($alt['eh_correta'] ?? 0) == 1;
    echo "<p><strong>Letra $letra</strong> → ID " . $alt['id_alternativa'] . " " . ($is_correct ? "✅ CORRETA" : "❌") . "</p>";
}

// 6. TESTAR LÓGICA DE ACERTO
echo "<h2>6. ✅ Teste de Lógica de Acerto</h2>";
echo "<h3>Simulando cliques em cada alternativa:</h3>";

foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    $id_alternativa = $alt['id_alternativa'];
    $is_correct = ($alt['eh_correta'] ?? 0) == 1;
    
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px 0; background: " . ($is_correct ? '#d4edda' : '#f8d7da') . ";'>";
    echo "<p><strong>Clique na Letra $letra:</strong></p>";
    echo "<p>• ID da alternativa: $id_alternativa</p>";
    echo "<p>• É correta: " . ($is_correct ? 'SIM' : 'NÃO') . "</p>";
    echo "<p>• Resultado esperado: " . ($is_correct ? '✅ ACERTOU' : '❌ ERROU') . "</p>";
    echo "</div>";
}

// 7. TESTAR TABELA respostas_usuario
echo "<h2>7. ✅ Teste da Tabela respostas_usuario</h2>";
try {
    $stmt = $pdo->prepare("DESCRIBE respostas_usuario");
    $stmt->execute();
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p style='color: green;'>✅ Tabela respostas_usuario existe</p>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela respostas_usuario: " . $e->getMessage() . "</p>";
}

// 8. TESTAR INSERÇÃO
echo "<h2>8. ✅ Teste de Inserção</h2>";
try {
    // Inserir uma resposta de teste
    $stmt = $pdo->prepare("INSERT INTO respostas_usuario (id_questao, id_alternativa, acertou, data_resposta) VALUES (?, ?, ?, NOW())");
    $stmt->execute([92, $alternativas[0]['id_alternativa'], 1]);
    echo "<p style='color: green;'>✅ Inserção de teste funcionou</p>";
    
    // Verificar se foi inserida
    $stmt = $pdo->prepare("SELECT * FROM respostas_usuario WHERE id_questao = 92 ORDER BY data_resposta DESC LIMIT 1");
    $stmt->execute();
    $resposta_teste = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resposta_teste) {
        echo "<p style='color: green;'>✅ Resposta de teste encontrada:</p>";
        echo "<ul>";
        echo "<li>ID Questão: " . $resposta_teste['id_questao'] . "</li>";
        echo "<li>ID Alternativa: " . $resposta_teste['id_alternativa'] . "</li>";
        echo "<li>Acertou: " . $resposta_teste['acertou'] . "</li>";
        echo "<li>Data: " . $resposta_teste['data_resposta'] . "</li>";
        echo "</ul>";
    }
    
    // Remover o registro de teste
    $stmt = $pdo->prepare("DELETE FROM respostas_usuario WHERE id_questao = 92 AND data_resposta > NOW() - INTERVAL 1 MINUTE");
    $stmt->execute();
    echo "<p style='color: green;'>✅ Limpeza de teste funcionou</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na inserção de teste: " . $e->getMessage() . "</p>";
}

// 9. TESTE FINAL - SIMULAR AJAX
echo "<h2>9. ✅ Teste Final - Simular AJAX</h2>";
echo "<p>Testando com a alternativa C (posição 2):</p>";

// Simular clique na alternativa C
$letra_teste = 'C';
$id_alternativa_teste = null;
$alternativa_correta_teste = null;

foreach ($alternativas as $index => $alt) {
    $letra = $letras[$index] ?? ($index + 1);
    if ($letra === $letra_teste) {
        $id_alternativa_teste = $alt['id_alternativa'];
        break;
    }
}

foreach ($alternativas as $alt) {
    if (($alt['eh_correta'] ?? 0) == 1) {
        $alternativa_correta_teste = $alt;
        break;
    }
}

if ($id_alternativa_teste && $alternativa_correta_teste) {
    $acertou_teste = ($id_alternativa_teste == $alternativa_correta_teste['id_alternativa']) ? 1 : 0;
    
    echo "<div style='border: 2px solid #007bff; padding: 15px; margin: 10px 0; background: #f8f9fa;'>";
    echo "<h4>Resultado do Teste:</h4>";
    echo "<p><strong>Letra selecionada:</strong> $letra_teste</p>";
    echo "<p><strong>ID da alternativa selecionada:</strong> $id_alternativa_teste</p>";
    echo "<p><strong>ID da alternativa correta:</strong> " . $alternativa_correta_teste['id_alternativa'] . "</p>";
    echo "<p><strong>Acertou:</strong> " . ($acertou_teste ? 'SIM ✅' : 'NÃO ❌') . "</p>";
    echo "</div>";
    
    if ($acertou_teste) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 TESTE PASSOU! A lógica está funcionando corretamente!</p>";
    } else {
        echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ TESTE FALHOU! A lógica não está funcionando!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Erro no teste final</p>";
}

echo "<h2>10. 📋 Resumo do Diagnóstico</h2>";
echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px;'>";
echo "<p><strong>Arquivos que podem interferir:</strong></p>";
echo "<ul>";
echo "<li>quiz_vertical_filtros.php - Lógica principal de processamento</li>";
echo "<li>modern-style.css - Estilos das alternativas</li>";
echo "<li>alternative-fix.css - Correções de pointer-events</li>";
echo "<li>alternative-clean.css - Estilos limpos</li>";
echo "<li>conexao.php - Conexão com banco</li>";
echo "</ul>";
echo "<p><strong>Possíveis problemas:</strong></p>";
echo "<ul>";
echo "<li>Campo 'eh_correta' não está sendo usado corretamente</li>";
echo "<li>Embaralhamento está alterando a ordem das alternativas</li>";
echo "<li>Mapeamento letra → ID não está funcionando</li>";
echo "<li>Lógica de comparação de IDs está incorreta</li>";
echo "<li>CSS está bloqueando cliques (pointer-events)</li>";
echo "</ul>";
echo "</div>";
?>


