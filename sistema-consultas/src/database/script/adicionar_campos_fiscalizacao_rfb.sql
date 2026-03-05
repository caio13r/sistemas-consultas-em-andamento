-- Script para adicionar campos de fiscalização e LGPD em tbl_rfb_auditoria
-- Módulo de Gerenciamento e Fiscalização RFB
-- Execute via MySQL: source adicionar_campos_fiscalizacao_rfb.sql

USE db_sistema_consultas;

DELIMITER $$

DROP PROCEDURE IF EXISTS AddColumnIfNotExistsFiscal$$
CREATE PROCEDURE AddColumnIfNotExistsFiscal(
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

-- Finalidade e LGPD
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'finalidade_consulta', "VARCHAR(100) NULL DEFAULT NULL COMMENT 'Rotina, Denúncia, Fiscalização Programada, Processo Ético, etc.'");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'base_legal_lgpd', "VARCHAR(150) NULL DEFAULT NULL COMMENT 'Execução de política pública, Obrigação legal, etc.'");

-- Vínculo com processos
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'numero_processo', "VARCHAR(50) NULL DEFAULT NULL");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'id_denuncia', "VARCHAR(50) NULL DEFAULT NULL");

-- Contexto organizacional
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'conselho_uf', "CHAR(2) NULL DEFAULT NULL");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'setor_origem', "VARCHAR(80) NULL DEFAULT NULL");

-- Classificação de risco e resultado fiscal
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'nivel_risco', "VARCHAR(20) NULL DEFAULT NULL COMMENT 'Baixo, Médio, Alto, Crítico'");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'indicador_irregularidade', "TINYINT(1) NULL DEFAULT 0");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'descricao_achado', "TEXT NULL DEFAULT NULL");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'acao_recomendada', "VARCHAR(100) NULL DEFAULT NULL");

-- Técnicos
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'origem_plataforma', "VARCHAR(30) NULL DEFAULT 'Web'");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'sessao_id', "VARCHAR(64) NULL DEFAULT NULL");
CALL AddColumnIfNotExistsFiscal('tbl_rfb_auditoria', 'codigo_regra_negocio', "VARCHAR(50) NULL DEFAULT NULL");

-- Índices para filtros (opcional - executar manualmente se necessário)
-- CREATE INDEX idx_finalidade ON tbl_rfb_auditoria(finalidade_consulta);
-- CREATE INDEX idx_nivel_risco ON tbl_rfb_auditoria(nivel_risco);
-- CREATE INDEX idx_indicador_irregularidade ON tbl_rfb_auditoria(indicador_irregularidade);
-- CREATE INDEX idx_conselho_uf ON tbl_rfb_auditoria(conselho_uf);

DROP PROCEDURE IF EXISTS AddColumnIfNotExistsFiscal;

SELECT 'Campos de fiscalização adicionados com sucesso!' AS status;
