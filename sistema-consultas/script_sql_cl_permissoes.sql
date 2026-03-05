-- Script para adicionar permissões de Eleições (CL) na tabela tbl_acessos

-- Permissões do módulo de Eleições (CL)
ALTER TABLE tbl_acessos 
ADD COLUMN CLacesso TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de acesso ao módulo Eleições',
ADD COLUMN CL1acesso TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de acesso à consulta-eleicoes-1',
ADD COLUMN CL1select TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de consulta nacional para CL1',
ADD COLUMN CL2acesso TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de acesso à consulta-eleicoes-2',
ADD COLUMN CL2select TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de consulta nacional para CL2',
ADD COLUMN CL3acesso TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de acesso à consulta-eleicoes-3',
ADD COLUMN CL3select TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de consulta nacional para CL3',
ADD COLUMN CL4acesso TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de acesso à consulta-eleicoes-4',
ADD COLUMN CL4select TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Permissão de consulta nacional para CL4';

-- As subpermissões abaixo seguem o padrão dinâmico das sublabels (referencial) de CL
-- Ajuste/repita conforme a quantidade de sublabels existentes em produção.

-- Exemplo para CL1..CL4 (ajuste conforme necessário)

/*
-- Conceder permissões padrão para Administrador (CFO)
-- Observação: alguns clientes/ORMs não permitem DDL (ALTER) + DML (UPDATE)
-- no mesmo batch. Se seu cliente acusar erro próximo ao UPDATE, execute
-- o bloco abaixo separadamente, após o ALTER concluir.

UPDATE tbl_acessos 
SET 
  CLacesso = 1,
  CL1acesso = 1, CL1select = 1,
  CL2acesso = 1, CL2select = 1,
  CL3acesso = 1, CL3select = 1,
  CL4acesso = 1, CL4select = 1
WHERE grupo = 'CFO' AND subgrupo = 'Administrador';
*/

/*
-- Consultas de verificação
SELECT grupo, subgrupo, CLacesso, CL1acesso, CL1select, CL2acesso, CL2select, CL3acesso, CL3select, CL4acesso, CL4select 
FROM tbl_acessos 
WHERE grupo IN ('CFO','CRO')
ORDER BY grupo, subgrupo;
*/

