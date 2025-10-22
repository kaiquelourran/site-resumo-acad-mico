<?php
// Arquivo footer.php - Rodapé moderno padronizado com o header
?>
<style>
/* Footer Modern - Paleta Azul Resumo Acadêmico */
.footer-modern { margin-top: 40px; }
.footer-modern .footer-container {
  max-width: 1100px;
  margin: 0 auto;
  background: #FFFFFF;
  border-radius: 16px;
  border: 1px solid #e9eef3;
  box-shadow: 0 10px 24px rgba(0,0,0,0.08);
  padding: 22px;
  position: relative;
}
.footer-modern .footer-container::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
  background: linear-gradient(90deg, #00C6FF 0%, #0072FF 100%);
}

/* Layout principal */
.footer-modern .footer-main { margin-bottom: 16px; }
.footer-modern .footer-content {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr 1fr;
  gap: 24px;
  align-items: start;
}

/* Branding */
.footer-modern .footer-brand { display: flex; flex-direction: column; gap: 12px; }
.footer-modern .footer-logo { display: flex; align-items: center; gap: 12px; }
.footer-modern .footer-logo-icon { font-size: 1.6rem; }
.footer-modern .footer-brand-title { margin: 0; font-size: 1.2rem; color: #222; }
.footer-modern .footer-brand-subtitle { color: #666; font-size: 0.95rem; }
.footer-modern .footer-description { color: #666; margin: 6px 0 0; }

/* Seções e títulos */
.footer-modern .footer-section-title { display: flex; align-items: center; gap: 8px; margin: 0 0 8px; color: #222; font-size: 1.05rem; }
.footer-modern .footer-section-icon { font-size: 1.05rem; }

/* Links do footer */
.footer-modern .footer-links { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
.footer-modern .footer-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #0072FF;
  text-decoration: none;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 8px;
  transition: background-color .2s ease, color .2s ease, transform .2s ease;
}
.footer-modern .footer-link:hover { background-color: rgba(0,114,255,0.08); transform: translateX(4px); }
.footer-modern .footer-link:focus { outline: 3px solid rgba(0,114,255,0.35); outline-offset: 2px; }
.footer-modern .footer-link-icon { font-size: 1rem; }

/* Contato */
.footer-modern .footer-contact { display: flex; flex-direction: column; gap: 8px; }
.footer-modern .footer-contact-item { display: flex; align-items: center; gap: 8px; color: #666; }
.footer-modern .footer-contact-icon { font-size: 1rem; color: #0072FF; }
.footer-modern .footer-contact-text { font-size: 0.95rem; }

/* Estilos animados para contatos da Cleice */
.footer-modern .footer-contact-intro {
    color: #333;
    font-size: 0.9rem;
    margin-bottom: 12px;
    font-style: italic;
    text-align: center;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    background: linear-gradient(45deg, #0072FF, #00C6FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    opacity: 0.9;
}

.footer-modern .footer-contact-item.animated-contact {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 8px;
}

.footer-modern .footer-contact-item.animated-contact:hover {
    background: linear-gradient(135deg, rgba(0, 198, 255, 0.1), rgba(0, 114, 255, 0.1));
    border-color: rgba(0, 198, 255, 0.3);
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0, 198, 255, 0.2);
}

.footer-modern .footer-contact-link {
    color: #0072FF;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.footer-modern .footer-contact-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s ease;
}

.footer-modern .footer-contact-link:hover::before {
    left: 100%;
}

.footer-modern .footer-contact-link:hover {
    color: #00C6FF;
    text-shadow: 0 0 8px rgba(0, 198, 255, 0.5);
}

.footer-modern .footer-contact-item.animated-contact .footer-contact-icon {
    font-size: 1.2rem;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.footer-modern .footer-contact-item.animated-contact:hover .footer-contact-icon {
    transform: scale(1.2) rotate(5deg);
    animation: bounce 0.6s ease;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: scale(1.2) rotate(5deg) translateY(0); }
    40% { transform: scale(1.3) rotate(10deg) translateY(-3px); }
    60% { transform: scale(1.25) rotate(7deg) translateY(-1px); }
}

/* Tecnologias */
.footer-modern .footer-tech-stack { display: flex; align-items: center; gap: 10px; }
.footer-modern .footer-tech-item {
  width: 36px; height: 36px;
  display: inline-flex; align-items: center; justify-content: center;
  background: #f4f7fb; border: 1px solid #e9eef3; color: #0072FF;
  border-radius: 10px;
  transition: transform .2s ease, background-color .2s ease;
}
.footer-modern .footer-tech-item:hover { transform: scale(1.08); background-color: #eaf3ff; }

/* Rodapé inferior */
.footer-modern .footer-bottom {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-top: 12px; padding-top: 12px;
  border-top: 1px solid #f0f2f5; color: #666; font-size: 0.95rem;
}
.footer-modern .footer-bottom-content { display: flex; align-items: center; justify-content: space-between; width: 100%; }
.footer-modern .footer-credits { display: inline-flex; align-items: center; gap: 6px; }
.footer-modern .credits-heart { color: #dc3545; display: inline-block; transition: transform .2s ease; }

/* Responsividade */
@media (max-width: 992px) {
  .footer-modern .footer-content { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 768px) {
  .footer-modern .footer-container { padding: 18px; }
  .footer-modern .footer-content { grid-template-columns: 1fr; gap: 16px; }
  .footer-modern .footer-bottom { flex-direction: column; align-items: flex-start; gap: 8px; }
}
</style>
 
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
                                Conteúdos
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
                            <span class="footer-section-icon">💬</span>
                            Contato
                        </h4>
                        <div class="footer-contact">
                            <p class="footer-contact-intro">
                                Para entrar em contato, fale com a nossa embaixadora.
                            </p>
                            <div class="footer-contact-item animated-contact">
                                <span class="footer-contact-icon">📧</span>
                                <a href="mailto:Cleicevitoria02@gmail.com" class="footer-contact-link">
                                    Cleicevitoria02@gmail.com
                                </a>
                            </div>
                            <div class="footer-contact-item animated-contact">
                                <span class="footer-contact-icon">📸</span>
                                <a href="https://www.instagram.com/cleice.santtana?igsh=ZmQ2bHExeTh0YWJ5&utm_source=qr" 
                                   target="_blank" class="footer-contact-link">
                                    @cleice.santtana
                                </a>
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