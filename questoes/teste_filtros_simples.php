<?php
session_start();
require_once __DIR__ . '/conexao.php';

$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 8;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todas';

// Criar tabela de respostas_usuario se não existir
$sql_create_table = "CREATE TABLE IF NOT EXISTS respostas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_questao INT NOT NULL,
    id_alternativa INT NOT NULL,
    acertou TINYINT(1) NOT NULL DEFAULT 0,
    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_questao) REFERENCES questoes(id),
    FOREIGN KEY (id_alternativa) REFERENCES alternativas(id),
    UNIQUE KEY unique_questao (id_questao)
)";
$pdo->query($sql_create_table);

// Buscar informações do assunto
$stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
$stmt_assunto->execute([$id_assunto]);
$assunto = $stmt_assunto->fetch(PDO::FETCH_ASSOC);

// Contar questões por categoria
$counts = [
    'total' => 0,
    'respondidas' => 0,
    'nao_respondidas' => 0,
    'acertadas' => 0,
    'erradas' => 0
];

// Total de questões
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE id_assunto = ?");
$stmt_total->execute([$id_assunto]);
$counts['total'] = $stmt_total->fetchColumn();

// Questões respondidas
$stmt_respondidas = $pdo->prepare("SELECT COUNT(*) FROM questoes q 
                                  INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                                  WHERE q.id_assunto = ?");
$stmt_respondidas->execute([$id_assunto]);
$counts['respondidas'] = $stmt_respondidas->fetchColumn();

// Questões não respondidas
$counts['nao_respondidas'] = $counts['total'] - $counts['respondidas'];

// Questões acertadas
$stmt_acertadas = $pdo->prepare("SELECT COUNT(*) FROM questoes q 
                                INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                                WHERE q.id_assunto = ? AND r.acertou = 1");
$stmt_acertadas->execute([$id_assunto]);
$counts['acertadas'] = $stmt_acertadas->fetchColumn();

// Questões erradas
$stmt_erradas = $pdo->prepare("SELECT COUNT(*) FROM questoes q 
                              INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                              WHERE q.id_assunto = ? AND r.acertou = 0");
$stmt_erradas->execute([$id_assunto]);
$counts['erradas'] = $stmt_erradas->fetchColumn();

// Buscar questões baseado no filtro
$sql_questoes = "SELECT q.id_questao, q.enunciado";

switch ($filtro) {
    case 'respondidas':
        $sql_questoes .= " FROM questoes q 
                          INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                          WHERE q.id_assunto = ?";
        break;
    case 'nao-respondidas':
        $sql_questoes .= " FROM questoes q 
                          LEFT JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                          WHERE q.id_assunto = ? AND r.id_questao IS NULL";
        break;
    case 'acertadas':
        $sql_questoes .= " FROM questoes q 
                          INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                          WHERE q.id_assunto = ? AND r.acertou = 1";
        break;
    case 'erradas':
        $sql_questoes .= " FROM questoes q 
                          INNER JOIN respostas_usuario r ON q.id_questao = r.id_questao 
                          WHERE q.id_assunto = ? AND r.acertou = 0";
        break;
    default: // 'todas'
        $sql_questoes .= " FROM questoes q WHERE q.id_assunto = ?";
        break;
}

$sql_questoes .= " ORDER BY q.id_questao";

$stmt_questoes = $pdo->prepare($sql_questoes);
$stmt_questoes->execute([$id_assunto]);
$questoes = $stmt_questoes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Filtros - <?php echo htmlspecialchars($assunto['nome']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .filtro-btn { 
            display: inline-block; 
            padding: 10px 15px; 
            margin: 5px; 
            background: #f0f0f0; 
            text-decoration: none; 
            border-radius: 5px; 
            color: #333;
        }
        .filtro-btn.ativo { background: #007bff; color: white; }
        .questao-item { 
            padding: 10px; 
            margin: 5px 0; 
            background: #f9f9f9; 
            border-radius: 5px; 
        }
        .debug-info { 
            background: #e9ecef; 
            padding: 15px; 
            margin: 20px 0; 
            border-radius: 5px; 
        }
    </style>
</head>
<body>
    <h1>Teste de Filtros - <?php echo htmlspecialchars($assunto['nome']); ?></h1>
    
    <div class="debug-info">
        <h3>Informações de Debug:</h3>
        <p><strong>ID do Assunto:</strong> <?php echo $id_assunto; ?></p>
        <p><strong>Filtro Atual:</strong> <?php echo $filtro; ?></p>
        <p><strong>Total de Questões:</strong> <?php echo $counts['total']; ?></p>
        <p><strong>Respondidas:</strong> <?php echo $counts['respondidas']; ?></p>
        <p><strong>Não Respondidas:</strong> <?php echo $counts['nao_respondidas']; ?></p>
        <p><strong>Acertadas:</strong> <?php echo $counts['acertadas']; ?></p>
        <p><strong>Erradas:</strong> <?php echo $counts['erradas']; ?></p>
        <p><strong>Questões Encontradas com Filtro:</strong> <?php echo count($questoes); ?></p>
    </div>
    
    <div>
        <h3>Filtros:</h3>
        <a href="?id=<?php echo $id_assunto; ?>&filtro=todas" 
           class="filtro-btn <?php echo $filtro == 'todas' ? 'ativo' : ''; ?>">
            Todas (<?php echo $counts['total']; ?>)
        </a>
        
        <a href="?id=<?php echo $id_assunto; ?>&filtro=nao-respondidas" 
           class="filtro-btn <?php echo $filtro == 'nao-respondidas' ? 'ativo' : ''; ?>">
            Não Respondidas (<?php echo $counts['nao_respondidas']; ?>)
        </a>
        
        <a href="?id=<?php echo $id_assunto; ?>&filtro=respondidas" 
           class="filtro-btn <?php echo $filtro == 'respondidas' ? 'ativo' : ''; ?>">
            Respondidas (<?php echo $counts['respondidas']; ?>)
        </a>
        
        <a href="?id=<?php echo $id_assunto; ?>&filtro=acertadas" 
           class="filtro-btn <?php echo $filtro == 'acertadas' ? 'ativo' : ''; ?>">
            Acertadas (<?php echo $counts['acertadas']; ?>)
        </a>
        
        <a href="?id=<?php echo $id_assunto; ?>&filtro=erradas" 
           class="filtro-btn <?php echo $filtro == 'erradas' ? 'ativo' : ''; ?>">
            Erradas (<?php echo $counts['erradas']; ?>)
        </a>
    </div>
    
    <div>
        <h3>Questões (<?php echo count($questoes); ?> encontradas):</h3>
        <?php if (!empty($questoes)): ?>
            <?php foreach ($questoes as $questao): ?>
                <div class="questao-item">
                    <strong>ID:</strong> <?php echo $questao['id_questao']; ?> - 
                    <?php echo htmlspecialchars(substr($questao['enunciado'], 0, 100)); ?>...
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhuma questão encontrada para este filtro.</p>
        <?php endif; ?>
    </div>
</body>
</html>