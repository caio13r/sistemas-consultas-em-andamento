from sqlalchemy import Column, Integer, String, DateTime, Boolean
from sqlalchemy.sql import func
from ..database import Base


class MonitorConfig(Base):
    __tablename__ = "monitor_config"

    id = Column(Integer, primary_key=True, index=True)
    mode = Column(String(20), nullable=False, default="desenvolvimento")  # desenvolvimento / producao
    host = Column(String(255), nullable=False, default="192.168.100.112")
    port = Column(Integer, nullable=False, default=9000)
    enabled = Column(Boolean, nullable=False, default=True)
    system_key = Column(String(100), nullable=False, default="dash-visao")
    environment = Column(String(20), nullable=False, default="dev")  # dev / homolog / prod
    ingest_key = Column(String(255), nullable=True, default="")  # x-ingest-key para autenticacao
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
