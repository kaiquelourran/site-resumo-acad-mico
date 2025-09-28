<?php
session_start();
require_once 'conexao.php';

// Buscar todos os assuntos que têm questões
$sql = "SELECT a.id_assunto, a.nome, COUNT(q.id_questao) as total_questoes 
        FROM assuntos a 
        INNER JOIN questoes q ON a.id_assunto = q.id_assunto 
        GROUP BY a.id_assunto, a.nome 
        ORDER BY a.nome";

$result = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolher Assunto - Questões</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .assuntos-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .assunto-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .assunto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .assunto-titulo {
            font-size: 1.4em;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .assunto-info {
            color: #666;
            font-size: 0.95em;
        }
        
        .questoes-count {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-block;
            margin-top: 10px;
        }
        
        .voltar-btn {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 30px;
            transition: background 0.3s ease;
        }
        
        .voltar-btn:hover {
            background: #5a6268;
            color: white;
            text-decoration: none;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 2.2em;
            font-weight: 600;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="assuntos-container">
            <a href="index.php" class="voltar-btn">← Voltar ao Início</a>
            
            <div class="page-header">
                <h1>🎯 Escolha um Assunto</h1>
                <p>Selecione o assunto que deseja estudar</p>
            </div>
            
            <?php if ($result && count($result) > 0): ?>
                <?php foreach($result as $assunto): ?>
                    <div class="assunto-card" onclick="window.location.href='listar_questoes.php?id=<?php echo $assunto['id_assunto']; ?>'">
                        <div class="assunto-titulo"><?php echo htmlspecialchars($assunto['nome']); ?></div>
                        <div class="assunto-info">
                            Clique para ver as questões deste assunto
                        </div>
                        <div class="questoes-count">
                            <?php echo $assunto['total_questoes']; ?> questões disponíveis
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="assunto-card" style="text-align: center; cursor: default;">
                    <div class="assunto-titulo">Nenhum assunto encontrado</div>
                    <div class="assunto-info">
                        Não há assuntos com questões cadastradas no sistema.
                        <br><br>
                        <a href="inserir_questoes_manual.php" class="btn">Cadastrar Questões</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>