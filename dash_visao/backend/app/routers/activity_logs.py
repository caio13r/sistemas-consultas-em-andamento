"""
Activity Logs - Endpoints de consulta de logs de atividades do sistema.
Acesso restrito a administradores.
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session
from sqlalchemy import desc, func as sqlfunc
from typing import Optional
from datetime import datetime, date
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
    error_detail: Optional[str] = None
    created_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class ActivityLogListResponse(BaseModel):
    total: int
    total_errors: int = 0
    logs: list[ActivityLogResponse]


@router.get("", response_model=ActivityLogListResponse)
def list_activity_logs(
    username: Optional[str] = Query(None, description="Filtrar por username"),
    method: Optional[str] = Query(None, description="Filtrar por metodo HTTP (GET, POST, etc)"),
    path: Optional[str] = Query(None, description="Filtrar por path (busca parcial)"),
    start_date: Optional[str] = Query(None, description="Data início (YYYY-MM-DD)"),
    end_date: Optional[str] = Query(None, description="Data fim (YYYY-MM-DD)"),
    status_filter: Optional[str] = Query(None, description="Filtrar por status: errors (4xx+5xx), client_errors (4xx), server_errors (5xx)"),
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

    if status_filter == "errors":
        query = query.filter(ActivityLog.status_code >= 400)
    elif status_filter == "client_errors":
        query = query.filter(ActivityLog.status_code >= 400, ActivityLog.status_code < 500)
    elif status_filter == "server_errors":
        query = query.filter(ActivityLog.status_code >= 500)

    # Contagem de erros (sem o filtro de status para mostrar badge na aba)
    base_query = db.query(ActivityLog)
    if username:
        base_query = base_query.filter(ActivityLog.username == username)
    if path:
        base_query = base_query.filter(ActivityLog.path.ilike(f"%{path}%"))
    total_errors = base_query.filter(ActivityLog.status_code >= 400).count()

    total = query.count()
    logs = (
        query.order_by(desc(ActivityLog.created_at))
        .offset((page - 1) * per_page)
        .limit(per_page)
        .all()
    )

    return ActivityLogListResponse(total=total, total_errors=total_errors, logs=logs)


class DailyLoginEntry(BaseModel):
    username: str
    first_login: datetime
    last_login: datetime
    login_count: int
    ip_address: Optional[str] = None

    class Config:
        from_attributes = True


class DailyLoginResponse(BaseModel):
    date: str
    total_users: int
    logins: list[DailyLoginEntry]


@router.get("/daily-logins", response_model=DailyLoginResponse)
def list_daily_logins(
    target_date: Optional[str] = Query(None, description="Data (YYYY-MM-DD), padrão: hoje"),
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Lista usuarios que logaram no sistema em um determinado dia."""
    if target_date:
        try:
            dt = datetime.strptime(target_date, "%Y-%m-%d").date()
        except ValueError:
            dt = date.today()
    else:
        dt = date.today()

    day_start = datetime(dt.year, dt.month, dt.day, 0, 0, 0)
    day_end = datetime(dt.year, dt.month, dt.day, 23, 59, 59)

    # Busca logins bem-sucedidos (POST /api/token com status 200)
    results = (
        db.query(
            ActivityLog.username,
            sqlfunc.min(ActivityLog.created_at).label("first_login"),
            sqlfunc.max(ActivityLog.created_at).label("last_login"),
            sqlfunc.count(ActivityLog.id).label("login_count"),
            sqlfunc.max(ActivityLog.ip_address).label("ip_address"),
        )
        .filter(
            ActivityLog.path.ilike("%/token"),
            ActivityLog.method == "POST",
            ActivityLog.status_code == 200,
            ActivityLog.username.isnot(None),
            ActivityLog.created_at >= day_start,
            ActivityLog.created_at <= day_end,
        )
        .group_by(ActivityLog.username)
        .order_by(sqlfunc.min(ActivityLog.created_at).desc())
        .all()
    )

    logins = [
        DailyLoginEntry(
            username=r.username,
            first_login=r.first_login,
            last_login=r.last_login,
            login_count=r.login_count,
            ip_address=r.ip_address,
        )
        for r in results
    ]

    return DailyLoginResponse(
        date=dt.isoformat(),
        total_users=len(logins),
        logins=logins,
    )


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


# =============================================
# USO DE FUNCIONALIDADES (por menu)
# =============================================

