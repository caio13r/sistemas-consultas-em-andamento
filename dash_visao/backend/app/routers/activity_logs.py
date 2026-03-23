"""
Activity Logs - Endpoints de consulta de logs de atividades do sistema.
Acesso restrito a administradores.
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from sqlalchemy import desc
from typing import Optional
from datetime import datetime
from pydantic import BaseModel
from ..database import get_db
from ..models import User
from ..models.activity_log import ActivityLog
from ..models.change_log import ChangeLog
from ..core.auth import get_current_active_user, check_permission

router = APIRouter(prefix="/activity-logs", tags=["activity-logs"])


class ActivityLogResponse(BaseModel):
    id: int
    user_id: Optional[int] = None
    username: Optional[str] = None
    method: str
    path: str
    status_code: Optional[int] = None
    ip_address: Optional[str] = None
    user_agent: Optional[str] = None
    query_params: Optional[str] = None
    duration_ms: Optional[int] = None
    created_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class ActivityLogListResponse(BaseModel):
    total: int
    logs: list[ActivityLogResponse]


@router.get("", response_model=ActivityLogListResponse)
def list_activity_logs(
    username: Optional[str] = Query(None, description="Filtrar por username"),
    method: Optional[str] = Query(None, description="Filtrar por metodo HTTP (GET, POST, etc)"),
    path: Optional[str] = Query(None, description="Filtrar por path (busca parcial)"),
    start_date: Optional[str] = Query(None, description="Data inicio (YYYY-MM-DD)"),
    end_date: Optional[str] = Query(None, description="Data fim (YYYY-MM-DD)"),
    page: int = Query(1, ge=1),
    per_page: int = Query(50, ge=1, le=200),
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Lista logs de atividades com filtros. Requer permissao manage_users."""
    query = db.query(ActivityLog)

    if username:
        query = query.filter(ActivityLog.username == username)
    if method:
        query = query.filter(ActivityLog.method == method.upper())
    if path:
        query = query.filter(ActivityLog.path.ilike(f"%{path}%"))
    if start_date:
        try:
            dt = datetime.strptime(start_date, "%Y-%m-%d")
            query = query.filter(ActivityLog.created_at >= dt)
        except ValueError:
            pass
    if end_date:
        try:
            dt = datetime.strptime(end_date, "%Y-%m-%d")
            dt = dt.replace(hour=23, minute=59, second=59)
            query = query.filter(ActivityLog.created_at <= dt)
        except ValueError:
            pass

    total = query.count()
    logs = (
        query.order_by(desc(ActivityLog.created_at))
        .offset((page - 1) * per_page)
        .limit(per_page)
        .all()
    )

    return ActivityLogListResponse(total=total, logs=logs)


@router.get("/stats")
def activity_log_stats(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Retorna estatisticas basicas de atividade."""
    from sqlalchemy import func

    total = db.query(func.count(ActivityLog.id)).scalar()
    unique_users = db.query(func.count(func.distinct(ActivityLog.username))).filter(
        ActivityLog.username.isnot(None)
    ).scalar()

    # Top 10 rotas mais acessadas
    top_routes = (
        db.query(ActivityLog.path, func.count(ActivityLog.id).label("count"))
        .group_by(ActivityLog.path)
        .order_by(desc("count"))
        .limit(10)
        .all()
    )

    # Top 10 usuarios mais ativos
    top_users = (
        db.query(ActivityLog.username, func.count(ActivityLog.id).label("count"))
        .filter(ActivityLog.username.isnot(None))
        .group_by(ActivityLog.username)
        .order_by(desc("count"))
        .limit(10)
        .all()
    )

    return {
        "total_logs": total,
        "unique_users": unique_users,
        "top_routes": [{"path": r[0], "count": r[1]} for r in top_routes],
        "top_users": [{"username": u[0], "count": u[1]} for u in top_users],
    }


# =============================================
# CHANGE LOGS — Histórico de alterações
# =============================================

class ChangeLogResponse(BaseModel):
    id: int
    user_id: Optional[int] = None
    username: Optional[str] = None
    action: str
    resource: str
    resource_id: Optional[str] = None
    description: Optional[str] = None
    request_body: Optional[str] = None
    ip_address: Optional[str] = None
    endpoint: str
    created_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class ChangeLogListResponse(BaseModel):
    total: int
    logs: list[ChangeLogResponse]


@router.get("/changes", response_model=ChangeLogListResponse)
def list_change_logs(
    username: Optional[str] = Query(None, description="Filtrar por username"),
    action: Optional[str] = Query(None, description="Filtrar por ação (CREATE, UPDATE, DELETE)"),
    resource: Optional[str] = Query(None, description="Filtrar por recurso (users, roles, documentos, etc)"),
    start_date: Optional[str] = Query(None, description="Data início (YYYY-MM-DD)"),
    end_date: Optional[str] = Query(None, description="Data fim (YYYY-MM-DD)"),
    page: int = Query(1, ge=1),
    per_page: int = Query(50, ge=1, le=200),
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Lista histórico de alterações (criações, edições, exclusões). Requer permissão manage_users."""
    query = db.query(ChangeLog)

    if username:
        query = query.filter(ChangeLog.username == username)
    if action:
        query = query.filter(ChangeLog.action == action.upper())
    if resource:
        query = query.filter(ChangeLog.resource == resource)
    if start_date:
        try:
            dt = datetime.strptime(start_date, "%Y-%m-%d")
            query = query.filter(ChangeLog.created_at >= dt)
        except ValueError:
            pass
    if end_date:
        try:
            dt = datetime.strptime(end_date, "%Y-%m-%d")
            dt = dt.replace(hour=23, minute=59, second=59)
            query = query.filter(ChangeLog.created_at <= dt)
        except ValueError:
            pass

    total = query.count()
    logs = (
        query.order_by(desc(ChangeLog.created_at))
        .offset((page - 1) * per_page)
        .limit(per_page)
        .all()
    )

    return ChangeLogListResponse(total=total, logs=logs)


@router.get("/changes/stats")
def change_log_stats(
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Estatísticas de alterações."""
    from sqlalchemy import func

    total = db.query(func.count(ChangeLog.id)).scalar()

    by_action = (
        db.query(ChangeLog.action, func.count(ChangeLog.id).label("count"))
        .group_by(ChangeLog.action)
        .all()
    )

    by_resource = (
        db.query(ChangeLog.resource, func.count(ChangeLog.id).label("count"))
        .group_by(ChangeLog.resource)
        .order_by(desc("count"))
        .limit(10)
        .all()
    )

    by_user = (
        db.query(ChangeLog.username, func.count(ChangeLog.id).label("count"))
        .filter(ChangeLog.username.isnot(None))
        .group_by(ChangeLog.username)
        .order_by(desc("count"))
        .limit(10)
        .all()
    )

    return {
        "total_changes": total,
        "by_action": [{"action": a[0], "count": a[1]} for a in by_action],
        "by_resource": [{"resource": r[0], "count": r[1]} for r in by_resource],
        "by_user": [{"username": u[0], "count": u[1]} for u in by_user],
    }
