from .database import SessionLocal, engine
from .init_permissions import init_permissions_data
from .init_servicos import init_servicos_data
from sqlalchemy import text


def run_migrations():
    """Executa ALTER TABLEs necessários para colunas novas em tabelas existentes"""
    with engine.connect() as conn:
        # Adicionar scope_type em permissions (se não existe)
        conn.execute(text("""
            ALTER TABLE permissions
            ADD COLUMN IF NOT EXISTS scope_type VARCHAR(20) DEFAULT 'global'
        """))

        # Adicionar scope_type em servicos (se não existe)
        conn.execute(text("""
            ALTER TABLE servicos
            ADD COLUMN IF NOT EXISTS scope_type VARCHAR(20) DEFAULT 'global'
        """))

        conn.commit()
        print("Migrações de colunas executadas com sucesso!")


def init_db():
    """Inicializa o banco de dados local (PostgreSQL) com dados básicos"""
    try:
        # Executa migrações de colunas em tabelas existentes
        run_migrations()
        # Inicializa dados de permissões, menus e roles
        init_permissions_data()
        # Inicializa catálogo de serviços
        init_servicos_data()
        print("Banco de dados inicializado com sucesso!")
    except Exception as e:
        print(f"Erro ao inicializar banco de dados: {e}")


if __name__ == "__main__":
    init_db()
