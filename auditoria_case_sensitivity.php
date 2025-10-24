<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria Case-Sensitivity - Hostinger</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Courier New', monospace; 
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: #252526;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        h1 { 
            color: #4EC9B0; 
            margin-bottom: 30px;
            text-align: center;
            font-size: 2em;
            text-shadow: 0 0 10px rgba(78, 201, 176, 0.5);
        }
        h2 { 
            color: #569CD6; 
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #569CD6;
        }
        .ok { 
            background: #1e3a1e; 
            color: #4EC9B0; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #4EC9B0;
        }
        .erro { 
            background: #3a1e1e; 
            color: #f48771; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #f48771;
        }
        .aviso { 
            background: #3a3a1e; 
            color: #dcdcaa; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dcdcaa;
        }
        .info { 
            background: #1e2a3a; 
            color: #9cdcfe; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #9cdcfe;
        }
        pre { 
            background: #1e1e1e; 
            padding: 15px; 
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9em;
            border: 1px solid #3e3e42;
            color: #d4d4d4;
        }
        .badge { 
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }
        .badge-success { background: #4EC9B0; color: #1e1e1e; }
        .badge-danger { background: #f48771; color: #1e1e1e; }
        .badge-warning { background: #dcdcaa; color: #1e1e1e; }
        .badge-info { background: #9cdcfe; color: #1e1e1e; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #3e3e42;
        }
        th {
            background: #1e1e1e;
            color: #4EC9B0;
            font-weight: bold;
        }
        tr:hover {
            background: #2d2d30;
        }
        .command {
            background: #0e639c;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Auditoria Case-Sensitivity (Linux/Hostinger)</h1>

        <?php
        // TESTE 1: Verificar arquivos CSS
        echo '<h2>1. 📄 Auditoria de Arquivos CSS</h2>';
        
        $arquivos_css = glob('*.css');
        if (!empty($arquivos_css)) {
            echo '<div class="info">';
            echo '<p><strong>Arquivos CSS encontrados:</strong></p>';
            echo '<table>';
            echo '<tr><th>Arquivo</th><th>Case-Sensitive OK?</th><th>Tamanho</th></tr>';
            foreach ($arquivos_css as $css) {
                $lowercase = strtolower($css) === $css;
                $size = filesize($css);
                $status = $lowercase ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ PROBLEMA</span>';
                echo '<tr><td>' . htmlspecialchars($css) . '</td><td>' . $status . '</td><td>' . number_format($size / 1024, 2) . ' KB</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        } else {
            echo '<div class="erro"><p>❌ Nenhum arquivo CSS encontrado na raiz!</p></div>';
        }

        // TESTE 2: Verificar pastas críticas
        echo '<h2>2. 📁 Auditoria de Pastas</h2>';
        
        $pastas_criticas = ['fotos', 'questoes', 'videos', 'apostilas'];
        echo '<div class="info">';
        echo '<table>';
        echo '<tr><th>Pasta</th><th>Existe?</th><th>Case OK?</th><th>Permissões</th></tr>';
        
        foreach ($pastas_criticas as $pasta) {
            $existe = is_dir($pasta);
            $lowercase = strtolower($pasta) === $pasta;
            
            if ($existe) {
                $perms = substr(sprintf('%o', fileperms($pasta)), -4);
                $existe_badge = '<span class="badge badge-success">✅ SIM</span>';
                $case_badge = $lowercase ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ PROBLEMA</span>';
                $perms_text = $perms;
            } else {
                $existe_badge = '<span class="badge badge-danger">❌ NÃO</span>';
                $case_badge = '<span class="badge badge-warning">N/A</span>';
                $perms_text = 'N/A';
            }
            
            echo '<tr><td>' . htmlspecialchars($pasta) . '</td><td>' . $existe_badge . '</td><td>' . $case_badge . '</td><td>' . $perms_text . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';

        // TESTE 3: Verificar referências em index.html
        echo '<h2>3. 🔗 Auditoria de Referências (index.html)</h2>';
        
        if (file_exists('index.html')) {
            $conteudo = file_get_contents('index.html');
            
            // Buscar referências a CSS
            preg_match_all('/href=["\'](.*?\.css)["\']/', $conteudo, $matches_css);
            preg_match_all('/src=["\'](.*?\.js)["\']/', $conteudo, $matches_js);
            preg_match_all('/href=["\'](.*?\.html)["\']/', $conteudo, $matches_html);
            preg_match_all('/href=["\'](.*?\.php)["\']/', $conteudo, $matches_php);
            
            echo '<div class="info">';
            echo '<p><strong>Referências CSS:</strong></p>';
            if (!empty($matches_css[1])) {
                echo '<ul>';
                foreach ($matches_css[1] as $ref) {
                    $existe = file_exists($ref);
                    $status = $existe ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ NÃO ENCONTRADO</span>';
                    echo '<li>' . htmlspecialchars($ref) . ' ' . $status . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="aviso">⚠️ Nenhuma referência CSS encontrada!</p>';
            }
            
            echo '<p><strong>Referências JS:</strong></p>';
            if (!empty($matches_js[1])) {
                echo '<ul>';
                foreach ($matches_js[1] as $ref) {
                    // Ignorar URLs externas
                    if (strpos($ref, 'http') !== 0 && strpos($ref, '//') !== 0) {
                        $existe = file_exists($ref);
                        $status = $existe ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ NÃO ENCONTRADO</span>';
                        echo '<li>' . htmlspecialchars($ref) . ' ' . $status . '</li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<p>Nenhuma referência JS local encontrada.</p>';
            }
            
            echo '<p><strong>Referências HTML:</strong></p>';
            if (!empty($matches_html[1])) {
                echo '<ul>';
                foreach ($matches_html[1] as $ref) {
                    $existe = file_exists($ref);
                    $status = $existe ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ NÃO ENCONTRADO</span>';
                    echo '<li>' . htmlspecialchars($ref) . ' ' . $status . '</li>';
                }
                echo '</ul>';
            }
            
            echo '<p><strong>Referências PHP:</strong></p>';
            if (!empty($matches_php[1])) {
                echo '<ul>';
                foreach ($matches_php[1] as $ref) {
                    $existe = file_exists($ref);
                    $status = $existe ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ NÃO ENCONTRADO</span>';
                    echo '<li>' . htmlspecialchars($ref) . ' ' . $status . '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="erro"><p>❌ Arquivo index.html não encontrado!</p></div>';
        }

        // TESTE 4: Verificar arquivos na pasta fotos
        echo '<h2>4. 🖼️ Auditoria de Imagens (pasta fotos)</h2>';
        
        if (is_dir('fotos')) {
            $imagens = glob('fotos/*');
            echo '<div class="info">';
            echo '<p><strong>Arquivos encontrados:</strong></p>';
            echo '<table>';
            echo '<tr><th>Arquivo</th><th>Case OK?</th><th>Tamanho</th></tr>';
            foreach ($imagens as $img) {
                $basename = basename($img);
                $lowercase = strtolower($basename) === $basename;
                $size = filesize($img);
                $status = $lowercase ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-warning">⚠️ ATENÇÃO</span>';
                echo '<tr><td>' . htmlspecialchars($basename) . '</td><td>' . $status . '</td><td>' . number_format($size / 1024, 2) . ' KB</td></tr>';
            }
            echo '</table>';
            echo '</div>';
        } else {
            echo '<div class="erro"><p>❌ Pasta fotos não encontrada!</p></div>';
        }

        // TESTE 5: Verificar arquivos PHP críticos
        echo '<h2>5. ⚙️ Auditoria de Arquivos PHP</h2>';
        
        $arquivos_php_criticos = [
            'buscar_temas.php',
            'contato.php',
            'sobre_nos.php',
            'politica_privacidade.php',
            'init_session.php',
            'processar_contato.php',
            'questoes/conexao.php',
            'questoes/index.php'
        ];
        
        echo '<div class="info">';
        echo '<table>';
        echo '<tr><th>Arquivo</th><th>Existe?</th><th>Case OK?</th><th>Permissões</th></tr>';
        
        foreach ($arquivos_php_criticos as $php) {
            $existe = file_exists($php);
            $basename = basename($php);
            $lowercase = strtolower($basename) === $basename;
            
            if ($existe) {
                $perms = substr(sprintf('%o', fileperms($php)), -4);
                $existe_badge = '<span class="badge badge-success">✅ SIM</span>';
                $case_badge = $lowercase ? '<span class="badge badge-success">✅ OK</span>' : '<span class="badge badge-danger">❌ PROBLEMA</span>';
                $perms_text = $perms;
            } else {
                $existe_badge = '<span class="badge badge-danger">❌ NÃO</span>';
                $case_badge = '<span class="badge badge-warning">N/A</span>';
                $perms_text = 'N/A';
            }
            
            echo '<tr><td>' . htmlspecialchars($php) . '</td><td>' . $existe_badge . '</td><td>' . $case_badge . '</td><td>' . $perms_text . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';

        // TESTE 6: Comandos de correção
        echo '<h2>6. 🛠️ Comandos de Correção (se necessário)</h2>';
        
        echo '<div class="aviso">';
        echo '<p><strong>Se houver arquivos com case incorreto, use estes comandos no servidor Linux:</strong></p>';
        echo '<div class="command">';
        echo '<p># Renomear arquivo CSS para minúsculas (exemplo):</p>';
        echo '<code>mv Style.css style.css</code>';
        echo '</div>';
        
        echo '<div class="command">';
        echo '<p># Renomear pasta para minúsculas (exemplo):</p>';
        echo '<code>mv Fotos fotos</code>';
        echo '</div>';
        
        echo '<div class="command">';
        echo '<p># Verificar permissões:</p>';
        echo '<code>chmod 644 *.css *.html *.php</code><br>';
        echo '<code>chmod 755 */</code>';
        echo '</div>';
        echo '</div>';

        // TESTE 7: Diagnóstico Final
        echo '<h2>7. 🎯 Diagnóstico Final e Recomendações</h2>';
        
        $problemas = [];
        
        // Verificar se style.css existe
        if (!file_exists('style.css')) {
            $problemas[] = '❌ Arquivo style.css não encontrado!';
        }
        
        // Verificar se há arquivos com maiúsculas
        foreach ($arquivos_css as $css) {
            if (strtolower($css) !== $css) {
                $problemas[] = '⚠️ Arquivo CSS com maiúsculas: ' . $css;
            }
        }
        
        if (empty($problemas)) {
            echo '<div class="ok">';
            echo '<h3>✅ SISTEMA OK!</h3>';
            echo '<p>Todos os arquivos parecem estar com nomenclatura correta.</p>';
            echo '<p><strong>Próximos passos:</strong></p>';
            echo '<ul>';
            echo '<li>Fazer upload dos arquivos para Hostinger</li>';
            echo '<li>Testar o site em https://www.resumoacademico.com/</li>';
            echo '<li>Verificar se CSS está carregando (Ctrl+Shift+I → Network)</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            echo '<div class="erro">';
            echo '<h3>❌ PROBLEMAS ENCONTRADOS:</h3>';
            echo '<ul>';
            foreach ($problemas as $problema) {
                echo '<li>' . $problema . '</li>';
            }
            echo '</ul>';
            echo '<p><strong>Ação necessária:</strong> Corrija os problemas acima antes de fazer upload para Hostinger.</p>';
            echo '</div>';
        }
        
        echo '<div class="info">';
        echo '<h3>💡 Dicas Importantes:</h3>';
        echo '<ul>';
        echo '<li>🔹 Linux é case-sensitive: "Style.css" ≠ "style.css"</li>';
        echo '<li>🔹 Windows não é: "Style.css" = "style.css"</li>';
        echo '<li>🔹 Sempre use minúsculas para arquivos e pastas</li>';
        echo '<li>🔹 Teste localmente com nomes corretos antes do upload</li>';
        echo '<li>🔹 Verifique as referências em todos os arquivos HTML/PHP</li>';
        echo '</ul>';
        echo '</div>';
        ?>

        <div class="info" style="margin-top: 30px;">
            <p><strong>Timestamp da Auditoria:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
            <p><strong>Servidor:</strong> <?php echo php_uname(); ?></p>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
        </div>
    </div>
</body>
</html>
