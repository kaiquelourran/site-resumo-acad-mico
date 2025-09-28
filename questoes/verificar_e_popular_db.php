<?php
require_once 'conexao.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-br'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Verificar e Popular Banco de Dados</title>";
echo "<link rel='stylesheet' href='modern-style.css'>";
echo "</head>";
echo "<body>";
echo "<div class='main-container fade-in'>";
echo "<div class='header'>";
echo "<div class='logo'>🔧</div>";
echo "<h1 class='title'>Verificação do Banco de Dados</h1>";
echo "<p class='subtitle'>Diagnóstico e População de Dados</p>";
echo "</div>";

try {
    // Verificar se as tabelas existem
    echo "<div class='alert alert-info'>📊 Verificando estrutura do banco de dados...</div>";
    
    // Verificar tabela assuntos
    $stmt = $pdo->query("SELECT COUNT(*) FROM assuntos");
    $total_assuntos = $stmt->fetchColumn();
    echo "<p><strong>Assuntos cadastrados:</strong> $total_assuntos</p>";
    
    // Verificar tabela questoes
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes");
    $total_questoes = $stmt->fetchColumn();
    echo "<p><strong>Questões cadastradas:</strong> $total_questoes</p>";
    
    // Verificar tabela alternativas
    $stmt = $pdo->query("SELECT COUNT(*) FROM alternativas");
    $total_alternativas = $stmt->fetchColumn();
    echo "<p><strong>Alternativas cadastradas:</strong> $total_alternativas</p>";
    
    // Se não há dados, popular com exemplos
    if ($total_assuntos == 0 || $total_questoes == 0) {
        echo "<div class='alert alert-warning'>⚠️ Banco de dados vazio! Populando com dados de exemplo...</div>";
        
        // Inserir assuntos de exemplo
        $assuntos_exemplo = [
            'Desenvolvimento Infantil',
            'Transtorno do Espectro Autista',
            'TDAH - Transtorno do Déficit de Atenção',
            'Síndrome de Down',
            'Dificuldades de Aprendizagem'
        ];
        
        foreach ($assuntos_exemplo as $assunto) {
            $stmt = $pdo->prepare("INSERT INTO assuntos (nome) VALUES (?)");
            $stmt->execute([$assunto]);
            echo "<p>✅ Assunto inserido: $assunto</p>";
        }
        
        // Buscar IDs dos assuntos inseridos
        $stmt = $pdo->query("SELECT id_assunto, nome FROM assuntos ORDER BY id_assunto");
        $assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Inserir questões de exemplo
        $questoes_exemplo = [
            [
                'assunto' => $assuntos[0]['id_assunto'], // Desenvolvimento Infantil
                'enunciado' => 'Qual é a idade típica para o desenvolvimento da marcha independente?',
                'alternativas' => [
                    ['texto' => '8-10 meses', 'correta' => false],
                    ['texto' => '12-15 meses', 'correta' => true],
                    ['texto' => '18-20 meses', 'correta' => false],
                    ['texto' => '24-30 meses', 'correta' => false]
                ]
            ],
            [
                'assunto' => $assuntos[1]['id_assunto'], // TEA
                'enunciado' => 'Qual é uma das principais características do Transtorno do Espectro Autista?',
                'alternativas' => [
                    ['texto' => 'Hiperatividade motora', 'correta' => false],
                    ['texto' => 'Dificuldades na comunicação social', 'correta' => true],
                    ['texto' => 'Deficiência intelectual severa', 'correta' => false],
                    ['texto' => 'Problemas de coordenação motora', 'correta' => false]
                ]
            ],
            [
                'assunto' => $assuntos[2]['id_assunto'], // TDAH
                'enunciado' => 'O TDAH é caracterizado principalmente por:',
                'alternativas' => [
                    ['texto' => 'Desatenção, hiperatividade e impulsividade', 'correta' => true],
                    ['texto' => 'Apenas problemas de atenção', 'correta' => false],
                    ['texto' => 'Deficiência intelectual', 'correta' => false],
                    ['texto' => 'Problemas de linguagem', 'correta' => false]
                ]
            ],
            [
                'assunto' => $assuntos[3]['id_assunto'], // Síndrome de Down
                'enunciado' => 'A Síndrome de Down é causada por:',
                'alternativas' => [
                    ['texto' => 'Deficiência de vitaminas', 'correta' => false],
                    ['texto' => 'Trissomia do cromossomo 21', 'correta' => true],
                    ['texto' => 'Infecção viral', 'correta' => false],
                    ['texto' => 'Trauma no nascimento', 'correta' => false]
                ]
            ],
            [
                'assunto' => $assuntos[4]['id_assunto'], // Dificuldades de Aprendizagem
                'enunciado' => 'A dislexia afeta principalmente:',
                'alternativas' => [
                    ['texto' => 'A capacidade de leitura e escrita', 'correta' => true],
                    ['texto' => 'A coordenação motora', 'correta' => false],
                    ['texto' => 'A memória visual', 'correta' => false],
                    ['texto' => 'A capacidade auditiva', 'correta' => false]
                ]
            ]
        ];
        
        foreach ($questoes_exemplo as $questao_data) {
            // Inserir questão
            $stmt = $pdo->prepare("INSERT INTO questoes (id_assunto, enunciado) VALUES (?, ?)");
            $stmt->execute([$questao_data['assunto'], $questao_data['enunciado']]);
            $id_questao = $pdo->lastInsertId();
            
            echo "<p>✅ Questão inserida: " . substr($questao_data['enunciado'], 0, 50) . "...</p>";
            
            // Inserir alternativas
            foreach ($questao_data['alternativas'] as $alt) {
                $stmt = $pdo->prepare("INSERT INTO alternativas (id_questao, texto, correta) VALUES (?, ?, ?)");
                $stmt->execute([$id_questao, $alt['texto'], $alt['correta'] ? 1 : 0]);
            }
        }
        
        echo "<div class='alert alert-success'>🎉 Banco de dados populado com sucesso!</div>";
    } else {
        echo "<div class='alert alert-success'>✅ Banco de dados já contém dados!</div>";
        
        // Mostrar assuntos existentes
        echo "<h3>Assuntos disponíveis:</h3>";
        $stmt = $pdo->query("SELECT a.nome, COUNT(q.id_questao) as qtd_questoes 
                            FROM assuntos a 
                            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                            GROUP BY a.id_assunto, a.nome 
                            ORDER BY a.nome");
        $assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<ul>";
        foreach ($assuntos as $assunto) {
            echo "<li><strong>{$assunto['nome']}</strong> - {$assunto['qtd_questoes']} questões</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-error'>❌ Erro: " . $e->getMessage() . "</div>";
}

echo "<div style='text-align: center; margin-top: 40px;'>";
echo "<a href='quiz_sem_login.php' class='btn'>🎯 Testar Questões</a>";
echo "<a href='gerenciar_questoes_sem_auth.php' class='btn btn-secondary' style='margin-left: 15px;'>📋 Gerenciar Questões</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>