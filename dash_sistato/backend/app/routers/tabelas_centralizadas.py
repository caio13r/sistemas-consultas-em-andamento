"""
Tabelas Centralizadas - Formações acadêmicas, IES, cursos
Fonte: DB3 (SQL Server - CFO_CWS)
Views: vw_Cons_Listagem_Formacoes_Academicas_IES, etc.
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/tabelas-centralizadas", tags=["tabelas-centralizadas"])


class TabelaResponse(BaseModel):
    total: int
    resultados: List[dict]


# Catálogo de tabelas centralizadas com suas views
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
        from fastapi import HTTPException
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(TABELA_TYPES.keys())}")

    tabela = TABELA_TYPES[tipo]
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

    return TabelaResponse(total=total, resultados=[dict(r) for r in rows])
