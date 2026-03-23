"""
Consulta Fiscalização - Estatísticas de fiscalizações dos CROs
Fonte: DB3 (SQL Server - CFO_CWS / cfo_br)
"""
import re
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db1, get_db3
from ..models import User
from ..core.auth import check_permission
from ..lib.sql_loader import load_sql
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-fiscalizacao", tags=["consulta-fiscalizacao"])

# Nome do banco de dados cfo_br no SQL Server (usado nos scripts com :banco)
CFO_BR_DB = "cfo_br"


class FiscalizacaoResponse(BaseModel):
    total: int
    tipo: str
    nome: str
    resultados: List[dict]


# ---------------------------------------------------------------------------
# Catálogo de tipos de consulta de fiscalização
# ---------------------------------------------------------------------------
FISC_TYPES = {
    # --- Tipos com view direta (filtros dinâmicos) ---
    "por-categoria-ano": {
        "nome": "Estatísticas por Categoria x Ano",
        "view": "CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes",
        "columns": """
            T.CRO, T.Categoria,
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
        """,
        "alias": "T",
        "filter_cols": {"cro": "T.CRO", "categoria": "T.Categoria", "ano": "T.ANO"},
        "order": "T.CRO, T.ANO DESC",
    },
    "sem-inscricao-tipo": {
        "nome": "Sem Inscrições por Tipo",
        "view": "CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao",
        "columns": """
            T.ANO, T.CRO, T.Pessoa,
            T.[Quantidade de Fiscalizações] AS QtdFiscalizacoes,
            T.[Fiscalizações ON-LINE] AS FiscOnline,
            T.[Fiscalizações PROATIVAS] AS FiscProativas,
            T.[Fiscalizações REATIVAS] AS FiscReativas,
            T.[Fiscalizações NÃO INFORMADO] AS FiscNaoInformado,
            T.[Fiscalizações Exercício Ilegal] AS FiscExercicioIlegal,
            T.[Notificações (indícios de irregularidades)] AS Notificacoes,
            T.[Fiscalizações com Termo] AS FiscComTermo
        """,
        "alias": "T",
        "filter_cols": {"cro": "T.CRO", "pessoa": "T.Pessoa", "ano": "T.ANO"},
        "order": "T.CRO, T.ANO DESC",
    },
    "por-fiscal": {
        "nome": "Por Fiscal",
        "view": "CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PorFiscal",
        "columns": "*",
        "alias": "T",
        "filter_cols": {"cro": "T.CRO"},
        "order": "T.CRO",
    },
    "por-fiscal-sem-inscricao": {
        "nome": "Fiscal Sem Inscrições",
        "view": "CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PessoasSemInscricao_PorFiscal",
        "columns": "*",
        "alias": "T",
        "filter_cols": {"cro": "T.CRO"},
        "order": "T.CRO",
    },
    "irregularidades": {
        "nome": "Tipos de Irregularidades",
        "view": "CFO_CWS.dbo.Cons_EstatisticasFiscalizacoes_PorTipos_Irregularidades",
        "columns": "*",
        "alias": "T",
        "filter_cols": {"cro": "T.CRO"},
        "order": "T.CRO",
    },
    "denuncias": {
        "nome": "Denúncias",
        "view": "CFO_CWS.dbo.Cons_Estatisticas_Denuncias",
        "columns": "*",
        "alias": "T",
        "filter_cols": {"cro": "T.CRO"},
        "order": "T.CRO",
    },
    "por-idade": {
        "nome": "Fiscalizados por Idade",
        "view": "CFO_CWS.dbo.Cons_Contagem_Fiscalizados_Por_Idade",
        "columns": "*",
        "alias": "T",
        "filter_cols": {"cro": "T.CRO"},
        "order": "T.Idade DESC",
    },

    # --- Tipos com SQL externo simples (param @CRO_UF) ---
    "qtd-fiscais": {
        "nome": "Quantidade de Fiscais",
        "sql_file": "consultaFiscalizacao9.sql",
        "params": ["cro_uf"],
    },
    "nomes-fiscais": {
        "nome": "Nomes dos Fiscais",
        "sql_file": "consultaFiscalizacao10.sql",
        "params": ["cro_uf"],
    },

    # --- Tipos com SQL externo complexo (params :banco, :inicio, :termino) ---
    "termos-categoria-periodo": {
        "nome": "Por Termos - Categoria e Período",
        "sql_file": "consultaFiscalizacao11.sql",
        "params": ["periodo", "categoria"],
    },
    "sem-inscricao-periodo": {
        "nome": "Sem Inscrições por Período",
        "sql_file": "consultaFiscalizacao12.sql",
        "params": ["periodo"],
    },
    "fiscal-categoria-periodo": {
        "nome": "Fiscal por Categoria e Período",
        "sql_file": "consultaFiscalizacao13.sql",
        "params": ["periodo", "categoria"],
    },
    "fiscal-sem-inscricao-periodo": {
        "nome": "Fiscal Sem Inscrição por Período",
        "sql_file": "consultaFiscalizacao14.sql",
        "params": ["periodo"],
    },
    "irregularidades-periodo": {
        "nome": "Irregularidades por Período",
        "sql_file": "consultaFiscalizacao15.sql",
        "params": ["periodo", "categoria"],
    },
    "denuncias-periodo": {
        "nome": "Denúncias por Período",
        "sql_file": "consultaFiscalizacao16.sql",
        "params": ["periodo"],
    },
    "idade-periodo": {
        "nome": "Fiscalizados por Idade e Período",
        "sql_file": "consultaFiscalizacao18.sql",
        "params": ["periodo"],
    },

    # --- Tipos com SQL externo (cfo_br sem parâmetros de data) ---
    "fiscais-ativos": {
        "nome": "Fiscais Ativos (Lista)",
        "sql_file": "consultaFiscalizacao19.sql",
        "params": ["cro_uf"],
    },
    "fiscais-acesso-sistema": {
        "nome": "Acesso de Fiscais ao Sistema",
        "sql_file": "consultaFiscalizacao20.sql",
        "params": [],
    },
}

