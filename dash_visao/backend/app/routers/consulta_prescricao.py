"""
Consulta Prescrição - Dados de prescrições odontológicas
Fonte: DB5 (MySQL - db_prescricao)
Tabela: tbl_prescricoes
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db5
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-prescricao", tags=["consulta-prescricao"])

MAX_RESULTS = 1000


class PrescricaoResponse(BaseModel):
    total: int
    resultados: List[dict]


@router.get("/por-tipo", response_model=PrescricaoResponse)
def prescricoes_por_tipo(
    data_inicial: str = Query(..., description="Data inicial (YYYY-MM-DD)"),
    data_final: str = Query(..., description="Data final (YYYY-MM-DD)"),
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Quantidade de prescrições agrupadas por tipo em um período"""
    query_sql = text("""
        SELECT
            tipo,
            COUNT(*) as quantidade
        FROM tbl_prescricoes
        WHERE STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') BETWEEN :data_inicial AND :data_final
        GROUP BY tipo
        ORDER BY quantidade DESC
    """)
    rows = db5.execute(query_sql, {
        "data_inicial": data_inicial,
        "data_final": data_final,
    }).mappings().all()

    return PrescricaoResponse(total=len(rows), resultados=[dict(r) for r in rows])


@router.get("/por-profissional", response_model=PrescricaoResponse)
def prescricoes_por_profissional(
    cd_nome: str = Query(..., description="Nome do profissional prescritor"),
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Busca prescrições por nome do profissional (CD)"""
    query_sql = text(f"""
        SELECT
            id, psc, tipo, paciente_nome, paciente_cpf,
            cd_nome, cpf, insc, uf, `data`
        FROM tbl_prescricoes
        WHERE cd_nome LIKE :cd_nome
        ORDER BY id DESC
        LIMIT :max_results
    """)
    rows = db5.execute(query_sql, {
        "cd_nome": f"%{cd_nome}%",
        "max_results": MAX_RESULTS,
    }).mappings().all()

    return PrescricaoResponse(total=len(rows), resultados=[dict(r) for r in rows])


@router.get("/por-periodo", response_model=PrescricaoResponse)
def prescricoes_por_periodo(
    data_inicial: str = Query(..., description="Data inicial (YYYY-MM-DD)"),
    data_final: str = Query(..., description="Data final (YYYY-MM-DD)"),
    uf: Optional[str] = Query(None),
    tipo: Optional[str] = Query(None),
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Lista prescrições em um período com filtros opcionais"""
    conditions = ["STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') BETWEEN :data_inicial AND :data_final"]
    params = {"data_inicial": data_inicial, "data_final": data_final}

    if uf:
        conditions.append("uf = :uf")
        params["uf"] = uf.upper()
    if tipo:
        conditions.append("tipo = :tipo")
        params["tipo"] = tipo

    where = " AND ".join(conditions)

    query_sql = text(f"""
        SELECT
            uf, insc, cd_nome, paciente_nome, tipo,
            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') as data_prescricao
        FROM tbl_prescricoes
        WHERE {where}
        ORDER BY STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') DESC
        LIMIT {MAX_RESULTS}
    """)
    rows = db5.execute(query_sql, params).mappings().all()
    resultados = [
        {k: str(v) if v is not None else None for k, v in dict(r).items()}
        for r in rows
    ]

    return PrescricaoResponse(total=len(resultados), resultados=resultados)


@router.get("/por-paciente", response_model=PrescricaoResponse)
def prescricoes_por_paciente(
    paciente_nome: Optional[str] = Query(None),
    paciente_cpf: Optional[str] = Query(None),
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Busca prescrições por nome ou CPF do paciente"""
    conditions = []
    params = {}

    if paciente_nome:
        conditions.append("paciente_nome LIKE :nome")
        params["nome"] = f"%{paciente_nome}%"
    if paciente_cpf:
        cpf_limpo = paciente_cpf.replace(".", "").replace("-", "")
        conditions.append("REPLACE(REPLACE(paciente_cpf, '.', ''), '-', '') LIKE :cpf")
        params["cpf"] = f"%{cpf_limpo}%"

    if not conditions:
        return PrescricaoResponse(total=0, resultados=[])

    where = " AND ".join(conditions)

    query_sql = text(f"""
        SELECT
            id, psc, tipo, paciente_nome, paciente_cpf,
            cd_nome, cpf, insc, uf, `data`
        FROM tbl_prescricoes
        WHERE {where}
        ORDER BY id DESC
        LIMIT {MAX_RESULTS}
    """)
    rows = db5.execute(query_sql, params).mappings().all()

    return PrescricaoResponse(total=len(rows), resultados=[dict(r) for r in rows])


@router.get("/tipos")
def listar_tipos_prescricao(
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Lista todos os tipos de consulta de prescrição"""
    return [
        {"codigo": "por-tipo", "nome": "Prescrições por Tipo"},
        {"codigo": "por-profissional", "nome": "Por Profissional"},
        {"codigo": "por-periodo", "nome": "Por Período"},
        {"codigo": "por-paciente", "nome": "Por Paciente"},
        {"codigo": "ultimos-registros", "nome": "Últimos 3000 Registros"},
        {"codigo": "por-uf", "nome": "Prescrições por UF"},
        {"codigo": "validacao", "nome": "Validação de Prescrição"},
        {"codigo": "profissionais", "nome": "Busca de Profissionais"},
    ]


@router.get("/ultimos-registros", response_model=PrescricaoResponse)
def ultimos_registros(
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Últimos 3000 registros de prescrições"""
    query_sql = text("""
        SELECT uf, insc, cd_nome, paciente_nome, id, tipo,
            STR_TO_DATE(`data`, '%d-%m-%Y %H:%i') as data_prescricao
        FROM tbl_prescricoes
        ORDER BY id DESC
        LIMIT 3000
    """)
    rows = db5.execute(query_sql).mappings().all()
    resultados = [
        {k: str(v) if v is not None else None for k, v in dict(r).items()}
        for r in rows
    ]
    return PrescricaoResponse(total=len(resultados), resultados=resultados)


@router.get("/por-uf", response_model=PrescricaoResponse)
def prescricoes_por_uf(
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Prescrições agrupadas por UF"""
    query_sql = text("""
        SELECT uf, COUNT(*) as quantidade
        FROM tbl_prescricoes
        WHERE uf IS NOT NULL AND uf != ''
        GROUP BY uf
        ORDER BY quantidade DESC
    """)
    rows = db5.execute(query_sql).mappings().all()
    return PrescricaoResponse(total=len(rows), resultados=[dict(r) for r in rows])


@router.get("/validacao", response_model=PrescricaoResponse)
def validar_prescricao(
    id_prescricao: str = Query(..., description="ID da prescrição para validação"),
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Validação de prescrição por ID"""
    query_sql = text("""
        SELECT id, psc, tipo, paciente_nome, paciente_cpf,
            cd_nome, cpf, insc, uf, `data`
        FROM tbl_prescricoes
        WHERE id = :id
    """)
    rows = db5.execute(query_sql, {"id": id_prescricao}).mappings().all()
    return PrescricaoResponse(total=len(rows), resultados=[dict(r) for r in rows])


@router.get("/profissionais", response_model=PrescricaoResponse)
def buscar_profissionais_prescricao(
    termo: str = Query(..., description="Nome ou CPF do profissional"),
    db5: Session = Depends(get_db5),
    current_user: User = Depends(check_permission("view_consulta_prescricao")),
):
    """Busca profissionais na base de prescrições"""
    termo_limpo = termo.replace(".", "").replace("-", "")
    query_sql = text("""
        SELECT SiglaCRO, SiglaCategoria, Inscricao, Nome, TipoInscricao,
            CPF, DataInscricaoCRO, Situacao, DetalheSituacao,
            DataSituacaoAtual, SituacaoFinanceira, IdRegistro
        FROM profissionais
        WHERE Nome LIKE :termo_nome OR CPF = :termo_cpf
        LIMIT 1000
    """)
    rows = db5.execute(query_sql, {
        "termo_nome": f"%{termo}%",
        "termo_cpf": termo_limpo,
    }).mappings().all()
    return PrescricaoResponse(total=len(rows), resultados=[dict(r) for r in rows])
