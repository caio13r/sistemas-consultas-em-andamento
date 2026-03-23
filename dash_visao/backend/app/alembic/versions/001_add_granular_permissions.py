"""add parent_role_id, level, authorization_mode and user_permissions

Revision ID: 001_granular
Revises: 
Create Date: 2025-02-28

"""
from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = '001_granular'
down_revision = None
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Roles: parent_role_id e level
    op.execute("""
        ALTER TABLE roles 
        ADD COLUMN IF NOT EXISTS parent_role_id INTEGER REFERENCES roles(id)
    """)
    op.execute("""
        ALTER TABLE roles 
        ADD COLUMN IF NOT EXISTS level INTEGER DEFAULT 1
    """)
    
    # Users: authorization_mode
    op.execute("""
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS authorization_mode VARCHAR(20) DEFAULT 'multi_role'
    """)
    
    # Tabela user_permissions (permissões diretas ao usuário)
    op.execute("""
        CREATE TABLE IF NOT EXISTS user_permissions (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            permission_id INTEGER NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
            granted BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, permission_id)
        )
    """)
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_user_permissions_user_id 
        ON user_permissions(user_id)
    """)
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_user_permissions_permission_id 
        ON user_permissions(permission_id)
    """)


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS user_permissions")
    op.execute("ALTER TABLE users DROP COLUMN IF EXISTS authorization_mode")
    op.execute("ALTER TABLE roles DROP COLUMN IF EXISTS level")
    op.execute("ALTER TABLE roles DROP COLUMN IF EXISTS parent_role_id")
