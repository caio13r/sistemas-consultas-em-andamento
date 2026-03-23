"""
Tabelas Centralizadas - Formações acadêmicas, IES, cursos e tabelas do sistema cfo_br
Fonte: DB3 (SQL Server - CFO_CWS / cfo_br)
"""
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3
from ..models import User
from ..core.auth import check_permission
from ..lib.sql_loader import load_sql
from pydantic import BaseModel

router = APIRouter(prefix="/tabelas-centralizadas", tags=["tabelas-centralizadas"])


class TabelaResponse(BaseModel):
    total: int
    tipo: str
    nome: str
    resultados: List[dict]


# Catálogo de tabelas centralizadas
# Tipos com "view" usam query dinâmica; tipos com "sql_file" carregam SQL externo
TABELA_TYPES = {
    "ies": {
        "nome": "Instituições de Ensino Superior (IES)",
        "view": "CFO_CWS.dbo.vw_Cons_Listagem_Formacoes_Academicas_IES",
        "columns": """
            Regional, Ativo, Razao_Social, Nome_Fantasia, CNPJ,
            Inscricao_Estadual, Sigla, Natureza_Juridica, Codigo,
            Codigo_Integracao_Federal, Codigo_IE, Reitor, Cursos,
            Especialidades, Campus, Coordenadores_qtd, Observacao, Coordenadores
        """,
        "filter_columns": {"curso": "Cursos", "nome": "Razao_Social"},
    },
    "atividade_economica": {
        "nome": "Atividade Econômica",
        "sql_file": "tabelasCentralizadas2.sql",
    },
    "capital_social": {
        "nome": "Capital Social Faixas",
        "sql_file": "tabelasCentralizadas3.sql",
    },
    "categorias": {
        "nome": "Categorias",
        "sql_file": "tabelasCentralizadas4.sql",
    },
    "classificacao_empresas": {
        "nome": "Classificação Empresas",
        "sql_file": "tabelasCentralizadas5.sql",
    },
    "cursos": {
        "nome": "Cursos",
        "sql_file": "tabelasCentralizadas6.sql",
    },
    "debito_tipos": {
        "nome": "Débito Tipos",
        "sql_file": "tabelasCentralizadas7.sql",
    },
    "especialidades": {
        "nome": "Especialidades",
        "sql_file": "tabelasCentralizadas8.sql",
    },
    "naturezas_juridicas": {
        "nome": "Naturezas Jurídicas",
        "sql_file": "tabelasCentralizadas9.sql",
    },
    "situacoes": {
        "nome": "Situações",
        "sql_file": "tabelasCentralizadas10.sql",
    },
    "situacoes_detalhes": {
        "nome": "Situações Detalhes",
        "sql_file": "tabelasCentralizadas11.sql",
    },
    "tipos_inscricoes": {
        "nome": "Tipos Inscrições",
        "sql_file": "tabelasCentralizadas12.sql",
    },
    "motivos_fiscalizacao": {
        "nome": "Motivos de Fiscalização",
        "sql_file": "tabelasCentralizadas13.sql",
    },
}


@router.get("/tipos")
def listar_tipos_tabela(
    current_user: User = Depends(check_permission("view_tabelas_centralizadas")),
):
    """Lista tipos de tabelas centralizadas disponíveis"""
    return [{"codigo": k, "nome": v["nome"]} for k, v in TABELA_TYPES.items()]


@router.get("/buscar", response_model=TabelaResponse)
def buscar_tabela(
    tipo: str = Query("ies", description="Tipo de tabela (ver /tipos)"),
    curso: Optional[str] = Query(None, description="Filtrar por curso"),
    nome: Optional[str] = Query(None, description="Filtrar por nome da instituição"),
    cro: Optional[str] = Query(None, description="Filtrar por regional"),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_tabelas_centralizadas")),
):
    """Busca dados de tabelas centralizadas"""
    if tipo not in TABELA_TYPES:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(TABELA_TYPES.keys())}")

    tabela = TABELA_TYPES[tipo]

    # Tipos baseados em SQL externo (tabelas cfo_br)
    if "sql_file" in tabela:
        sql = load_sql("tabelas_centralizadas", tabela["sql_file"])
        query_sql = text(sql)
        rows = db3.execute(query_sql).mappings().all()
        resultados = [dict(r) for r in rows]
        return TabelaResponse(
            total=len(resultados),
            tipo=tipo,
            nome=tabela["nome"],
            resultados=resultados,
        )

    # Tipo IES (view com filtros dinâmicos)
    conditions = []
    params = {}

    if curso:
        conditions.append(f"{tabela['filter_columns'].get('curso', 'Cursos')} LIKE :curso")
        params["curso"] = f"%{curso}%"
    if nome:
        conditions.append(f"{tabela['filter_columns'].get('nome', 'Razao_Social')} LIKE :nome")
        params["nome"] = f"%{nome}%"
    if cro:
        conditions.append("Regional = :cro")
        params["cro"] = cro.upper()

    where = " AND ".join(conditions) if conditions else "1=1"

    count_sql = text(f"SELECT COUNT(*) FROM {tabela['view']} WHERE {where}")
    total = db3.execute(count_sql, params).scalar() or 0

    query_sql = text(f"""
        SELECT TOP 2000 {tabela['columns']}
        FROM {tabela['view']}
        WHERE {where}
        ORDER BY Razao_Social
    """)
    rows = db3.execute(query_sql, params).mappings().all()

    return TabelaResponse(
        total=total,
        tipo=tipo,
        nome=tabela["nome"],
        resultados=[dict(r) for r in rows],
    )