# Regex para validar datas no formato YYYY-MM-DD
_DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}$")
# Regex para validar categorias (apenas letras)
_CAT_RE = re.compile(r"^[A-Za-z%]+$")


def _prepare_period_sql(sql: str, inicio: str, termino: str,
                        categoria: Optional[str] = None) -> str:
    """Substitui placeholders de template nos scripts SQL de fiscalização."""
    sql = sql.replace(":banco", CFO_BR_DB)
    sql = sql.replace(":inicio", inicio)
    sql = sql.replace(":termino", termino)
    if categoria:
        sql = sql.replace(":categoria", categoria)
    else:
        sql = sql.replace(":categoria", "%")
    # :tipoPessoaFisica — fragmento WHERE para filtro PF/PJ; por padrão sem filtro
    sql = sql.replace(":tipoPessoaFisica", "")
    return sql


@router.get("/tipos")
def listar_tipos_fiscalizacao(
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
):
    """Lista todos os tipos de consulta de fiscalização"""
    return [{"codigo": k, "nome": v["nome"]} for k, v in FISC_TYPES.items()]


@router.get("/buscar", response_model=FiscalizacaoResponse)
def buscar_fiscalizacao(
    tipo: str = Query(..., description="Tipo de consulta (ver /tipos)"),
    cro: Optional[str] = Query(None, description="Filtrar por CRO/UF"),
    categoria: Optional[str] = Query(None, description="Categoria (CD, TPD, etc.)"),
    ano: Optional[int] = Query(None, description="Ano"),
    pessoa: Optional[str] = Query(None, description="PF SEM INSCRIÇÃO ou PJ SEM INSCRIÇÃO"),
    inicio: Optional[str] = Query(None, description="Data início (YYYY-MM-DD)"),
    termino: Optional[str] = Query(None, description="Data término (YYYY-MM-DD)"),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
):
    """Busca dados de fiscalização por tipo"""
    if tipo not in FISC_TYPES:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(FISC_TYPES.keys())}")

    fisc = FISC_TYPES[tipo]

    # --- Tipos baseados em SQL externo ---
    if "sql_file" in fisc:
        required = fisc.get("params", [])
        sql = load_sql("consulta_fiscalizacao", fisc["sql_file"])

        # Scripts com @CRO_UF (simples)
        if "cro_uf" in required:
            if not cro:
                raise HTTPException(status_code=400, detail="Parâmetro 'cro' é obrigatório.")
            sql = sql.replace("@CRO_UF", ":CRO_UF")
            rows = db3.execute(text(sql), {"CRO_UF": cro.upper()}).mappings().all()

        # Scripts com período (:banco, :inicio, :termino)
        elif "periodo" in required:
            if not inicio or not termino:
                raise HTTPException(status_code=400, detail="Parâmetros 'inicio' e 'termino' são obrigatórios (YYYY-MM-DD).")
            if not _DATE_RE.match(inicio) or not _DATE_RE.match(termino):
                raise HTTPException(status_code=400, detail="Datas devem estar no formato YYYY-MM-DD.")
            cat_val = None
            if "categoria" in required and categoria:
                if not _CAT_RE.match(categoria):
                    raise HTTPException(status_code=400, detail="Categoria inválida.")
                cat_val = categoria.upper()
            sql = _prepare_period_sql(sql, inicio, termino, cat_val)
            rows = db3.execute(text(sql)).mappings().all()

        # Scripts sem parâmetros (cfo_br direto)
        else:
            sql = sql.replace(":banco", CFO_BR_DB)
            rows = db3.execute(text(sql)).mappings().all()

        resultados = [dict(r) for r in rows]
        return FiscalizacaoResponse(
            total=len(resultados), tipo=tipo, nome=fisc["nome"], resultados=resultados,
        )

    # --- Tipos baseados em view com filtros dinâmicos ---
    alias = fisc.get("alias", "T")
    filter_cols = fisc.get("filter_cols", {})
    conditions = []
    params = {}

    if cro and "cro" in filter_cols:
        conditions.append(f"{filter_cols['cro']} = :cro")
        params["cro"] = cro.upper()
    if categoria and "categoria" in filter_cols:
        conditions.append(f"{filter_cols['categoria']} = :categoria")
        params["categoria"] = categoria.upper()
    if ano and "ano" in filter_cols:
        conditions.append(f"{filter_cols['ano']} = :ano")
        params["ano"] = ano
    if pessoa and "pessoa" in filter_cols:
        conditions.append(f"{filter_cols['pessoa']} = :pessoa")
        params["pessoa"] = pessoa

    where = " AND ".join(conditions) if conditions else "1=1"
    order = fisc.get("order", "1")

    count_sql = text(f"SELECT COUNT(*) FROM {fisc['view']} {alias} WHERE {where}")
    total = db3.execute(count_sql, params).scalar() or 0

    query_sql = text(f"""
        SELECT {fisc['columns']}
        FROM {fisc['view']} AS {alias}
        WHERE {where}
        ORDER BY {order}
    """)
    rows = db3.execute(query_sql, params).mappings().all()

    return FiscalizacaoResponse(
        total=total, tipo=tipo, nome=fisc["nome"], resultados=[dict(r) for r in rows],
    )


# --- Endpoint legado: coordenadores de fiscalização (DB1 MySQL) ---
@router.get("/coordenadores", response_model=FiscalizacaoResponse)
def coordenadores_fiscalizacao(
    db1: Session = Depends(get_db1),
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
):
    """Coordenadores de Fiscalização (DB1 - tbl_users subgrupo Fiscalização)"""
    query_sql = text("""
        SELECT nome, email, grupo, subgrupo
        FROM tbl_users
        WHERE subgrupo = 'Fiscalização - Coordenação'
        ORDER BY nome
    """)
    rows = db1.execute(query_sql).mappings().all()
    resultados = [dict(r) for r in rows]

    return FiscalizacaoResponse(
        total=len(resultados),
        tipo="coordenadores",
        nome="Coordenadores de Fiscalização",
        resultados=resultados,
    )
