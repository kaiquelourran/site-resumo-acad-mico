<?php
require_once 'questoes/conexao.php';

try {
    // Buscar apenas temas (não concursos ou profissionais)
    $sql = "SELECT a.id_assunto, a.nome, COUNT(q.id_questao) as total_questoes 
            FROM assuntos a 
            LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
            WHERE a.tipo_assunto = 'tema' OR a.tipo_assunto IS NULL
            GROUP BY a.id_assunto, a.nome 
            ORDER BY a.nome
            LIMIT 12";
    
    $stmt = $pdo->query($sql);
    $temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver temas com tipo_assunto, buscar todos os assuntos
    if (empty($temas)) {
        $sql_fallback = "SELECT a.id_assunto, a.nome, COUNT(q.id_questao) as total_questoes 
                        FROM assuntos a 
                        LEFT JOIN questoes q ON a.id_assunto = q.id_assunto 
                        GROUP BY a.id_assunto, a.nome 
                        ORDER BY a.nome
                        LIMIT 12";
        
        $stmt_fallback = $pdo->query($sql_fallback);
        $temas = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Retornar como JSON
    header('Content-Type: application/json');
    echo json_encode($temas);
    
} catch (Exception $e) {
    // Em caso de erro, retornar array vazio
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
