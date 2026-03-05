-- Migração manual: parent_role_id, level, authorization_mode e user_permissions
-- Execute este script se preferir não usar Alembic
-- PostgreSQL
--
-- Para executar via Docker (a partir da raiz do projeto):
--   docker-compose exec -T db psql -U postgres -d appdb < docs/migrations/001_add_granular_permissions.sql
--
-- Ou copie o conteúdo e execute no cliente PostgreSQL.

-- 1. Roles: parent_role_id e level
ALTER TABLE roles ADD COLUMN IF NOT EXISTS parent_role_id INTEGER REFERENCES roles(id);
ALTER TABLE roles ADD COLUMN IF NOT EXISTS level INTEGER DEFAULT 1;

-- 2. Users: authorization_mode
ALTER TABLE users ADD COLUMN IF NOT EXISTS authorization_mode VARCHAR(20) DEFAULT 'multi_role';

-- 3. Tabela user_permissions (permissões diretas ao usuário)
CREATE TABLE IF NOT EXISTS user_permissions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    granted BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, permission_id)
);
CREATE INDEX IF NOT EXISTS ix_user_permissions_user_id ON user_permissions(user_id);
CREATE INDEX IF NOT EXISTS ix_user_permissions_permission_id ON user_permissions(permission_id);
