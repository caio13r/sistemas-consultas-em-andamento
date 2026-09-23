"""
Relatorios - Endpoints de relatorios diversos e financeiros.
Fonte: DB3 (SQL Server - CFO_CWS)
"""
import re
import time
import json
import threading
import uuid
from datetime import datetime
from fastapi import APIRouter, Depends, Query, HTTPException
from fastapi.responses import StreamingResponse
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
import io
import logging

from ..database import get_db3, fix_row_encoding, SessionDB3
from ..lib.excel_export import generate_excel_multi, generate_cielo_excel
from ..lib.siscaf_cielo import (
    CIELO_REPORT_TYPES,
    EXCEL_COLUMNS,
    EXCEL_COLUMNS_ANALITICO,
    EXCEL_COLUMNS_SINTETICO,
    EXCEL_COLUMNS_RENEGOCIACOES,
    MAX_PERIOD_DAYS,
    SISCAF_JSON_MAX_ROWS,
    consolidate_cielo_checkout,
    resolve_ufs,
    sample_linhas_balanceado,
)
from ..lib.siscaf_job_queue import enqueue_cielo_job
from ..models import User
from ..core.auth import get_current_active_user, check_any_permission
from ..lib.sql_loader import load_sql
from .consulta_auditorias import AUDIT_TYPES

_DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}$")

logger = logging.getLogger(__name__)

# Cache em memória para auditoria (sem Redis)
_audit_cache: dict[str, tuple[float, dict]] = {}
_AUDIT_CACHE_TTL = 1800  # 30 minutos

_excel_jobs: dict[str, dict] = {}
_excel_jobs_lock = threading.Lock()
_EXCEL_JOB_TTL = 1800  # 30 minutos

_cielo_json_jobs: dict[str, dict] = {}
_cielo_json_jobs_lock = threading.Lock()
_CIELO_JSON_JOB_TTL = 1800

router = APIRouter(prefix="/relatorios", tags=["relatorios"])

# Coluna CRO contém formato "SP - SÃO PAULO", "RJ - RIO DE JANEIRO", etc.
# LEFT(CRO, 2) extrai a sigla UF. Para filtrar: CRO LIKE 'SP%'


@router.get("/metadata/profissionais")
def metadata_profissionais(
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_consulta_estatistica"])),
    db3: Session = Depends(get_db3),
):
    """Retorna valores distintos de Categoria e Situacao da view de profissionais."""
    try:
        cat_result = db3.execute(text(
            "SELECT DISTINCT Categoria FROM Cons_Visao_Nacional_PF_Dados_do_Profissional WHERE Categoria IS NOT NULL ORDER BY Categoria"
        ))
        categorias = [row[0] for row in cat_result]

        sit_result = db3.execute(text(
            "SELECT DISTINCT Situacao FROM Cons_Visao_Nacional_PF_Dados_do_Profissional WHERE Situacao IS NOT NULL ORDER BY Situacao"
        ))
        situacoes = [row[0] for row in sit_result]

        return {"categorias": categorias, "situacoes": situacoes}
    except Exception as e:
        logger.error(f"Erro ao buscar metadata de profissionais: {e}")
        raise HTTPException(status_code=500, detail="Erro ao buscar metadata.")


