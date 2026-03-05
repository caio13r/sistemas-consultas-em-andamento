from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text
from sqlalchemy.sql import func
from ..database import Base


class Servico(Base):
    __tablename__ = "servicos"

    id = Column(Integer, primary_key=True, index=True)
    nome = Column(String(255), nullable=False)
    slug = Column(String(100), unique=True, index=True, nullable=False)
    descricao = Column(Text, nullable=True)
    icone = Column(String(100), nullable=True)
    ativo = Column(Boolean, default=True)
    ordem = Column(Integer, default=0)
    permissao_nome = Column(String(255), nullable=False)  # ex: view_consulta_integrada
    scope_type = Column(String(20), default="global")  # "global", "cfo", "cro"
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
