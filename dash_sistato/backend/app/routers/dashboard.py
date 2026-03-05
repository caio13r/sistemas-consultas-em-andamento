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

    # Usuarios ativos hoje (pelo log de atividade)
    today_start = datetime.utcnow().replace(hour=0, minute=0, second=0, microsecond=0)
    active_today = 0
    try:
        active_today = (
            db.query(func.count(func.distinct(ActivityLog.username)))
            .filter(ActivityLog.created_at >= today_start, ActivityLog.username.isnot(None))
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

    try:
        # Total de profissionais ativos
        result = db3.execute(text(
            "SELECT COUNT(*) as total FROM Cons_Visao_Nacional_PF_Dados_do_Profissional WHERE Situacao = 'Ativo'"
        ))
        row = result.fetchone()
        stats["profissionais_ativos"] = row[0] if row else 0
    except Exception as e:
        logger.warning(f"Erro ao buscar profissionais ativos: {e}")
        stats["profissionais_ativos"] = None

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
        # Profissionais por categoria (top 5)
        result = db3.execute(text("""
            SELECT TOP 5 Categoria, COUNT(*) as total
            FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
            WHERE Situacao = 'Ativo'
            GROUP BY Categoria
            ORDER BY total DESC
        """))
        stats["profissionais_por_categoria"] = [
            {"categoria": r[0], "total": r[1]} for r in result
        ]
    except Exception as e:
        logger.warning(f"Erro ao buscar por categoria: {e}")
        stats["profissionais_por_categoria"] = []

    try:
        # Profissionais por UF (extrair do CRO)
        result = db3.execute(text("""
            SELECT TOP 10
                SUBSTRING(CRO, 5, 2) as uf,
                COUNT(*) as total
            FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
            WHERE Situacao = 'Ativo' AND LEN(CRO) >= 6
            GROUP BY SUBSTRING(CRO, 5, 2)
            ORDER BY total DESC
        """))
        stats["profissionais_por_uf"] = [
            {"uf": r[0], "total": r[1]} for r in result
        ]
    except Exception as e:
        logger.warning(f"Erro ao buscar por UF: {e}")
        stats["profissionais_por_uf"] = []

    return stats


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
