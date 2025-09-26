<?php
session_start();
require_once 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

// Verificar se foi passado o ID do assunto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id_assunto = (int)$_GET['id'];
$usuario_id = $_SESSION['id_usuario'];

try {
    // Buscar informações do assunto
    $stmt = $pdo->prepare("SELECT nome FROM assuntos WHERE id_assunto = ?");
    $stmt->execute([$id_assunto]);
    $assunto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assunto) {
        header('Location: index.php');
        exit();
    }
    
    // Buscar todas as questões do assunto
    $stmt = $pdo->prepare("SELECT id_questao, enunciado FROM questoes WHERE id_assunto = ? ORDER BY id_questao");
    $stmt->execute([$id_assunto]);
    $questoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar respostas do usuário para essas questões
    $stmt = $pdo->prepare("
        SELECT q.id_questao, r.acertou as correta 
        FROM questoes q 
        LEFT JOIN respostas_usuarios r ON q.id_questao = r.id_questao AND r.id_usuario = ?
        WHERE q.id_assunto = ?
    ");
    $stmt->execute([$usuario_id, $id_assunto]);
    $respostas_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar respostas por ID da questão
    $status_questoes = [];
    foreach ($respostas_usuario as $resposta) {
        $status_questoes[$resposta['id_questao']] = $resposta['correta'];
    }
    
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questões - <?= htmlspecialchars($assunto['nome']) ?></title>
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
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.3);
            border-radius: 15px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .header h1 {
            color: #0056b3;
            font-size: 2.2em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #6c757d;
            font-size: 1.1em;
        }

        .btn-voltar {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-voltar:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .filtros {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filtros button {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            color: #495057;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filtros button:hover,
        .filtros button.active {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            color: white;
            border-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3);
        }

        .estatisticas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .estatistica {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .estatistica:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .estatistica h3 {
            color: #0056b3;
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .estatistica p {
            color: #6c757d;
            font-weight: 600;
        }

        .questoes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .questao-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .questao-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .questao-card.respondida {
            border-color: #17a2b8;
            background: linear-gradient(135deg, #e8f4f8 0%, #d1ecf1 100%);
        }

        .questao-card.correta {
            border-color: #28a745;
            background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
        }

        .questao-card.incorreta {
            border-color: #dc3545;
            background: linear-gradient(135deg, #f8e8e8 0%, #f5c6cb 100%);
        }

        .questao-numero {
            font-size: 1.1em;
            font-weight: 700;
            color: #0056b3;
            margin-bottom: 10px;
        }

        .questao-texto {
            color: #495057;
            margin-bottom: 15px;
            line-height: 1.5;
            font-size: 1em;
        }

        .questao-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .status-badge.nao-respondida {
            background-color: #6c757d;
            color: white;
        }

        .status-badge.respondida {
            background-color: #17a2b8;
            color: white;
        }

        .status-badge.correta {
            background-color: #28a745;
            color: white;
        }

        .status-badge.incorreta {
            background-color: #dc3545;
            color: white;
        }

        .btn-responder {
            background: linear-gradient(135deg, #0056b3 0%, #003d82 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-responder:hover {
            background: linear-gradient(135deg, #003d82 0%, #002752 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.3);
            color: white;
            text-decoration: none;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 10px;
            }
            
            .questoes-grid {
                grid-template-columns: 1fr;
            }
            
            .estatisticas {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filtros {
                justify-content: center;
            }
            
            .filtros button {
                padding: 8px 16px;
                font-size: 0.9em;
            }
        }

        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .estatisticas {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-voltar">← Voltar aos Assuntos</a>
        
        <div class="header">
            <h1><?= htmlspecialchars($assunto['nome']) ?></h1>
            <p>Todas as questões disponíveis</p>
        </div>

        <div class="filtros">
            <button class="btn-filtro active" data-filtro="todas">Todas</button>
            <button class="btn-filtro" data-filtro="respondidas">Respondidas</button>
            <button class="btn-filtro" data-filtro="nao-respondidas">Não Respondidas</button>
            <button class="btn-filtro" data-filtro="corretas">Acertos</button>
            <button class="btn-filtro" data-filtro="incorretas">Erros</button>
        </div>
        
        <div class="estatisticas">
            <div class="estatistica">
                <h3 id="total-questoes"><?= count($questoes) ?></h3>
                <p>Total</p>
            </div>
            <div class="estatistica">
                <h3 id="questoes-respondidas">0</h3>
                <p>Respondidas</p>
            </div>
            <div class="estatistica">
                <h3 id="questoes-corretas">0</h3>
                <p>Acertos</p>
            </div>
            <div class="estatistica">
                <h3 id="questoes-incorretas">0</h3>
                <p>Erros</p>
            </div>
        </div>

        <div class="questoes-grid">
            <?php foreach ($questoes as $index => $questao): ?>
                <?php 
                $status = 'nao-respondida';
                $status_texto = 'Não Respondida';
                $status_classe = 'nao-respondida';
                
                if (isset($status_questoes[$questao['id_questao']])) {
                    if ($status_questoes[$questao['id_questao']] == 1) {
                        $status = 'correta';
                        $status_texto = 'Correta';
                        $status_classe = 'correta';
                    } else {
                        $status = 'incorreta';
                        $status_texto = 'Incorreta';
                        $status_classe = 'incorreta';
                    }
                }
                ?>
                
                <div class="questao-card <?= $status ?>" data-status="<?= $status ?>">
                    <div class="questao-numero">Questão <?= $index + 1 ?></div>
                    <div class="questao-texto"><?= htmlspecialchars($questao['enunciado']) ?></div>
                    <div class="questao-status">
                        <span class="status-badge <?= $status_classe ?>"><?= $status_texto ?></span>
                        <button type="button" class="btn-responder" onclick="abrirQuestao(<?= $questao['id_questao'] ?>)">
                            <?= $status == 'nao-respondida' ? 'Responder' : 'Ver Questão' ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Calcular estatísticas
        function calcularEstatisticas() {
            const cards = document.querySelectorAll('.questao-card');
            let respondidas = 0;
            let corretas = 0;
            let incorretas = 0;
            
            cards.forEach(card => {
                const status = card.dataset.status;
                if (status === 'correta') {
                    respondidas++;
                    corretas++;
                } else if (status === 'incorreta') {
                    respondidas++;
                    incorretas++;
                }
            });
            
            document.getElementById('questoes-respondidas').textContent = respondidas;
            document.getElementById('questoes-corretas').textContent = corretas;
            document.getElementById('questoes-incorretas').textContent = incorretas;
        }
        
        // Filtrar questões
        function filtrarQuestoes(filtro) {
            const cards = document.querySelectorAll('.questao-card');
            
            cards.forEach(card => {
                const status = card.dataset.status;
                let mostrar = false;
                
                switch(filtro) {
                    case 'todas':
                        mostrar = true;
                        break;
                    case 'respondidas':
                        mostrar = status === 'correta' || status === 'incorreta';
                        break;
                    case 'nao-respondidas':
                        mostrar = status === 'nao-respondida';
                        break;
                    case 'corretas':
                        mostrar = status === 'correta';
                        break;
                    case 'incorretas':
                        mostrar = status === 'incorreta';
                        break;
                }
                
                card.style.display = mostrar ? 'block' : 'none';
            });
        }
        
        // Event listeners para os filtros
        document.querySelectorAll('.btn-filtro').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remover classe active de todos os botões
                document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('active'));
                // Adicionar classe active ao botão clicado
                this.classList.add('active');
                // Salvar filtro ativo no localStorage
                localStorage.setItem('filtro_ativo', this.dataset.filtro);
                // Filtrar questões
                filtrarQuestoes(this.dataset.filtro);
            });
        });
        
        // Verificar se há um filtro na URL ou no localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const filtroUrl = urlParams.get('filtro');
            const filtroSalvo = localStorage.getItem('filtro_ativo');
            const filtroParaAplicar = filtroUrl || filtroSalvo || 'todas';
            
            // Aplicar o filtro
            const btnFiltro = document.querySelector(`[data-filtro="${filtroParaAplicar}"]`);
            if (btnFiltro) {
                document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('active'));
                btnFiltro.classList.add('active');
                filtrarQuestoes(filtroParaAplicar);
                localStorage.setItem('filtro_ativo', filtroParaAplicar);
            }
            
            // Função simplificada para abrir questão
            function abrirQuestao(idQuestao) {
                try {
                    console.log('Abrindo questão:', idQuestao);
                    
                    const filtroAtivo = localStorage.getItem('filtro_ativo') || 'todas';
                    const urlParams = new URLSearchParams(window.location.search);
                    const idAssunto = urlParams.get('id');
                    
                    console.log('Filtro ativo:', filtroAtivo);
                    console.log('ID Assunto:', idAssunto);
                    
                    let url;
                    
                    // Se há filtro ativo e não é "todas", vai para quiz sequencial
                    if (filtroAtivo && filtroAtivo !== 'todas') {
                        url = `quiz_sequencial.php?id=${idAssunto}&filtro=${filtroAtivo}&questao=${idQuestao}`;
                        console.log('Redirecionando para quiz sequencial:', url);
                    } else {
                        // Comportamento normal - vai para quiz individual
                        url = `quiz.php?id=${idQuestao}`;
                        console.log('Redirecionando para quiz individual:', url);
                    }
                    
                    // Redirecionar
                    window.location.href = url;
                    
                } catch (error) {
                    console.error('Erro ao abrir questão:', error);
                    alert('Erro ao abrir questão: ' + error.message);
                }
            }
            
            // Função para redirecionar questão baseada no filtro ativo (mantida para compatibilidade)
            function redirecionarQuestao(idQuestao, statusQuestao) {
                abrirQuestao(idQuestao);
            }
            
            // Calcular estatísticas
            calcularEstatisticas();
        });
    </script>
</body>
</html>