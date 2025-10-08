-- Remover constraint UNIQUE da tabela respostas_usuario
ALTER TABLE respostas_usuario DROP INDEX unique_questao;

-- Adicionar índice normal para performance
ALTER TABLE respostas_usuario ADD INDEX idx_questao (id_questao);
