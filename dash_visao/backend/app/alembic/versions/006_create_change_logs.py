"""create change_logs table for audit trail

Revision ID: 006_change_logs
Revises: 005_documentos
Create Date: 2026-03-17

"""
from alembic import op
import sqlalchemy as sa


revision = '006_change_logs'
down_revision = '005_documentos'
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.execute("""
        CREATE TABLE IF NOT EXISTS change_logs (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id),
            username VARCHAR(50),
            action VARCHAR(10) NOT NULL,
            resource VARCHAR(100) NOT NULL,
            resource_id VARCHAR(50),
            description VARCHAR(500),
            request_body TEXT,
            ip_address VARCHAR(45),
            endpoint VARCHAR(500) NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    """)
    op.execute("CREATE INDEX IF NOT EXISTS ix_change_logs_username ON change_logs(username)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_change_logs_action ON change_logs(action)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_change_logs_resource ON change_logs(resource)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_change_logs_created_at ON change_logs(created_at)")


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS change_logs")
