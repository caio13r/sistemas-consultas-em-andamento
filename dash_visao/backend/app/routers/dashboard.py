"""
Dashboard - Endpoints de estatisticas e indicadores para o dashboard.
Fonte: DB3 (SQL Server) + Local (PostgreSQL)
"""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import text, func
from sqlalchemy.orm import Session
from typing import Optional
from datetime import datetime, timedelta
import logging

from ..database import get_db, get_db3
from ..models import User
from ..models.activity_log import ActivityLog
from ..core.auth import get_current_active_user

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/dashboard", tags=["dashboard"])

# Total oficial exibido no painel (Total Brasil Ativo)
TOTAL_BRASIL_ATIVO = 856_181


@router.get("/stats")
def get_dashboard_stats(
    current_user: User = Depends(get_current_active_user),
    db: Session = Depends(get_db),
):
    """Retorna estatisticas gerais para o dashboard."""

    # Usuarios do sistema
    total_users = db.query(func.count(User.id)).scalar() or 0
    active_users = db.query(func.count(User.id)).filter(User.is_active == True).scalar() or 0

    # Atividade recente (ultimos 7 dias)
    seven_days_ago = datetime.utcnow() - timedelta(days=7)
    recent_activity_count = 0
    try:
        recent_activity_count = (
            db.query(func.count(ActivityLog.id))
            .filter(ActivityLog.created_at >= seven_days_ago)
            .scalar() or 0
        )
    except Exception:
        pass

    # Usuarios que fizeram login hoje (POST /auth/token com sucesso)
    today_start = datetime.utcnow().replace(hour=0, minute=0, second=0, microsecond=0)
    active_today = 0
    try:
        active_today = (
            db.query(func.count(func.distinct(ActivityLog.username)))
            .filter(
                ActivityLog.created_at >= today_start,
                ActivityLog.username.isnot(None),
                ActivityLog.method == "POST",
                ActivityLog.path.like("%/token"),
                ActivityLog.status_code == 200,
            )
            .scalar() or 0
        )
    except Exception:
        pass

    return {
        "total_users": total_users,
        "active_users": active_users,
        "active_today": active_today,
        "recent_activity_7d": recent_activity_count,
    }


@router.get("/stats/db3")
def get_dashboard_stats_db3(
    current_user: User = Depends(get_current_active_user),
    db3: Session = Depends(get_db3),
):
    """Retorna estatisticas do banco de dados principal (DB3 SQL Server)."""
    stats = {}

    stats["profissionais_ativos"] = TOTAL_BRASIL_ATIVO

    try:
        # Total de empresas ativas
        result = db3.execute(text(
            "SELECT COUNT(*) as total FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa WHERE Situacao = 'Ativo'"
        ))
        row = result.fetchone()
        stats["empresas_ativas"] = row[0] if row else 0
    except Exception as e:
        logger.warning(f"Erro ao buscar empresas ativas: {e}")
        stats["empresas_ativas"] = None

    try:
        # Profissionais por categoria (top 5) — totais nacionais por categoria
        result = db3.execute(text("""
            SELECT [CD], [TPD], [TSB], [ASB], [APD], [EPAO], [LB], [ECIPO]
            FROM CFO_CWS.dbo.Cons_Total_Ativos_Localidade
            WHERE CRO = 'BRASIL' AND UF = 'TOTAL'
        """))
        row = result.fetchone()
        if row:
            categorias = [
                {"categoria": "CD", "total": row[0] or 0},
                {"categoria": "TPD", "total": row[1] or 0},
                {"categoria": "TSB", "total": row[2] or 0},
                {"categoria": "ASB", "total": row[3] or 0},
                {"categoria": "APD", "total": row[4] or 0},
                {"categoria": "EPAO", "total": row[5] or 0},
                {"categoria": "LB", "total": row[6] or 0},
                {"categoria": "ECIPO", "total": row[7] or 0},
            ]
            stats["profissionais_por_categoria"] = sorted(
                categorias, key=lambda item: item["total"], reverse=True
            )[:5]
        else:
            stats["profissionais_por_categoria"] = []
    except Exception as e:
        logger.warning(f"Erro ao buscar por categoria: {e}")
        stats["profissionais_por_categoria"] = []

    try:
        # Profissionais por UF — totais por CRO na visão nacional de ativos
        result = db3.execute(text("""
            SELECT TOP 10 CRO as uf, TOTAL as total
            FROM CFO_CWS.dbo.Cons_Total_Ativos_Localidade
            WHERE UF = 'TOTAL' AND CRO <> 'BRASIL'
            ORDER BY TOTAL DESC
        """))
        stats["profissionais_por_uf"] = [
            {"uf": r[0], "total": r[1]} for r in result
        ]
    except Exception as e:
        logger.warning(f"Erro ao buscar por UF: {e}")
        stats["profissionais_por_uf"] = []

    return stats


