"""
Relatorios - Endpoints de relatorios diversos.
Fonte: DB3 (SQL Server - CFO_CWS)
"""
from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
import logging

from ..database import get_db3
from ..models import User
from ..core.auth import get_current_active_user, check_any_permission

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/relatorios", tags=["relatorios"])


@router.get("/profissionais-por-uf")
def relatorio_profissionais_por_uf(
    situacao: Optional[str] = Query(None, description="Filtrar por situacao (Ativo, Inativo, etc)"),
    categoria: Optional[str] = Query(None, description="Filtrar por categoria (CD, TPD, etc)"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_consulta_estatistica"])),
    db3: Session = Depends(get_db3),
):
    """Relatorio de profissionais agrupados por UF."""
    conditions = []
    params = {}

    if situacao:
        conditions.append("Situacao = :situacao")
        params["situacao"] = situacao
    if categoria:
        conditions.append("Categoria = :categoria")
        params["categoria"] = categoria

    where = " AND ".join(conditions) if conditions else "1=1"

    sql = f"""
        SELECT
            SUBSTRING(CRO, 5, 2) as uf,
            Categoria as categoria,
            Situacao as situacao,
            COUNT(*) as total
        FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
        WHERE {where} AND LEN(CRO) >= 6
        GROUP BY SUBSTRING(CRO, 5, 2), Categoria, Situacao
        ORDER BY uf, Categoria
    """
    result = db3.execute(text(sql), params)
    return [dict(row._mapping) for row in result]


@router.get("/profissionais-por-categoria")
def relatorio_profissionais_por_categoria(
    uf: Optional[str] = Query(None, description="Filtrar por UF"),
    situacao: Optional[str] = Query(None, description="Filtrar por situacao"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_consulta_estatistica"])),
    db3: Session = Depends(get_db3),
):
    """Relatorio de profissionais agrupados por categoria."""
    conditions = []
    params = {}

    if uf:
        conditions.append("CRO LIKE :uf_prefix")
        params["uf_prefix"] = f"CRO-{uf}%"
    if situacao:
        conditions.append("Situacao = :situacao")
        params["situacao"] = situacao

    where = " AND ".join(conditions) if conditions else "1=1"

    sql = f"""
        SELECT
            Categoria as categoria,
            Situacao as situacao,
            COUNT(*) as total
        FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
        WHERE {where}
        GROUP BY Categoria, Situacao
        ORDER BY Categoria, Situacao
    """
    result = db3.execute(text(sql), params)
    return [dict(row._mapping) for row in result]


@router.get("/empresas-por-uf")
def relatorio_empresas_por_uf(
    situacao: Optional[str] = Query(None, description="Filtrar por situacao"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatorio de empresas agrupadas por UF."""
    conditions = []
    params = {}

    if situacao:
        conditions.append("Situacao = :situacao")
        params["situacao"] = situacao

    where = " AND ".join(conditions) if conditions else "1=1"

    sql = f"""
        SELECT
            UF as uf,
            Categoria as categoria,
            Situacao as situacao,
            COUNT(*) as total
        FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa
        WHERE {where}
        GROUP BY UF, Categoria, Situacao
        ORDER BY UF, Categoria
    """
    result = db3.execute(text(sql), params)
    return [dict(row._mapping) for row in result]


@router.get("/profissional-x-formacao")
def relatorio_profissional_formacao(
    uf: Optional[str] = Query(None, description="Filtrar por UF"),
    categoria: Optional[str] = Query(None, description="Filtrar por categoria"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatorio de profissionais por formacao (instituicao de ensino)."""
    conditions = ["f.InstituicaoDeEnsino IS NOT NULL"]
    params = {}

    if uf:
        conditions.append("p.CRO LIKE :uf_prefix")
        params["uf_prefix"] = f"CRO-{uf}%"
    if categoria:
        conditions.append("p.Categoria = :categoria")
        params["categoria"] = categoria

    where = " AND ".join(conditions)

    sql = f"""
        SELECT TOP 500
            f.InstituicaoDeEnsino as instituicao,
            f.Curso as curso,
            p.Categoria as categoria,
            COUNT(*) as total
        FROM Cons_Visao_Nacional_PF_Formacoes f
        INNER JOIN Cons_Visao_Nacional_PF_Dados_do_Profissional p
            ON f.ID_Registro = p.ID_Registro
        WHERE {where}
        GROUP BY f.InstituicaoDeEnsino, f.Curso, p.Categoria
        ORDER BY total DESC
    """
    try:
        result = db3.execute(text(sql), params)
        return [dict(row._mapping) for row in result]
    except Exception as e:
        logger.error(f"Erro no relatorio profissional x formacao: {e}")
        raise HTTPException(status_code=500, detail="Erro ao gerar relatorio de formacao.")


@router.get("/resumo-nacional")
def relatorio_resumo_nacional(
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Resumo nacional com totais gerais de profissionais e empresas."""
    resumo = {}

    try:
        # Total PF por situacao
        result = db3.execute(text("""
            SELECT Situacao, COUNT(*) as total
            FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
            GROUP BY Situacao
            ORDER BY total DESC
        """))
        resumo["pf_por_situacao"] = [dict(row._mapping) for row in result]
    except Exception:
        resumo["pf_por_situacao"] = []

    try:
        # Total PJ por situacao
        result = db3.execute(text("""
            SELECT Situacao, COUNT(*) as total
            FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa
            GROUP BY Situacao
            ORDER BY total DESC
        """))
        resumo["pj_por_situacao"] = [dict(row._mapping) for row in result]
    except Exception:
        resumo["pj_por_situacao"] = []

    try:
        # Total PF geral
        result = db3.execute(text(
            "SELECT COUNT(*) as total FROM Cons_Visao_Nacional_PF_Dados_do_Profissional"
        ))
        row = result.fetchone()
        resumo["total_pf"] = row[0] if row else 0
    except Exception:
        resumo["total_pf"] = 0

    try:
        # Total PJ geral
        result = db3.execute(text(
            "SELECT COUNT(*) as total FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa"
        ))
        row = result.fetchone()
        resumo["total_pj"] = row[0] if row else 0
    except Exception:
        resumo["total_pj"] = 0

    return resumo
