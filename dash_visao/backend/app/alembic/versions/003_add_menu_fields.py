"""add permission_name, is_section to menus and icon, permission_name to submenus

Revision ID: 003_menu_fields
Revises: 002_scope_user_req
Create Date: 2026-02-28

"""
from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = '003_menu_fields'
down_revision = '002_scope_user_req'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Adicionar campos em menus
    op.execute("""
        ALTER TABLE menus
        ADD COLUMN IF NOT EXISTS permission_name VARCHAR(255)
    """)
    op.execute("""
        ALTER TABLE menus
        ADD COLUMN IF NOT EXISTS is_section BOOLEAN DEFAULT FALSE
    """)

    # Adicionar campos em submenus
    op.execute("""
        ALTER TABLE submenus
        ADD COLUMN IF NOT EXISTS icon VARCHAR(255)
    """)
    op.execute("""
        ALTER TABLE submenus
        ADD COLUMN IF NOT EXISTS permission_name VARCHAR(255)
    """)


def downgrade() -> None:
    op.execute("ALTER TABLE submenus DROP COLUMN IF EXISTS permission_name")
    op.execute("ALTER TABLE submenus DROP COLUMN IF EXISTS icon")
    op.execute("ALTER TABLE menus DROP COLUMN IF EXISTS is_section")
    op.execute("ALTER TABLE menus DROP COLUMN IF EXISTS permission_name")
