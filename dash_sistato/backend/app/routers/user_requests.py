from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from sqlalchemy import func as sa_func
from typing import Optional
from datetime import datetime, timezone

from ..database import get_db
from ..models import User, Role, Permission
from ..models.servico import Servico
from ..models.user_request import UserRequest, UserRequestItem
from ..models.permission import user_permissions, user_roles
from ..schemas.user_request import (
    UserRequestCreate,
    UserRequestResponse,
    UserRequestListResponse,
    UserRequestItemResponse,
    StatusCheckRequest,
    StatusCheckResponse,
    UserRequestApprove,
    UserRequestReject,
    UserRequestClarification,
    UserRequestModify,
    UserRequestStats,
)
from ..core.auth import get_password_hash, check_permission

router = APIRouter(
    prefix="/user-requests",
    tags=["user-requests"],
)


# ============================================================
# Helper: serialize request with items
# ============================================================

def _serialize_request(req: UserRequest, db: Session) -> dict:
    items = []
    for item in req.items:
        servico_nome = None
        if item.servico_id:
            svc = db.query(Servico).filter(Servico.id == item.servico_id).first()
            if svc:
                servico_nome = svc.nome
        items.append(UserRequestItemResponse(
            id=item.id,
            servico_id=item.servico_id,
            permission_name=item.permission_name,
            approved=item.approved,
            servico_nome=servico_nome,
        ))
    return UserRequestResponse(
        id=req.id,
        nome_completo=req.nome_completo,
        email=req.email,
        telefone=req.telefone,
        origem_tipo=req.origem_tipo,
        organizacao=req.organizacao,
        departamento=req.departamento,
        justificativa=req.justificativa,
        outro=req.outro,
        sugestao_desenvolvimento=req.sugestao_desenvolvimento,
        status=req.status,
        admin_notes=req.admin_notes,
        reject_reason=req.reject_reason,
        clarification_message=req.clarification_message,
        created_at=req.created_at,
        updated_at=req.updated_at,
        resolved_at=req.resolved_at,
        items=items,
    )


# ============================================================
# PUBLIC ENDPOINTS (sem auth)
# ============================================================

@router.get("/public/servicos")
def list_public_servicos(
    origem_tipo: str = Query(..., description="cfo ou cro"),
    db: Session = Depends(get_db),
):
    """Lista serviços filtrados por scope_type para o formulário público."""
    if origem_tipo not in ("cfo", "cro"):
        raise HTTPException(status_code=400, detail="origem_tipo deve ser 'cfo' ou 'cro'")

    if origem_tipo == "cfo":
        # CFO vê tudo
        servicos = db.query(Servico).filter(Servico.ativo == True).order_by(Servico.ordem).all()
    else:
        # CRO vê só global + cro
        servicos = (
            db.query(Servico)
            .filter(Servico.ativo == True, Servico.scope_type.in_(["global", "cro"]))
            .order_by(Servico.ordem)
            .all()
        )

    return [
        {
            "id": s.id,
            "nome": s.nome,
            "slug": s.slug,
            "descricao": s.descricao,
            "icone": s.icone,
            "permissao_nome": s.permissao_nome,
            "scope_type": s.scope_type,
        }
        for s in servicos
    ]


@router.post("/", response_model=UserRequestResponse, status_code=status.HTTP_201_CREATED)
def create_user_request(
    data: UserRequestCreate,
    db: Session = Depends(get_db),
):
    """Cria uma nova solicitação de usuário (público)."""
    req = UserRequest(
        nome_completo=data.nome_completo,
        email=data.email,
        telefone=data.telefone,
        origem_tipo=data.origem_tipo,
        organizacao=data.organizacao,
        departamento=data.departamento,
        justificativa=data.justificativa,
        outro=data.outro,
        sugestao_desenvolvimento=data.sugestao_desenvolvimento,
        status="pendente",
    )
    db.add(req)
    db.flush()  # get the id

    for item_data in data.items:
        item = UserRequestItem(
            user_request_id=req.id,
            servico_id=item_data.servico_id,
            permission_name=item_data.permission_name,
        )
        db.add(item)

    db.commit()
    db.refresh(req)
    return _serialize_request(req, db)


@router.post("/status-check", response_model=StatusCheckResponse)
def check_status(
    data: StatusCheckRequest,
    db: Session = Depends(get_db),
):
    """Verifica status das solicitações por email (público)."""
    requests = (
        db.query(UserRequest)
        .filter(UserRequest.email == data.email)
        .order_by(UserRequest.created_at.desc())
        .all()
    )
    return StatusCheckResponse(
        email=data.email,
        requests=[_serialize_request(r, db) for r in requests],
    )


# ============================================================
# ADMIN ENDPOINTS (requer manage_user_requests)
# ============================================================

