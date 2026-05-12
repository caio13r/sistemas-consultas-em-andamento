from sqlalchemy import Column, Integer, String, DateTime
from sqlalchemy.sql import func
from ..database import Base


class ContatoFiscalizacao(Base):
    __tablename__ = "contatos_fiscalizacao"

    id = Column(Integer, primary_key=True, index=True)
    cro = Column(String(2), nullable=False, index=True)
    nome = Column(String(200), nullable=False)
    email = Column(String(200), nullable=True)
    telefone_contato = Column(String(30), nullable=True)
    telefone_whatsapp = Column(String(30), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
