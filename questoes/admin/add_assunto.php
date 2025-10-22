<?php
session_start();

// Verifica se o usuário é um administrador logado.
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php'; // Caminho para o arquivo conexao.php

$mensagem_status = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_assunto = $_POST['tipo_assunto'] ?? '';
    $nome_assunto = trim($_POST['nome_assunto'] ?? '');
    
    // Campos específicos de concurso
    $concurso_ano = trim($_POST['concurso_ano'] ?? '');
    $concurso_banca = trim($_POST['concurso_banca'] ?? '');
    $concurso_orgao = trim($_POST['concurso_orgao'] ?? '');
    $concurso_prova = trim($_POST['concurso_prova'] ?? '');

    if (empty($tipo_assunto)) {
        $mensagem_status = 'error';
        $mensagem_texto = 'Por favor, selecione o tipo de conteúdo.';
    } elseif (empty($nome_assunto) && $tipo_assunto !== 'concurso') {
        $mensagem_status = 'error';
        $mensagem_texto = 'Por favor, digite o nome do conteúdo.';
    } elseif ($tipo_assunto === 'concurso' && (empty($concurso_ano) || empty($concurso_banca) || empty($concurso_orgao) || empty($concurso_prova))) {
        $mensagem_status = 'error';
        $mensagem_texto = 'Para concursos, preencha todos os campos obrigatórios (Ano, Banca, Órgão e Prova).';
    } else {
        try {
            // Construir nome final baseado no tipo
            if ($tipo_assunto === 'concurso') {
                $nome_final = "$concurso_ano - $concurso_banca - $concurso_orgao - $concurso_prova";
            } else {
                $nome_final = $nome_assunto;
            }
            
            // Verificar se já existe
            $stmt_check = $pdo->prepare("SELECT id_assunto FROM assuntos WHERE nome = ? AND tipo_assunto = ?");
            $stmt_check->execute([$nome_final, $tipo_assunto]);
            
            if ($stmt_check->fetch()) {
                $mensagem_status = 'error';
                $mensagem_texto = 'Já existe um conteúdo com este nome para este tipo.';
            } else {
                // Inserir o novo assunto
                if ($tipo_assunto === 'concurso') {
                    $stmt = $pdo->prepare("INSERT INTO assuntos (nome, tipo_assunto, concurso_ano, concurso_banca, concurso_orgao, concurso_prova) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome_final, $tipo_assunto, $concurso_ano, $concurso_banca, $concurso_orgao, $concurso_prova]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO assuntos (nome, tipo_assunto) VALUES (?, ?)");
                    $stmt->execute([$nome_final, $tipo_assunto]);
                }
                
            $mensagem_status = 'success';
                $tipo_display = ucfirst($tipo_assunto);
                $mensagem_texto = "$tipo_display '" . htmlspecialchars($nome_final) . '" adicionado com sucesso!';
            }
        } catch (Exception $e) {
            $mensagem_status = 'error';
            $mensagem_texto = 'Erro ao adicionar o conteúdo: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Conteúdo - Resumo Acadêmico</title>
    <link rel="icon" href="../../fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="../../fotos/minha-logo-apple.png">
    <link rel="stylesheet" href="../modern-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header {
            position: relative;
        }
        
        .header-nav {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 2;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="main-container fade-in">
        <header class="header">
            <div class="header-nav">
                <button onclick="goBack()" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
            </div>
            <div class="logo">
                <img src="../../fotos/Logotipo_resumo_academico.png" alt="Resumo Acadêmico">
            </div>
            <div class="title-section">
                <h1>Adicionar Novo Conteúdo</h1>
                <p class="subtitle">Preencha os dados do novo conteúdo</p>
            </div>
        </header>

        <div class="user-info">
            <a href="dashboard.php" class="btn btn-outline">Voltar ao Dashboard</a>
        </div>

        <main class="content">
            <?php if (!empty($mensagem_status)): ?>
                <div class="alert alert-<?= $mensagem_status ?> fade-in">
                    <?= htmlspecialchars($mensagem_texto) ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form action="add_assunto.php" method="post" class="modern-form" id="formAddAssunto">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="tipo_assunto">
                            <i class="fas fa-tag"></i> Tipo de Conteúdo
                        </label>
                        <select name="tipo_assunto" id="tipo_assunto" class="form-control" required onchange="toggleCamposConcurso()">
                            <option value="">Selecione o tipo de conteúdo</option>
                            <option value="tema">📚 Temas</option>
                            <option value="concurso">🏆 Concursos</option>
                            <option value="profissional">💼 Profissionais</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="grupo-nome-basico">
                        <label for="nome_assunto">
                            <i class="fas fa-book"></i> Nome do Conteúdo
                        </label>
                        <input type="text" id="nome_assunto" name="nome_assunto" class="form-control" placeholder="Digite o nome do conteúdo...">
                    </div>
                    
                    <!-- Campos específicos de concurso (inicialmente ocultos) -->
                    <div id="campos-concurso" style="display: none;">
                        <div class="form-group">
                            <label for="concurso_ano">
                                <i class="fas fa-calendar"></i> Ano
                            </label>
                            <input type="text" id="concurso_ano" name="concurso_ano" class="form-control" placeholder="Ex: 2024">
                        </div>
                        
                        <div class="form-group">
                            <label for="concurso_banca">
                                <i class="fas fa-university"></i> Banca Organizadora
                            </label>
                            <input type="text" id="concurso_banca" name="concurso_banca" class="form-control" placeholder="Ex: CESPE, FGV, VUNESP...">
                        </div>
                        
                        <div class="form-group">
                            <label for="concurso_orgao">
                                <i class="fas fa-building"></i> Órgão
                            </label>
                            <input type="text" id="concurso_orgao" name="concurso_orgao" class="form-control" placeholder="Ex: INSS, TRT, Polícia Civil...">
                        </div>
                        
                        <div class="form-group">
                            <label for="concurso_prova">
                                <i class="fas fa-file-alt"></i> Prova
                            </label>
                            <input type="text" id="concurso_prova" name="concurso_prova" class="form-control" placeholder="Ex: Prova Objetiva, Discursiva, Oral...">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="dashboard.php" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Conteúdo</button>
                    </div>
                </form>
            </div>
        </main>

        <footer class="footer">
            <p>Desenvolvido por Resumo Acadêmico &copy; 2025</p>
        </footer>
    </div>

    <script>
        // Função para voltar à página anterior
        function goBack() {
            // Verifica se há histórico de navegação
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // Se não há histórico, vai para a página principal
                window.location.href = '../../index.php';
            }
        }
        
        // Função para alternar campos de concurso
        function toggleCamposConcurso() {
            const tipoAssunto = document.getElementById('tipo_assunto').value;
            const camposConcurso = document.getElementById('campos-concurso');
            const grupoNomeBasico = document.getElementById('grupo-nome-basico');
            const nomeAssunto = document.getElementById('nome_assunto');
            
            if (tipoAssunto === 'concurso') {
                camposConcurso.style.display = 'block';
                grupoNomeBasico.style.display = 'none';
                
                // Tornar campos de concurso obrigatórios
                document.getElementById('concurso_ano').required = true;
                document.getElementById('concurso_banca').required = true;
                document.getElementById('concurso_orgao').required = true;
                document.getElementById('concurso_prova').required = true;
                nomeAssunto.required = false;
            } else {
                camposConcurso.style.display = 'none';
                grupoNomeBasico.style.display = 'block';
                
                // Tornar campo nome básico obrigatório
                nomeAssunto.required = true;
                document.getElementById('concurso_ano').required = false;
                document.getElementById('concurso_banca').required = false;
                document.getElementById('concurso_orgao').required = false;
                document.getElementById('concurso_prova').required = false;
            }
        }
        
        // Validação do formulário
        document.getElementById('formAddAssunto').addEventListener('submit', function(e) {
            const tipoAssunto = document.getElementById('tipo_assunto').value;
            
            if (tipoAssunto === 'concurso') {
                const ano = document.getElementById('concurso_ano').value.trim();
                const banca = document.getElementById('concurso_banca').value.trim();
                const orgao = document.getElementById('concurso_orgao').value.trim();
                const prova = document.getElementById('concurso_prova').value.trim();
                
                if (!ano || !banca || !orgao || !prova) {
                    e.preventDefault();
                    alert('Para concursos, preencha todos os campos obrigatórios (Ano, Banca, Órgão e Prova).');
                    return false;
                }
            }
        });
        
        // Inicializar estado dos campos
        document.addEventListener('DOMContentLoaded', function() {
            toggleCamposConcurso();
        });
    </script>
</body>
</html>