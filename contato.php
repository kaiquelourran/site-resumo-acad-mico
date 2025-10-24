<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - Resumo Acadêmico</title>
    <meta name="description" content="Entre em contato com o Resumo Acadêmico. Envie suas dúvidas, sugestões ou solicite informações sobre nosso sistema de questões.">
    <link rel="icon" href="fotos/Logotipo_resumo_academico.png" type="image/png">
    <link rel="apple-touch-icon" href="fotos/minha-logo-apple.png">
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17492102079"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'AW-17492102079');
    </script>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- CABEÇALHO ATUALIZADO -->
    <header>
        <h1>Resumo Acadêmico - Terapia Ocupacional</h1>
        <p>Sistema interativo de questões para estudantes e profissionais de Terapia Ocupacional</p>
        
        <!-- Menu de Navegação -->
        <nav class="menu" id="menu">
            <ul>
                <li><a href="index.html">🏠 HOME</a></li>
                <li><a href="sobre_nos.php">👥 SOBRE</a></li>
                <li><a href="questoes/index.php">📚 QUESTÕES</a></li>
                <li><a href="origem_to.html">📖 ORIGEM TO</a></li>
                <li><a href="contato.php">📞 CONTATO</a></li>
            </ul>
        </nav>
        
        <!-- Botão Hambúrguer para Mobile -->
        <button class="menu-toggle" id="menuToggle">
            <span>☰</span>
        </button>
    </header>

    <!-- CONTEÚDO PRINCIPAL -->
    <main>
        <article>
            <h2>📞 Entre em Contato Conosco</h2>
            <p style="text-align: center; margin-bottom: 40px; color: #666;">
                Tem dúvidas, sugestões ou precisa de ajuda? Estamos aqui para ajudar você!
            </p>

            <!-- MENSAGEM DE STATUS -->
            <?php
            session_start();
            if (isset($_SESSION['mensagem_contato'])) {
                $tipo = $_SESSION['mensagem_tipo'] ?? 'success';
                $cor = $tipo === 'success' ? '#10b981' : '#ef4444';
                $icone = $tipo === 'success' ? '✅' : '❌';
                echo "<div style='background: " . ($tipo === 'success' ? '#f0fdf4' : '#fef2f2') . "; 
                                  border-left: 4px solid $cor; 
                                  padding: 20px; 
                                  border-radius: 8px; 
                                  margin-bottom: 30px;'>
                        <strong style='color: $cor;'>$icone " . $_SESSION['mensagem_contato'] . "</strong>
                      </div>";
                unset($_SESSION['mensagem_contato']);
                unset($_SESSION['mensagem_tipo']);
            }
            ?>

            <div class="contact-grid">
                <!-- FORMULÁRIO DE CONTATO -->
                <div class="contact-form-container">
                    <h3>✉️ Envie sua Mensagem</h3>
                    <form method="POST" action="processar_contato.php" class="contact-form">
                        <div class="form-group">
                            <label for="nome">Nome Completo *</label>
                            <input type="text" id="nome" name="nome" required 
                                   placeholder="Seu nome completo" 
                                   value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="seu@email.com" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="assunto">Assunto *</label>
                            <select id="assunto" name="assunto" required>
                                <option value="">Selecione um assunto</option>
                                <option value="duvida" <?php echo ($_POST['assunto'] ?? '') === 'duvida' ? 'selected' : ''; ?>>Dúvida sobre o Sistema</option>
                                <option value="sugestao" <?php echo ($_POST['assunto'] ?? '') === 'sugestao' ? 'selected' : ''; ?>>Sugestão de Melhoria</option>
                                <option value="problema" <?php echo ($_POST['assunto'] ?? '') === 'problema' ? 'selected' : ''; ?>>Reportar Problema</option>
                                <option value="parceria" <?php echo ($_POST['assunto'] ?? '') === 'parceria' ? 'selected' : ''; ?>>Proposta de Parceria</option>
                                <option value="outro" <?php echo ($_POST['assunto'] ?? '') === 'outro' ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="mensagem">Mensagem *</label>
                            <textarea id="mensagem" name="mensagem" required rows="5" 
                                      placeholder="Descreva sua dúvida, sugestão ou problema..."><?php echo htmlspecialchars($_POST['mensagem'] ?? ''); ?></textarea>
                        </div>

                        <button type="submit" class="submit-btn">
                            <span>📤</span>
                            Enviar Mensagem
                        </button>
                    </form>
                </div>

                <!-- INFORMAÇÕES DE CONTATO -->
                <div class="contact-info">
                    <h3>📋 Informações de Contato</h3>
                    
                    <div class="contact-item">
                        <div class="contact-icon">📧</div>
                        <div class="contact-details">
                            <h4>E-mail</h4>
                            <p>contato@resumoacademico.com</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">⏰</div>
                        <div class="contact-details">
                            <h4>Horário de Atendimento</h4>
                            <p>Segunda a Sexta: 9h às 18h<br>Sábado: 9h às 12h</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon">⚡</div>
                        <div class="contact-details">
                            <h4>Tempo de Resposta</h4>
                            <p>Até 24 horas em dias úteis</p>
                        </div>
                    </div>


                    <!-- FAQ RÁPIDO -->
                    <div class="faq-section">
                        <h4>❓ Perguntas Frequentes</h4>
                        <div class="faq-item">
                            <strong>Como acessar o sistema de questões?</strong>
                            <p>Clique em "QUESTÕES" no menu ou <a href="questoes/index.php">acesse aqui</a>.</p>
                        </div>
                        <div class="faq-item">
                            <strong>O sistema é gratuito?</strong>
                            <p>Sim! Nosso sistema é completamente gratuito para estudantes e profissionais.</p>
                        </div>
                        <div class="faq-item">
                            <strong>Posso sugerir novos temas?</strong>
                            <p>Claro! Use o formulário ao lado com o assunto "Sugestão de Melhoria".</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA FINAL -->
            <div style="text-align: center; margin-top: 40px; padding: 30px; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); border-radius: 12px;">
                <h3>🚀 Pronto para Começar?</h3>
                <p style="margin-bottom: 20px;">Acesse nosso sistema de questões e teste seus conhecimentos em Terapia Ocupacional!</p>
                <a href="questoes/index.php" class="botao-quiz">
                    <span class="btn-icon">📚</span>
                    <span class="btn-text">Acessar Sistema de Questões</span>
                </a>
            </div>
        </article>
    </main>

    <!-- RODAPÉ ATUALIZADO -->
    <footer>
        <div class="footer-container">
            <!-- Seção Principal do Footer -->
            <div class="footer-main">
                <div class="footer-content">
                    <!-- Branding do Footer -->
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <span class="footer-logo-icon">🎓</span>
                            <div class="footer-brand-text">
                                <h3 class="footer-brand-title">Resumo Acadêmico</h3>
                                <span class="footer-brand-subtitle">Terapia Ocupacional</span>
                            </div>
                        </div>
                        <p class="footer-description">
                            Sistema educacional especializado em Terapia Ocupacional, 
                            oferecendo banco de questões de qualidade para estudantes e profissionais.
                        </p>
                    </div>

                    <!-- Links Rápidos -->
                    <div class="footer-section">
                        <h4 class="footer-section-title">
                            <span class="footer-section-icon">🔗</span>
                            Links Rápidos
                        </h4>
                        <ul class="footer-links">
                            <li><a href="index.html" class="footer-link">
                                <span class="footer-link-icon">🏠</span>
                                Página Inicial
                            </a></li>
                            <li><a href="questoes/index.php" class="footer-link">
                                <span class="footer-link-icon">📚</span>
                                Sistema de Questões
                            </a></li>
                            <li><a href="origem_to.html" class="footer-link">
                                <span class="footer-link-icon">📖</span>
                                Origem da TO
                            </a></li>
                        </ul>
                    </div>

                    <!-- Informações de Contato -->
                    <div class="footer-section">
                        <h4 class="footer-section-title">
                            <span class="footer-section-icon">📞</span>
                            Contato
                        </h4>
                        <div class="footer-contact">
                            <div class="footer-contact-intro">
                                Entre em contato conosco para dúvidas, sugestões ou parcerias.
                            </div>
                            <div class="footer-contact-item animated-contact">
                                <a href="contato.php" class="footer-contact-link">
                                    <span class="footer-contact-icon">✉️</span>
                                    <span class="footer-contact-text">Enviar Mensagem</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Links Legais -->
            <div class="footer-legal">
                <div class="footer-legal-content">
                    <div class="footer-legal-links">
                        <a href="politica_privacidade.php" class="footer-legal-link">Política de Privacidade</a>
                        <a href="sobre_nos.php" class="footer-legal-link">Sobre Nós</a>
                        <a href="contato.php" class="footer-legal-link">Contato</a>
                    </div>
                    <div class="footer-legal-text">
                        <p>© 2024 Resumo Acadêmico. Todos os direitos reservados.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Banner de Consentimento de Cookies (LGPD) -->
    <div id="cookie-banner" class="cookie-banner" style="display: none;">
        <div class="cookie-content">
            <p>🍪 Este site utiliza cookies para melhorar sua experiência e exibir publicidade relevante. Ao continuar navegando, você concorda com nossa <a href="politica_privacidade.php">Política de Privacidade</a>.</p>
            <button id="accept-cookies" class="cookie-accept-btn">Aceitar Cookies</button>
        </div>
    </div>

    <!-- Scripts -->
    <script>
    // Verificar se o cookie de aceitação já existe
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }

    // Criar cookie de aceitação
    function setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = `expires=${date.toUTCString()}`;
        document.cookie = `${name}=${value};${expires};path=/`;
    }

    // Verificar e exibir banner
    window.addEventListener('DOMContentLoaded', function() {
        if (!getCookie('cookies_aceitos')) {
            document.getElementById('cookie-banner').style.display = 'block';
        }
    });

    // Aceitar cookies
    document.addEventListener('DOMContentLoaded', function() {
        const acceptBtn = document.getElementById('accept-cookies');
        if (acceptBtn) {
            acceptBtn.addEventListener('click', function() {
                setCookie('cookies_aceitos', 'true', 365);
                document.getElementById('cookie-banner').style.display = 'none';
            });
        }
    });

    // Menu Hambúrguer - Mobile
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('menuToggle');
        const menu = document.getElementById('menu');
        
        // Criar overlay
        const overlay = document.createElement('div');
        overlay.className = 'menu-overlay';
        document.body.appendChild(overlay);
        
        // Toggle menu
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                menu.classList.toggle('active');
                overlay.classList.toggle('active');
                this.querySelector('span').textContent = menu.classList.contains('active') ? '✕' : '☰';
            });
        }
        
        // Fechar ao clicar no overlay
        overlay.addEventListener('click', function() {
            menu.classList.remove('active');
            overlay.classList.remove('active');
            if (menuToggle) {
                menuToggle.querySelector('span').textContent = '☰';
            }
        });
        
        // Fechar ao clicar em um link
        const menuLinks = menu.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                menu.classList.remove('active');
                overlay.classList.remove('active');
                if (menuToggle) {
                    menuToggle.querySelector('span').textContent = '☰';
                }
            });
        });
    });
    </script>

    <!-- CSS específico para a página de contato -->
    <style>
    .contact-form-container {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }

    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 0.95em;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1em;
        transition: all 0.3s ease;
        background: #fff;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #0072FF;
        box-shadow: 0 0 0 3px rgba(0, 114, 255, 0.1);
    }

    .submit-btn {
        background: linear-gradient(135deg, #0072FF 0%, #00C6FF 100%);
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 8px;
        font-size: 1.1em;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 114, 255, 0.3);
    }

    .contact-info {
        background: #f8fafc;
        padding: 30px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 25px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .contact-icon {
        font-size: 1.5em;
        min-width: 40px;
        text-align: center;
    }

    .contact-details h4 {
        color: #374151;
        margin-bottom: 5px;
        font-size: 1.1em;
    }

    .contact-details p {
        color: #6b7280;
        margin: 0;
        line-height: 1.5;
    }

    .faq-section {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #e2e8f0;
    }

    .faq-section h4 {
        color: #374151;
        margin-bottom: 15px;
        font-size: 1.1em;
    }

    .faq-item {
        margin-bottom: 15px;
        padding: 10px 0;
    }

    .faq-item strong {
        color: #374151;
        display: block;
        margin-bottom: 5px;
    }

    .faq-item p {
        color: #6b7280;
        margin: 0;
        font-size: 0.95em;
    }

    .faq-item a {
        color: #0072FF;
        text-decoration: none;
    }

    .faq-item a:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .contact-form-container,
        .contact-info {
            padding: 20px;
        }
        
        .contact-item {
            flex-direction: column;
            text-align: center;
        }
        
        .contact-icon {
            margin-bottom: 10px;
        }
    }
    </style>
</body>
</html>