"""
Envia eventos para o MonitorSistema seguindo o Event Contract v1.
Tipo: push_http — POST http://host:porta/ingest/push-http
Header obrigatorio: x-ingest-key
Aceito: status 202
Retry: backoff exponencial com jitter (3 tentativas)
"""
import uuid
import random
import time
import logging
import threading
from datetime import datetime, timezone
from typing import Optional

import requests as http_client

from ..database import SessionLocal
from ..models.monitor_config import MonitorConfig

logger = logging.getLogger(__name__)

MAX_RETRIES = 3
BASE_DELAY = 0.5  # segundos
TIMEOUT = 5  # segundos

# Cache em memória da config (atualizado periodicamente)
_config_cache: Optional[dict] = None
_cache_lock = threading.Lock()


def _load_config() -> Optional[dict]:
    """Carrega a config de monitoramento do banco."""
    try:
        db = SessionLocal()
        try:
            cfg = db.query(MonitorConfig).first()
            if not cfg:
                return None
            return {
                "enabled": cfg.enabled,
                "host": cfg.host,
                "port": cfg.port,
                "mode": cfg.mode,
                "system_key": cfg.system_key,
                "environment": cfg.environment,
                "ingest_key": cfg.ingest_key or "",
            }
        finally:
            db.close()
    except Exception as e:
        logger.warning("Erro ao carregar config do monitor: %s", e)
        return None


def get_config() -> Optional[dict]:
    """Retorna a config do cache ou carrega do banco."""
    global _config_cache
    with _cache_lock:
        if _config_cache is None:
            _config_cache = _load_config()
        return _config_cache


def invalidate_cache():
    """Invalida o cache quando a config é alterada via API."""
    global _config_cache
    with _cache_lock:
        _config_cache = None


def build_url(cfg: dict) -> str:
    """Monta a URL de destino: http://host:porta/ingest/push-http"""
    return f"http://{cfg['host']}:{cfg['port']}/ingest/push-http"


def _build_headers(cfg: dict) -> dict:
    """Monta headers obrigatorios: content-type e x-ingest-key."""
    headers = {"Content-Type": "application/json"}
    if cfg.get("ingest_key"):
        headers["x-ingest-key"] = cfg["ingest_key"]
    return headers


def build_event(
    *,
    cfg: dict,
    operation: str,
    status: str,
    severity: str,
    message: str,
    error_code: Optional[str] = None,
    trace_id: Optional[str] = None,
    username: Optional[str] = None,
    metadata: Optional[dict] = None,
) -> dict:
    """Constroi payload seguindo MonitorSistema Event Contract v1."""
    event = {
        "eventId": str(uuid.uuid4()),
        "schemaVersion": "v1",
        "timestamp": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S.000Z"),
        "systemKey": cfg["system_key"],
        "environment": cfg["environment"],
        "serviceName": "dash-visao-api",
        "operation": operation,
        "status": status,
        "severity": severity,
        "message": message,
    }
    if error_code:
        event["errorCode"] = error_code
    if trace_id:
        event["traceId"] = trace_id
    if username:
        event["userContext"] = {"username": username}
    if metadata:
        event["metadata"] = metadata
    return event


def _send_with_retry(url: str, headers: dict, event: dict):
    """Envia com retry exponencial + jitter. Aceita 202 como sucesso."""
    for attempt in range(MAX_RETRIES):
        try:
            resp = http_client.post(url, json=event, headers=headers, timeout=TIMEOUT)
            # 202 = aceito pelo monitor
            if resp.status_code in (200, 201, 202):
                return
            if resp.status_code == 401:
                logger.warning("Monitor: x-ingest-key invalido (401)")
                return  # Não faz retry para auth inválida
            if resp.status_code == 422:
                logger.warning("Monitor: payload rejeitado (422): %s", resp.text[:200])
                return  # Não faz retry para validação
            logger.warning(
                "Monitor respondeu %s (tentativa %d/%d): %s",
                resp.status_code, attempt + 1, MAX_RETRIES, resp.text[:200],
            )
        except (http_client.ConnectionError, http_client.Timeout) as e:
            logger.debug(
                "Erro de conexao com monitor (tentativa %d/%d): %s",
                attempt + 1, MAX_RETRIES, e,
            )
        except Exception as e:
            logger.debug("Erro inesperado ao enviar para monitor: %s", e)
            return  # Não faz retry para erros inesperados

        # Backoff exponencial com jitter
        if attempt < MAX_RETRIES - 1:
            delay = BASE_DELAY * (2 ** attempt) + random.uniform(0, 0.5)
            time.sleep(delay)


def send_event(event: dict):
    """Envia evento para o MonitorSistema via push_http (fire-and-forget em thread)."""
    cfg = get_config()
    if not cfg or not cfg["enabled"]:
        return

    url = build_url(cfg)
    headers = _build_headers(cfg)

    threading.Thread(target=_send_with_retry, args=(url, headers, event), daemon=True).start()


def send_activity_event(
    *,
    method: str,
    path: str,
    status_code: int,
    duration_ms: int,
    username: Optional[str] = None,
    ip_address: Optional[str] = None,
    error_detail: Optional[str] = None,
):
    """Envia um evento de activity log para o MonitorSistema."""
    cfg = get_config()
    if not cfg or not cfg["enabled"]:
        return

    # Mapeia status HTTP para status do contrato
    if status_code < 400:
        status = "SUCCESS"
        severity = "INFO"
    elif status_code < 500:
        status = "FAILURE"
        severity = "WARN"
    else:
        status = "ERROR"
        severity = "ERROR"

    # Severity CRITICAL para 5xx em rotas importantes
    if status_code >= 500 and any(
        p in path for p in ["/token", "/users", "/consulta"]
    ):
        severity = "CRITICAL"

    message = f"{method} {path} -> {status_code}"

    metadata = {"httpMethod": method, "httpStatus": status_code}
    if ip_address:
        metadata["ipAddress"] = ip_address

    event = build_event(
        cfg=cfg,
        operation=f"{method} {path}",
        status=status,
        severity=severity,
        message=message,
        error_code=f"HTTP_{status_code}" if status_code >= 400 else None,
        username=username,
        metadata=metadata,
    )

    send_event(event)
