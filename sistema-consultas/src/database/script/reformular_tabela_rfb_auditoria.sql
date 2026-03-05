-- Script para reformular tabela de auditoria RFB com suporte completo para CPF e CNPJ
-- Autor: Sistema de Consultas
-- Data: 2025-01-XX
-- MySQL compatível

USE db_sistema_consultas;

-- Mudar delimitador para permitir múltiplos comandos
DELIMITER $$

-- ============================================
-- PROCEDURE para adicionar coluna se não existir
-- ============================================
DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(128),
    IN columnName VARCHAR(128),
    IN columnDefinition TEXT
)
BEGIN
    DECLARE columnExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO columnExists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = tableName
    AND COLUMN_NAME = columnName;
    
    IF columnExists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', columnName, ' ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- CAMPOS COMUNS
-- ============================================

CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'tipo_consulta', "ENUM('CPF', 'CNPJ') AFTER usuario_subgrupo");
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'documento_consultado', 'VARCHAR(14) AFTER tipo_consulta');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'nome_consultado', 'VARCHAR(255) AFTER documento_consultado');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'situacao_cadastral', 'VARCHAR(100) AFTER nome_consultado');

-- ============================================
-- CAMPOS ESPECÍFICOS CPF
-- ============================================

-- CPF - Dados Pessoais
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_nome', 'VARCHAR(255) AFTER situacao_cadastral');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_nome_mae', 'VARCHAR(255) AFTER cpf_nome');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_data_nascimento', 'DATE AFTER cpf_nome_mae');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_sexo', 'VARCHAR(1) AFTER cpf_data_nascimento');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_sexo_descricao', 'VARCHAR(20) AFTER cpf_sexo');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_situacao_cadastral_codigo', 'VARCHAR(10) AFTER cpf_sexo_descricao');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_residente_exterior', 'VARCHAR(1) AFTER cpf_situacao_cadastral_codigo');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_pais_exterior', 'VARCHAR(100) AFTER cpf_residente_exterior');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_estrangeiro', 'VARCHAR(1) AFTER cpf_pais_exterior');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_pais_nacionalidade', 'VARCHAR(100) AFTER cpf_estrangeiro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_municipio_naturalidade', 'VARCHAR(100) AFTER cpf_pais_nacionalidade');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_uf_naturalidade', 'VARCHAR(2) AFTER cpf_municipio_naturalidade');

-- CPF - Ocupação
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_natureza_ocupacao', 'VARCHAR(50) AFTER cpf_uf_naturalidade');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_natureza_ocupacao_descricao', 'VARCHAR(255) AFTER cpf_natureza_ocupacao');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_ocupacao_principal', 'VARCHAR(50) AFTER cpf_natureza_ocupacao_descricao');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_ocupacao_principal_descricao', 'VARCHAR(255) AFTER cpf_ocupacao_principal');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_exercicio_ocupacao', 'VARCHAR(50) AFTER cpf_ocupacao_principal_descricao');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_exercicio_ocupacao_descricao', 'VARCHAR(255) AFTER cpf_exercicio_ocupacao');

-- CPF - Endereço
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_tipo_logradouro', 'VARCHAR(50) AFTER cpf_exercicio_ocupacao_descricao');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_logradouro', 'VARCHAR(255) AFTER cpf_tipo_logradouro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_numero_logradouro', 'VARCHAR(20) AFTER cpf_logradouro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_complemento', 'VARCHAR(100) AFTER cpf_numero_logradouro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_bairro', 'VARCHAR(100) AFTER cpf_complemento');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_cep', 'VARCHAR(10) AFTER cpf_bairro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_uf', 'VARCHAR(2) AFTER cpf_cep');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_codigo_municipio', 'VARCHAR(10) AFTER cpf_uf');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_municipio', 'VARCHAR(100) AFTER cpf_codigo_municipio');

-- CPF - Contato
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_ddd', 'VARCHAR(3) AFTER cpf_municipio');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_telefone', 'VARCHAR(20) AFTER cpf_ddd');

-- CPF - Administrativo
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_data_inscricao', 'DATE AFTER cpf_telefone');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_data_atualizacao', 'DATE AFTER cpf_data_inscricao');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cpf_ano_obito', 'VARCHAR(4) AFTER cpf_data_atualizacao');

-- ============================================
-- CAMPOS ESPECÍFICOS CNPJ
-- ============================================

-- CNPJ - Dados Básicos
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_razao_social', 'VARCHAR(255) AFTER cpf_ano_obito');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_nome_fantasia', 'VARCHAR(255) AFTER cnpj_razao_social');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_estabelecimento', 'VARCHAR(10) AFTER cnpj_nome_fantasia');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_situacao_cadastral_codigo', 'VARCHAR(10) AFTER cnpj_estabelecimento');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_data_situacao_cadastral', 'DATE AFTER cnpj_situacao_cadastral_codigo');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_motivo_situacao_cadastral_codigo', 'VARCHAR(10) AFTER cnpj_data_situacao_cadastral');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_motivo_situacao_cadastral', 'VARCHAR(255) AFTER cnpj_motivo_situacao_cadastral_codigo');

