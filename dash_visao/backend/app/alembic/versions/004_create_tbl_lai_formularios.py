"""create tbl_lai_formularios for LAI/LGPD form submissions

Revision ID: 004_lai_formularios
Revises: 003_menu_fields
Create Date: 2026-03-17

"""
from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision = '004_lai_formularios'
down_revision = '003_menu_fields'
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.create_table(
        'tbl_lai_formularios',
        sa.Column('id', sa.Integer(), primary_key=True, autoincrement=True),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id', ondelete='SET NULL'), nullable=True),
        sa.Column('f_autlai', sa.String(200), nullable=False),
        sa.Column('f_portlai', sa.String(20), nullable=False),
        sa.Column('f_cargolai', sa.String(50), nullable=False),
        sa.Column('f_vinculolai', sa.String(300), nullable=False),
        sa.Column('f_aptoautlai', sa.String(5), nullable=False),
        sa.Column('f_aptolai', sa.String(5), nullable=False),
        sa.Column('f_sitelai', sa.String(5), nullable=False),
        sa.Column('f_portallai', sa.String(5), nullable=False),
        sa.Column('f_sitesolu', sa.String(50), nullable=True),
        sa.Column('f_anolai', sa.String(4), nullable=True),
        sa.Column('f_lgpd', sa.String(5), nullable=False),
        sa.Column('f_autlgpd', sa.String(5), nullable=False),
        sa.Column('f_arealgpd', sa.String(30), nullable=True),
        sa.Column('f_explgpd', sa.String(10), nullable=True),
        sa.Column('created_at', sa.DateTime(), server_default=sa.text('NOW()'), nullable=False),
        sa.Column('updated_at', sa.DateTime(), server_default=sa.text('NOW()'), onupdate=sa.text('NOW()'), nullable=False),
    )
    op.create_index('ix_tbl_lai_formularios_user_id', 'tbl_lai_formularios', ['user_id'])


def downgrade() -> None:
    op.drop_index('ix_tbl_lai_formularios_user_id', table_name='tbl_lai_formularios')
    op.drop_table('tbl_lai_formularios')
