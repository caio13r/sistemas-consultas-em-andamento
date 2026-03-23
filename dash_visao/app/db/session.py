from sqlalchemy import create_engine
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from app.core.config import settings

# Criar o engine do SQLAlchemy
engine = create_engine(settings.DATABASE_URL)

# Criar uma sessão local
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# Criar a base para os modelos
Base = declarative_base()

# Função para obter uma sessão do banco de dados
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close() 