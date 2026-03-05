-- Script para criar tabela de logs de atividade dos usuários
-- Banco: db_sistema_consultas (Database1 - MySQL)

USE db_sistema_consultas;

CREATE TABLE IF NOT EXISTS tbl_logs_atividade (
    id BIGINT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    usuario_nome VARCHAR(255) NOT NULL,
    usuario_email VARCHAR(255) NOT NULL,
    usuario_grupo VARCHAR(50) NOT NULL,
    usuario_subgrupo VARCHAR(50) NOT NULL,
    tipo_acao ENUM('login', 'logout', 'acesso_pagina') NOT NULL,
    rota_acessada VARCHAR(255) DEFAULT NULL,
    metodo_http VARCHAR(10) DEFAULT NULL,
    ip_origem VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    sessao_id VARCHAR(128) DEFAULT NULL,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_tipo_acao (tipo_acao),
    INDEX idx_data_hora (data_hora),
    INDEX idx_rota (rota_acessada),
    INDEX idx_usuario_data (usuario_id, data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Tabela tbl_logs_atividade criada com sucesso!' AS status;
