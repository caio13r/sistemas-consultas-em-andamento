from sqlalchemy import Column, Integer, String, DateTime, Text, ForeignKey
from sqlalchemy.sql import func
from ..database import Base


class ChangeLog(Base):
    __tablename__ = "change_logs"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True)
    username = Column(String(50), nullable=True, index=True)
    action = Column(String(10), nullable=False, index=True)  # CREATE, UPDATE, DELETE
    resource = Column(String(100), nullable=False, index=True)  # users, roles, permissions, documentos, etc.
    resource_id = Column(String(50), nullable=True)  # ID do registro alterado
    description = Column(String(500), nullable=True)  # Resumo legível da alteração
    request_body = Column(Text, nullable=True)  # JSON do corpo da requisição (dados enviados)
    ip_address = Column(String(45), nullable=True)
    endpoint = Column(String(500), nullable=False)  # Path da API
    created_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)
