-- ============================================
-- ÍNDICES PARA OTIMIZAÇÃO DE PERFORMANCE
-- Sistema de Impressão Digital - consultaIdentidade-24.php
-- ============================================
-- Execute este script no banco identity_professional
-- para melhorar significativamente a performance das queries
-- ============================================

USE identity_professional;

-- Índices para professional_register
-- Acelera filtros por tipo de inscrição
CREATE INDEX IF NOT EXISTS idx_pr_tipo_insc 
ON professional_register(tipo_insc);

-- Acelera filtros por categoria (CD, TPD, ASB, TSB, APD)
CREATE INDEX IF NOT EXISTS idx_pr_categoria 
ON professional_register(categoria);

-- Acelera joins com identity por CPF
CREATE INDEX IF NOT EXISTS idx_pr_cpf 
ON professional_register(cpf);

-- Acelera filtros por naturalidade
CREATE INDEX IF NOT EXISTS idx_pr_naturalidade 
ON professional_register(naturalidade);

-- Acelera filtros por UF de naturalidade
CREATE INDEX IF NOT EXISTS idx_pr_naturalidade_uf 
ON professional_register(naturalidade_uf);

-- Índice composto para filtros comuns
CREATE INDEX IF NOT EXISTS idx_pr_categoria_tipo 
ON professional_register(categoria, tipo_insc);

-- Índices para identity
-- Acelera joins com professional_register
CREATE INDEX IF NOT EXISTS idx_identity_cpf 
ON identity(cpf);

-- Acelera joins com tracking_identity
CREATE INDEX IF NOT EXISTS idx_identity_id_prof 
ON identity(id_professional);

-- Índices para tracking_identity
-- Acelera filtros por status de rastreamento (COLETADO PELO ECT)
CREATE INDEX IF NOT EXISTS idx_tracking_description 
ON tracking_identity(description);

-- Acelera joins com identity
CREATE INDEX IF NOT EXISTS idx_tracking_identity_id 
ON tracking_identity(identity_id);

-- Acelera joins alternativos
CREATE INDEX IF NOT EXISTS idx_tracking_id_prof 
ON tracking_identity(id_professional);

-- Índice composto para query mais eficiente
CREATE INDEX IF NOT EXISTS idx_tracking_id_desc 
ON tracking_identity(identity_id, description);

-- ============================================
-- VERIFICAR ÍNDICES CRIADOS
-- ============================================
-- Execute a query abaixo para ver todos os índices:
/*
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS COLUMNS
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = 'identity_professional'
  AND TABLE_NAME IN ('professional_register', 'identity', 'tracking_identity')
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;
*/

-- ============================================
-- ESTATÍSTICAS DA TABELA (Para o otimizador)
-- ============================================
-- Execute após criar os índices para atualizar as estatísticas:

ANALYZE TABLE professional_register;
ANALYZE TABLE identity;
ANALYZE TABLE tracking_identity;

-- ============================================
-- FIM DO SCRIPT
-- ============================================