# Mapeamento de prefixo de path → nome da funcionalidade
_FEATURE_MAP = [
    ("/api/consulta-integrada", "Visão integrada"),
    ("/api/consulta-auditorias", "Auditorias"),
    ("/api/consulta-fiscalizacao", "Consulta Fiscalização"),
    ("/api/consulta-identidade", "Consulta Identidade"),
    ("/api/consulta-estatistica", "Consulta Estatística"),
    ("/api/consulta-prescricao", "Consulta Prescrição"),
    ("/api/tabelas-centralizadas", "Tabelas Centralizadas"),
    ("/api/relatorios", "Relatórios"),
    ("/api/eleicoes", "Eleições Regionais"),
    ("/api/cracha", "Crachá"),
    ("/api/export", "Exportação"),
]


def _path_to_feature(path: str) -> Optional[str]:
    for prefix, name in _FEATURE_MAP:
        if path.startswith(prefix):
            return name
    return None


@router.get("/feature-usage")
def feature_usage(
    start_date: Optional[str] = Query(None, description="Data início (YYYY-MM-DD)"),
    end_date: Optional[str] = Query(None, description="Data fim (YYYY-MM-DD)"),
    current_user: User = Depends(check_permission("manage_users")),
    db: Session = Depends(get_db),
):
    """Ranking de uso das funcionalidades do sistema, baseado nos logs de atividade."""
    from sqlalchemy import func, case, literal

    # Constrói CASE WHEN para mapear path → feature no SQL
    whens = []
    for prefix, name in _FEATURE_MAP:
        whens.append((ActivityLog.path.like(f"{prefix}%"), literal(name)))

    feature_col = case(*whens, else_=None).label("feature")

    query = (
        db.query(
            feature_col,
            func.count(ActivityLog.id).label("total_acessos"),
            func.count(func.distinct(ActivityLog.username)).label("usuarios_unicos"),
            func.min(ActivityLog.created_at).label("primeiro_acesso"),
            func.max(ActivityLog.created_at).label("ultimo_acesso"),
        )
        .filter(ActivityLog.status_code < 400)  # só sucessos
    )

    if start_date:
        try:
            dt = datetime.strptime(start_date, "%Y-%m-%d")
            query = query.filter(ActivityLog.created_at >= dt)
        except ValueError:
            pass
    if end_date:
        try:
            dt = datetime.strptime(end_date, "%Y-%m-%d").replace(hour=23, minute=59, second=59)
            query = query.filter(ActivityLog.created_at <= dt)
        except ValueError:
            pass

    results = (
        query
        .group_by(feature_col)
        .having(feature_col.isnot(None))
        .order_by(desc("total_acessos"))
        .all()
    )

    # Busca usuários por funcionalidade com contagem individual
    user_query = (
        db.query(
            feature_col,
            ActivityLog.username,
            func.count(ActivityLog.id).label("acessos"),
            func.max(ActivityLog.created_at).label("ultimo_acesso"),
        )
        .filter(ActivityLog.status_code < 400, ActivityLog.username.isnot(None))
    )
    if start_date:
        try:
            dt = datetime.strptime(start_date, "%Y-%m-%d")
            user_query = user_query.filter(ActivityLog.created_at >= dt)
        except ValueError:
            pass
    if end_date:
        try:
            dt = datetime.strptime(end_date, "%Y-%m-%d").replace(hour=23, minute=59, second=59)
            user_query = user_query.filter(ActivityLog.created_at <= dt)
        except ValueError:
            pass

    user_results = (
        user_query
        .group_by(feature_col, ActivityLog.username)
        .having(feature_col.isnot(None))
        .order_by(desc("acessos"))
        .all()
    )

    # Agrupa usuários por funcionalidade
    users_by_feature: dict[str, list] = {}
    for r in user_results:
        users_by_feature.setdefault(r.feature, []).append({
            "username": r.username,
            "acessos": r.acessos,
            "ultimo_acesso": r.ultimo_acesso.isoformat() if r.ultimo_acesso else None,
        })

    return {
        "total_funcionalidades": len(results),
        "funcionalidades": [
            {
                "nome": r.feature,
                "total_acessos": r.total_acessos,
                "usuarios_unicos": r.usuarios_unicos,
                "primeiro_acesso": r.primeiro_acesso.isoformat() if r.primeiro_acesso else None,
                "ultimo_acesso": r.ultimo_acesso.isoformat() if r.ultimo_acesso else None,
                "usuarios": users_by_feature.get(r.feature, []),
            }
            for r in results
        ],
    }
