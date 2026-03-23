"""
Crachá - Geração de crachás/identidades profissionais
Upload de foto, busca por CPF e geração do crachá
"""
from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db1, get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel
import logging

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/cracha", tags=["cracha"])


class CrachaResponse(BaseModel):
    total: int
    resultados: List[dict]


@router.get("/dados/{cpf}", response_model=CrachaResponse)
def buscar_dados_profissional(
    cpf: str,
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_cracha")),
):
    """Busca dados do profissional por CPF para geração do crachá"""
    cpf_limpo = cpf.replace(".", "").replace("-", "")
    query_sql = text("""
        SELECT TOP 1
            CRO, Categoria, Inscricao, Nome, CPF, Tipo_Inscricao,
            Situacao, Detalhe, Data_Inscricao
        FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional
        WHERE REPLACE(REPLACE(CPF, '.', ''), '-', '') = :cpf
        AND Situacao = 'Ativo'
    """)
    rows = db3.execute(query_sql, {"cpf": cpf_limpo}).mappings().all()
    return CrachaResponse(total=len(rows), resultados=[dict(r) for r in rows])


@router.post("/upload-foto")
def upload_foto(
    cpf: str = Query(...),
    foto_base64: str = Query(..., description="Foto em base64"),
    db1: Session = Depends(get_db1),
    current_user: User = Depends(check_permission("view_cracha")),
):
    """Upload de foto para crachá (base64)"""
    cpf_limpo = cpf.replace(".", "").replace("-", "")
    try:
        # Verificar se já existe registro
        check_sql = text("SELECT COUNT(*) FROM tbl_users WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf")
        count = db1.execute(check_sql, {"cpf": cpf_limpo}).scalar() or 0

        if count == 0:
            raise HTTPException(status_code=404, detail="Usuário não encontrado")

        update_sql = text("""
            UPDATE tbl_users SET foto = :foto
            WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf
        """)
        db1.execute(update_sql, {"foto": foto_base64, "cpf": cpf_limpo})
        db1.commit()

        return {"message": "Foto atualizada com sucesso"}
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Erro upload foto: {e}")
        raise HTTPException(status_code=500, detail="Erro ao salvar foto")


class GerarCrachaRequest(BaseModel):
    cpf: str
    foto_base64: Optional[str] = None


@router.post("/gerar")
def gerar_cracha(
    req: GerarCrachaRequest,
    db1: Session = Depends(get_db1),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_cracha")),
):
    """Gera dados do crachá: busca profissional + foto e marca como gerado"""
    cpf_limpo = req.cpf.replace(".", "").replace("-", "")

    # Busca dados do profissional no DB3
    prof_sql = text("""
        SELECT TOP 1
            CRO, Categoria, Inscricao, Nome, CPF, Tipo_Inscricao,
            Situacao, Detalhe, Data_Inscricao
        FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional
        WHERE REPLACE(REPLACE(CPF, '.', ''), '-', '') = :cpf
        AND Situacao = 'Ativo'
    """)
    rows = db3.execute(prof_sql, {"cpf": cpf_limpo}).mappings().all()
    if not rows:
        raise HTTPException(status_code=404, detail="Profissional não encontrado ou inativo.")

    profissional = dict(rows[0])

    # Busca foto no DB1 (se não enviada no request)
    foto = req.foto_base64
    if not foto:
        foto_sql = text("SELECT foto FROM tbl_users WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf")
        foto_row = db1.execute(foto_sql, {"cpf": cpf_limpo}).fetchone()
        if foto_row and foto_row[0]:
            foto = foto_row[0]

    # Atualiza flag de crachá gerado
    try:
        update_sql = text("""
            UPDATE tbl_users SET cracha = 1
            WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf
        """)
        db1.execute(update_sql, {"cpf": cpf_limpo})
        db1.commit()
    except Exception:
        pass  # Não bloquear se falhar o update do flag

    return {
        "profissional": profissional,
        "foto": foto,
        "message": "Crachá gerado com sucesso.",
    }
