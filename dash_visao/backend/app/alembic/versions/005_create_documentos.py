"""create documentos table for document management

Revision ID: 005_documentos
Revises: 004_lai_formularios
Create Date: 2026-03-17

"""
from alembic import op
import sqlalchemy as sa


revision = '005_documentos'
down_revision = '004_lai_formularios'
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.execute("""
        CREATE TABLE IF NOT EXISTS documentos (
            id SERIAL PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,
            categoria VARCHAR(100) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(512) NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            file_size INTEGER NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pendente',
            uploaded_by INTEGER NOT NULL REFERENCES users(id),
            validated_by INTEGER REFERENCES users(id),
            validation_date TIMESTAMP,
            validation_notes TEXT,
            cro VARCHAR(5),
            created_at TIMESTAMP DEFAULT NOW(),
            updated_at TIMESTAMP
        )
    """)
    op.execute("CREATE INDEX IF NOT EXISTS ix_documentos_categoria ON documentos(categoria)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_documentos_status ON documentos(status)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_documentos_uploaded_by ON documentos(uploaded_by)")


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS documentos")
