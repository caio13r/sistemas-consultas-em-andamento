from sqlalchemy import create_engine, text
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from urllib.parse import quote_plus
import os
from dotenv import load_dotenv

load_dotenv()

# =============================================
# DATABASE LOCAL - PostgreSQL (Docker)
# Usado para: Users, Roles, Permissions, Menus, Temas, Audit, Servicos
# =============================================
DATABASE_URL = os.getenv("DATABASE_URL", "postgresql://postgres:postgres@db:5432/appdb")
engine = create_engine(DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()


# =============================================
# DB1 - MySQL (Locaweb) - RFB Auditoria, Labels, Cadastro
# =============================================
def _build_mysql_url(host_env, port_env, name_env, user_env, pass_env):
    host = os.getenv(host_env, "")
    port = os.getenv(port_env, "3306")
    name = os.getenv(name_env, "")
    user = os.getenv(user_env, "")
    pwd = os.getenv(pass_env, "")
    if not all([host, name, user]):
        return None
    return f"mysql+pymysql://{quote_plus(user)}:{quote_plus(pwd)}@{host}:{port}/{name}?charset=utf8mb4"


_db1_url = _build_mysql_url("DB1_HOST", "DB1_PORT", "DB1_NAME", "DB1_USERNAME", "DB1_PASSWORD")
engine_db1 = create_engine(_db1_url, pool_pre_ping=True, pool_recycle=3600) if _db1_url else None
SessionDB1 = sessionmaker(autocommit=False, autoflush=False, bind=engine_db1) if engine_db1 else None


def get_db1():
    if SessionDB1 is None:
        raise RuntimeError("DB1 (MySQL db_sistema_consultas) não configurado. Verifique .env")
    db = SessionDB1()
    try:
        yield db
    finally:
        db.close()


# =============================================
# DB2 - MySQL (WSCFO) - Webservice siscaf (sexo x especialidade x município)
# =============================================
_db2_url = _build_mysql_url("DB2_HOST", "DB2_PORT", "DB2_NAME", "DB2_USERNAME", "DB2_PASSWORD")
engine_db2 = create_engine(_db2_url, pool_pre_ping=True, pool_recycle=3600) if _db2_url else None
SessionDB2 = sessionmaker(autocommit=False, autoflush=False, bind=engine_db2) if engine_db2 else None


def get_db2():
    if SessionDB2 is None:
        raise RuntimeError("DB2 (MySQL WSCFO) não configurado. Verifique .env")
    db = SessionDB2()
    try:
        yield db
    finally:
        db.close()


# =============================================
# DB3 - SQL Server (Implanta) - Consulta Integrada, Auditoria, Fiscalizacao, etc.
# =============================================
def _build_sqlserver_url():
    host = os.getenv("DB3_HOST", "")
    name = os.getenv("DB3_NAME", "CFO_CWS")
    user = os.getenv("DB3_USERNAME", "")
    pwd = os.getenv("DB3_PASSWORD", "")
    if not all([host, user]):
        return None
    return (
        f"mssql+pyodbc://{quote_plus(user)}:{quote_plus(pwd)}@{host}/{name}"
        "?driver=ODBC+Driver+17+for+SQL+Server"
        "&Encrypt=no&TrustServerCertificate=yes"
    )


_db3_url = _build_sqlserver_url()
engine_db3 = create_engine(_db3_url, pool_pre_ping=True, pool_recycle=3600, pool_size=15, max_overflow=10) if _db3_url else None
SessionDB3 = sessionmaker(autocommit=False, autoflush=False, bind=engine_db3) if engine_db3 else None


def get_db3():
    if SessionDB3 is None:
        raise RuntimeError("DB3 (SQL Server CFO_CWS) não configurado. Verifique .env")
    db = SessionDB3()
    try:
        yield db
    finally:
        db.close()


# =============================================
# DB5 - MySQL (Prescrição)
# =============================================
_db5_url = _build_mysql_url("DB5_HOST", "DB5_PORT", "DB5_NAME", "DB5_USERNAME", "DB5_PASSWORD")
engine_db5 = create_engine(_db5_url, pool_pre_ping=True, pool_recycle=3600) if _db5_url else None
SessionDB5 = sessionmaker(autocommit=False, autoflush=False, bind=engine_db5) if engine_db5 else None


def get_db5():
    if SessionDB5 is None:
        raise RuntimeError("DB5 (MySQL db_prescricao) não configurado. Verifique .env")
    db = SessionDB5()
    try:
        yield db
    finally:
        db.close()


# =============================================
# DB6 - MySQL (Identity Professional)
# =============================================
_db6_url = _build_mysql_url("DB6_HOST", "DB6_PORT", "DB6_NAME", "DB6_USERNAME", "DB6_PASSWORD")
engine_db6 = create_engine(_db6_url, pool_pre_ping=True, pool_recycle=3600) if _db6_url else None
SessionDB6 = sessionmaker(autocommit=False, autoflush=False, bind=engine_db6) if engine_db6 else None


def get_db6():
    if SessionDB6 is None:
        raise RuntimeError("DB6 (MySQL identity_professional) não configurado. Verifique .env")
    db = SessionDB6()
    try:
        yield db
    finally:
        db.close()


# =============================================
# Redis
# =============================================
_redis_client = None


def get_redis():
    global _redis_client
    if _redis_client is None:
        import redis
        host = os.getenv("REDIS_HOST", "redis")
        port = int(os.getenv("REDIS_PORT", "6379"))
        pwd = os.getenv("REDIS_PASSWORD", "")
        _redis_client = redis.Redis(host=host, port=port, password=pwd or None, decode_responses=True)
    return _redis_client
