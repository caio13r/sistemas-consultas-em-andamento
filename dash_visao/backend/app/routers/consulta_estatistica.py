"""
Consulta Estatística - Dados populacionais e profissionais por CRO/Categoria/Sexo
Fonte: DB3 (SQL Server - CFO_CWS), DB2 (MySQL - WSCFO)
"""
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db2, get_db3, fix_row_encoding
from ..models import User
from ..core.auth import check_permission
from ..lib.sql_loader import load_sql
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-estatistica", tags=["consulta-estatistica"])


class EstatisticaResponse(BaseModel):
    total: int
    tipo: str
    nome: str
    resultados: List[dict]


# ---------------------------------------------------------------------------
# Catálogo de tipos de consulta estatística
# "view"     → query dinâmica com filtros de CRO
# "sql_file" → carrega SQL externo (parâmetros @CRO_UF / @Ano → :cro_uf / :ano)
# ---------------------------------------------------------------------------
ESTAT_TYPES = {
    "populacao": {
        "nome": "Dados Populacionais",
        "view": "CFO_CWS.dbo.vw_Cons_Dados_Somados_Populacao",
        "columns": "REGIAO, UF, POPULACAO, CD_FEM, CD_MAS, APD_FEM, APD_MAS, TPD_FEM, TPD_MAS, ASB_FEM, ASB_MAS, TSB_FEM, TSB_MAS, TOT_FEM, TOT_MAS, TOT_BRASIL",
        "filter_col": "UF",
        "order": "REGIAO, UF",
    },
    "inscricao-categoria-ano": {
        "nome": "CRO x Categoria x Ano de Registro",
        "view": "CFO_CWS.dbo.vw_Cons_Consolidados_Inscricao_Categoria_Ano_Mes",
        "columns": "CRO, ANO_REGISTRO, TOT_CD_FEM, TOT_CD_MAS, TOT_APD_FEM, TOT_APD_MAS, TOT_TPD_FEM, TOT_TPD_MAS, TOT_ASB_FEM, TOT_ASB_MAS, TOT_TSB_FEM, TOT_TSB_MAS, TOTAL_PROF_FEM, TOTAL_PROF_MAS, TOTAL_GERAL",
        "filter_col": "CRO",
        "order": "ANO_REGISTRO ASC",
    },
    "categoria-faixa-etaria": {
        "nome": "CRO x Categoria x Faixa Etária",
        "view": "CFO_CWS.dbo.vw_Cons_Consolidados_Nascimento_Categoria_Ano",
        "columns": "CRO, FAIXA_ETARIA, CD_FEM, CD_MAS, APD_FEM, APD_MAS, TPD_FEM, TPD_MAS, ASB_FEM, ASB_MAS, TSB_FEM, TSB_MAS, TOTAL_FEM, TOTAL_MAS, TOTAL_CRO",
        "filter_col": "CRO",
        "order": "FAIXA_ETARIA ASC",
    },
    "especialidade-sexo": {
        "nome": "CRO x Especialidade x Sexo",
        "view": "CFO_CWS.dbo.vw_Cons_Dados_Basicos_Especialidades_Sexo_Somados_CRO",
        "columns": "*",
        "filter_col": "CRO",
        "order": "CRO, especializacao",
    },
    "especialidade-faixa-etaria": {
        "nome": "CRO x Especialidade x Faixa Etária",
        "view": "CFO_CWS.dbo.vw_Cons_Consolidados_Nascimento_Especialidade_Ano",
        "columns": "*",
        "filter_col": "CRO",
        "order": "FAIXA_ETARIA ASC",
    },
    "profissionais-por-idade": {
        "nome": "Ativos por Idade",
        "view": "CFO_CWS.dbo.vw_Cons_Contagem_profissionais_por_idade",
        "columns": "CRO, Idade, APD, ASB, CD, TPD, TSB, Total_geral",
        "filter_col": "CRO",
        "order": "Idade DESC",
    },
    "especialidade-sexo-detalhado": {
        "nome": "Especialidade x Sexo (detalhado)",
        "view": "CFO_CWS.dbo.vw_Cons_Registro_Especialidades",
        "columns": "CRO, Especialidade, Masculino, Feminino, TOTAL",
        "filter_col": "CRO",
        "order": "Especialidade",
    },
    "ativos-localidade-br": {
        "nome": "Ativos por Localidade (Brasil)",
        "sql_file": "consultaEstatistica10_br.sql",
        "params": [],
    },
    "ativos-localidade-uf": {
        "nome": "Ativos por Localidade (UF)",
        "sql_file": "consultaEstatistica10_uf.sql",
        "params": ["cro_uf"],
    },
    "dda-ativos": {
        "nome": "Endereços Residenciais (DDA Ativos)",
        "sql_file": "consultaEstatistica11.sql",
        "params": ["cro_uf"],
    },
    "enderecos-residenciais": {
        "nome": "Endereços Residenciais",
        "sql_file": "consultaEstatistica12.sql",
        "params": ["cro_uf"],
    },
    "enderecos-comerciais": {
        "nome": "Endereços Comerciais",
        "sql_file": "consultaEstatistica13.sql",
        "params": ["cro_uf"],
    },
    "especialidade-sexo-ano": {
        "nome": "Especialidade x Sexo x Ano",
        "sql_file": "consultaEstatistica14.sql",
        "params": ["cro_uf", "ano"],
    },
    "especialidade-tecnica-sexo": {
        "nome": "Especialidade Técnica x Sexo",
        "sql_file": "consultaEstatistica15.sql",
        "params": ["cro_uf"],
    },
    "especialidade-tecnica-sexo-ano": {
        "nome": "Especialidade Técnica x Sexo x Ano",
        "sql_file": "consultaEstatistica16.sql",
        "params": ["cro_uf", "ano"],
    },
    "habilitacao-sexo": {
        "nome": "Habilitação x Sexo",
        "sql_file": "consultaEstatistica17.sql",
        "params": ["cro_uf"],
    },
    "habilitacao-sexo-ano": {
        "nome": "Habilitação x Sexo x Ano",
        "sql_file": "consultaEstatistica18.sql",
        "params": ["cro_uf", "ano"],
    },
    "ativos-por-ano": {
        "nome": "Ativos por Ano (18 anos)",
        "view": "CFO_CWS.dbo.Cons_Total_Inscritos_Ativos_Por_Ano_Por_Categoria",
        "columns": "Ate_Ano, CRO, APD, ASB, CD, ECIPO, EPAO, LB, TPD, TSB, TOTAL",
        "filter_col": "CRO",
        "order": "Ate_Ano DESC",
    },
    "geral": {
        "nome": "Resumo Nacional",
        "view": "CFO_CWS.dbo.Cons_Total_Ativos_Localidade",
        "columns": "[CRO], [UF], [TOTAL]",
        "fixed_where": "[UF] = 'TOTAL'",
        "filter_col": "CRO",
        "order": "IIF([CRO] = 'BRASIL', 1, 0), [CRO]",
    },
}


