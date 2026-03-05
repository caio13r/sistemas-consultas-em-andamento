"""
Consulta Eleições Regionais - Dados eleitorais dos conselhos
Fonte: DB3 (SQL Server - CFO_CWS)
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/eleicoes-regionais", tags=["eleicoes-regionais"])


class EleicaoSearchResponse(BaseModel):
    total: int
    resultados: List[dict]


@router.get("/buscar", response_model=EleicaoSearchResponse)
def buscar_eleicoes(
    cro: Optional[str] = Query(None, description="CRO (ex: SP, RJ)"),
    nome: Optional[str] = Query(None, description="Nome do eleitor/candidato"),
    inscricao: Optional[str] = Query(None),
    cpf: Optional[str] = Query(None),
    email: Optional[str] = Query(None),
    celular: Optional[str] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_eleicoes_regionais")),
):
    """Busca dados eleitorais dos conselhos regionais"""
    conditions = []
    params = {}

    if cro:
        conditions.append("CRO = :cro")
        params["cro"] = cro.upper()
    if nome:
        conditions.append("Nome LIKE :nome")
        params["nome"] = f"%{nome}%"
    if inscricao:
        conditions.append("Inscricao LIKE :inscricao")
        params["inscricao"] = f"%{inscricao}%"
    if cpf:
        cpf_limpo = cpf.replace(".", "").replace("-", "")
        conditions.append("REPLACE(REPLACE(CPF, '.', ''), '-', '') LIKE :cpf")
        params["cpf"] = f"%{cpf_limpo}%"
    if email:
        conditions.append("Email LIKE :email")
        params["email"] = f"%{email}%"
    if celular:
        conditions.append("Celular LIKE :celular")
        params["celular"] = f"%{celular}%"

    if not conditions:
        return EleicaoSearchResponse(total=0, resultados=[])

    where = " AND ".join(conditions)

    # Nota: a view exata de eleições depende do tipo de consulta.
    # O PHP usa views diferentes por tipo (consultaeleicoes-1 a 4).
    # Aqui usamos uma query genérica que deve ser ajustada conforme a view real.
    query_sql = text(f"""
        SELECT TOP 1000 *
        FROM CFO_CWS.dbo.vw_Cons_Eleitores
        WHERE {where}
        ORDER BY Nome
    """)

    try:
        rows = db3.execute(query_sql, params).mappings().all()
        resultados = [dict(r) for r in rows]
        return EleicaoSearchResponse(total=len(resultados), resultados=resultados)
    except Exception as e:
        # Se a view não existir, retorna erro informativo
        import logging
        logging.getLogger(__name__).error(f"Erro ao consultar eleições: {e}")
        from fastapi import HTTPException
        raise HTTPException(
            status_code=500,
            detail=f"Erro ao consultar dados eleitorais. Verifique se a view está disponível no SQL Server."
        )
