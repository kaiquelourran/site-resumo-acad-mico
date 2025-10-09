<?php
session_start();
require_once 'conexao.php';

// Inserir alguns comentários de teste
try {
    // Verificar se já existem comentários de teste
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comentarios_questoes WHERE nome_usuario LIKE 'Usuário Teste%'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Inserir comentários de teste
        $comentarios_teste = [
            [
                'id_questao' => 92,
                'nome_usuario' => 'Bruno Collovini',
                'email_usuario' => 'bruno@teste.com',
                'comentario' => "A-í= Hiato\nDe-pois= ditongo\ncar-re-ga-dor= RR é o dígrafo consonantal.",
                'aprovado' => 1
            ],
            [
                'id_questao' => 92,
                'nome_usuario' => 'Wandinha',
                'email_usuario' => 'wandinha@teste.com',
                'comentario' => 'LETRA A',
                'aprovado' => 1
            ],
            [
                'id_questao' => 92,
                'nome_usuario' => 'João Silva',
                'email_usuario' => 'joao@teste.com',
                'comentario' => 'Excelente questão! Ajudou muito no meu estudo.',
                'aprovado' => 1
            ]
        ];
        
        foreach ($comentarios_teste as $comentario) {
            $stmt = $pdo->prepare("
                INSERT INTO comentarios_questoes (id_questao, nome_usuario, email_usuario, comentario, aprovado, ativo) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $comentario['id_questao'],
                $comentario['nome_usuario'],
                $comentario['email_usuario'],
                $comentario['comentario'],
                $comentario['aprovado']
            ]);
        }
        
        echo "✅ Comentários de teste inseridos com sucesso!<br>";
    } else {
        echo "ℹ️ Comentários de teste já existem.<br>";
    }
    
    // Adicionar algumas curtidas de teste
    $stmt = $pdo->prepare("SELECT id_comentario FROM comentarios_questoes WHERE nome_usuario = 'Bruno Collovini' LIMIT 1");
    $stmt->execute();
    $comentario_id = $stmt->fetchColumn();
    
    if ($comentario_id) {
        // Verificar se já tem curtidas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM curtidas_comentarios WHERE id_comentario = ?");
        $stmt->execute([$comentario_id]);
        $curtidas_count = $stmt->fetchColumn();
        
        if ($curtidas_count == 0) {
            // Adicionar curtidas de teste
            $ips_teste = ['192.168.1.1', '192.168.1.2', '192.168.1.3', '192.168.1.4'];
            
            foreach ($ips_teste as $ip) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO curtidas_comentarios (id_comentario, ip_usuario) VALUES (?, ?)");
                    $stmt->execute([$comentario_id, $ip]);
                } catch (PDOException $e) {
                    // Ignorar se já existe
                }
            }
            echo "✅ Curtidas de teste adicionadas!<br>";
        } else {
            echo "ℹ️ Curtidas de teste já existem.<br>";
        }
    }
    
    // Mostrar estatísticas
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_comentarios,
            COUNT(CASE WHEN aprovado = 1 THEN 1 END) as comentarios_aprovados,
            COUNT(CASE WHEN ativo = 1 THEN 1 END) as comentarios_ativos
        FROM comentarios_questoes
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 Estatísticas do Sistema de Comentários:</h3>";
    echo "<ul>";
    echo "<li>Total de comentários: " . $stats['total_comentarios'] . "</li>";
    echo "<li>Comentários aprovados: " . $stats['comentarios_aprovados'] . "</li>";
    echo "<li>Comentários ativos: " . $stats['comentarios_ativos'] . "</li>";
    echo "</ul>";
    
    // Mostrar comentários de teste
    $stmt = $pdo->prepare("
        SELECT c.*, 
               (SELECT COUNT(*) FROM curtidas_comentarios cc WHERE cc.id_comentario = c.id_comentario) as total_curtidas
        FROM comentarios_questoes c 
        WHERE c.id_questao = 1 AND c.aprovado = 1 AND c.ativo = 1
        ORDER BY c.data_comentario DESC
    ");
    $stmt->execute();
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>💬 Comentários de Teste:</h3>";
    foreach ($comentarios as $comentario) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>" . htmlspecialchars($comentario['nome_usuario']) . "</strong> ";
        echo "<small>(" . date('d/m/Y H:i', strtotime($comentario['data_comentario'])) . ")</small><br>";
        echo "<p>" . nl2br(htmlspecialchars($comentario['comentario'])) . "</p>";
        echo "<small>👍 " . $comentario['total_curtidas'] . " curtidas</small>";
        echo "</div>";
    }
    
    echo "<br><a href='quiz_vertical_filtros.php?id=8&filtro=todas&questao_inicial=92' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Testar Sistema de Comentários</a>";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
