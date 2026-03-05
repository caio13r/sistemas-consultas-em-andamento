-- ============================================================================
-- SCRIPT SQL PARA ADICIONAR COLUNAS display_order - EXECUÇÃO EM ETAPAS
-- ============================================================================
-- Este script adiciona colunas para permitir ordenação customizada hierárquica
-- Compatível com MySQL 5.7+, MariaDB e DBeaver
-- 
-- INSTRUÇÕES: Execute cada seção separadamente na ordem indicada
-- ============================================================================

-- ============================================================================
-- ETAPA 1: SELECIONAR O BANCO DE DADOS
-- Execute esta seção primeiro
-- ============================================================================
USE db_sistema_consultas;

-- ============================================================================
-- ETAPA 2: VERIFICAR SE COLUNA display_order EXISTE EM tbl_labels
-- Execute esta seção após a Etapa 1
-- ============================================================================
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'COLUNA display_order JÁ EXISTE em tbl_labels'
        ELSE 'COLUNA display_order NÃO EXISTE em tbl_labels - PODE ADICIONAR'
    END AS status_coluna_labels
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_labels' 
  AND COLUMN_NAME = 'display_order'
  AND TABLE_SCHEMA = 'db_sistema_consultas';

-- ============================================================================
-- ETAPA 3: ADICIONAR COLUNA display_order EM tbl_labels (SE NÃO EXISTIR)
-- Execute esta seção APENAS se a Etapa 2 mostrou que a coluna NÃO EXISTE
-- ============================================================================
ALTER TABLE tbl_labels ADD COLUMN display_order INT NULL;

-- ============================================================================
-- ETAPA 4: INICIALIZAR VALORES EM tbl_labels
-- Execute esta seção após a Etapa 3
-- ============================================================================
UPDATE tbl_labels 
SET display_order = id 
WHERE display_order IS NULL;

-- ============================================================================
-- ETAPA 5: VERIFICAR SE COLUNA display_order EXISTE EM tbl_child_labels
-- Execute esta seção após a Etapa 4
-- ============================================================================
SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'COLUNA display_order JÁ EXISTE em tbl_child_labels'
        ELSE 'COLUNA display_order NÃO EXISTE em tbl_child_labels - PODE ADICIONAR'
    END AS status_coluna_child_labels
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_child_labels' 
  AND COLUMN_NAME = 'display_order'
  AND TABLE_SCHEMA = 'db_sistema_consultas';

-- ============================================================================
-- ETAPA 6: ADICIONAR COLUNA display_order EM tbl_child_labels (SE NÃO EXISTIR)
-- Execute esta seção APENAS se a Etapa 5 mostrou que a coluna NÃO EXISTE
-- ============================================================================
ALTER TABLE tbl_child_labels ADD COLUMN display_order INT NULL;

-- ============================================================================
-- ETAPA 7: INICIALIZAR VALORES EM tbl_child_labels
-- Execute esta seção após a Etapa 6
-- ============================================================================
UPDATE tbl_child_labels 
SET display_order = referencial 
WHERE display_order IS NULL;

-- ============================================================================
-- ETAPA 8: VERIFICAR ESTRUTURA DA TABELA tbl_labels
-- Execute esta seção após a Etapa 7 para confirmar
-- ============================================================================
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_labels' 
    AND TABLE_SCHEMA = 'db_sistema_consultas'
    AND COLUMN_NAME = 'display_order';

-- ============================================================================
-- ETAPA 9: VERIFICAR ESTRUTURA DA TABELA tbl_child_labels
-- Execute esta seção após a Etapa 8 para confirmar
-- ============================================================================
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_child_labels' 
    AND TABLE_SCHEMA = 'db_sistema_consultas'
    AND COLUMN_NAME = 'display_order';

-- ============================================================================
-- ETAPA 10: VERIFICAR DADOS DA TABELA tbl_labels
-- Execute esta seção após a Etapa 9 para ver os dados
-- ============================================================================
SELECT 
    id, 
    label_key, 
    label_value, 
    display_order 
FROM tbl_labels 
ORDER BY display_order ASC 
LIMIT 10;

-- ============================================================================
-- ETAPA 11: VERIFICAR DADOS HIERÁRQUICOS
-- Execute esta seção por último para ver a estrutura hierárquica
-- ============================================================================
SELECT 
    cl.id_label,
    cl.nome,
    cl.referencial,
    cl.grupo,
    cl.fk_label,
    cl.display_order,
    l.label_value as parent_name
FROM tbl_child_labels cl
INNER JOIN tbl_labels l ON cl.fk_label = l.id
ORDER BY l.display_order ASC, cl.display_order ASC 
LIMIT 10;

-- ============================================================================
-- FIM DO SCRIPT - TODAS AS ETAPAS CONCLUÍDAS
-- ============================================================================ 