@router.get("/tipos")
def listar_tipos_estatistica(
    current_user: User = Depends(check_permission("view_consulta_estatistica")),
):
    """Lista todos os tipos de consulta estatística disponíveis"""
    return [{"codigo": k, "nome": v["nome"]} for k, v in ESTAT_TYPES.items()]


@router.get("/buscar", response_model=EstatisticaResponse)
def buscar_estatistica(
    tipo: str = Query(..., description="Tipo de consulta (ver /tipos)"),
    cro: Optional[str] = Query(None, description="Filtrar por CRO/UF"),
    ano: Optional[str] = Query(None, description="Ano (para tipos que exigem)"),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_estatistica")),
):
    """Busca dados estatísticos por tipo"""
    if tipo not in ESTAT_TYPES:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(ESTAT_TYPES.keys())}")

    est = ESTAT_TYPES[tipo]

    # --- Tipos baseados em SQL externo ---
    if "sql_file" in est:
        required = est.get("params", [])
        bind = {}
        if "cro_uf" in required:
            if not cro:
                raise HTTPException(status_code=400, detail="Parâmetro 'cro' é obrigatório para este tipo.")
            bind["CRO_UF"] = cro.upper()
        if "ano" in required:
            if not ano:
                raise HTTPException(status_code=400, detail="Parâmetro 'ano' é obrigatório para este tipo.")
            bind["Ano"] = ano

        sql = load_sql("consulta_estatistica", est["sql_file"])
        # Converte parâmetros @Param do SQL Server para :Param do SQLAlchemy
        sql = sql.replace("@CRO_UF", ":CRO_UF").replace("@Ano", ":Ano")
        rows = db3.execute(text(sql), bind).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return EstatisticaResponse(
            total=len(resultados), tipo=tipo, nome=est["nome"], resultados=resultados,
        )

    # --- Tipos baseados em view com filtros dinâmicos ---
    conditions = []
    params = {}

    if est.get("fixed_where"):
        conditions.append(est["fixed_where"])

    if cro:
        cro_upper = cro.upper()
        filter_col = est.get("filter_col", "CRO")
        if cro_upper not in ("BR", "ALL"):
            conditions.append(f"{filter_col} = :cro")
            params["cro"] = cro_upper

    if ano and "Ate_Ano" in est.get("columns", ""):
        conditions.append("Ate_Ano = :ano")
        params["ano"] = ano

    where = " AND ".join(conditions) if conditions else "1=1"
    order = est.get("order", "1")

    query_sql = text(f"""
        SELECT {est['columns']}
        FROM {est['view']}
        WHERE {where}
        ORDER BY {order}
    """)
    rows = db3.execute(query_sql, params).mappings().all()
    resultados = [fix_row_encoding(dict(r)) for r in rows]

    return EstatisticaResponse(
        total=len(resultados), tipo=tipo, nome=est["nome"], resultados=resultados,
    )


# --- Endpoint legado de sexo x especialidade x município (DB2 MySQL) ---
@router.get("/sexo-especialidade-municipio", response_model=EstatisticaResponse)
def sexo_especialidade_municipio(
    municipio: str = Query(..., description="Município (obrigatório)"),
    sexo: Optional[str] = Query(None, description="F, M ou vazio para todos"),
    especialidade: Optional[str] = Query(None, description="Nome da especialidade"),
    db2: Session = Depends(get_db2),
    current_user: User = Depends(check_permission("view_consulta_estatistica")),
):
    """Sexo x Especialidade x Município (fonte DB2 - WSCFO)"""
    conditions = ["enderecocorrespondencia_municipio = :municipio"]
    params = {"municipio": municipio}

    if sexo and sexo.upper() in ("F", "M"):
        conditions.append("sexo = :sexo")
        params["sexo"] = sexo.upper()
    if especialidade:
        conditions.append("especialidades LIKE :esp")
        params["esp"] = f"%{especialidade}%"

    where = " AND ".join(conditions)
    query_sql = text(f"SELECT * FROM WSCFO.siscaf_webservice WHERE {where}")
    rows = db2.execute(query_sql, params).mappings().all()
    resultados = [dict(r) for r in rows]

    return EstatisticaResponse(
        total=len(resultados),
        tipo="sexo-especialidade-municipio",
        nome="Sexo x Especialidade x Município",
        resultados=resultados,
    )
