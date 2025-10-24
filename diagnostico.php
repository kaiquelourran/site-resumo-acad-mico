<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Completo - Hostinger</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: linear-gradient(to bottom, #f0f4f8, #d9e2ec);
            padding: 20px;
            line-height: 1.6;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 { 
            color: #0072FF; 
            margin-bottom: 30px;
            text-align: center;
            font-size: 2em;
        }
        .teste { 
            margin: 15px 0; 
            padding: 15px; 
            border-radius: 8px;
            border-left: 4px solid #ccc;
        }
        .sucesso { 
            background: #d4edda; 
            color: #155724; 
            border-left-color: #28a745;
        }
        .erro { 
            background: #f8d7da; 
            color: #721c24; 
            border-left-color: #dc3545;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            border-left-color: #17a2b8;
        }
        .aviso { 
            background: #fff3cd; 
            color: #856404; 
            border-left-color: #ffc107;
        }
        h3 { margin-bottom: 10px; }
        pre { 
            background: #f4f4f4; 
            padding: 10px; 
            border-radius: 5px;
            overflow-x: auto;
            font-size: 0.9em;
        }
        .badge { 
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Completo - Resumo Acadêmico</h1>

        <?php
        // TESTE 1: Informações do Servidor
        echo '<div class="teste info">';
        echo '<h3>1. 📊 Informações do Servidor</h3>';
        echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
        echo '<p><strong>Servidor:</strong> ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
        echo '<p><strong>Document Root:</strong> ' . $_SERVER['DOCUMENT_ROOT'] . '</p>';
        echo '<p><strong>Script Path:</strong> ' . __FILE__ . '</p>';
        echo '</div>';

        // TESTE 2: Verificar se arquivos principais existem
        echo '<div class="teste info">';
        echo '<h3>2. 📁 Verificação de Arquivos</h3>';
        
        $arquivos = [
            'index.html',
            'style.css',
            'buscar_temas.php',
            'questoes/conexao.php',
            'sobre_nos.php',
            'contato.php',
            'origem_to.html'
        ];
        
        foreach ($arquivos as $arquivo) {
            if (file_exists($arquivo)) {
                echo '<span class="badge badge-success">✅ ' . $arquivo . '</span>';
            } else {
                echo '<span class="badge badge-danger">❌ ' . $arquivo . '</span>';
            }
        }
        echo '</div>';

        // TESTE 3: Conexão com Banco de Dados
        echo '<div class="teste">';
        echo '<h3>3. 🗄️ Teste de Conexão com Banco de Dados</h3>';
        
        try {
            require_once 'questoes/conexao.php';
            echo '<div class="sucesso">';
            echo '<p><strong>✅ Conexão estabelecida com sucesso!</strong></p>';
            echo '</div>';
            
            // Testar consulta
            try {
                $sql = "SELECT COUNT(*) as total FROM assuntos";
                $stmt = $pdo->query($sql);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo '<div class="sucesso">';
                echo '<p><strong>✅ Total de assuntos:</strong> ' . $result['total'] . '</p>';
                echo '</div>';
                
                // Buscar alguns assuntos
                $sql = "SELECT id_assunto, nome, tipo_assunto FROM assuntos LIMIT 5";
                $stmt = $pdo->query($sql);
                $assuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($assuntos)) {
                    echo '<div class="info">';
                    echo '<p><strong>ℹ️ Primeiros 5 assuntos:</strong></p>';
                    echo '<pre>' . print_r($assuntos, true) . '</pre>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="erro">';
                echo '<p><strong>❌ Erro na consulta:</strong> ' . $e->getMessage() . '</p>';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="erro">';
            echo '<p><strong>❌ Erro de conexão:</strong> ' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        echo '</div>';

        // TESTE 4: Teste do buscar_temas.php
        echo '<div class="teste">';
        echo '<h3>4. 🔄 Teste do buscar_temas.php</h3>';
        
        if (file_exists('buscar_temas.php')) {
            ob_start();
            include 'buscar_temas.php';
            $output = ob_get_clean();
            
            $temas = json_decode($output, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if (!empty($temas)) {
                    echo '<div class="sucesso">';
                    echo '<p><strong>✅ buscar_temas.php retornou dados:</strong></p>';
                    echo '<p><strong>Total de temas:</strong> ' . count($temas) . '</p>';
                    echo '<pre>' . print_r($temas, true) . '</pre>';
                    echo '</div>';
                } else {
                    echo '<div class="aviso">';
                    echo '<p><strong>⚠️ buscar_temas.php retornou array vazio</strong></p>';
                    echo '</div>';
                }
            } else {
                echo '<div class="erro">';
                echo '<p><strong>❌ Erro ao decodificar JSON:</strong> ' . json_last_error_msg() . '</p>';
                echo '<p><strong>Output:</strong></p>';
                echo '<pre>' . htmlspecialchars($output) . '</pre>';
                echo '</div>';
            }
        } else {
            echo '<div class="erro">';
            echo '<p><strong>❌ Arquivo buscar_temas.php não encontrado!</strong></p>';
            echo '</div>';
        }
        echo '</div>';

        // TESTE 5: Verificar permissões
        echo '<div class="teste info">';
        echo '<h3>5. 🔐 Verificação de Permissões</h3>';
        
        $arquivos_verificar = ['index.html', 'style.css', 'buscar_temas.php'];
        foreach ($arquivos_verificar as $arquivo) {
            if (file_exists($arquivo)) {
                $perms = substr(sprintf('%o', fileperms($arquivo)), -4);
                echo '<p><strong>' . $arquivo . ':</strong> ' . $perms;
                if (is_readable($arquivo)) {
                    echo ' <span class="badge badge-success">✅ Legível</span>';
                } else {
                    echo ' <span class="badge badge-danger">❌ Não legível</span>';
                }
                echo '</p>';
            }
        }
        echo '</div>';

        // TESTE 6: Verificar configuração PHP
        echo '<div class="teste info">';
        echo '<h3>6. ⚙️ Configurações PHP Relevantes</h3>';
        echo '<p><strong>display_errors:</strong> ' . ini_get('display_errors') . '</p>';
        echo '<p><strong>error_reporting:</strong> ' . ini_get('error_reporting') . '</p>';
        echo '<p><strong>max_execution_time:</strong> ' . ini_get('max_execution_time') . ' segundos</p>';
        echo '<p><strong>memory_limit:</strong> ' . ini_get('memory_limit') . '</p>';
        echo '<p><strong>PDO Drivers:</strong> ' . implode(', ', PDO::getAvailableDrivers()) . '</p>';
        echo '</div>';

        // TESTE 7: Verificar se CSS está acessível
        echo '<div class="teste info">';
        echo '<h3>7. 🎨 Teste de Carregamento de CSS</h3>';
        if (file_exists('style.css')) {
            $css_size = filesize('style.css');
            echo '<p><strong>✅ style.css encontrado</strong></p>';
            echo '<p><strong>Tamanho:</strong> ' . number_format($css_size / 1024, 2) . ' KB</p>';
        } else {
            echo '<p class="erro"><strong>❌ style.css não encontrado!</strong></p>';
        }
        echo '</div>';

        // TESTE 8: Diagnóstico Final
        echo '<div class="teste info">';
        echo '<h3>8. 🎯 Diagnóstico Final</h3>';
        echo '<p><strong>URL Atual:</strong> ' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '</p>';
        echo '<p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        echo '</div>';
        ?>

        <div class="teste aviso">
            <h3>📝 Próximos Passos</h3>
            <p><strong>Se todos os testes passaram:</strong></p>
            <ul>
                <li>✅ O problema não é de configuração</li>
                <li>✅ O problema pode ser de cache do navegador</li>
                <li>✅ Tente acessar index.html com Ctrl+F5 (hard refresh)</li>
            </ul>
            <p style="margin-top: 15px;"><strong>Se algum teste falhou:</strong></p>
            <ul>
                <li>❌ Verifique os erros acima</li>
                <li>❌ Corrija as permissões de arquivos</li>
                <li>❌ Verifique a conexão com o banco</li>
            </ul>
        </div>
    </div>

    <script>
        // JavaScript adicional para teste
        console.log('✅ JavaScript funcionando no diagnóstico!');
    </script>
</body>
</html>
