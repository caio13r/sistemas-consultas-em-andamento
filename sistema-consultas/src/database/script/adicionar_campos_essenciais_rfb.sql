-- Script SQL SIMPLES - Execute cada comando manualmente se necessário
-- Se algum campo já existir, ignore o erro e continue para o próximo

USE db_sistema_consultas;

-- Campo comum essencial
ALTER TABLE tbl_rfb_auditoria ADD COLUMN documento_consultado VARCHAR(14) AFTER tipo_consulta;

-- Campos CPF principais
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_nome VARCHAR(255) AFTER nome_consultado;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_data_nascimento DATE AFTER cpf_nome;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_logradouro VARCHAR(255) AFTER cpf_data_nascimento;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_numero_logradouro VARCHAR(20) AFTER cpf_logradouro;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_bairro VARCHAR(100) AFTER cpf_numero_logradouro;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_cep VARCHAR(10) AFTER cpf_bairro;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_uf VARCHAR(2) AFTER cpf_cep;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_municipio VARCHAR(100) AFTER cpf_uf;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cpf_telefone VARCHAR(20) AFTER cpf_municipio;

-- Campos CNPJ principais
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_razao_social VARCHAR(255) AFTER cpf_telefone;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_nome_fantasia VARCHAR(255) AFTER cnpj_razao_social;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_logradouro VARCHAR(255) AFTER cnpj_nome_fantasia;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_numero_logradouro VARCHAR(20) AFTER cnpj_logradouro;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_bairro VARCHAR(100) AFTER cnpj_numero_logradouro;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_cep VARCHAR(10) AFTER cnpj_bairro;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_uf VARCHAR(2) AFTER cnpj_cep;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_municipio VARCHAR(100) AFTER cnpj_uf;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_cnae_fiscal VARCHAR(10) AFTER cnpj_municipio;
ALTER TABLE tbl_rfb_auditoria ADD COLUMN cnpj_capital_social DECIMAL(15,2) AFTER cnpj_cnae_fiscal;

-- Criar índices (ignorar erro se já existirem)
CREATE INDEX idx_documento_consultado ON tbl_rfb_auditoria(documento_consultado);
CREATE INDEX idx_tipo_consulta ON tbl_rfb_auditoria(tipo_consulta);

-- Verificar estrutura
DESC tbl_rfb_auditoria;
