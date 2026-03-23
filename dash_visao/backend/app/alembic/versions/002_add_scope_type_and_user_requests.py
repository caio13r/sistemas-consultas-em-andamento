"""add scope_type to permissions/servicos and user_requests tables

Revision ID: 002_scope_user_req
Revises: 001_granular
Create Date: 2026-02-28

"""
from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = '002_scope_user_req'
down_revision = '001_granular'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Adicionar scope_type em permissions
    op.execute("""
        ALTER TABLE permissions
        ADD COLUMN IF NOT EXISTS scope_type VARCHAR(20) DEFAULT 'global'
    """)

    # Adicionar scope_type em servicos
    op.execute("""
        ALTER TABLE servicos
        ADD COLUMN IF NOT EXISTS scope_type VARCHAR(20) DEFAULT 'global'
    """)

    # Criar tabela user_requests
    op.execute("""
        CREATE TABLE IF NOT EXISTS user_requests (
            id SERIAL PRIMARY KEY,
            nome_completo VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            telefone VARCHAR(20),
            origem_tipo VARCHAR(10) NOT NULL,
            organizacao VARCHAR(100) NOT NULL,
            departamento VARCHAR(100),
            justificativa TEXT NOT NULL,
            outro TEXT,
            sugestao_desenvolvimento TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'pendente',
            admin_id INTEGER REFERENCES users(id),
            admin_notes TEXT,
            reject_reason TEXT,
            clarification_message TEXT,
            created_user_id INTEGER REFERENCES users(id),
            resolved_at TIMESTAMP WITH TIME ZONE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP WITH TIME ZONE
        )
    """)
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_user_requests_email
        ON user_requests(email)
    """)
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_user_requests_status
        ON user_requests(status)
    """)

    # Criar tabela user_request_items
    op.execute("""
        CREATE TABLE IF NOT EXISTS user_request_items (
            id SERIAL PRIMARY KEY,
            user_request_id INTEGER NOT NULL REFERENCES user_requests(id) ON DELETE CASCADE,
            servico_id INTEGER REFERENCES servicos(id),
            permission_name VARCHAR(255),
            approved BOOLEAN DEFAULT TRUE
        )
    """)
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_user_request_items_request_id
        ON user_request_items(user_request_id)
    """)


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS user_request_items")
    op.execute("DROP TABLE IF EXISTS user_requests")
    op.execute("ALTER TABLE servicos DROP COLUMN IF EXISTS scope_type")
    op.execute("ALTER TABLE permissions DROP COLUMN IF EXISTS scope_type")
