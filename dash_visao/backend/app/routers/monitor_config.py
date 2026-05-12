"""
Monitor Config - Configuração do destino de monitoramento central.
Acesso restrito a administradores.
"""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from pydantic import BaseModel
from typing import Optional
from datetime import datetime
from ..database import get_db
from ..models import User
from ..models.monitor_config import MonitorConfig
from ..core.auth import get_current_active_user, check_permission
from ..monitoring.sender import invalidate_cache

router = APIRouter(prefix="/monitor-config", tags=["monitor-config"])


class MonitorConfigResponse(BaseModel):
    id: int
    mode: str
    host: str
    port: int
    enabled: bool
    system_key: str
    environment: str
    ingest_key_configured: bool
    effective_url: str
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class MonitorConfigUpdate(BaseModel):
    mode: Optional[str] = None
    host: Optional[str] = None
    port: Optional[int] = None
    enabled: Optional[bool] = None
    system_key: Optional[str] = None
    environment: Optional[str] = None
    ingest_key: Optional[str] = None


def _ensure_config(db: Session) -> MonitorConfig:
    """Garante que existe uma config, criando com defaults se necessario."""
    cfg = db.query(MonitorConfig).first()
    if not cfg:
        cfg = MonitorConfig(
            mode="desenvolvimento",
            host="192.168.100.112",
            port=9000,
            enabled=True,
            system_key="dash-visao",
            environment="dev",
            ingest_key="",
        )
        db.add(cfg)
        db.commit()
        db.refresh(cfg)
    return cfg


def _to_response(cfg: MonitorConfig) -> MonitorConfigResponse:
    return MonitorConfigResponse(
        id=cfg.id,
        mode=cfg.mode,
        host=cfg.host,
        port=cfg.port,
        enabled=cfg.enabled,
        system_key=cfg.system_key,
        environment=cfg.environment,
        ingest_key_configured=bool(cfg.ingest_key),
        effective_url=f"http://{cfg.host}:{cfg.port}/ingest/push-http",
        updated_at=cfg.updated_at,
    )


@router.get("", response_model=MonitorConfigResponse)
def get_monitor_config(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Retorna a configuracao atual do destino de monitoramento."""
    cfg = _ensure_config(db)
    return _to_response(cfg)


@router.put("", response_model=MonitorConfigResponse)
def update_monitor_config(
    data: MonitorConfigUpdate,
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Atualiza a configuracao do destino de monitoramento."""
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
    if data.enabled is not None:
        cfg.enabled = data.enabled
    if data.system_key is not None:
        cfg.system_key = data.system_key.strip()
    if data.environment is not None:
        if data.environment not in ("dev", "homolog", "prod"):
            raise HTTPException(status_code=422, detail="Environment deve ser 'dev', 'homolog' ou 'prod'")
        cfg.environment = data.environment
    if data.ingest_key is not None:
        cfg.ingest_key = data.ingest_key.strip()

    db.commit()
    db.refresh(cfg)

    # Invalida cache do sender
    invalidate_cache()

    return _to_response(cfg)


@router.post("/test")
def test_monitor_connection(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Envia um evento de teste para o MonitorSistema."""
    from ..monitoring.sender import build_event, build_url, _build_headers
    import requests as http_client

    cfg_db = _ensure_config(db)
    cfg = {
        "enabled": cfg_db.enabled,
        "host": cfg_db.host,
        "port": cfg_db.port,
        "mode": cfg_db.mode,
        "system_key": cfg_db.system_key,
        "environment": cfg_db.environment,
        "ingest_key": cfg_db.ingest_key or "",
    }

    if not cfg["enabled"]:
        raise HTTPException(status_code=400, detail="Monitoramento esta desabilitado")

    url = build_url(cfg)
    headers = _build_headers(cfg)
    event = build_event(
        cfg=cfg,
        operation="TEST /monitor-config/test",
        status="SUCCESS",
        severity="INFO",
        message=f"Teste de conexao do dash-visao por {current_user.username}",
        username=current_user.username,
        metadata={"testEvent": True},
    )

    try:
        resp = http_client.post(url, json=event, headers=headers, timeout=5)
        success = resp.status_code in (200, 201, 202)
        msg = "Evento de teste aceito pelo monitor (202)" if success else f"Monitor respondeu com erro: {resp.status_code}"
        if resp.status_code == 401:
            msg = "x-ingest-key inválido — verifique a chave configurada"
        elif resp.status_code == 422:
            msg = f"Payload rejeitado pelo monitor: {resp.text[:200]}"
        return {
            "success": success,
            "status_code": resp.status_code,
            "url": url,
            "message": msg,
        }
    except http_client.ConnectionError:
        return {
            "success": False,
            "status_code": None,
            "url": url,
            "message": f"Não foi possível conectar em {url}",
        }
    except http_client.Timeout:
        return {
            "success": False,
            "status_code": None,
            "url": url,
            "message": f"Timeout ao conectar em {url} (limite: 5s)",
        }
