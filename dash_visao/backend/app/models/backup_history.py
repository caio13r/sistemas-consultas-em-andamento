from sqlalchemy import Column, Integer, String, DateTime, BigInteger
from sqlalchemy.sql import func
from ..database import Base


class BackupHistory(Base):
    __tablename__ = "backup_history"

    id = Column(Integer, primary_key=True, index=True)
    started_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)
    result = Column(String(50), nullable=False, default="pending")  # pending, sem_job, dump_ok, upload_ok, error
    job_id = Column(String(255), nullable=True)
    poll_status = Column(Integer, nullable=True)  # HTTP status do poll (200, 204, etc)
    upload_status = Column(Integer, nullable=True)  # HTTP status do upload
    file_size = Column(BigInteger, nullable=True)  # tamanho do dump em bytes
    duration_ms = Column(Integer, nullable=True)
    message = Column(String(500), nullable=True)
