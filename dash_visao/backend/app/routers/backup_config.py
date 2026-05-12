"""
Backup Config - Configuracao do agente de backup apontando para o Monitor.
Acesso restrito a administradores.
"""
import os
import shutil
import subprocess
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy.orm import Session
from sqlalchemy import desc
from pydantic import BaseModel
from typing import Optional
from datetime import datetime
from ..database import get_db, DATABASE_URL
from ..models import User
from ..models.backup_config import BackupConfig
from ..models.backup_history import BackupHistory
from ..models.monitor_config import MonitorConfig
from ..core.auth import get_current_active_user, check_permission

router = APIRouter(prefix="/backup-config", tags=["backup-config"])

WEEKDAY_NAMES = {0: "Dom", 1: "Seg", 2: "Ter", 3: "Qua", 4: "Qui", 5: "Sex", 6: "Sab"}


# --- Schemas ---

class BackupConfigResponse(BaseModel):
    id: int
    mode: str
    host: str
    port: int
    poll_interval: int
    weekdays: str
    weekdays_labels: list[str]
    start_time: str
    end_time: str
    enabled: bool
    effective_url: str
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class BackupConfigUpdate(BaseModel):
    mode: Optional[str] = None
    host: Optional[str] = None
    port: Optional[int] = None
    poll_interval: Optional[int] = None
    weekdays: Optional[str] = None
    start_time: Optional[str] = None
    end_time: Optional[str] = None
    enabled: Optional[bool] = None


class BackupHistoryItem(BaseModel):
    id: int
    started_at: Optional[datetime] = None
    result: str
    job_id: Optional[str] = None
    poll_status: Optional[int] = None
    upload_status: Optional[int] = None
    file_size: Optional[int] = None
    duration_ms: Optional[int] = None
    message: Optional[str] = None

    class Config:
        from_attributes = True


# --- Helpers ---

def _ensure_config(db: Session) -> BackupConfig:
    cfg = db.query(BackupConfig).first()
    if not cfg:
        cfg = BackupConfig(
            mode="desenvolvimento",
            host="192.168.100.112",
            port=9000,
            poll_interval=60,
            weekdays="0",
            start_time="16:00",
            end_time="16:20",
            enabled=True,
        )
        db.add(cfg)
        db.commit()
        db.refresh(cfg)
    return cfg


def _weekday_labels(weekdays_str: str) -> list[str]:
    try:
        return [WEEKDAY_NAMES[int(d.strip())] for d in weekdays_str.split(",") if d.strip().isdigit()]
    except (ValueError, KeyError):
        return []


def _effective_url(cfg: BackupConfig) -> str:
    if cfg.mode == "producao":
        return f"https://{cfg.host}"
    return f"http://{cfg.host}:{cfg.port}"