-- CNPJ - Endereço
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_tipo_logradouro', 'VARCHAR(50) AFTER cnpj_motivo_situacao_cadastral');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_logradouro', 'VARCHAR(255) AFTER cnpj_tipo_logradouro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_numero_logradouro', 'VARCHAR(20) AFTER cnpj_logradouro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_complemento', 'VARCHAR(100) AFTER cnpj_numero_logradouro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_bairro', 'VARCHAR(100) AFTER cnpj_complemento');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_cep', 'VARCHAR(10) AFTER cnpj_bairro');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_uf', 'VARCHAR(2) AFTER cnpj_cep');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_codigo_municipio', 'VARCHAR(10) AFTER cnpj_uf');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_municipio', 'VARCHAR(100) AFTER cnpj_codigo_municipio');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_referencia', 'VARCHAR(255) AFTER cnpj_municipio');

-- CNPJ - Contato
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_ddd', 'VARCHAR(3) AFTER cnpj_referencia');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_telefone', 'VARCHAR(20) AFTER cnpj_ddd');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_ddd2', 'VARCHAR(3) AFTER cnpj_telefone');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_telefone2', 'VARCHAR(20) AFTER cnpj_ddd2');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_email', 'VARCHAR(255) AFTER cnpj_telefone2');

-- CNPJ - Natureza Jurídica
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_codigo_natureza_juridica', 'VARCHAR(10) AFTER cnpj_email');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_natureza_juridica', 'VARCHAR(255) AFTER cnpj_codigo_natureza_juridica');

-- CNPJ - CNAE
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_cnae_fiscal', 'VARCHAR(10) AFTER cnpj_natureza_juridica');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_descricao_cnae_fiscal', 'VARCHAR(255) AFTER cnpj_cnae_fiscal');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_cnaes_secundarios', 'TEXT AFTER cnpj_descricao_cnae_fiscal');

-- CNPJ - Quadro Societário (QSA) - JSON
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_qsa', 'TEXT AFTER cnpj_cnaes_secundarios');

-- CNPJ - Responsável Legal
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_cpf_responsavel', 'VARCHAR(11) AFTER cnpj_qsa');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_nome_responsavel', 'VARCHAR(255) AFTER cnpj_cpf_responsavel');

-- CNPJ - Capital e Porte
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_capital_social', 'DECIMAL(15,2) AFTER cnpj_nome_responsavel');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_porte', 'VARCHAR(10) AFTER cnpj_capital_social');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_descricao_porte', 'VARCHAR(50) AFTER cnpj_porte');

-- CNPJ - Simples Nacional / MEI
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_opcao_simples', 'VARCHAR(1) AFTER cnpj_descricao_porte');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_opcao_mei', 'VARCHAR(1) AFTER cnpj_opcao_simples');

-- CNPJ - Situação Especial
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_situacao_especial', 'VARCHAR(100) AFTER cnpj_opcao_mei');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_data_situacao_especial', 'DATE AFTER cnpj_situacao_especial');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_cidade_exterior', 'VARCHAR(100) AFTER cnpj_data_situacao_especial');
CALL AddColumnIfNotExists('tbl_rfb_auditoria', 'cnpj_data_abertura', 'DATE AFTER cnpj_cidade_exterior');

-- ============================================
-- ÍNDICES (criar se não existirem)
-- ============================================

DELIMITER $$

DROP PROCEDURE IF EXISTS AddIndexIfNotExists$$
CREATE PROCEDURE AddIndexIfNotExists(
    IN tableName VARCHAR(128),
    IN indexName VARCHAR(128),
    IN indexDefinition TEXT
)
BEGIN
    DECLARE indexExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO indexExists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = tableName
    AND INDEX_NAME = indexName;
    
    IF indexExists = 0 THEN
        SET @sql = CONCAT('CREATE INDEX ', indexName, ' ON ', tableName, '(', indexDefinition, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL AddIndexIfNotExists('tbl_rfb_auditoria', 'idx_tipo_consulta', 'tipo_consulta');
CALL AddIndexIfNotExists('tbl_rfb_auditoria', 'idx_documento_consultado', 'documento_consultado');

-- Limpar procedures temporárias
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;
DROP PROCEDURE IF EXISTS AddIndexIfNotExists;

-- Verificar estrutura final
DESC tbl_rfb_auditoria;

-- Exibir mensagem de sucesso
SELECT 'Tabela tbl_rfb_auditoria reformulada com sucesso!' as status;
