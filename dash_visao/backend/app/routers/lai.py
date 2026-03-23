"""
Formulário LAI/LGPD - Lei de Acesso à Informação
Envio de formulário e relatórios de LAI
"""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db
from ..models import User
from ..core.auth import check_permission, get_current_active_user
from pydantic import BaseModel
import logging

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/lai", tags=["lai"])


class LAIFormulario(BaseModel):
    f_autlai: str
    f_portlai: str
    f_cargolai: str
    f_vinculolai: str
    f_aptoautlai: str
    f_aptolai: str
    f_sitelai: str
    f_portallai: str
    f_sitesolu: Optional[str] = None
    f_anolai: Optional[str] = None
    f_lgpd: str
    f_autlgpd: str
    f_arealgpd: Optional[str] = None
    f_explgpd: Optional[str] = None


class LAIResponse(BaseModel):
    total: int
    resultados: List[dict]


@router.post("/enviar")
def enviar_formulario_lai(
    form: LAIFormulario,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user),
):
    """Envia formulário LAI/LGPD"""
    try:
        insert_sql = text("""
            INSERT INTO tbl_lai_formularios
            (user_id, f_autlai, f_portlai, f_cargolai, f_vinculolai,
             f_aptoautlai, f_aptolai, f_sitelai, f_portallai, f_sitesolu,
             f_anolai, f_lgpd, f_autlgpd, f_arealgpd, f_explgpd)
            VALUES (:user_id, :f_autlai, :f_portlai, :f_cargolai, :f_vinculolai,
                    :f_aptoautlai, :f_aptolai, :f_sitelai, :f_portallai, :f_sitesolu,
                    :f_anolai, :f_lgpd, :f_autlgpd, :f_arealgpd, :f_explgpd)
        """)
        db.execute(insert_sql, {
            "user_id": current_user.id,
            "f_autlai": form.f_autlai,
            "f_portlai": form.f_portlai,
            "f_cargolai": form.f_cargolai,
            "f_vinculolai": form.f_vinculolai,
            "f_aptoautlai": form.f_aptoautlai,
            "f_aptolai": form.f_aptolai,
            "f_sitelai": form.f_sitelai,
            "f_portallai": form.f_portallai,
            "f_sitesolu": form.f_sitesolu,
            "f_anolai": form.f_anolai,
            "f_lgpd": form.f_lgpd,
            "f_autlgpd": form.f_autlgpd,
            "f_arealgpd": form.f_arealgpd,
            "f_explgpd": form.f_explgpd,
        })
        db.commit()
        return {"message": "Formulário LAI enviado com sucesso"}
    except Exception as e:
        db.rollback()
        logger.error(f"Erro ao enviar formulário LAI: {e}")
        raise HTTPException(status_code=500, detail="Erro ao enviar formulário")


@router.get("/relatorio", response_model=LAIResponse)
def relatorio_lai(
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("view_lai")),
):
    """Relatório de formulários LAI enviados (admin)"""
    try:
        query_sql = text("""
            SELECT l.*, u.full_name as nome_usuario, u.email as email_usuario
            FROM tbl_lai_formularios l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.created_at DESC
        """)
        rows = db.execute(query_sql).mappings().all()
        resultados = [
            {k: str(v) if v is not None else None for k, v in dict(r).items()}
            for r in rows
        ]
        return LAIResponse(total=len(resultados), resultados=resultados)
    except Exception as e:
        logger.error(f"Erro ao gerar relatório LAI: {e}")
        raise HTTPException(status_code=500, detail="Erro ao gerar relatório")
