from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from typing import List
from ..database import get_db
from ..models import User
from ..models.servico import Servico
from ..schemas.servico import Servico as ServicoSchema
from ..core.auth import get_current_active_user, get_user_permissions

router = APIRouter(prefix="/servicos", tags=["servicos"])


@router.get("/", response_model=List[ServicoSchema])
def listar_servicos(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user),
):
    """Lista serviços disponíveis para o usuário baseado em suas permissões"""
    all_servicos = db.query(Servico).filter(Servico.ativo == True).order_by(Servico.ordem).all()

    if current_user.is_superuser:
        return all_servicos

    user_perms = get_user_permissions(db, current_user.id)

    return [s for s in all_servicos if s.permissao_nome in user_perms]
