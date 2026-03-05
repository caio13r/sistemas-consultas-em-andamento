-- Script para adicionar colunas display_order nas tabelas de labels
-- Execute este script caso as colunas não existam

-- Adicionar coluna display_order na tabela tbl_labels (se não existir)
ALTER TABLE tbl_labels 
ADD COLUMN IF NOT EXISTS display_order INT DEFAULT NULL 
COMMENT 'Ordem de exibição personalizada das labels';

-- Adicionar coluna display_order na tabela tbl_child_labels (se não existir)  
ALTER TABLE tbl_child_labels 
ADD COLUMN IF NOT EXISTS display_order INT DEFAULT NULL 
COMMENT 'Ordem de exibição personalizada das sub-labels';

-- Criar índices para melhorar performance das consultas de ordenação
CREATE INDEX IF NOT EXISTS idx_labels_display_order ON tbl_labels(display_order);
CREATE INDEX IF NOT EXISTS idx_child_labels_display_order ON tbl_child_labels(display_order);
CREATE INDEX IF NOT EXISTS idx_child_labels_fk_display ON tbl_child_labels(fk_label, display_order);

-- Verificar se as colunas foram criadas
SELECT 
    'tbl_labels' as tabela,
    COLUMN_NAME as coluna,
    DATA_TYPE as tipo,
    IS_NULLABLE as permite_null
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_labels' 
AND COLUMN_NAME = 'display_order'

UNION ALL

SELECT 
    'tbl_child_labels' as tabela,
    COLUMN_NAME as coluna,
    DATA_TYPE as tipo,
    IS_NULLABLE as permite_null
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_child_labels' 
AND COLUMN_NAME = 'display_order'; 