from sqlalchemy import Column, Integer, String, Boolean, DateTime, ForeignKey, Text
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from ..database import Base


class UserRequest(Base):
    __tablename__ = "user_requests"

    id = Column(Integer, primary_key=True, index=True)
    nome_completo = Column(String(255), nullable=False)
    email = Column(String(255), nullable=False, index=True)
    telefone = Column(String(20), nullable=True)
    origem_tipo = Column(String(10), nullable=False)  # "cfo" ou "cro"
    organizacao = Column(String(100), nullable=False)  # "CFO" ou "CRO-XX"
    departamento = Column(String(100), nullable=True)
    justificativa = Column(Text, nullable=False)
    outro = Column(Text, nullable=True)
    sugestao_desenvolvimento = Column(Text, nullable=True)
    hashed_password = Column(String(255), nullable=True)

    # Workflow
    status = Column(String(20), default="pendente", nullable=False, index=True)
    # pendente, em_analise, aprovado, rejeitado, esclarecimento
    admin_id = Column(Integer, ForeignKey("users.id"), nullable=True)
    admin_notes = Column(Text, nullable=True)
    reject_reason = Column(Text, nullable=True)
    clarification_message = Column(Text, nullable=True)
    created_user_id = Column(Integer, ForeignKey("users.id"), nullable=True)
    resolved_at = Column(DateTime(timezone=True), nullable=True)

    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relationships
    items = relationship("UserRequestItem", back_populates="user_request", cascade="all, delete-orphan")
    admin = relationship("User", foreign_keys=[admin_id])
    created_user = relationship("User", foreign_keys=[created_user_id])


class UserRequestItem(Base):
    __tablename__ = "user_request_items"

    id = Column(Integer, primary_key=True, index=True)
    user_request_id = Column(Integer, ForeignKey("user_requests.id"), nullable=False)
    servico_id = Column(Integer, ForeignKey("servicos.id"), nullable=True)
    permission_name = Column(String(255), nullable=True)
    approved = Column(Boolean, default=True)  # Admin pode modificar

    # Relationships
    user_request = relationship("UserRequest", back_populates="items")
    servico = relationship("Servico")
