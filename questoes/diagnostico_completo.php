<?php
require_once 'conexao.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 DIAGNÓSTICO COMPLETO - HOSTINGER</h1>";
echo "<hr>";

try {
    // 1. Verificar estrutura da tabela assuntos
    echo "<h2>1. Estrutura da Tabela 'assuntos':</h2>";
    $stmt = $pdo->query("DESCRIBE assuntos");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($cols as $col) {
        echo "<tr>";
        foreach ($col as $key => $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";

    // 2. Verificar se as colunas necessárias existem
    echo "<h2>2. Verificação de Colunas Necessárias:</h2>";
    $columns_existentes = array_column($cols, 'Field');
    $colunas_necessarias = ['tipo_assunto', 'tipo', 'concurso_ano', 'concurso_banca', 'concurso_orgao', 'concurso_prova'];
    
    foreach ($colunas_necessarias as $coluna) {
        if (in_array($coluna, $columns_existentes)) {
            echo "<p style='color: green;'>✅ Coluna '<b>$coluna</b>' existe</p>";
        } else {
            echo "<p style='color: red;'>❌ Coluna '<b>$coluna</b>' NÃO existe</p>";
        }
    }
    echo "<br>";

    // 3. Dados brutos da tabela assuntos
    echo "<h2>3. Dados da Tabela 'assuntos':</h2>";
    $sql_dados = "SELECT id_assunto, nome, tipo_assunto, tipo, concurso_ano, concurso_banca, concurso_orgao, concurso_prova FROM assuntos ORDER BY id_assunto";
    $dados = $pdo->query($sql_dados)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($dados)) {
        echo "<p>Nenhum dado encontrado na tabela 'assuntos'.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Tipo Assunto</th><th>Tipo</th><th>Ano</th><th>Banca</th><th>Órgão</th><th>Prova</th></tr>";
        foreach ($dados as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id_assunto']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($row['tipo_assunto'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['tipo'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['concurso_ano'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['concurso_banca'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['concurso_orgao'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['concurso_prova'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "<br>";

    // 4. Simular a query exata do escolher_assunto.php
    echo "<h2>4. Simulação da Query do escolher_assunto.php:</h2>";
    
    // Verificar se o campo 'tipo_assunto' existe
    $tem_campo_tipo_assunto = in_array('tipo_assunto', $columns_existentes);
    echo "<p><b>Campo 'tipo_assunto' existe:</b> " . ($tem_campo_tipo_assunto ? "✅ SIM" : "❌ NÃO") . "</p>";
    
    if ($tem_campo_tipo_assunto) {
        echo "<p><b>Query usada:</b> SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes FROM assuntos a LEFT JOIN questoes q ON a.id_assunto = q.id_assunto GROUP BY a.id_assunto, a.nome, a.tipo_assunto ORDER BY a.tipo_assunto, a.nome</p>";
        
        $sql = "SELECT a.id_assunto, a.nome, a.tipo_assunto, COUNT(q.id_questao) as total_questoes 
                FROM assuntos a 
                LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                GROUP BY a.id_assunto, a.nome, a.tipo_assunto 
                ORDER BY a.tipo_assunto, a.nome";
        $result = $pdo->query($sql)->fetchAll();
        
        echo "<h3>Resultados da Query:</h3>";
        if (empty($result)) {
            echo "<p>Nenhum resultado encontrado.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Tipo Assunto</th><th>Total Questões</th><th>Tipo Mapeado</th></tr>";
            foreach ($result as $assunto) {
                // Mapear tipo_assunto para tipo (igual ao código)
                $tipo_mapeado = 'temas';
                switch ($assunto['tipo_assunto']) {
                    case 'concurso':
                        $tipo_mapeado = 'concursos';
                        break;
                    case 'profissional':
                        $tipo_mapeado = 'profissionais';
                        break;
                    case 'tema':
                    default:
                        $tipo_mapeado = 'temas';
                        break;
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($assunto['id_assunto']) . "</td>";
                echo "<td>" . htmlspecialchars($assunto['nome']) . "</td>";
                echo "<td>" . htmlspecialchars($assunto['tipo_assunto'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($assunto['total_questoes']) . "</td>";
                echo "<td style='background: " . ($tipo_mapeado === 'concursos' ? '#ffebee' : ($tipo_mapeado === 'profissionais' ? '#e8f5e8' : '#e3f2fd')) . ";'>" . htmlspecialchars($tipo_mapeado) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ Campo 'tipo_assunto' não existe, usando fallback baseado no nome.</p>";
        
        $sql_fallback = "SELECT a.id_assunto, a.nome, COUNT(q.id_questao) as total_questoes 
                        FROM assuntos a 
                        LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                        GROUP BY a.id_assunto, a.nome 
                        ORDER BY a.nome";
        $result_fallback = $pdo->query($sql_fallback)->fetchAll();
        
        echo "<h3>Resultados do Fallback:</h3>";
        if (empty($result_fallback)) {
            echo "<p>Nenhum resultado encontrado.</p>";
        } else {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Nome</th><th>Total Questões</th><th>Tipo Mapeado (por nome)</th></tr>";
            foreach ($result_fallback as $assunto) {
                // Categorizar baseado no nome (igual ao código)
                $nome = strtolower($assunto['nome']);
                $tipo_mapeado = 'temas';
                if (strpos($nome, 'concurso') !== false || strpos($nome, 'prova') !== false || strpos($nome, 'edital') !== false) {
                    $tipo_mapeado = 'concursos';
                } elseif (strpos($nome, 'profissional') !== false || strpos($nome, 'carreira') !== false || strpos($nome, 'trabalho') !== false) {
                    $tipo_mapeado = 'profissionais';
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($assunto['id_assunto']) . "</td>";
                echo "<td>" . htmlspecialchars($assunto['nome']) . "</td>";
                echo "<td>" . htmlspecialchars($assunto['total_questoes']) . "</td>";
                echo "<td style='background: " . ($tipo_mapeado === 'concursos' ? '#ffebee' : ($tipo_mapeado === 'profissionais' ? '#e8f5e8' : '#e3f2fd')) . ";'>" . htmlspecialchars($tipo_mapeado) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "<br>";

    // 5. Contagem final por categoria
    echo "<h2>5. Contagem Final por Categoria:</h2>";
    $categorias = [
        'temas' => [],
        'concursos' => [],
        'profissionais' => []
    ];

    if ($tem_campo_tipo_assunto && !empty($result)) {
        foreach ($result as $assunto) {
            $tipo = 'temas';
            switch ($assunto['tipo_assunto']) {
                case 'concurso':
                    $tipo = 'concursos';
                    break;
                case 'profissional':
                    $tipo = 'profissionais';
                    break;
                case 'tema':
                default:
                    $tipo = 'temas';
                    break;
            }
            $categorias[$tipo][] = $assunto;
        }
    } elseif (!empty($result_fallback)) {
        foreach ($result_fallback as $assunto) {
            $nome = strtolower($assunto['nome']);
            $tipo = 'temas';
            if (strpos($nome, 'concurso') !== false || strpos($nome, 'prova') !== false || strpos($nome, 'edital') !== false) {
                $tipo = 'concursos';
            } elseif (strpos($nome, 'profissional') !== false || strpos($nome, 'carreira') !== false || strpos($nome, 'trabalho') !== false) {
                $tipo = 'profissionais';
            }
            $categorias[$tipo][] = $assunto;
        }
    }

    echo "<p><b>Temas:</b> " . count($categorias['temas']) . " assuntos</p>";
    echo "<p><b>Concursos:</b> " . count($categorias['concursos']) . " assuntos</p>";
    echo "<p><b>Profissionais:</b> " . count($categorias['profissionais']) . " assuntos</p>";

    if (!empty($categorias['concursos'])) {
        echo "<h3>Assuntos de Concurso Encontrados:</h3>";
        foreach ($categorias['concursos'] as $assunto) {
            echo "<p>- " . htmlspecialchars($assunto['nome']) . " (" . htmlspecialchars($assunto['total_questoes']) . " questões)</p>";
        }
    }

    echo "<br><h2>🎯 DIAGNÓSTICO FINAL:</h2>";
    if (count($categorias['concursos']) > 0) {
        echo "<p style='color: green;'>✅ Concursos estão sendo identificados corretamente!</p>";
        echo "<p>Se eles não aparecem no frontend, o problema pode ser:</p>";
        echo "<ul>";
        echo "<li>Cache do navegador</li>";
        echo "<li>JavaScript não carregando</li>";
        echo "<li>Problema na renderização HTML</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Nenhum concurso foi identificado!</p>";
        echo "<p>Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>Campo 'tipo_assunto' não existe ou está vazio</li>";
        echo "<li>Nomes dos assuntos não contêm palavras-chave</li>";
        echo "<li>Nenhum assunto foi criado como 'concurso'</li>";
        echo "</ul>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Erro no diagnóstico: " . $e->getMessage());
}
?>
