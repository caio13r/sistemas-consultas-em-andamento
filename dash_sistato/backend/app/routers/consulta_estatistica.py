"""
Consulta Estatística - Dados populacionais e profissionais por CRO/Categoria/Sexo
Fonte: DB3 (SQL Server - CFO_CWS)
Views: vw_Cons_Dados_Somados_Populacao, Cons_Total_Ativos_Localidade, etc.
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-estatistica", tags=["consulta-estatistica"])


class EstatisticaResponse(BaseModel):
    total: int
    resultados: List[dict]


@router.get("/populacao", response_model=EstatisticaResponse)
def dados_populacao(
    cro: Optional[str] = Query(None, description="BR (Brasil), RG (Regional), ou sigla UF"),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_estatistica")),
):
    """Dados somados de população por região, UF e categorias profissionais"""
    conditions = []
    params = {}

    if cro:
        cro_upper = cro.upper()
        if cro_upper == "BR":
            pass  # sem filtro, retorna tudo
        elif cro_upper == "RG":
            conditions.append("UF IS NOT NULL")
        else:
            conditions.append("UF = :cro")
            params["cro"] = cro_upper

    where = " AND ".join(conditions) if conditions else "1=1"

    query_sql = text(f"""
        SELECT
            REGIAO, UF, POPULACAO,
            CD_FEM, CD_MAS, APD_FEM, APD_MAS, TPD_FEM, TPD_MAS,
            ASB_FEM, ASB_MAS, TSB_FEM, TSB_MAS, TOT_FEM, TOT_MAS, TOT_BRASIL
        FROM CFO_CWS.dbo.vw_Cons_Dados_Somados_Populacao
        WHERE {where}
        ORDER BY REGIAO, UF
    """)
    rows = db3.execute(query_sql, params).mappings().all()
    resultados = [dict(r) for r in rows]

    return EstatisticaResponse(total=len(resultados), resultados=resultados)


@router.get("/ativos-localidade", response_model=EstatisticaResponse)
def ativos_por_localidade(
    cro: Optional[str] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_estatistica")),
):
    """Total de profissionais ativos por CRO, UF e localidade"""
    conditions = []
    params = {}

    if cro:
        conditions.append("[CRO] = :cro")
        params["cro"] = cro.upper()

    where = " AND ".join(conditions) if conditions else "1=1"

    query_sql = text(f"""
        SELECT [CRO], [UF], [LOCALIDADE], [CD], [TPD], [TSB], [ASB],
            [APD], [EPAO], [LB], [ECIPO], [TOTAL]
        FROM CFO_CWS.dbo.Cons_Total_Ativos_Localidade
        WHERE {where}
        ORDER BY IIF([CRO] = 'BRASIL', 1, 0), [CRO],
            IIF([UF] = 'TOTAL', 1, 0), [UF], [LOCALIDADE]
    """)
    rows = db3.execute(query_sql, params).mappings().all()
    resultados = [dict(r) for r in rows]

    return EstatisticaResponse(total=len(resultados), resultados=resultados)


@router.get("/geral", response_model=EstatisticaResponse)
def estatisticas_gerais(
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_estatistica")),
):
    """Dashboard com estatísticas gerais (resumo nacional)"""
    # Total ativos por CRO (agregado)
    query_sql = text("""
        SELECT [CRO], [UF], [TOTAL]
        FROM CFO_CWS.dbo.Cons_Total_Ativos_Localidade
        WHERE [UF] = 'TOTAL'
        ORDER BY IIF([CRO] = 'BRASIL', 1, 0), [CRO]
    """)
    rows = db3.execute(query_sql).mappings().all()
    resultados = [dict(r) for r in rows]

    return EstatisticaResponse(total=len(resultados), resultados=resultados)
