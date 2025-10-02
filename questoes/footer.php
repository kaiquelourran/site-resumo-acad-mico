<?php
// Arquivo footer.php - Rodapé moderno padronizado com o header
?>
    <footer class="footer-modern">
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
                            Plataforma educacional especializada em Terapia Ocupacional, 
                            oferecendo conteúdo de qualidade para estudantes e profissionais.
                        </p>
                    </div>

                    <!-- Links Rápidos -->
                    <div class="footer-section">
                        <h4 class="footer-section-title">
                            <span class="footer-section-icon">🔗</span>
                            Links Rápidos
                        </h4>
                        <ul class="footer-links">
                            <li><a href="index.php" class="footer-link">
                                <span class="footer-link-icon">🏠</span>
                                Página Inicial
                            </a></li>
                            <li><a href="escolher_assunto.php" class="footer-link">
                                <span class="footer-link-icon">📚</span>
                                Assuntos
                            </a></li>
                            <li><a href="../index.html" class="footer-link">
                                <span class="footer-link-icon">🌐</span>
                                Site Principal
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
                            <div class="footer-contact-item">
                                <span class="footer-contact-icon">📧</span>
                                <span class="footer-contact-text">contato@resumoacademico.com</span>
                            </div>
                            <div class="footer-contact-item">
                                <span class="footer-contact-icon">📱</span>
                                <span class="footer-contact-text">WhatsApp: (11) 99999-9999</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tecnologias -->
                    <div class="footer-section">
                        <h4 class="footer-section-title">
                            <span class="footer-section-icon">⚡</span>
                            Tecnologias
                        </h4>
                        <div class="footer-tech-stack">
                            <span class="footer-tech-item" title="PHP">🐘</span>
                            <span class="footer-tech-item" title="MySQL">🗄️</span>
                            <span class="footer-tech-item" title="JavaScript">⚡</span>
                            <span class="footer-tech-item" title="CSS3">🎨</span>
                            <span class="footer-tech-item" title="HTML5">📄</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rodapé Inferior -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="footer-copyright">
                        <span class="copyright-icon">©</span>
                        <span class="copyright-text">
                            <?php echo date('Y'); ?> Resumo Acadêmico. Todos os direitos reservados.
                        </span>
                    </div>
                    <div class="footer-credits">
                        <span class="credits-text">Desenvolvido com</span>
                        <span class="credits-heart">❤️</span>
                        <span class="credits-text">para educação</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Funcionalidades do Footer Moderno
        document.addEventListener('DOMContentLoaded', function() {
            // Animação de entrada para elementos do footer
            const footerElements = document.querySelectorAll('.footer-section, .footer-brand, .footer-bottom');
            
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            footerElements.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(element);
            });

            // Efeitos hover para links do footer
            const footerLinks = document.querySelectorAll('.footer-link');
            footerLinks.forEach(link => {
                link.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.querySelector('.footer-link-icon').style.transform = 'scale(1.2)';
                });
                
                link.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                    this.querySelector('.footer-link-icon').style.transform = 'scale(1)';
                });
            });

            // Animação para tecnologias
            const techItems = document.querySelectorAll('.footer-tech-item');
            techItems.forEach((item, index) => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.3) rotate(10deg)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1) rotate(0deg)';
                });

                // Animação sequencial ao carregar
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1)';
                }, index * 100);
            });

            // Efeito pulsante no coração
            const heart = document.querySelector('.credits-heart');
            if (heart) {
                setInterval(() => {
                    heart.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        heart.style.transform = 'scale(1)';
                    }, 200);
                }, 2000);
            }
        });
    </script>
    </main> <!-- Fechamento da tag main aberta no header.php -->
</div> <!-- Fechamento da div main-container que foi aberta no header.php -->