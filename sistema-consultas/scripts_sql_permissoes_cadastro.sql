-- Script para adicionar permissões de Cadastro na tabela tbl_acessos
-- Execute este script no banco de dados para habilitar o controle de permissões do módulo de Cadastros

-- Verifica se as colunas já existem antes de adicionar
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_acessos' AND COLUMN_NAME = 'CDacesso')
BEGIN
    ALTER TABLE tbl_acessos ADD CDacesso BIT DEFAULT 0;
    PRINT 'Coluna CDacesso adicionada com sucesso.';
END
ELSE
BEGIN
    PRINT 'Coluna CDacesso já existe.';
END

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'tbl_acessos' AND COLUMN_NAME = 'CD1acesso')
BEGIN
    ALTER TABLE tbl_acessos ADD CD1acesso BIT DEFAULT 0;
    PRINT 'Coluna CD1acesso adicionada com sucesso.';
END
ELSE
BEGIN
    PRINT 'Coluna CD1acesso já existe.';
END

-- Define permissões padrão para administradores
-- Ajuste os valores de grupo e subgrupo conforme sua base de dados
UPDATE tbl_acessos 
SET CDacesso = 1, CD1acesso = 1 
WHERE grupo = 0; -- Assumindo que grupo 0 são os administradores

-- Opcional: Verificar os dados existentes na tabela
SELECT TOP 10 
    grupo, 
    subgrupo, 
    CDacesso, 
    CD1acesso 
FROM tbl_acessos 
ORDER BY grupo, subgrupo;

-- Verificar se as colunas foram adicionadas corretamente
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbl_acessos' 
AND COLUMN_NAME IN ('CDacesso', 'CD1acesso');

-- Inserir entrada na tabela de labels (se necessário)
INSERT INTO tbl_labels 
(label_key, label_value, created_at, updated_at, url, class, description, disable)
VALUES
('CDacesso', 'Cadastro', NOW(), NOW(), '/cadastro', 'fas fa-file-alt', 'Sistema de Cadastros - Gerencie e cadastre informações no sistema', 0);

-- Verificar a estrutura da tabela após as alterações
DESCRIBE tbl_acessos; 