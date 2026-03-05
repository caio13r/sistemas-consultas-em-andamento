-- Script para criar tabela de auditoria de consultas RFB
-- Autor: Claude Code
-- Data: 2025-11-04

USE db_sistema_consultas;

-- Criar tabela de auditoria para consultas RFB
CREATE TABLE IF NOT EXISTS tbl_rfb_auditoria (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    -- Identificação do usuário
    usuario_id INT NOT NULL,
    usuario_nome VARCHAR(255),
    usuario_email VARCHAR(255),
    usuario_grupo VARCHAR(50),
    usuario_subgrupo VARCHAR(50),

    -- Documento consultado
    tipo_consulta ENUM('CPF', 'CNPJ') NOT NULL,
    documento_consultado VARCHAR(14) NOT NULL,  -- CPF 11 dígitos ou CNPJ 14 dígitos

    -- Dados da CFO (se existir)
    existe_base_cfo BOOLEAN DEFAULT 0,
    inscricao_cfo VARCHAR(50),
    nome_cfo VARCHAR(255),
    cro_cfo VARCHAR(5),
    situacao_cfo VARCHAR(50),

    -- Resultado da consulta
    sucesso BOOLEAN DEFAULT 0,
    mensagem_erro TEXT,
    tempo_resposta_ms INT,

    -- Dados da consulta
    situacao_cadastral VARCHAR(100),
    nome_consultado VARCHAR(255),

    -- Informações técnicas
    ip_origem VARCHAR(45),
    user_agent TEXT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Auditoria
    xml_completo LONGTEXT,  -- Para armazenar resposta completa (opcional)

    INDEX idx_usuario (usuario_id),
    INDEX idx_documento (documento_consultado),
    INDEX idx_data_hora (data_hora),
    INDEX idx_tipo_consulta (tipo_consulta),
    INDEX idx_sucesso (sucesso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar estrutura
DESC tbl_rfb_auditoria;

-- Exibir mensagem de sucesso
SELECT 'Tabela tbl_rfb_auditoria criada com sucesso!' as status;