@router.get("/admin/stats", response_model=UserRequestStats)
def get_stats(
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Contadores por status."""
    counts = (
        db.query(UserRequest.status, sa_func.count(UserRequest.id))
        .group_by(UserRequest.status)
        .all()
    )
    stats = {s: c for s, c in counts}
    total = sum(stats.values())
    return UserRequestStats(
        pendente=stats.get("pendente", 0),
        em_analise=stats.get("em_analise", 0),
        aprovado=stats.get("aprovado", 0),
        rejeitado=stats.get("rejeitado", 0),
        esclarecimento=stats.get("esclarecimento", 0),
        total=total,
    )


@router.get("/admin", response_model=UserRequestListResponse)
def list_requests(
    status_filter: Optional[str] = None,
    origem_tipo: Optional[str] = None,
    search: Optional[str] = None,
    skip: int = 0,
    limit: int = 50,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Lista solicitações com filtros."""
    query = db.query(UserRequest)

    if status_filter:
        query = query.filter(UserRequest.status == status_filter)
    if origem_tipo:
        query = query.filter(UserRequest.origem_tipo == origem_tipo)
    if search:
        search_term = f"%{search}%"
        query = query.filter(
            (UserRequest.nome_completo.ilike(search_term))
            | (UserRequest.email.ilike(search_term))
            | (UserRequest.organizacao.ilike(search_term))
        )

    total = query.count()
    requests = query.order_by(UserRequest.created_at.desc()).offset(skip).limit(limit).all()

    return UserRequestListResponse(
        requests=[_serialize_request(r, db) for r in requests],
        total=total,
    )


@router.get("/admin/{request_id}", response_model=UserRequestResponse)
def get_request(
    request_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Detalhe de uma solicitação."""
    req = db.query(UserRequest).filter(UserRequest.id == request_id).first()
    if not req:
        raise HTTPException(status_code=404, detail="Solicitação não encontrada")
    return _serialize_request(req, db)


@router.post("/admin/{request_id}/analyze", response_model=UserRequestResponse)
def analyze_request(
    request_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Muda status para em_analise."""
    req = db.query(UserRequest).filter(UserRequest.id == request_id).first()
    if not req:
        raise HTTPException(status_code=404, detail="Solicitação não encontrada")
    if req.status not in ("pendente", "esclarecimento"):
        raise HTTPException(status_code=400, detail=f"Não é possível analisar solicitação com status '{req.status}'")

    req.status = "em_analise"
    req.admin_id = current_user.id
    db.commit()
    db.refresh(req)
    return _serialize_request(req, db)


@router.post("/admin/{request_id}/approve", response_model=UserRequestResponse)
def approve_request(
    request_id: int,
    data: UserRequestApprove,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Aprova a solicitação e cria o usuário."""
    req = db.query(UserRequest).filter(UserRequest.id == request_id).first()
    if not req:
        raise HTTPException(status_code=404, detail="Solicitação não encontrada")
    if req.status not in ("pendente", "em_analise"):
        raise HTTPException(status_code=400, detail=f"Não é possível aprovar solicitação com status '{req.status}'")

    # Validar que email e username não existem
    if db.query(User).filter(User.email == req.email).first():
        raise HTTPException(status_code=400, detail="Já existe um usuário com este email")
    if db.query(User).filter(User.username == data.username).first():
        raise HTTPException(status_code=400, detail="Já existe um usuário com este username")

    # Criar o usuário
    new_user = User(
        username=data.username,
        email=req.email,
        full_name=req.nome_completo,
        hashed_password=get_password_hash(data.password),
        is_active=True,
        is_superuser=False,
    )
    db.add(new_user)
    db.flush()

    # Atribuir roles
    if data.role_ids:
        roles = db.query(Role).filter(Role.id.in_(data.role_ids)).all()
        new_user.roles = roles

    # Atribuir permissões diretas dos items aprovados
    approved_perm_names = [
        item.permission_name
        for item in req.items
        if item.approved and item.permission_name
    ]
    if approved_perm_names:
        perms = db.query(Permission).filter(Permission.name.in_(approved_perm_names)).all()
        new_user.direct_permissions = perms

    # Atualizar request
    req.status = "aprovado"
    req.admin_id = current_user.id
    req.admin_notes = data.admin_notes
    req.created_user_id = new_user.id
    req.resolved_at = datetime.now(timezone.utc)

    db.commit()
    db.refresh(req)
    return _serialize_request(req, db)


@router.post("/admin/{request_id}/modify", response_model=UserRequestResponse)
def modify_request(
    request_id: int,
    data: UserRequestModify,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Modifica itens da solicitação (aprovar/rejeitar individualmente)."""
    req = db.query(UserRequest).filter(UserRequest.id == request_id).first()
    if not req:
        raise HTTPException(status_code=404, detail="Solicitação não encontrada")

    for item_mod in data.items:
        item = db.query(UserRequestItem).filter(
            UserRequestItem.id == item_mod.item_id,
            UserRequestItem.user_request_id == request_id,
        ).first()
        if item:
            item.approved = item_mod.approved

    if data.admin_notes:
        req.admin_notes = data.admin_notes
    req.admin_id = current_user.id

    db.commit()
    db.refresh(req)
    return _serialize_request(req, db)


@router.post("/admin/{request_id}/reject", response_model=UserRequestResponse)
def reject_request(
    request_id: int,
    data: UserRequestReject,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Rejeita a solicitação."""
    req = db.query(UserRequest).filter(UserRequest.id == request_id).first()
    if not req:
        raise HTTPException(status_code=404, detail="Solicitação não encontrada")
    if req.status in ("aprovado",):
        raise HTTPException(status_code=400, detail="Solicitação já aprovada, não pode ser rejeitada")

    req.status = "rejeitado"
    req.reject_reason = data.reject_reason
    req.admin_notes = data.admin_notes
    req.admin_id = current_user.id
    req.resolved_at = datetime.now(timezone.utc)

    db.commit()
    db.refresh(req)
    return _serialize_request(req, db)


@router.post("/admin/{request_id}/clarify", response_model=UserRequestResponse)
def clarify_request(
    request_id: int,
    data: UserRequestClarification,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_user_requests")),
):
    """Pede esclarecimentos ao solicitante."""
    req = db.query(UserRequest).filter(UserRequest.id == request_id).first()
    if not req:
        raise HTTPException(status_code=404, detail="Solicitação não encontrada")

    req.status = "esclarecimento"
    req.clarification_message = data.clarification_message
    req.admin_notes = data.admin_notes
    req.admin_id = current_user.id

    db.commit()
    db.refresh(req)
    return _serialize_request(req, db)
