<?php
session_start();
require_once 'conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$id_assunto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'nao-respondidas';

if ($id_assunto == 0) {
    header('Location: index.php');
    exit;
}

// Busca informações do assunto
$stmt_assunto = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
$stmt_assunto->execute([$id_assunto]);
$assunto = $stmt_assunto->fetch(PDO::FETCH_ASSOC);

if (!$assunto) {
    header('Location: index.php');
    exit;
}

// Monta a query baseada no filtro
$sql = "";
$params = [$id_usuario, $id_assunto];

switch ($filtro) {
    case 'nao-respondidas':
        $sql = "SELECT q.id_questao, q.enunciado 
                FROM questoes q 
                LEFT JOIN respostas_usuarios r ON q.id_questao = r.id_questao AND r.id_usuario = ?
                WHERE q.id_assunto = ? AND r.id_questao IS NULL
                ORDER BY q.id_questao";
        break;
        
    case 'respondidas':
        $sql = "SELECT q.id_questao, q.enunciado 
                FROM questoes q 
                INNER JOIN respostas_usuarios r ON q.id_questao = r.id_questao AND r.id_usuario = ?
                WHERE q.id_assunto = ?
                ORDER BY q.id_questao";
        break;
        
    case 'corretas':
        $sql = "SELECT q.id_questao, q.enunciado 
                FROM questoes q 
                INNER JOIN respostas_usuarios r ON q.id_questao = r.id_questao AND r.id_usuario = ?
                WHERE q.id_assunto = ? AND r.correta = 1
                ORDER BY q.id_questao";
        break;
        
    case 'incorretas':
        $sql = "SELECT q.id_questao, q.enunciado 
                FROM questoes q 
                INNER JOIN respostas_usuarios r ON q.id_questao = r.id_questao AND r.id_usuario = ?
                WHERE q.id_assunto = ? AND r.correta = 0
                ORDER BY q.id_questao";
        break;
        
    default:
        $sql = "SELECT q.id_questao, q.enunciado 
                FROM questoes q 
                WHERE q.id_assunto = ?
                ORDER BY q.id_questao";
        $params = [$id_assunto];
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$questoes_filtradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se não há questões no filtro, redireciona
if (empty($questoes_filtradas)) {
    header("Location: listar_questoes.php?id=$id_assunto&filtro=todas");
    exit;
}

// Busca alternativas para todas as questões
$questoes_com_alternativas = [];
foreach ($questoes_filtradas as $questao) {
    $stmt_alt = $pdo->prepare("SELECT * FROM alternativas WHERE id_questao = ? ORDER BY id_alternativa");
    $stmt_alt->execute([$questao['id_questao']]);
    $alternativas = $stmt_alt->fetchAll(PDO::FETCH_ASSOC);
    
    $questoes_com_alternativas[] = [
        'questao' => $questao,
        'alternativas' => $alternativas
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Sequencial - <?= htmlspecialchars($assunto['nome']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.2em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .progress-bar {
            background: #e9ecef;
            height: 8px;
            margin: 20px 30px;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }

        .questao-container {
            padding: 30px;
            border-bottom: 2px solid #f8f9fa;
            margin-bottom: 20px;
        }

        .questao-container:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .questao-numero {
            background: #0056b3;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .questao-enunciado {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .alternativas {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .alternativa {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .alternativa:hover {
            border-color: #0056b3;
            background: #e3f2fd;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,86,179,0.2);
        }

        .alternativa input[type="radio"] {
            display: none;
        }

        .alternativa-texto {
            font-size: 1.1em;
            color: #495057;
            font-weight: 500;
        }

        .alternativa.correta {
            background: linear-gradient(135deg, #d1f2eb, #a3e4d7);
            border: 3px solid #28a745;
            color: #0d5d2b;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
            animation: pulseCorrect 0.6s ease-in-out;
        }

        .alternativa.incorreta {
            background: linear-gradient(135deg, #fadbd8, #f1948a);
            border: 3px solid #dc3545;
            color: #8b1538;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
            animation: pulseIncorrect 0.6s ease-in-out;
        }

        .alternativa.selecionada {
            border: 3px solid #0056b3;
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            transform: scale(1.02);
        }

        @keyframes pulseCorrect {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1.02); }
        }

        @keyframes pulseIncorrect {
            0% { transform: scale(1); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-3px); }
            100% { transform: translateX(0) scale(1.02); }
        }

        .feedback-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2em;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: iconBounce 0.8s ease-in-out;
        }

        @keyframes iconBounce {
            0% { transform: translateY(-50%) scale(0); }
            50% { transform: translateY(-50%) scale(1.3); }
            100% { transform: translateY(-50%) scale(1); }
        }

        .alternativa.correta .feedback-icon::before {
            content: "✅";
            filter: drop-shadow(0 0 8px rgba(40, 167, 69, 0.6));
        }

        .alternativa.incorreta .feedback-icon::before {
            content: "❌";
            filter: drop-shadow(0 0 8px rgba(220, 53, 69, 0.6));
        }

        .questao-respondida {
            opacity: 0.7;
            pointer-events: none;
        }

        .botoes-acao {
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 0 10px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0056b3, #007bff);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #004494, #0056b3);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,86,179,0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .estatisticas {
            background: #e3f2fd;
            padding: 20px;
            margin: 20px 30px;
            border-radius: 10px;
            text-align: center;
        }

        .estatisticas h3 {
            color: #0056b3;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 1.8em;
            font-weight: bold;
            color: #0056b3;
        }

        .stat-label {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
            
            .questao-container {
                padding: 20px;
            }
            
            .alternativa {
                padding: 15px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 1em;
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($assunto['nome']) ?></h1>
            <p>Quiz Sequencial - 
            <?php 
            switch($filtro) {
                case 'nao-respondidas': echo 'Questões Não Respondidas'; break;
                case 'respondidas': echo 'Questões Respondidas'; break;
                case 'corretas': echo 'Questões Corretas'; break;
                case 'incorretas': echo 'Questões Incorretas'; break;
                default: echo 'Todas as Questões'; break;
            }
            ?>
            </p>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>

        <div class="estatisticas" id="estatisticas" style="display: none;">
            <h3>Estatísticas da Sessão</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number" id="total-respondidas">0</div>
                    <div class="stat-label">Respondidas</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="total-acertos">0</div>
                    <div class="stat-label">Acertos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="total-erros">0</div>
                    <div class="stat-label">Erros</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number" id="percentual-acerto">0%</div>
                    <div class="stat-label">Aproveitamento</div>
                </div>
            </div>
        </div>

        <?php foreach ($questoes_com_alternativas as $index => $item): ?>
            <div class="questao-container" data-questao-id="<?= $item['questao']['id_questao'] ?>">
                <div class="questao-numero"><?= $index + 1 ?></div>
                <div class="questao-enunciado"><?= htmlspecialchars($item['questao']['enunciado']) ?></div>
                
                <div class="alternativas">
                    <?php foreach ($item['alternativas'] as $alternativa): ?>
                        <label class="alternativa" data-alternativa-id="<?= $alternativa['id_alternativa'] ?>" data-correta="<?= $alternativa['correta'] ?>">
                            <input type="radio" name="questao_<?= $item['questao']['id_questao'] ?>" value="<?= $alternativa['id_alternativa'] ?>">
                            <span class="alternativa-texto"><?= htmlspecialchars($alternativa['texto']) ?></span>
                            <span class="feedback-icon"></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="botoes-acao">
            <a href="listar_questoes.php?id=<?= $id_assunto ?>" class="btn btn-secondary">← Voltar à Lista</a>
            <button id="btn-finalizar" class="btn btn-primary" style="display: none;">Finalizar Quiz</button>
        </div>
    </div>

    <script>
        let totalQuestoes = <?= count($questoes_com_alternativas) ?>;
        let questoesRespondidas = 0;
        let acertos = 0;
        let erros = 0;

        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners para as alternativas
            document.querySelectorAll('.alternativa').forEach(alternativa => {
                alternativa.addEventListener('click', function() {
                    const questaoContainer = this.closest('.questao-container');
                    const questaoId = questaoContainer.dataset.questaoId;
                    const alternativaId = this.dataset.alternativaId;
                    const isCorreta = this.dataset.correta === '1';
                    
                    // Se já foi respondida, não faz nada
                    if (questaoContainer.classList.contains('questao-respondida')) {
                        return;
                    }
                    
                    // Marca como respondida
                    questaoContainer.classList.add('questao-respondida');
                    questoesRespondidas++;
                    
                    // Mostra feedback visual com delay para melhor experiência
                    const alternativasContainer = questaoContainer.querySelector('.alternativas');
                    const todasAlternativas = alternativasContainer.querySelectorAll('.alternativa');
                    
                    // Primeiro marca a selecionada
                    this.classList.add('selecionada');
                    
                    // Depois de um pequeno delay, mostra o feedback
                    setTimeout(() => {
                        todasAlternativas.forEach(alt => {
                            if (alt.dataset.correta === '1') {
                                alt.classList.add('correta');
                                // Adiciona ícone de feedback
                                if (!alt.querySelector('.feedback-icon')) {
                                    const icon = document.createElement('div');
                                    icon.className = 'feedback-icon';
                                    alt.appendChild(icon);
                                }
                            } else if (alt === this && !isCorreta) {
                                alt.classList.add('incorreta');
                                // Adiciona ícone de feedback
                                if (!alt.querySelector('.feedback-icon')) {
                                    const icon = document.createElement('div');
                                    icon.className = 'feedback-icon';
                                    alt.appendChild(icon);
                                }
                            }
                        });
                    }, 200);
                    
                    // Atualiza estatísticas
                    if (isCorreta) {
                        acertos++;
                    } else {
                        erros++;
                    }
                    
                    // Salva resposta no servidor
                    salvarResposta(questaoId, alternativaId, isCorreta);
                    
                    // Atualiza interface
                    atualizarProgresso();
                    atualizarEstatisticas();
                    
                    // Se todas foram respondidas, mostra botão finalizar
                    if (questoesRespondidas === totalQuestoes) {
                        document.getElementById('btn-finalizar').style.display = 'inline-block';
                    }
                });
            });
            
            // Event listener para finalizar
            document.getElementById('btn-finalizar').addEventListener('click', function() {
                window.location.href = `listar_questoes.php?id=<?= $id_assunto ?>&filtro=todas`;
            });
        });
        
        function salvarResposta(questaoId, alternativaId, acertou) {
            fetch('salvar_resposta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_questao=${questaoId}&id_alternativa=${alternativaId}&acertou=${acertou ? 1 : 0}`
            });
        }
        
        function atualizarProgresso() {
            const percentual = (questoesRespondidas / totalQuestoes) * 100;
            document.getElementById('progress-fill').style.width = percentual + '%';
        }
        
        function atualizarEstatisticas() {
            document.getElementById('estatisticas').style.display = 'block';
            document.getElementById('total-respondidas').textContent = questoesRespondidas;
            document.getElementById('total-acertos').textContent = acertos;
            document.getElementById('total-erros').textContent = erros;
            
            const percentualAcerto = questoesRespondidas > 0 ? Math.round((acertos / questoesRespondidas) * 100) : 0;
            document.getElementById('percentual-acerto').textContent = percentualAcerto + '%';
        }
    </script>
</body>
</html>