from sqlalchemy import Column, Integer, String, Text, DateTime, ForeignKey
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from app.database import Base


class Documento(Base):
    __tablename__ = "documentos"

    id = Column(Integer, primary_key=True, index=True, autoincrement=True)
    titulo = Column(String(255), nullable=False)
    descricao = Column(Text, nullable=True)
    categoria = Column(String(100), nullable=False)  # oficio, portaria, ata, relatorio, outro
    filename = Column(String(255), nullable=False)
    file_path = Column(String(512), nullable=False)
    file_type = Column(String(50), nullable=False)   # mime type
    file_size = Column(Integer, nullable=False)       # bytes
    status = Column(String(20), default="pendente", nullable=False)  # pendente, aprovado, rejeitado
    uploaded_by = Column(Integer, ForeignKey("users.id"), nullable=False)
    validated_by = Column(Integer, ForeignKey("users.id"), nullable=True)
    validation_date = Column(DateTime, nullable=True)
    validation_notes = Column(Text, nullable=True)
    cro = Column(String(5), nullable=True)  # UF do CRO associado
    created_at = Column(DateTime, server_default=func.now())
    updated_at = Column(DateTime, onupdate=func.now())

    uploader = relationship("User", foreign_keys=[uploaded_by])
    validator = relationship("User", foreign_keys=[validated_by])
