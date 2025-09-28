<?php
// Script de Diagnóstico Completo do Sistema
require_once 'conexao.php';

echo "<h1>🔍 DIAGNÓSTICO COMPLETO DO SISTEMA</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
.success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
</style>";

$problemas = [];
$sucessos = [];

try {
    // 1. TESTE DE CONEXÃO COM BANCO
    echo "<h2>1. 🔌 TESTE DE CONEXÃO COM BANCO</h2>";
    
    $stmt = $pdo->query("SELECT 1");
    if ($stmt) {
        echo "<div class='success'>✅ Conexão com banco: OK</div>";
        $sucessos[] = "Conexão com banco funcionando";
    }
    
    // 2. VERIFICAR ESTRUTURA DAS TABELAS
    echo "<h2>2. 🗃️ ESTRUTURA DAS TABELAS</h2>";
    
    $tabelas = ['assuntos', 'questoes', 'alternativas'];
    foreach ($tabelas as $tabela) {
        try {
            $stmt = $pdo->query("DESCRIBE $tabela");
            $colunas = $stmt->fetchAll();
            echo "<div class='success'>✅ Tabela '$tabela': OK (" . count($colunas) . " colunas)</div>";
            $sucessos[] = "Tabela $tabela existe";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Tabela '$tabela': ERRO - " . $e->getMessage() . "</div>";
            $problemas[] = "Tabela $tabela com problema: " . $e->getMessage();
        }
    }
    
    // 3. VERIFICAR DADOS NO BANCO
    echo "<h2>3. 📊 DADOS NO BANCO</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assuntos");
    $total_assuntos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM questoes");
    $total_questoes = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alternativas");
    $total_alternativas = $stmt->fetch()['total'];
    
    echo "<table>";
    echo "<tr><th>Tabela</th><th>Total de Registros</th><th>Status</th></tr>";
    echo "<tr><td>Assuntos</td><td>$total_assuntos</td><td>" . ($total_assuntos > 0 ? "✅ OK" : "⚠️ Vazio") . "</td></tr>";
    echo "<tr><td>Questões</td><td>$total_questoes</td><td>" . ($total_questoes > 0 ? "✅ OK" : "⚠️ Vazio") . "</td></tr>";
    echo "<tr><td>Alternativas</td><td>$total_alternativas</td><td>" . ($total_alternativas > 0 ? "✅ OK" : "⚠️ Vazio") . "</td></tr>";
    echo "</table>";
    
    if ($total_assuntos == 0) $problemas[] = "Nenhum assunto cadastrado";
    if ($total_questoes == 0) $problemas[] = "Nenhuma questão cadastrada";
    if ($total_alternativas == 0) $problemas[] = "Nenhuma alternativa cadastrada";
    
    // 4. TESTE DE ARQUIVOS ESSENCIAIS
    echo "<h2>4. 📁 ARQUIVOS ESSENCIAIS</h2>";
    
    $arquivos_essenciais = [
        'conexao.php' => 'Conexão com banco',
        'gerenciar_questoes_sem_auth.php' => 'Gerenciador de questões',
        'quiz_sem_login.php' => 'Questões principal',
        'processar_resposta.php' => 'Processamento de respostas',
        'resultado.php' => 'Exibição de resultados',
        'style.css' => 'Estilos CSS',
        'quiz.js' => 'JavaScript das questões'
    ];
    
    foreach ($arquivos_essenciais as $arquivo => $descricao) {
        if (file_exists($arquivo)) {
            echo "<div class='success'>✅ $arquivo ($descricao): OK</div>";
            $sucessos[] = "Arquivo $arquivo existe";
        } else {
            echo "<div class='error'>❌ $arquivo ($descricao): AUSENTE</div>";
            $problemas[] = "Arquivo $arquivo não encontrado";
        }
    }
    
    // 5. TESTE DE ARQUIVOS ADMIN
    echo "<h2>5. 👨‍💼 ARQUIVOS ADMIN</h2>";
    
    $arquivos_admin = [
        'admin/dashboard.php' => 'Dashboard admin',
        'admin/add_assunto.php' => 'Adicionar assunto',
        'admin/add_questao.php' => 'Adicionar questão',
        'admin/editar_questao.php' => 'Editar questão',
        'admin/deletar_questao.php' => 'Deletar questão',
        'admin/gerenciar_questoes_sem_auth.php' => 'Gerenciador admin'
    ];
    
    foreach ($arquivos_admin as $arquivo => $descricao) {
        if (file_exists($arquivo)) {
            echo "<div class='success'>✅ $arquivo ($descricao): OK</div>";
            $sucessos[] = "Arquivo admin $arquivo existe";
        } else {
            echo "<div class='error'>❌ $arquivo ($descricao): AUSENTE</div>";
            $problemas[] = "Arquivo admin $arquivo não encontrado";
        }
    }
    
    // 6. TESTE DE PERMISSÕES
    echo "<h2>6. 🔐 PERMISSÕES DE ARQUIVOS</h2>";
    
    $arquivos_teste = ['conexao.php', 'gerenciar_questoes_sem_auth.php', 'quiz_sem_login.php'];
    foreach ($arquivos_teste as $arquivo) {
        if (is_readable($arquivo)) {
            echo "<div class='success'>✅ $arquivo: Legível</div>";
        } else {
            echo "<div class='error'>❌ $arquivo: Não legível</div>";
            $problemas[] = "Arquivo $arquivo sem permissão de leitura";
        }
    }
    
    // 7. TESTE DE FUNCIONALIDADES ESPECÍFICAS
    echo "<h2>7. ⚙️ FUNCIONALIDADES ESPECÍFICAS</h2>";
    
    // Teste de inserção de assunto
    try {
        $stmt = $pdo->prepare("SELECT * FROM assuntos WHERE nome = ?");
        $stmt->execute(['MARCOS DO DESENVOLVIMENTO INFANTIL']);
        $assunto_marcos = $stmt->fetch();
        
        if ($assunto_marcos) {
            echo "<div class='success'>✅ Assunto 'MARCOS DO DESENVOLVIMENTO INFANTIL': Encontrado (ID: " . $assunto_marcos['id_assunto'] . ")</div>";
            $sucessos[] = "Assunto principal existe";
        } else {
            echo "<div class='warning'>⚠️ Assunto 'MARCOS DO DESENVOLVIMENTO INFANTIL': Não encontrado</div>";
            $problemas[] = "Assunto principal não existe";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao verificar assunto: " . $e->getMessage() . "</div>";
        $problemas[] = "Erro ao verificar assunto: " . $e->getMessage();
    }
    
    // 8. RESUMO FINAL
    echo "<h2>8. 📋 RESUMO FINAL</h2>";
    
    echo "<div class='info'>";
    echo "<h3>✅ SUCESSOS (" . count($sucessos) . "):</h3>";
    if (count($sucessos) > 0) {
        echo "<ul>";
        foreach ($sucessos as $sucesso) {
            echo "<li>$sucesso</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Nenhum sucesso registrado</p>";
    }
    echo "</div>";
    
    if (count($problemas) > 0) {
        echo "<div class='error'>";
        echo "<h3>❌ PROBLEMAS ENCONTRADOS (" . count($problemas) . "):</h3>";
        echo "<ul>";
        foreach ($problemas as $problema) {
            echo "<li>$problema</li>";
        }
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>🎉 SISTEMA FUNCIONANDO PERFEITAMENTE!</h3>";
        echo "<p>Nenhum problema encontrado. Todas as funcionalidades estão operacionais.</p>";
        echo "</div>";
    }
    
    // 9. LINKS PARA TESTE
    echo "<h2>9. 🔗 LINKS PARA TESTE</h2>";
    echo "<div style='display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;'>";
    echo "<a href='gerenciar_questoes_sem_auth.php' target='_blank' style='background: #28a745; color: white; padding: 15px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;'>📋 Gerenciador</a>";
    echo "<a href='quiz_sem_login.php' target='_blank' style='background: #007bff; color: white; padding: 15px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;'>🎮 Questões</a>";
    echo "<a href='admin/dashboard.php' target='_blank' style='background: #6c757d; color: white; padding: 15px 20px; text-decoration: none; border-radius: 8px; font-weight: bold;'>👨‍💼 Admin</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ ERRO CRÍTICO!</h3>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>";
echo "<h2>🔍 DIAGNÓSTICO CONCLUÍDO</h2>";
echo "<p style='font-size: 16px; margin: 0;'>Verifique os resultados acima para identificar problemas</p>";
echo "</div>";
?>