def _estimate_queries(cfg: BackupConfig) -> int:
    try:
        sh, sm = map(int, cfg.start_time.split(":"))
        eh, em = map(int, cfg.end_time.split(":"))
        window_seconds = (eh * 60 + em - sh * 60 - sm) * 60
        if window_seconds <= 0:
            return 0
        return max(1, window_seconds // max(cfg.poll_interval, 1))
    except Exception:
        return 0


def _to_response(cfg: BackupConfig) -> BackupConfigResponse:
    return BackupConfigResponse(
        id=cfg.id,
        mode=cfg.mode,
        host=cfg.host,
        port=cfg.port,
        poll_interval=cfg.poll_interval,
        weekdays=cfg.weekdays,
        weekdays_labels=_weekday_labels(cfg.weekdays),
        start_time=cfg.start_time,
        end_time=cfg.end_time,
        enabled=cfg.enabled,
        effective_url=_effective_url(cfg),
        updated_at=cfg.updated_at,
    )


def _detect_db_engine() -> str:
    """Detecta o motor do banco a partir de DATABASE_URL."""
    url = DATABASE_URL.lower()
    if "postgresql" in url or "postgres" in url:
        return "postgresql"
    if "mysql" in url:
        return "mysql"
    if "sqlite" in url:
        return "sqlite"
    return "unknown"


def _check_dump_tool() -> dict:
    """Verifica disponibilidade de pg_dump / mysqldump."""
    pg_dump = shutil.which("pg_dump")
    mysqldump = shutil.which("mysqldump")
    return {
        "pg_dump": {"available": pg_dump is not None, "path": pg_dump},
        "mysqldump": {"available": mysqldump is not None, "path": mysqldump},
    }


def _check_python_drivers() -> dict:
    """Verifica drivers Python instalados."""
    drivers = {}
    for mod in ("psycopg2", "pymysql"):
        try:
            __import__(mod)
            drivers[mod] = True
        except ImportError:
            drivers[mod] = False
    return drivers


def _get_system_key(db: Session) -> str:
    """Pega system_key do monitor_config (mesma chave usada na ingestao)."""
    mcfg = db.query(MonitorConfig).first()
    return mcfg.system_key if mcfg else "dash-visao"


# --- Endpoints ---

@router.get("", response_model=BackupConfigResponse)
def get_backup_config(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    cfg = _ensure_config(db)
    return _to_response(cfg)


@router.put("", response_model=BackupConfigResponse)
def update_backup_config(
    data: BackupConfigUpdate,
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    cfg = _ensure_config(db)

    if data.mode is not None:
        if data.mode not in ("desenvolvimento", "producao"):
            raise HTTPException(status_code=422, detail="Mode deve ser 'desenvolvimento' ou 'producao'")
        cfg.mode = data.mode
    if data.host is not None:
        cfg.host = data.host.strip()
    if data.port is not None:
        if data.port < 1 or data.port > 65535:
            raise HTTPException(status_code=422, detail="Porta deve estar entre 1 e 65535")
        cfg.port = data.port
    if data.poll_interval is not None:
        if data.poll_interval < 15:
            raise HTTPException(status_code=422, detail="Intervalo minimo e 15 segundos")
        cfg.poll_interval = data.poll_interval
    if data.weekdays is not None:
        parts = [p.strip() for p in data.weekdays.split(",")]
        for p in parts:
            if not p.isdigit() or int(p) > 6:
                raise HTTPException(status_code=422, detail="Dias devem ser numeros de 0 (Dom) a 6 (Sab)")
        cfg.weekdays = ",".join(parts)
    if data.start_time is not None:
        cfg.start_time = data.start_time.strip()
    if data.end_time is not None:
        cfg.end_time = data.end_time.strip()
    if data.enabled is not None:
        cfg.enabled = data.enabled

    db.commit()
    db.refresh(cfg)
    return _to_response(cfg)


@router.get("/progress")
def get_backup_progress(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Retorna progresso detalhado: worker status, fase, motor, ferramentas."""
    cfg = _ensure_config(db)
    now = datetime.now()
    current_weekday = (now.weekday() + 1) % 7
    active_days = [int(d.strip()) for d in cfg.weekdays.split(",") if d.strip().isdigit()]
    is_active_day = current_weekday in active_days
    current_time = now.strftime("%H:%M")
    in_window = is_active_day and cfg.start_time <= current_time <= cfg.end_time

    # Ultimo ciclo
    last_cycle = db.query(BackupHistory).order_by(desc(BackupHistory.started_at)).first()

    # Motor e ferramentas
    db_engine = _detect_db_engine()
    dump_tools = _check_dump_tool()
    python_drivers = _check_python_drivers()
    system_key = _get_system_key(db)

    # Fase atual
    if not cfg.enabled:
        phase = "Desabilitado"
        phase_detail = "O backup esta desabilitado na configuracao."
    elif not in_window:
        days_str = ",".join(str(d) for d in active_days)
        phase = "Em espera"
        phase_detail = f"Fora da janela de consultas. Dias=[{days_str}] horario={cfg.start_time}-{cfg.end_time}."
    else:
        phase = "Ativo"
        phase_detail = f"Dentro da janela ({cfg.start_time}-{cfg.end_time}). Polling a cada {cfg.poll_interval}s."

    return {
        "worker_active": cfg.enabled,
        "poll_interval": cfg.poll_interval,
        "poll_origin": "interface",
        "in_window": in_window,
        "phase": phase,
        "phase_detail": phase_detail,
        "last_cycle": {
            "started_at": last_cycle.started_at.isoformat() if last_cycle and last_cycle.started_at else None,
            "result": last_cycle.result if last_cycle else None,
            "job_id": last_cycle.job_id if last_cycle else None,
        } if last_cycle else None,
        "db_engine": db_engine,
        "dump_tools": dump_tools,
        "python_drivers": python_drivers,
        "system_key": system_key,
        "effective_url": _effective_url(cfg),
        "queries_per_window": _estimate_queries(cfg),
        "weekdays": cfg.weekdays,
        "weekdays_labels": _weekday_labels(cfg.weekdays),
        "start_time": cfg.start_time,
        "end_time": cfg.end_time,
    }


@router.get("/history")
def get_backup_history(
    page: int = Query(1, ge=1),
    per_page: int = Query(10, ge=1, le=100),
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Retorna historico de ciclos de backup paginado."""
    total = db.query(BackupHistory).count()
    items = (
        db.query(BackupHistory)
        .order_by(desc(BackupHistory.started_at))
        .offset((page - 1) * per_page)
        .limit(per_page)
        .all()
    )
    total_pages = max(1, (total + per_page - 1) // per_page)

    return {
        "items": [
            BackupHistoryItem.model_validate(item).model_dump()
            for item in items
        ],
        "total": total,
        "page": page,
        "per_page": per_page,
        "total_pages": total_pages,
    }


@router.get("/reference")
def get_backup_reference(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Retorna dados de referencia para cadastro no Monitor e contrato de saida."""
    cfg = _ensure_config(db)
    base_url = _effective_url(cfg)
    system_key = _get_system_key(db)
    db_engine = _detect_db_engine()
    dump_tools = _check_dump_tool()

    motor_map = {"postgresql": "POSTGRES", "mysql": "MYSQL", "sqlite": "SQLITE"}
    motor_label = motor_map.get(db_engine, db_engine.upper())

    dump_tool = "pg_dump" if db_engine == "postgresql" else "mysqldump" if db_engine == "mysql" else "unknown"

    poll_url = f"{base_url}/backup-agent/jobs/next?systemKey={system_key}"

    # Tabela de cadastro no Monitor
    registration = [
        {"field": "Sistema monitorado", "value": f"dash-visao (chave: {system_key})", "note": "Selecione na lista o sistema ja cadastrado cuja chave tecnica e a mesma da ingestao (systemKey)."},
        {"field": "Nome do alvo", "value": f"{motor_label} - dash-visao", "note": "Nome descritivo do backup; ajuste se o motor detectado nao refletir o ambiente real."},
        {"field": "Motor", "value": motor_label, "note": f"Inferido de DATABASE_URL neste ambiente; POSTGRES -> pg_dump, MYSQL -> mysqldump."},
        {"field": "Modo de backup", "value": "dump_periodico", "note": "Dump periodico alinhado ao agente que faz poll e envia o artifact."},
        {"field": "Execucao", "value": "AGENT_CALLBACK (recomendado)", "note": "O agente no sistema monitorado chama GET /backup-agent/jobs/next e faz POST do arquivo."},
        {"field": "Frequencia", "value": "Todos os dias (usa fuso abaixo)", "note": "No servidor monitorado mantenha o agente em cron/systemd com intervalo curto."},
        {"field": "Fuso", "value": "America/Sao_Paulo", "note": "Obrigatorio quando a Frequencia for periodica (horario no Monitor)."},
        {"field": "Retencao (dias)", "value": "30", "note": "Ajuste conforme politica de retencao da organizacao."},
        {"field": "URL documentacao restore", "value": "/admin/documentacao", "note": "URL da documentacao administrativa / runbook de restauracao deste sistema."},
        {"field": "Chave do agente (opcional)", "value": "Mesmo valor que MONITOR_BACKUP_AGENT_KEY (definido no ambiente)", "note": "Defina no Monitor o mesmo segredo que esta em MONITOR_BACKUP_AGENT_KEY neste servidor."},
        {"field": "Alvo ativo", "value": "Sim (marcado)", "note": "Manter ativo para o Monitor entregar jobs a este alvo."},
    ]

    # Config JSON sugerido
    config_json = {
        "agentScript": "scripts/backup_monitor_agent.py",
        "dumpTool": dump_tool,
        "environmentVariables": [
            "MONITOR_API_URL",
            "MONITOR_SYSTEM_KEY",
            "MONITOR_BACKUP_AGENT_KEY",
            "DATABASE_URL",
        ],
        "hint": "comandos e variaveis sugeridos no agente (sem credenciais)",
        "monitorFrequencyField": {
            "cronAdvancedExample": "0 2 * * *",
            "label": "Frequencia",
            "options": [
                "So 'Executar agora'",
                "Todos os dias (usa fuso abaixo)",
                "Semanal (usa fuso abaixo)",
                "Cron (avancado)",
            ],
            "recommendedPeriodic": "Todos os dias (usa fuso abaixo)",
        },
        "note": "Nao coloque senhas ou DATABASE_URL neste JSON; credenciais ficam so no host do agente.",
        "pollHeader": "x-backup-agent-key",
        "pollUrlExample": poll_url,
    }

    # Contrato de saida (egress)
    egress = {
        "poll_header": "x-backup-agent-key",
        "poll_header_env_var": "MONITOR_BACKUP_AGENT_KEY",
        "system_key": system_key,
        "poll_url": poll_url,
        "upload_method": "POST multipart, campo file, Authorization: Bearer conforme job",
    }

    # JSON de referencia do agente
    agent_reference_json = {
        "transportType": "backup_agent_pull",
        "pollMethod": "GET",
        "pollUrl": poll_url,
        "pollHeader": "x-backup-agent-key",
        "pollHeaderEnvVar": "MONITOR_BACKUP_AGENT_KEY",
        "uploadMethod": "POST",
        "uploadContentType": "multipart/form-data",
        "uploadFieldName": "file",
        "uploadAuth": "Authorization: Bearer <token do corpo do job>",
        "notes": [
            "Resposta 204: nenhum job pendente.",
            "Resposta 200 JSON: contem URL do artifact e token Bearer para o POST.",
        ],
    }

    # Exemplos curl
    curl_poll = f'curl -sS -H "x-backup-agent-key: $MONITOR_BACKUP_AGENT_KEY" "{poll_url}"'
    curl_upload = 'curl -sS -X POST "${ARTIFACT_URL}" -H "Authorization: Bearer ${UPLOAD_TOKEN}" -F "file=@./backup.dump"'

    return {
        "registration": registration,
        "config_json": config_json,
        "egress": egress,
        "agent_reference_json": agent_reference_json,
        "curl_poll": curl_poll,
        "curl_upload": curl_upload,
        "db_engine": db_engine,
        "motor_label": motor_label,
        "dump_tool": dump_tool,
        "system_key": system_key,
        "effective_url": base_url,
    }


@router.get("/status")
def get_backup_status(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Retorna status e estimativas da janela de backup."""
    cfg = _ensure_config(db)
    now = datetime.now()
    current_weekday = (now.weekday() + 1) % 7
    active_days = [int(d.strip()) for d in cfg.weekdays.split(",") if d.strip().isdigit()]
    is_active_day = current_weekday in active_days
    current_time = now.strftime("%H:%M")
    in_window = is_active_day and cfg.start_time <= current_time <= cfg.end_time
    queries_per_window = _estimate_queries(cfg)

    return {
        "enabled": cfg.enabled,
        "in_window": in_window,
        "is_active_day": is_active_day,
        "current_weekday": current_weekday,
        "current_time": current_time,
        "queries_per_window": queries_per_window,
        "effective_url": _effective_url(cfg),
        "origin": "interface",
    }
