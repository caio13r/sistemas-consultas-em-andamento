-- Script para adicionar permissões de Consulta RFB e Gerenciamento RFB na tabela tbl_acessos
-- Autor: Sistema de Consultas
-- Data: 2025-01-XX
--
-- CRacesso: Permissão de acesso à Consulta RFB (já existe)
-- CRselect: Permissão de consulta/seleção na Consulta RFB (nova)
-- CR1acesso: Permissão de acesso ao Gerenciamento RFB (nova)
-- CR1select: Permissão de consulta/seleção no Gerenciamento RFB (nova)

USE db_sistema_consultas;

-- Verificar se CRacesso já existe (não fazer nada se existir)
-- CRacesso já existe, então vamos apenas verificar

-- Adicionar coluna CRselect (Permissão de seleção/consulta na Consulta RFB)
ALTER TABLE tbl_acessos
ADD COLUMN CRselect BOOLEAN DEFAULT 0 COMMENT 'Permissão de seleção/consulta na Consulta RFB';

-- Adicionar coluna CR1acesso (Permissão de acesso ao Gerenciamento RFB)
ALTER TABLE tbl_acessos
ADD COLUMN CR1acesso BOOLEAN DEFAULT 0 COMMENT 'Permissão de acesso ao Gerenciamento RFB';

-- Adicionar coluna CR1select (Permissão de seleção/consulta no Gerenciamento RFB)
ALTER TABLE tbl_acessos
ADD COLUMN CR1select BOOLEAN DEFAULT 0 COMMENT 'Permissão de seleção/consulta no Gerenciamento RFB';

-- Dar permissão total para CFO Administrador, Gestor e TI
UPDATE tbl_acessos
SET CRacesso = 1, CRselect = 1, CR1acesso = 1, CR1select = 1
WHERE grupo = 'CFO' AND subgrupo IN ('Administrador', 'Gestor', 'TI');

-- Dar permissão de consulta (sem gerenciamento) para CRO
UPDATE tbl_acessos
SET CRacesso = 1, CRselect = 1, CR1acesso = 0, CR1select = 0
WHERE grupo LIKE 'CRO%';

-- Verificar as permissões criadas
SELECT grupo, subgrupo, CRacesso, CRselect, CR1acesso, CR1select
FROM tbl_acessos
ORDER BY grupo, subgrupo;

-- Exibir mensagem de sucesso
SELECT 'Colunas CRselect, CR1acesso e CR1select adicionadas com sucesso!' as status;

