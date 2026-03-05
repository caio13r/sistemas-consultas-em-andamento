-- Script para adicionar permissões de Consulta RFB na tabela tbl_acessos
-- Autor: Claude Code
-- Data: 2025-11-03

USE db_sistema_consultas;

-- Adicionar coluna CRacesso (Permissão de Acesso à Consulta RFB)
ALTER TABLE tbl_acessos
ADD CRacesso BOOLEAN DEFAULT 0 COMMENT 'Permissão de acesso à Consulta RFB';

-- Adicionar coluna CRgerenciar (Permissão de Gerenciamento RFB - Logs e Auditoria)
ALTER TABLE tbl_acessos
ADD CRgerenciar BOOLEAN DEFAULT 0 COMMENT 'Permissão para gerenciar e visualizar logs da Consulta RFB';

-- Dar permissão total para CFO Administrador
UPDATE tbl_acessos
SET CRacesso = 1, CRgerenciar = 1
WHERE grupo = 'CFO' AND subgrupo IN ('Administrador', 'Gestor', 'TI');

-- Dar permissão de acesso (sem gerenciamento) para CRO
UPDATE tbl_acessos
SET CRacesso = 1, CRgerenciar = 0
WHERE grupo LIKE 'CRO%';

-- Verificar as permissões criadas
SELECT grupo, subgrupo, CRacesso, CRgerenciar
FROM tbl_acessos
ORDER BY grupo, subgrupo;
