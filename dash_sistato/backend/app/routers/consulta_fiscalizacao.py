"""
Consulta Fiscalização - Estatísticas de fiscalizações dos CROs
Fonte: DB3 (SQL Server - CFO_CWS)
Views: Cons_EstatisticasFiscalizacoes, Cons_EstatisticasFiscalizacoes_PessoasSemInscricao, vw_Cons_Qtd_Fiscais
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-fiscalizacao", tags=["consulta-fiscalizacao"])


class FiscalizacaoEstatistica(BaseModel):
    total: int
    resultados: List[dict]


@router.get("/estatisticas", response_model=FiscalizacaoEstatistica)
def estatisticas_fiscalizacao(
    cro: Optional[str] = Query(None, description="Filtrar por CRO (ex: SP, RJ)"),
    categoria: Optional[str] = Query(None, description="Filtrar por categoria (ex: CD, TPD)"),
    ano: Optional[int] = Query(None, description="Filtrar por ano"),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
):
    """Estatísticas de fiscalizações por CRO, categoria e ano"""
    conditions = []
    params = {}

    if cro:
        conditions.append("T.CRO = :cro")
        params["cro"] = cro.upper()
    if categoria:
        conditions.append("T.Categoria = :categoria")
        params["categoria"] = categoria.upper()
    if ano:
        conditions.append("T.ANO = :ano")
        params["ano"] = ano

    where = " AND ".join(conditions) if conditions else "1=1"

    count_sql = text(f"SELECT COUNT(*) FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes T WHERE {where}")
    total = db3.execute(count_sql, params).scalar() or 0

    query_sql = text(f"""
        SELECT
            T.CRO,
            T.Categoria,
            T.[Total de Ativos] AS TotalAtivos,
            (T.ANO - 2) AS AnoProfAtivos,
            T.[Quantidade de Fiscalizações] AS QtdFiscalizacoes,
            T.ANO AS AnoFiscalizacoes,
            T.[Percentual Fiscalizações] AS PercentualFiscalizacoes,
            T.[Fiscalizações ON-LINE] AS FiscOnline,
            T.[Fiscalizações PROATIVAS] AS FiscProativas,
            T.[Fiscalizações REATIVAS] AS FiscReativas,
            T.[Fiscalizações NÃO INFORMADO] AS FiscNaoInformado,
            T.[Fiscalizações Exercício Ilegal] AS FiscExercicioIlegal,
            T.[Notificações (indícios de irregularidades)] AS Notificacoes,
            T.[Fiscalizações com Termo] AS FiscComTermo
        FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes AS T
        WHERE {where}
        ORDER BY T.CRO, T.ANO DESC
    """)
    rows = db3.execute(query_sql, params).mappings().all()

    return FiscalizacaoEstatistica(total=total, resultados=[dict(r) for r in rows])


@router.get("/sem-inscricao", response_model=FiscalizacaoEstatistica)
def fiscalizacao_sem_inscricao(
    cro: Optional[str] = Query(None),
    pessoa: Optional[str] = Query(None, description="PF SEM INSCRIÇÃO ou PJ SEM INSCRIÇÃO"),
    ano: Optional[int] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
):
    """Fiscalizações de pessoas sem inscrição"""
    conditions = []
    params = {}

    if cro:
        conditions.append("T.CRO = :cro")
        params["cro"] = cro.upper()
    if pessoa:
        conditions.append("T.Pessoa = :pessoa")
        params["pessoa"] = pessoa
    if ano:
        conditions.append("T.ANO = :ano")
        params["ano"] = ano

    where = " AND ".join(conditions) if conditions else "1=1"

    count_sql = text(f"SELECT COUNT(*) FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao T WHERE {where}")
    total = db3.execute(count_sql, params).scalar() or 0

    query_sql = text(f"""
        SELECT
            T.ANO,
            T.CRO,
            T.Pessoa,
            T.[Quantidade de Fiscalizações] AS QtdFiscalizacoes,
            T.[Fiscalizações ON-LINE] AS FiscOnline,
            T.[Fiscalizações PROATIVAS] AS FiscProativas,
            T.[Fiscalizações REATIVAS] AS FiscReativas,
            T.[Fiscalizações NÃO INFORMADO] AS FiscNaoInformado,
            T.[Fiscalizações Exercício Ilegal] AS FiscExercicioIlegal,
            T.[Notificações (indícios de irregularidades)] AS Notificacoes,
            T.[Fiscalizações com Termo] AS FiscComTermo
        FROM CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao AS T
        WHERE {where}
        ORDER BY T.CRO, T.ANO DESC
    """)
    rows = db3.execute(query_sql, params).mappings().all()

    return FiscalizacaoEstatistica(total=total, resultados=[dict(r) for r in rows])


@router.get("/fiscais", response_model=FiscalizacaoEstatistica)
def quantidade_fiscais(
    cro: Optional[str] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
):
    """Quantidade de fiscais por CRO"""
    conditions = []
    params = {}

    if cro:
        conditions.append("CRO = :cro")
        params["cro"] = cro.upper()

    where = " AND ".join(conditions) if conditions else "1=1"

    query_sql = text(f"SELECT * FROM CFO_CWS.dbo.vw_Cons_Qtd_Fiscais WHERE {where} ORDER BY CRO")
    rows = db3.execute(query_sql, params).mappings().all()

    return FiscalizacaoEstatistica(total=len(rows), resultados=[dict(r) for r in rows])
