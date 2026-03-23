import logging
from sqlalchemy.orm import Session
from app.db.session import SessionLocal
from app.db.init_db import init_db

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

def init() -> None:
    db = SessionLocal()
    try:
        logger.info("Inicializando banco de dados...")
        init_db(db)
        logger.info("Banco de dados inicializado com sucesso!")
    except Exception as e:
        logger.error(f"Erro ao inicializar banco de dados: {e}")
        raise e
    finally:
        db.close()

def main() -> None:
    logger.info("Criando dados iniciais")
    init()
    logger.info("Dados iniciais criados")

if __name__ == "__main__":
    main() 