@router.get("/profissionais-por-uf")
def relatorio_profissionais_por_uf(
    situacao: Optional[str] = Query(None, description="Filtrar por situação (Ativo, Inativo, etc)"),
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
            LEFT(CRO, 2) as uf,
            Categoria as categoria,
            Situacao as situacao,
            COUNT(*) as total
        FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
        WHERE {where} AND CRO IS NOT NULL AND LEN(CRO) >= 2
        GROUP BY LEFT(CRO, 2), Categoria, Situacao
        ORDER BY uf, Categoria
    """
    result = db3.execute(text(sql), params)
    return [fix_row_encoding(dict(row._mapping)) for row in result]


@router.get("/profissionais-por-categoria")
def relatorio_profissionais_por_categoria(
    uf: Optional[str] = Query(None, description="Filtrar por UF"),
    situacao: Optional[str] = Query(None, description="Filtrar por situação"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_consulta_estatistica"])),
    db3: Session = Depends(get_db3),
):
    """Relatorio de profissionais agrupados por categoria."""
    conditions = []
    params = {}

    if uf:
        conditions.append("LEFT(CRO, 2) = :uf")
        params["uf"] = uf.upper()
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
    return [fix_row_encoding(dict(row._mapping)) for row in result]


@router.get("/empresas-por-uf")
def relatorio_empresas_por_uf(
    situacao: Optional[str] = Query(None, description="Filtrar por situação"),
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
            LEFT(CRO, 2) as uf,
            Categoria as categoria,
            Situacao as situacao,
            COUNT(*) as total
        FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa
        WHERE {where} AND CRO IS NOT NULL AND LEN(CRO) >= 2
        GROUP BY LEFT(CRO, 2), Categoria, Situacao
        ORDER BY uf, Categoria
    """
    result = db3.execute(text(sql), params)
    return [fix_row_encoding(dict(row._mapping)) for row in result]


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
        conditions.append("LEFT(p.CRO, 2) = :uf")
        params["uf"] = uf.upper()
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
            ON f.IdRegistro = p.IdRegistro
        WHERE {where}
        GROUP BY f.InstituicaoDeEnsino, f.Curso, p.Categoria
        ORDER BY total DESC
    """
    try:
        result = db3.execute(text(sql), params)
        return [fix_row_encoding(dict(row._mapping)) for row in result]
    except Exception as e:
        logger.error(f"Erro no relatorio profissional x formacao: {e}")
        raise HTTPException(status_code=500, detail="Erro ao gerar relatório de formação.")


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
        resumo["pf_por_situacao"] = [fix_row_encoding(dict(row._mapping)) for row in result]
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
        resumo["pj_por_situacao"] = [fix_row_encoding(dict(row._mapping)) for row in result]
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


# =============================================
# RELATÓRIO DE ADIMPLÊNCIA (DB3)
# =============================================

CATEGORIAS_VALIDAS = {"CD", "TPD", "TSB", "ASB", "APD", "EPAO", "LB", "ECIPO"}


def _build_adimplencia_query(view: str, ano: int, categoria: Optional[str], extra_cols: str = "") -> tuple:
    """Constrói query de adimplência com filtro de categoria."""
    extra = f", {extra_cols}" if extra_cols else ""
    base = f"""
        SELECT
            CRO,
            AVG(Ano) AS Ano,
            SUM(Total_Anuidades) AS Anuidades,
            SUM(Pago) AS Pago,
            ROUND(
                (CAST(SUM(Pago) AS FLOAT) /
                CASE WHEN CAST(SUM(Total_Anuidades) AS FLOAT) = 0 THEN 1
                     ELSE CAST(SUM(Total_Anuidades) AS FLOAT) END) * 100, 2
            ) AS Perc_Adimplente,
            100 - ROUND(
                (CAST(SUM(Pago) AS FLOAT) /
                CASE WHEN CAST(SUM(Total_Anuidades) AS FLOAT) = 0 THEN 1
                     ELSE CAST(SUM(Total_Anuidades) AS FLOAT) END) * 100, 2
            ) AS Perc_Inadimplente,
            SUM(Nao_pago) AS Nao_Pago{extra}
        FROM CFO_CWS.dbo.{view}
    """
    params = {"ano": ano}

    if categoria and categoria.upper() in CATEGORIAS_VALIDAS:
        where = "WHERE Ano = :ano AND Categoria = :cat AND Categoria <> 'TOTAL' AND CRO <> 'BR'"
        params["cat"] = categoria.upper()
    else:
        where = "WHERE Ano = :ano AND Categoria <> 'TOTAL' AND CRO <> 'BR'"

    return f"{base} {where} GROUP BY CRO ORDER BY Perc_Adimplente DESC", params


def _add_brasil_row(rows: list, extra_value_cols: list = None) -> list:
    """Adiciona linha BRASIL com totais nacionais."""
    if not rows:
        return rows
    total_anuidades = sum(r.get("Anuidades", 0) or 0 for r in rows)
    total_pago = sum(r.get("Pago", 0) or 0 for r in rows)
    total_nao_pago = sum(r.get("Nao_Pago", 0) or 0 for r in rows)
    perc_adim = round((total_pago / total_anuidades) * 100, 2) if total_anuidades else 0
    perc_inadim = round(100 - perc_adim, 2)

    brasil = {
        "CRO": "BRASIL",
        "Ano": rows[0].get("Ano", ""),
        "Anuidades": total_anuidades,
        "Pago": total_pago,
        "Perc_Adimplente": perc_adim,
        "Perc_Inadimplente": perc_inadim,
        "Nao_Pago": total_nao_pago,
    }

    if extra_value_cols:
        for col in extra_value_cols:
            brasil[col] = sum(r.get(col, 0) or 0 for r in rows)

    rows.append(brasil)
    return rows


@router.get("/adimplencia")
def relatorio_adimplencia(
    ano: int = Query(..., description="Ano (ex: 2024)"),
    categoria: Optional[str] = Query(None, description="Categoria (CD, TPD, etc.) ou vazio para Todos"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_relatorio_adimplencia"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Adimplência por CRO (8 colunas)"""
    sql_str, params = _build_adimplencia_query(
        "Cons_adimplencia", ano, categoria,
        extra_cols="SUM(Pago_a_menor) AS Pago_a_Menor",
    )
    rows = db3.execute(text(sql_str), params).mappings().all()
    resultados = [fix_row_encoding(dict(r)) for r in rows]
    resultados = _add_brasil_row(resultados, ["Pago_a_Menor"])
    return {"total": len(resultados), "resultados": resultados}


@router.get("/adimplencia-valores")
def relatorio_adimplencia_valores(
    ano: int = Query(..., description="Ano (ex: 2024)"),
    categoria: Optional[str] = Query(None, description="Categoria ou vazio para Todos"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_relatorio_adimplencia"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Adimplência com Valores (10 colunas)"""
    sql_str, params = _build_adimplencia_query(
        "Cons_adimplencia_com_valores", ano, categoria,
        extra_cols="SUM(Valor_Devido_Nao_pago) AS Valor_Devido_Nao_Pago, SUM(Pago_a_menor) AS Pago_a_Menor, SUM(Valor_Devido_Pago_a_menor) AS Valor_Devido_Pago_a_Menor",
    )
    rows = db3.execute(text(sql_str), params).mappings().all()
    resultados = [fix_row_encoding(dict(r)) for r in rows]

    # Calcula Valor_Pago estimado: proporcional ao que foi pago vs não pago
    for r in resultados:
        nao_pago = r.get("Nao_Pago") or 0
        pago = r.get("Pago") or 0
        valor_nao_pago = r.get("Valor_Devido_Nao_Pago") or 0
        if nao_pago > 0 and pago > 0:
            valor_medio = valor_nao_pago / nao_pago
            r["Valor_Pago"] = round(valor_medio * pago, 2)
        else:
            r["Valor_Pago"] = 0

    resultados = _add_brasil_row(resultados, ["Valor_Pago", "Valor_Devido_Nao_Pago", "Pago_a_Menor", "Valor_Devido_Pago_a_Menor"])
    return {"total": len(resultados), "resultados": resultados}


# =============================================
# RELATÓRIO DE AUDITORIA - RESUMO (DB3)
# =============================================

_QUERY_TIMEOUT = 15  # segundos por view


def _count_single_view(engine, view: str, cro: str) -> tuple[str, int, float]:
    """Executa COUNT(*) em uma view com conexão própria e timeout."""
    t0 = time.time()
    try:
        with engine.connect() as conn:
            # Timeout de comando no pyodbc (corta query lenta automaticamente)
            raw_conn = conn.connection.dbapi_connection
            raw_conn.timeout = _QUERY_TIMEOUT
            sql = text(f"SELECT COUNT(*) FROM {view} WHERE CRO = :cro")
            count = conn.execute(sql, {"cro": cro}).scalar() or 0
            return (view, count, time.time() - t0)
    except Exception as e:
        elapsed = time.time() - t0
        if elapsed >= _QUERY_TIMEOUT - 1:
            logger.warning(f"TIMEOUT ({_QUERY_TIMEOUT}s) em {view}")
        else:
            logger.warning(f"Erro COUNT em {view}: {e}")
        return (view, -2 if elapsed >= _QUERY_TIMEOUT - 1 else -1, elapsed)


def _build_auditoria_resumo(cro: str) -> dict:
    """Executa todos os COUNTs de auditoria em paralelo (threads)."""
    from concurrent.futures import ThreadPoolExecutor, as_completed
    from ..database import engine_db3

    cro_upper = cro.upper()
    t_total = time.time()

    counts = {}
    with ThreadPoolExecutor(max_workers=10) as executor:
        futures = {
            executor.submit(_count_single_view, engine_db3, audit["view"], cro_upper): codigo
            for codigo, audit in AUDIT_TYPES.items()
        }
        for future in as_completed(futures):
            codigo = futures[future]
            view_name, count, elapsed = future.result()
            counts[codigo] = count
            if elapsed > 3:
                logger.warning(f"Auditoria LENTA: {codigo} ({view_name}) = {count} em {elapsed:.1f}s")

    elapsed_total = time.time() - t_total
    logger.info(f"Auditoria resumo CRO={cro_upper}: {len(counts)} views em {elapsed_total:.1f}s")

    resultados = []
    total_geral = 0
    for codigo, audit in AUDIT_TYPES.items():
        count = counts.get(codigo, -1)
        resultados.append({
            "codigo": codigo,
            "tipo": audit["nome"],
            "quantidade": count,
        })
        if count > 0:
            total_geral += count

    resultados.sort(key=lambda x: x["quantidade"] if x["quantidade"] >= 0 else -1, reverse=True)
    resultados.append({"codigo": "_total", "tipo": "TOTAL", "quantidade": total_geral})

    return {
        "total": len(resultados) - 1,
        "cro": cro_upper,
        "resultados": resultados,
    }


@router.get("/auditoria-resumo")
def relatorio_auditoria_resumo(
    cro: str = Query(..., description="CRO/UF obrigatório (ex: SP, RJ)"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos", "view_relatorio_auditoria"])),
):
    """Resumo consolidado de todas as auditorias: contagem de inconsistencias por tipo"""
    if not cro or not cro.strip():
        raise HTTPException(status_code=400, detail="Selecione um CRO/UF para gerar o relatório.")

    cache_key = f"auditoria_resumo:{cro.upper()}"

    # Verifica cache em memória
    if cache_key in _audit_cache:
        cached_time, cached_data = _audit_cache[cache_key]
        if time.time() - cached_time < _AUDIT_CACHE_TTL:
            logger.info(f"Auditoria resumo CRO={cro.upper()}: servido do cache")
            return cached_data

    resultado = _build_auditoria_resumo(cro)

    # Salva no cache em memória
    _audit_cache[cache_key] = (time.time(), resultado)

    return resultado


# =============================================
# RELATÓRIOS FINANCEIROS (DB3)
# =============================================

def _validate_dates(inicio: Optional[str], termino: Optional[str]):
    if not inicio or not termino:
        raise HTTPException(status_code=400, detail="Parâmetros 'início' e 'término' são obrigatórios (YYYY-MM-DD).")
    if not _DATE_RE.match(inicio) or not _DATE_RE.match(termino):
        raise HTTPException(status_code=400, detail="Datas devem estar no formato YYYY-MM-DD.")
    return inicio, termino


@router.get("/arrecadacao-bb")
def relatorio_arrecadacao_bb(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Arrecadação do Banco do Brasil"""
    _validate_dates(inicio, termino)
    sql = text("""
        SELECT CRO, Convenio AS Convenio_BB, Codigo AS Codigo_Convenio_BB,
            COUNT(NossoNumero) AS Quantidade,
            SUM(ValorTarifaBancaria) AS Total_Tarifa_Liquidacao,
            SUM(ValorPagamento) AS Total_Arrecadado
        FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Arrecadacao_do_Banco_do_Brasil
        WHERE (DataCredito BETWEEN :inicio AND :termino OR DataCredito IS NULL)
        GROUP BY CRO, Convenio, Codigo
        ORDER BY CRO, Convenio
    """)
    rows = db3.execute(sql, {"inicio": inicio, "termino": termino}).mappings().all()
    return [fix_row_encoding(dict(r)) for r in rows]


@router.get("/tarifas-bb")
def relatorio_tarifas_bb(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Tarifas do Banco do Brasil (com CTE)"""
    _validate_dates(inicio, termino)
    sql = text("""
        WITH TiposTarifa AS (
            SELECT TipoTarifa FROM (VALUES ('REGISTRO'), ('LIQUIDAÇÃO'), ('BAIXA')) AS TT(TipoTarifa)
        ),
        ConveniosEsperados AS (
            SELECT DISTINCT CRO, Convenio, CodigoConvenio
            FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Tarifas_do_Banco_do_Brasil
        ),
        Combinacoes AS (
            SELECT CE.CRO, CE.Convenio AS Convenio_BB, CE.CodigoConvenio AS Codigo_Convenio_BB, TT.TipoTarifa
            FROM ConveniosEsperados CE CROSS JOIN TiposTarifa TT
        ),
        Tarifas AS (
            SELECT CRO, Convenio, CodigoConvenio, TipoTarifa,
                COUNT(NossoNumero) AS Total_Tarifa,
                SUM(ValorTarifaBancaria) AS Total_Pago
            FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Tarifas_do_Banco_do_Brasil
            WHERE DataPagamento BETWEEN :inicio AND :termino
            GROUP BY CRO, Convenio, CodigoConvenio, TipoTarifa
        )
        SELECT C.CRO, C.Convenio_BB, C.Codigo_Convenio_BB, C.TipoTarifa,
            ISNULL(T.Total_Tarifa, 0) AS Total_Tarifa,
            ISNULL(T.Total_Pago, 0) AS Total_Pago
        FROM Combinacoes C
        LEFT JOIN Tarifas T
            ON C.CRO = T.CRO AND C.Convenio_BB = T.Convenio
            AND C.Codigo_Convenio_BB = T.CodigoConvenio AND C.TipoTarifa = T.TipoTarifa
        ORDER BY C.CRO, C.Convenio_BB, C.TipoTarifa
    """)
    rows = db3.execute(sql, {"inicio": inicio, "termino": termino}).mappings().all()
    return [fix_row_encoding(dict(r)) for r in rows]


@router.get("/pagamentos-diversos")
def relatorio_pagamentos_diversos(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Pagamentos Diversos"""
    _validate_dates(inicio, termino)
    sql = text("""
        SELECT CRO, COUNT(IdPagamento) AS Total_Pagamento,
            FormaPagamento AS Forma_Pagamento,
            SUM(ValorPagamento) AS Total_Arrecadado
        FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Pagamentos_Diversos
        WHERE DataPagamento BETWEEN :inicio AND :termino
        GROUP BY CRO, FormaPagamento
        ORDER BY CRO, FormaPagamento
    """)
    rows = db3.execute(sql, {"inicio": inicio, "termino": termino}).mappings().all()
    return [fix_row_encoding(dict(r)) for r in rows]


def _excel_stream(excel_bytes: bytes, filename: str) -> StreamingResponse:
    return StreamingResponse(
        io.BytesIO(excel_bytes),
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


def _validate_cielo_excel_request(
    inicio: str,
    termino: str,
    cro: Optional[str],
    tipo_relatorio: str,
) -> tuple[datetime, datetime]:
    if tipo_relatorio not in CIELO_REPORT_TYPES:
        raise HTTPException(
            status_code=400,
            detail=f"tipo_relatorio inválido. Use: {', '.join(CIELO_REPORT_TYPES.keys())}",
        )

    _validate_dates(inicio, termino)

    try:
        d_inicio = datetime.strptime(inicio, "%Y-%m-%d")
        d_termino = datetime.strptime(termino, "%Y-%m-%d")
    except ValueError:
        raise HTTPException(status_code=400, detail="Datas inválidas.")

    if d_termino < d_inicio:
        raise HTTPException(status_code=400, detail="Data término deve ser igual ou posterior à data início.")

    if (d_termino - d_inicio).days > MAX_PERIOD_DAYS:
        raise HTTPException(status_code=400, detail=f"Período máximo permitido: {MAX_PERIOD_DAYS} dias.")

    try:
        resolve_ufs(cro)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    return d_inicio, d_termino


def _build_cielo_excel(
    db3: Session,
    inicio: str,
    termino: str,
    cro: Optional[str],
    tipo_relatorio: str,
) -> tuple[bytes, str]:
    d_inicio, d_termino = _validate_cielo_excel_request(inicio, termino, cro, tipo_relatorio)

    try:
        resultado = consolidate_cielo_checkout(db3, cro, inicio, termino, tipo_relatorio, for_excel=True)
    except Exception as e:
        logger.error("Erro consolidação Cielo: %s", e, exc_info=True)
        raise HTTPException(status_code=500, detail="Erro ao consolidar pagamentos Cielo.")

    report_cfg = CIELO_REPORT_TYPES[tipo_relatorio]
    linhas = [fix_row_encoding(row) for row in resultado["linhas"]]
    resumo = resultado["resumo_regionais"]
    cro_label = (cro or "TODOS").upper()
    inicio_br = d_inicio.strftime("%d-%m-%Y")
    termino_br = d_termino.strftime("%d-%m-%Y")
    titulo = f"{report_cfg['nome']} — CRO: {cro_label} — {inicio_br} a {termino_br}"

    if tipo_relatorio == "analitico":
        data_columns = EXCEL_COLUMNS_ANALITICO
    elif tipo_relatorio == "sintetico":
        data_columns = EXCEL_COLUMNS_SINTETICO
    elif tipo_relatorio == "renegociacoes":
        data_columns = EXCEL_COLUMNS_RENEGOCIACOES
    else:
        data_columns = EXCEL_COLUMNS

    sheet_name = "Renegociações Ativas" if tipo_relatorio == "renegociacoes" else "Pagamentos Cielo"
    todos_regionais = (cro or "TODOS").strip().upper() in ("TODOS", "ALL", "*")

    try:
        excel_bytes = generate_cielo_excel(
            linhas=linhas,
            columns=data_columns,
            sheet_name=sheet_name,
            title=titulo,
            todos_regionais=todos_regionais,
            resumo=resumo,
        )
    except Exception as e:
        logger.error("Erro ao gerar Excel Cielo [%s]: %s", tipo_relatorio, e, exc_info=True)
        raise HTTPException(status_code=500, detail=f"Erro ao gerar planilha Excel: {e}")

    inicio_fmt = d_inicio.strftime("%d-%m-%Y")
    termino_fmt = d_termino.strftime("%d-%m-%Y")
    cro_prefix = "TODOS-REGIONAIS" if cro_label in ("TODOS", "ALL", "*") else f"CRO-{cro_label}"
    filename = f"{cro_prefix}_{tipo_relatorio}_{inicio_fmt}_a_{termino_fmt}.xlsx"
    return excel_bytes, filename


def _cleanup_excel_jobs() -> None:
    now = time.time()
    with _excel_jobs_lock:
        expired = [job_id for job_id, job in _excel_jobs.items() if now - job.get("created_at", now) > _EXCEL_JOB_TTL]
        for job_id in expired:
            _excel_jobs.pop(job_id, None)


def _cleanup_cielo_json_jobs() -> None:
    now = time.time()
    with _cielo_json_jobs_lock:
        expired = [job_id for job_id, job in _cielo_json_jobs.items() if now - job.get("created_at", now) > _CIELO_JSON_JOB_TTL]
        for job_id in expired:
            _cielo_json_jobs.pop(job_id, None)


def _build_cielo_json_payload(
    resultado: dict,
    cro: Optional[str],
    tipo_relatorio: str,
) -> dict:
    """Monta resposta JSON do relatório Cielo a partir do resultado consolidado."""
    linhas_completas = [fix_row_encoding(row) for row in resultado["linhas"]]
    total_completo = len(linhas_completas)
    todos_regionais = (cro or "TODOS").strip().upper() in ("TODOS", "ALL", "*")
    linhas = linhas_completas
    if todos_regionais and total_completo > SISCAF_JSON_MAX_ROWS:
        date_field = CIELO_REPORT_TYPES[tipo_relatorio].get("date_field", "")
        linhas = sample_linhas_balanceado(linhas_completas, SISCAF_JSON_MAX_ROWS, date_field)
        logger.info(
            "Cielo JSON [%s]: amostra balanceada %d/%d linhas para exibição em tela",
            tipo_relatorio, len(linhas), total_completo,
        )
    pendentes = [
        r["CRO"] for r in resultado.get("resumo_regionais", [])
        if r.get("Registros", 0) == 0 and r.get("Status") not in ("OK", "Sem registros")
    ]
    resumo_regionais = resultado.get("resumo_regionais", [])
    ufs_na_tela = len({r.get("CRO") for r in linhas if r.get("CRO")})
    logger.info(
        "Cielo JSON [%s] cro=%s: %d linhas (%d UFs, total %d), %d pendentes %s",
        tipo_relatorio, cro, len(linhas), ufs_na_tela, total_completo, len(pendentes), pendentes,
    )
    return {
        "total": total_completo,
        "resultados": linhas,
        "pendentes": pendentes,
        "resumo_regionais": resumo_regionais,
    }


def _run_cielo_json_job(job_id: str, inicio: str, termino: str, cro: Optional[str], tipo_relatorio: str) -> None:
    if SessionDB3 is None:
        with _cielo_json_jobs_lock:
            _cielo_json_jobs[job_id].update({"status": "error", "detail": "DB3 não configurado.", "updated_at": time.time()})
        return

    with _cielo_json_jobs_lock:
        _cielo_json_jobs[job_id].update({"status": "running", "updated_at": time.time()})

    db3 = SessionDB3()
    try:
        resultado = consolidate_cielo_checkout(db3, cro, inicio, termino, tipo_relatorio, for_excel=True)
        payload = _build_cielo_json_payload(resultado, cro, tipo_relatorio)
        with _cielo_json_jobs_lock:
            _cielo_json_jobs[job_id].update({
                "status": "done",
                "updated_at": time.time(),
                **payload,
            })
    except Exception as exc:
        logger.error("Erro job JSON Cielo %s: %s", job_id, exc, exc_info=True)
        with _cielo_json_jobs_lock:
            _cielo_json_jobs[job_id].update({
                "status": "error",
                "detail": "Erro ao consolidar pagamentos Cielo.",
                "updated_at": time.time(),
            })
    finally:
        db3.close()


def _run_cielo_excel_job(job_id: str, inicio: str, termino: str, cro: Optional[str], tipo_relatorio: str) -> None:
    if SessionDB3 is None:
        with _excel_jobs_lock:
            _excel_jobs[job_id].update({"status": "error", "detail": "DB3 não configurado."})
        return

    with _excel_jobs_lock:
        _excel_jobs[job_id].update({"status": "running", "updated_at": time.time()})

    db3 = SessionDB3()
    try:
        excel_bytes, filename = _build_cielo_excel(db3, inicio, termino, cro, tipo_relatorio)
        with _excel_jobs_lock:
            _excel_jobs[job_id].update({
                "status": "done",
                "filename": filename,
                "excel_bytes": excel_bytes,
                "size": len(excel_bytes),
                "updated_at": time.time(),
            })
    except HTTPException as exc:
        with _excel_jobs_lock:
            _excel_jobs[job_id].update({
                "status": "error",
                "detail": exc.detail,
                "updated_at": time.time(),
            })
    except Exception as exc:
        logger.error("Erro job Excel Cielo %s: %s", job_id, exc, exc_info=True)
        with _excel_jobs_lock:
            _excel_jobs[job_id].update({
                "status": "error",
                "detail": "Erro ao gerar planilha Excel.",
                "updated_at": time.time(),
            })
    finally:
        db3.close()


@router.get("/cielo-checkout")
def relatorio_cielo_checkout_json(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    cro: Optional[str] = Query("TODOS", description="UF do CRO ou TODOS"),
    tipo_relatorio: str = Query("sintetico", description="Tipo: analitico | sintetico | renegociacoes"),
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
    db3: Session = Depends(get_db3),
):
    """Legado síncrono — preferir POST /cielo-checkout/jobs (fila assíncrona)."""
    if tipo_relatorio not in CIELO_REPORT_TYPES:
        raise HTTPException(status_code=400, detail=f"tipo_relatorio inválido. Use: {', '.join(CIELO_REPORT_TYPES.keys())}")
    _validate_dates(inicio, termino)
    try:
        d_inicio = datetime.strptime(inicio, "%Y-%m-%d")
        d_termino = datetime.strptime(termino, "%Y-%m-%d")
    except ValueError:
        raise HTTPException(status_code=400, detail="Datas inválidas.")
    if d_termino < d_inicio:
        raise HTTPException(status_code=400, detail="Data término deve ser igual ou posterior à data início.")
    if (d_termino - d_inicio).days > MAX_PERIOD_DAYS:
        raise HTTPException(status_code=400, detail=f"Período máximo permitido: {MAX_PERIOD_DAYS} dias.")
    try:
        resolve_ufs(cro)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    try:
        resultado = consolidate_cielo_checkout(db3, cro, inicio, termino, tipo_relatorio, for_excel=True)
    except Exception as e:
        logger.error("Erro consolidação Cielo JSON (%s): %s", tipo_relatorio, e)
        raise HTTPException(status_code=500, detail="Erro ao consolidar pagamentos Cielo.")
    return _build_cielo_json_payload(resultado, cro, tipo_relatorio)


@router.post("/cielo-checkout/jobs")
def iniciar_cielo_checkout_json_job(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    cro: Optional[str] = Query("TODOS", description="UF do CRO ou TODOS"),
    tipo_relatorio: str = Query("sintetico", description="Tipo: analitico | sintetico | renegociacoes"),
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
):
    """Enfileira consolidação Cielo — executa somente após ação do usuário (botão Gerar)."""
    if tipo_relatorio not in CIELO_REPORT_TYPES:
        raise HTTPException(status_code=400, detail=f"tipo_relatorio inválido. Use: {', '.join(CIELO_REPORT_TYPES.keys())}")
    _validate_dates(inicio, termino)
    try:
        d_inicio = datetime.strptime(inicio, "%Y-%m-%d")
        d_termino = datetime.strptime(termino, "%Y-%m-%d")
    except ValueError:
        raise HTTPException(status_code=400, detail="Datas inválidas.")
    if d_termino < d_inicio:
        raise HTTPException(status_code=400, detail="Data término deve ser igual ou posterior à data início.")
    if (d_termino - d_inicio).days > MAX_PERIOD_DAYS:
        raise HTTPException(status_code=400, detail=f"Período máximo permitido: {MAX_PERIOD_DAYS} dias.")
    try:
        resolve_ufs(cro)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

    _cleanup_cielo_json_jobs()
    job_id = uuid.uuid4().hex
    now = time.time()
    with _cielo_json_jobs_lock:
        _cielo_json_jobs[job_id] = {
            "status": "pending",
            "created_at": now,
            "updated_at": now,
            "inicio": inicio,
            "termino": termino,
            "cro": cro or "TODOS",
            "tipo_relatorio": tipo_relatorio,
        }

    position = enqueue_cielo_job(
        lambda: _run_cielo_json_job(job_id, inicio, termino, cro, tipo_relatorio)
    )
    return {"job_id": job_id, "status": "pending", "queue_position": position}


@router.get("/cielo-checkout/jobs/{job_id}")
def consultar_cielo_checkout_json_job(
    job_id: str,
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
):
    _cleanup_cielo_json_jobs()
    with _cielo_json_jobs_lock:
        job = _cielo_json_jobs.get(job_id)
        if not job:
            raise HTTPException(status_code=404, detail="Consulta não encontrada ou expirada.")
        response = {
            "job_id": job_id,
            "status": job.get("status"),
            "detail": job.get("detail"),
        }
        if job.get("status") == "done":
            response.update({
                "total": job.get("total"),
                "resultados": job.get("resultados"),
                "pendentes": job.get("pendentes", []),
                "resumo_regionais": job.get("resumo_regionais", []),
            })
        return response


@router.get("/cielo-checkout-consolidado/excel")
def relatorio_cielo_checkout_excel(
    inicio: str = Query(..., description="Data início YYYY-MM-DD (data crédito/pagamento)"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    cro: Optional[str] = Query("TODOS", description="UF do CRO ou TODOS"),
    tipo_relatorio: str = Query("analitico", description="Tipo: analitico | sintetico | renegociacoes"),
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
    db3: Session = Depends(get_db3),
):
    """Consolida pagamentos Cielo Checkout e gera planilha Excel (sem pré-visualização)."""
    excel_bytes, filename = _build_cielo_excel(db3, inicio, termino, cro, tipo_relatorio)
    return _excel_stream(excel_bytes, filename)


@router.post("/cielo-checkout-consolidado/excel/jobs")
def iniciar_cielo_checkout_excel_job(
    inicio: str = Query(..., description="Data início YYYY-MM-DD (data crédito/pagamento)"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    cro: Optional[str] = Query("TODOS", description="UF do CRO ou TODOS"),
    tipo_relatorio: str = Query("analitico", description="Tipo: analitico | sintetico | renegociacoes"),
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
):
    """Enfileira geração assíncrona do Excel Cielo — somente após ação do usuário."""
    _validate_cielo_excel_request(inicio, termino, cro, tipo_relatorio)
    _cleanup_excel_jobs()

    job_id = uuid.uuid4().hex
    now = time.time()
    with _excel_jobs_lock:
        _excel_jobs[job_id] = {
            "status": "pending",
            "created_at": now,
            "updated_at": now,
            "inicio": inicio,
            "termino": termino,
            "cro": cro or "TODOS",
            "tipo_relatorio": tipo_relatorio,
        }

    position = enqueue_cielo_job(
        lambda: _run_cielo_excel_job(job_id, inicio, termino, cro, tipo_relatorio)
    )
    return {"job_id": job_id, "status": "pending", "queue_position": position}


@router.get("/cielo-checkout-consolidado/excel/jobs/{job_id}")
def consultar_cielo_checkout_excel_job(
    job_id: str,
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
):
    _cleanup_excel_jobs()
    with _excel_jobs_lock:
        job = _excel_jobs.get(job_id)
        if not job:
            raise HTTPException(status_code=404, detail="Geração de Excel não encontrada ou expirada.")
        return {
            "job_id": job_id,
            "status": job.get("status"),
            "detail": job.get("detail"),
            "filename": job.get("filename"),
            "size": job.get("size"),
        }


@router.get("/cielo-checkout-consolidado/excel/jobs/{job_id}/download")
def baixar_cielo_checkout_excel_job(
    job_id: str,
    current_user: User = Depends(check_any_permission(["view_relatorio_financeiro", "export_relatorio_financeiro"])),
):
    with _excel_jobs_lock:
        job = _excel_jobs.get(job_id)
        if not job:
            raise HTTPException(status_code=404, detail="Geração de Excel não encontrada ou expirada.")
        if job.get("status") == "error":
            raise HTTPException(status_code=500, detail=job.get("detail") or "Erro ao gerar planilha Excel.")
        if job.get("status") != "done":
            raise HTTPException(status_code=409, detail="Planilha ainda está sendo gerada.")
        excel_bytes = job.get("excel_bytes")
        filename = job.get("filename") or "relatorio_cielo.xlsx"

    return _excel_stream(excel_bytes, filename)


@router.get("/arrecadacao-selfpay")
def relatorio_arrecadacao_selfpay(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Arrecadação e Tarifas SelfPay/BkBank"""
    _validate_dates(inicio, termino)
    sql = text("""
        SELECT CRO, CONVERT(VARCHAR, DataCredito, 103) AS Data_Credito,
            SUM(ValorBruto) AS Valor_Bruto,
            SUM(ValorLiquido - SplitFederal) AS Valor_CRO,
            SUM(ValorBruto - ValorLiquido) AS Tarifa_Cartao,
            SUM(SplitFederal) AS Split_Federal
        FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Arrecadacao_e_Tarifas_Selfpay
        WHERE DataCredito BETWEEN :inicio AND :termino
        GROUP BY CRO, DataCredito
        ORDER BY CRO, DataCredito
    """)
    rows = db3.execute(sql, {"inicio": inicio, "termino": termino}).mappings().all()
    return [fix_row_encoding(dict(r)) for r in rows]


@router.get("/processos-especialidade")
def relatorio_processos_especialidade(
    inicio: str = Query(..., description="Data início YYYY-MM-DD"),
    termino: str = Query(..., description="Data término YYYY-MM-DD"),
    estado: Optional[str] = Query(None, description="CRO/UF"),
    etapa: Optional[str] = Query(None, description="Etapa do processo"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Processos de Especialidade e Habilitação"""
    _validate_dates(inicio, termino)
    conditions = [
        "DataAndamento >= CONVERT(datetime, :inicio, 120)",
        "DataAndamento < DATEADD(DAY, 1, CONVERT(datetime, :termino, 120))",
    ]
    params = {"inicio": inicio, "termino": termino}

    if estado:
        conditions.append("CRO = :estado")
        params["estado"] = estado.upper()
    if etapa:
        conditions.append("EtapaProcesso = :etapa")
        params["etapa"] = etapa

    where = " AND ".join(conditions)
    sql = text(f"""
        SELECT CRO, NumeroProcesso, Nome, Classificacao, EtapaProcesso,
            Andamento, CONVERT(VARCHAR, DataAndamento, 103) AS DataAndamento,
            DiasDesdeAndamento
        FROM CFO_CWS.dbo.vw_Cons_Relatorio_de_Processos_de_Especialidade_e_Habilitacao
        WHERE {where}
        ORDER BY CRO, DataAndamento DESC
    """)
    rows = db3.execute(sql, params).mappings().all()
    return [fix_row_encoding(dict(r)) for r in rows]


@router.get("/delegado-eleitor")
def relatorio_delegado_eleitor(
    uf: str = Query(..., description="UF do CRO"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Delegado Eleitor (email + telefone)"""
    # Email data
    sql_email = text("""
        SELECT CpfCnpj, profissional, Email, CRO, CATEGORIA, INSCRICAO
        FROM CFO_CWS.dbo.vw_Delegado_Eleitor_Email
        WHERE CRO = :uf
    """)
    emails = {row["CpfCnpj"]: fix_row_encoding(dict(row)) for row in db3.execute(sql_email, {"uf": uf.upper()}).mappings().all()}

    # Phone data
    sql_tel = text("""
        SELECT CpfCnpj, telefone
        FROM CFO_CWS.dbo.vw_Delegado_Eleitor_telefone
        WHERE CRO = :uf
    """)
    phones = db3.execute(sql_tel, {"uf": uf.upper()}).mappings().all()

    # Merge
    for row in phones:
        cpf = row["CpfCnpj"]
        if cpf in emails:
            emails[cpf]["telefone"] = row["telefone"]

    resultados = list(emails.values())
    return resultados


@router.get("/profissional-formacao-sql")
def relatorio_profissional_formacao_sql(
    uf: Optional[str] = Query(None, description="Filtrar por CRO/UF"),
    current_user: User = Depends(check_any_permission(["view_relatorios_diversos"])),
    db3: Session = Depends(get_db3),
):
    """Relatório de Profissional x Formação (SQL externo completo)"""
    sql_content = load_sql("relatorios", "profissional-formacao.sql")
    conditions = []
    params = {}

    if uf:
        # Append CRO filter after WHERE clause
        conditions.append("DP.SiglaCRO = :uf")
        params["uf"] = uf.upper()

    if conditions:
        sql_content = sql_content.rstrip().rstrip(";")
        sql_content += " AND " + " AND ".join(conditions)

    rows = db3.execute(text(sql_content), params).mappings().all()
    return [fix_row_encoding(dict(r)) for r in rows]
