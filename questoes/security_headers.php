<?php
/**
 * Headers de Segurança Modernos
 * Substitui headers obsoletos por versões atualizadas
 */

// Content Security Policy (CSP) - Protege contra XSS
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://pagead2.googlesyndication.com https://accounts.google.com https://apis.google.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https://www.google-analytics.com https://accounts.google.com; frame-src 'self' https://accounts.google.com;");

// X-Content-Type-Options - Previne MIME sniffing
header('X-Content-Type-Options: nosniff');

// X-Frame-Options - Protege contra clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Referrer-Policy - Controla informações de referrer
header('Referrer-Policy: strict-origin-when-cross-origin');

// Permissions-Policy - Controla features do navegador
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Strict-Transport-Security (apenas se HTTPS estiver ativo)
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// NOTA: X-XSS-Protection foi REMOVIDO pois está obsoleto
// Navegadores modernos usam CSP ao invés disso
?>

