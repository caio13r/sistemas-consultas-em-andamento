-- Script para aumentar tamanho da coluna cpf_consultado para aceitar CNPJ também
-- CPF tem 11 dígitos, CNPJ tem 14 dígitos
-- Autor: Sistema de Consultas
-- Data: 2025-01-XX

USE db_sistema_consultas;

-- Modificar coluna cpf_consultado para aceitar até 14 caracteres
ALTER TABLE tbl_rfb_auditoria MODIFY COLUMN cpf_consultado VARCHAR(14) NOT NULL;

-- Verificar alteração
DESC tbl_rfb_auditoria;

-- Exibir mensagem de sucesso
SELECT 'Coluna cpf_consultado alterada para VARCHAR(14) com sucesso!' as status;