@router.get("/stats/db3/regioes")
def get_stats_by_region(
    current_user: User = Depends(get_current_active_user),
    db3: Session = Depends(get_db3),
):
    """Retorna profissionais e empresas agrupados por regiao do Brasil."""
    uf_regiao = {
        "AC": "Norte", "AM": "Norte", "AP": "Norte", "PA": "Norte",
        "RO": "Norte", "RR": "Norte", "TO": "Norte",
        "AL": "Nordeste", "BA": "Nordeste", "CE": "Nordeste", "MA": "Nordeste",
        "PB": "Nordeste", "PE": "Nordeste", "PI": "Nordeste", "RN": "Nordeste",
        "SE": "Nordeste",
        "DF": "Centro-Oeste", "GO": "Centro-Oeste", "MS": "Centro-Oeste",
        "MT": "Centro-Oeste",
        "ES": "Sudeste", "MG": "Sudeste", "RJ": "Sudeste", "SP": "Sudeste",
        "PR": "Sul", "RS": "Sul", "SC": "Sul",
    }

    regioes: dict = {}
    for r in ["Norte", "Nordeste", "Centro-Oeste", "Sudeste", "Sul"]:
        regioes[r] = {"profissionais": 0, "empresas": 0}

    try:
        result = db3.execute(text("""
            SELECT CRO as uf, TOTAL as total
            FROM CFO_CWS.dbo.Cons_Total_Ativos_Localidade
            WHERE UF = 'TOTAL' AND CRO <> 'BRASIL'
        """))
        for row in result:
            uf = row[0].strip().upper() if row[0] else None
            if uf and uf in uf_regiao:
                regioes[uf_regiao[uf]]["profissionais"] += row[1]
    except Exception as e:
        logger.warning(f"Erro ao buscar profissionais por regiao: {e}")

    try:
        result = db3.execute(text("""
            SELECT LEFT(CRO, 2) as uf, COUNT(*) as total
            FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa
            WHERE Situacao = 'Ativo' AND CRO IS NOT NULL AND LEN(CRO) >= 2
            GROUP BY LEFT(CRO, 2)
        """))
        for row in result:
            uf = row[0].strip().upper() if row[0] else None
            if uf and uf in uf_regiao:
                regioes[uf_regiao[uf]]["empresas"] += row[1]
    except Exception as e:
        logger.warning(f"Erro ao buscar empresas por regiao: {e}")

    return regioes


@router.get("/activity-chart")
def get_activity_chart(
    current_user: User = Depends(get_current_active_user),
    db: Session = Depends(get_db),
):
    """Retorna consultas por dia nos ultimos 7 dias e top modulos acessados."""
    seven_days_ago = datetime.utcnow() - timedelta(days=7)
    result = {}

    # Consultas por dia (7 dias)
    try:
        rows = (
            db.query(
                func.date(ActivityLog.created_at).label("dia"),
                func.count(ActivityLog.id).label("total"),
            )
            .filter(ActivityLog.created_at >= seven_days_ago)
            .group_by(func.date(ActivityLog.created_at))
            .order_by(func.date(ActivityLog.created_at))
            .all()
        )
        result["por_dia"] = [
            {"dia": str(r.dia), "total": r.total} for r in rows
        ]
    except Exception:
        result["por_dia"] = []

    # Top 5 modulos mais acessados (7 dias)
    try:
        rows = (
            db.query(
                ActivityLog.path,
                func.count(ActivityLog.id).label("total"),
            )
            .filter(
                ActivityLog.created_at >= seven_days_ago,
                ActivityLog.path.isnot(None),
                ActivityLog.method == "GET",
                ~ActivityLog.path.like("/health%"),
                ~ActivityLog.path.like("/api/dashboard%"),
            )
            .group_by(ActivityLog.path)
            .order_by(func.count(ActivityLog.id).desc())
            .limit(5)
            .all()
        )
        result["top_modulos"] = [
            {"modulo": r.path, "total": r.total} for r in rows
        ]
    except Exception:
        result["top_modulos"] = []

    # Usuarios unicos por dia (7 dias)
    try:
        rows = (
            db.query(
                func.date(ActivityLog.created_at).label("dia"),
                func.count(func.distinct(ActivityLog.username)).label("total"),
            )
            .filter(
                ActivityLog.created_at >= seven_days_ago,
                ActivityLog.username.isnot(None),
            )
            .group_by(func.date(ActivityLog.created_at))
            .order_by(func.date(ActivityLog.created_at))
            .all()
        )
        result["usuarios_por_dia"] = [
            {"dia": str(r.dia), "total": r.total} for r in rows
        ]
    except Exception:
        result["usuarios_por_dia"] = []

    return result


@router.get("/recent-activity")
def get_recent_activity(
    current_user: User = Depends(get_current_active_user),
    db: Session = Depends(get_db),
):
    """Retorna atividades recentes do usuario logado."""
    try:
        logs = (
            db.query(ActivityLog)
            .filter(ActivityLog.username == current_user.username)
            .order_by(ActivityLog.created_at.desc())
            .limit(10)
            .all()
        )
        return [
            {
                "method": log.method,
                "path": log.path,
                "status_code": log.status_code,
                "created_at": log.created_at.isoformat() if log.created_at else None,
                "duration_ms": log.duration_ms,
            }
            for log in logs
        ]
    except Exception:
        return []
