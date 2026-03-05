-- Script para adicionar permissão CI23acesso na tabela tbl_acessos
-- Execute este script no banco de dados para habilitar o controle de permissões para consultaIdentidade-23.php

-- Adiciona coluna para permissão específica da Consulta de Envios de Identidades ao CRO - Por Período
ALTER TABLE tbl_acessos 
ADD COLUMN CI23acesso BOOLEAN DEFAULT FALSE COMMENT 'Permissão de acesso à Consulta de Envios de Identidades ao CRO - Por Período';

-- Adiciona coluna para permissão de consulta nacional (se necessário)
ALTER TABLE tbl_acessos 
ADD COLUMN CI23select BOOLEAN DEFAULT FALSE COMMENT 'Permissão de consulta nacional para CI23';

-- Define permissões padrão para administradores (grupo = 'CFO', subgrupo = 'Administrador')
UPDATE tbl_acessos 
SET CI23acesso = TRUE, CI23select = TRUE 
WHERE grupo = 'CFO' AND subgrupo = 'Administrador';

-- Opcional: Definir permissões para outros grupos específicos
-- Descomente as linhas abaixo conforme necessário:

-- Para dar acesso ao CI23 para CFO Coordenador:
-- UPDATE tbl_acessos 
-- SET CI23acesso = TRUE, CI23select = TRUE 
-- WHERE grupo = 'CFO' AND subgrupo = 'Coordenador';

-- Para dar acesso ao CI23 para CFO Presidente:
-- UPDATE tbl_acessos 
-- SET CI23acesso = TRUE, CI23select = TRUE 
-- WHERE grupo = 'CFO' AND subgrupo = 'Presidente';

-- Para dar acesso ao CI23 para CRO Presidente:
-- UPDATE tbl_acessos 
-- SET CI23acesso = TRUE, CI23select = FALSE 
-- WHERE grupo = 'CRO' AND subgrupo = 'Presidente';

-- Verificar se as colunas foram adicionadas corretamente
SELECT 
    grupo, 
    subgrupo, 
    CI23acesso, 
    CI23select 
FROM tbl_acessos 
WHERE grupo IN ('CFO', 'CRO') 
ORDER BY grupo, subgrupo;

-- Verificar a estrutura da tabela após as alterações
DESCRIBE tbl_acessos; 