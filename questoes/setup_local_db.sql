-- Script para configurar banco de dados local para desenvolvimento
-- Execute este script no MySQL/MariaDB do XAMPP

-- Criar banco de dados local
CREATE DATABASE IF NOT EXISTS resumo_quiz_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar o banco criado
USE resumo_quiz_local;

-- Criar usuário local (opcional, pode usar root)
-- CREATE USER IF NOT EXISTS 'quiz_user'@'localhost' IDENTIFIED BY 'quiz_password';
-- GRANT ALL PRIVILEGES ON resumo_quiz_local.* TO 'quiz_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Criar tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('usuario', 'admin') DEFAULT 'usuario',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Criar tabela de assuntos
CREATE TABLE IF NOT EXISTS assuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar tabela de questões
CREATE TABLE IF NOT EXISTS questoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assunto_id INT,
    pergunta TEXT NOT NULL,
    alternativa_a TEXT NOT NULL,
    alternativa_b TEXT NOT NULL,
    alternativa_c TEXT NOT NULL,
    alternativa_d TEXT NOT NULL,
    resposta_correta ENUM('a', 'b', 'c', 'd') NOT NULL,
    explicacao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assunto_id) REFERENCES assuntos(id) ON DELETE SET NULL
);

-- Criar tabela de respostas dos usuários
CREATE TABLE IF NOT EXISTS respostas_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    questao_id INT,
    resposta_escolhida ENUM('a', 'b', 'c', 'd') NOT NULL,
    correta BOOLEAN NOT NULL,
    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (questao_id) REFERENCES questoes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_question (usuario_id, questao_id)
);

-- Inserir dados de exemplo
INSERT INTO assuntos (nome, descricao) VALUES 
('Desenvolvimento Infantil', 'Questões sobre marcos do desenvolvimento infantil'),
('Terapia Ocupacional', 'Questões sobre práticas de terapia ocupacional'),
('Transtornos do Desenvolvimento', 'Questões sobre TEA, TDAH e outros transtornos');

-- Inserir usuário admin de exemplo
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@resumoacademico.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Inserir algumas questões de exemplo
INSERT INTO questoes (assunto_id, pergunta, alternativa_a, alternativa_b, alternativa_c, alternativa_d, resposta_correta, explicacao) VALUES 
(1, 'Qual é a idade típica para o início da marcha independente?', '8-10 meses', '12-15 meses', '18-20 meses', '24-30 meses', 'b', 'A marcha independente geralmente se desenvolve entre 12-15 meses de idade.'),
(2, 'O que significa a sigla TEA?', 'Transtorno do Espectro Autista', 'Terapia Educacional Adaptada', 'Técnica de Estimulação Auditiva', 'Tratamento de Emergência Ambulatorial', 'a', 'TEA significa Transtorno do Espectro Autista.'),
(3, 'Qual é o principal objetivo da Terapia Ocupacional?', 'Curar doenças', 'Promover independência nas atividades diárias', 'Prescrever medicamentos', 'Realizar cirurgias', 'b', 'O principal objetivo da TO é promover a independência e qualidade de vida nas atividades cotidianas.');

SHOW TABLES;
SELECT 'Banco de dados configurado com sucesso!' as status;