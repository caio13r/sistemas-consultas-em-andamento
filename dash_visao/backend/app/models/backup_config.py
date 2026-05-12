from sqlalchemy import Column, Integer, String, DateTime, Boolean, Text
from sqlalchemy.sql import func
from ..database import Base


class BackupConfig(Base):
    __tablename__ = "backup_config"

    id = Column(Integer, primary_key=True, index=True)
    mode = Column(String(20), nullable=False, default="desenvolvimento")  # desenvolvimento / producao
    host = Column(String(255), nullable=False, default="192.168.100.112")
    port = Column(Integer, nullable=False, default=9000)
    poll_interval = Column(Integer, nullable=False, default=60)  # segundos entre consultas
    weekdays = Column(Text, nullable=False, default="0")  # dias da semana separados por virgula (0=dom,1=seg..6=sab)
    start_time = Column(String(5), nullable=False, default="16:00")  # HH:MM
    end_time = Column(String(5), nullable=False, default="16:20")  # HH:MM
    enabled = Column(Boolean, nullable=False, default=True)
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
