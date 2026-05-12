"""
Consulta Eleições Regionais - Dados eleitorais dos conselhos
Fonte: DB3 (SQL Server - CFO_CWS)
Views: Cons_Eleicoes_Lista_Completa, Cons_Eleicoes_Lista_Suplementar
"""
from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3, fix_row_encoding
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/eleicoes-regionais", tags=["eleicoes-regionais"])


class EleicaoResponse(BaseModel):
    total: int
    tipo: str
    nome: str
    resultados: List[dict]


ELEICAO_TYPES = {
    "lista-completa": {
        "nome": "Lista Completa de Eleitores",
    },
    "estatisticas": {
        "nome": "Estatísticas Eleitorais",
    },
    "cpf-duplicados": {
        "nome": "CPFs Duplicados (Multi-CRO)",
    },
    "delegados": {
        "nome": "Delegados Eleitores (Suplementar)",
    },
}


@router.get("/tipos")
def listar_tipos_eleicao(
    current_user: User = Depends(check_permission("view_eleicoes_regionais")),
):
    """Lista tipos de consulta eleitoral"""
    return [{"codigo": k, "nome": v["nome"]} for k, v in ELEICAO_TYPES.items()]


@router.get("/buscar", response_model=EleicaoResponse)
def buscar_eleicoes(
    tipo: str = Query("lista-completa", description="Tipo de consulta (ver /tipos)"),
    cro: Optional[str] = Query(None, description="CRO (ex: SP, RJ)"),
    nome: Optional[str] = Query(None),
    inscricao: Optional[str] = Query(None),
    cpf: Optional[str] = Query(None),
    email: Optional[str] = Query(None),
    celular: Optional[str] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_eleicoes_regionais")),
):
    """Busca dados eleitorais por tipo"""
    if tipo not in ELEICAO_TYPES:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(ELEICAO_TYPES.keys())}")

    tipo_info = ELEICAO_TYPES[tipo]

    # --- Lista completa de eleitores ---
    if tipo == "lista-completa":
        conditions = []
        params = {}
        if cro:
            conditions.append("ele.CRO = :cro")
            params["cro"] = cro.upper()
        if nome:
            conditions.append("ele.NOME_COMPLETO LIKE :nome")
            params["nome"] = f"%{nome}%"
        if inscricao:
            conditions.append("ele.INSCRICAO LIKE :inscricao")
            params["inscricao"] = f"%{inscricao}%"
        if cpf:
            cpf_limpo = cpf.replace(".", "").replace("-", "")
            conditions.append("REPLACE(REPLACE(ele.CPF, '.', ''), '-', '') LIKE :cpf")
            params["cpf"] = f"%{cpf_limpo}%"
        if email:
            conditions.append("ele.EMAIL LIKE :email")
            params["email"] = f"%{email}%"
        if celular:
            conditions.append("ele.CELULAR_ATUALIZADO LIKE :celular")
            params["celular"] = f"%{celular}%"

        if not conditions:
            return EleicaoResponse(total=0, tipo=tipo, nome=tipo_info["nome"], resultados=[])

        where = " AND ".join(conditions)
        sql = text(f"""
            SELECT TOP 1000
                ele.NOME_COMPLETO, ele.CPF, ele.CRO, ele.CATEGORIA, ele.INSCRICAO,
                ele.TIPO_INSCRICAO, ele.SITUACAO, ele.DETALHE_SITUACAO, ele.ADIMPLENCIA,
                COALESCE(v.ELEITOR, ele.VOTANTE) AS VOTANTE,
                COALESCE(v.DEVEDOR, ele.DEVEDOR) AS DEVEDOR,
                ele.MOTIVO_NAO_VOTANTE, ele.EMAIL, ele.CELULAR_ATUALIZADO,
                ele.DATA_NASCIMENTO, ele.DATA_INSCRICAO_CRO, ele.DATA_REGISTRO_CFO
            FROM (
                SELECT NOME_COMPLETO, CPF, CRO, CATEGORIA, INSCRICAO,
                    TIPO_INSCRICAO, SITUACAO, DETALHE_SITUACAO, ADIMPLENCIA,
                    ELEITOR AS VOTANTE, DEVEDOR, MOTIVOS_NAO_ELEITOR AS MOTIVO_NAO_VOTANTE,
                    EMAIL, CELULAR_ATUALIZADO, DATA_NASCIMENTO, DATA_INSCRICAO_CRO, DATA_REGISTRO_CFO
                FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
            ) AS ele
            LEFT JOIN CFO_CWS.dbo.Cons_Eleicoes_Lista_Suplementar v
                ON v.CPF = ele.CPF AND v.CRO = ele.CRO
            WHERE {where}
            ORDER BY ele.NOME_COMPLETO, ele.CRO, ele.INSCRICAO
        """)
        rows = db3.execute(sql, params).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return EleicaoResponse(
            total=len(resultados), tipo=tipo, nome=tipo_info["nome"], resultados=resultados,
        )

    # --- Estatísticas eleitorais ---
    if tipo == "estatisticas":
        resultados = []

        # Por CRO
        sql_cro = text("""
            SELECT CRO, COUNT(*) as total_geral,
                SUM(CASE WHEN UPPER(LTRIM(RTRIM(SITUACAO))) IN ('ATIVO','REGULAR') THEN 1 ELSE 0 END) as total_ativos,
                SUM(CASE WHEN UPPER(LTRIM(RTRIM(SITUACAO))) IN ('DESATIVADO','CANCELADO','SUSPENSO','BAIXADO') THEN 1 ELSE 0 END) as total_desativados,
                SUM(CASE WHEN UPPER(LTRIM(RTRIM(ELEITOR))) = 'SIM' THEN 1 ELSE 0 END) as total_votantes,
                SUM(CASE WHEN UPPER(LTRIM(RTRIM(ELEITOR))) = 'SIM' AND UPPER(LTRIM(RTRIM(DEVEDOR))) = 'SIM' THEN 1 ELSE 0 END) as votantes_devedores,
                SUM(CASE WHEN UPPER(LTRIM(RTRIM(ELEITOR))) = 'SIM' AND UPPER(LTRIM(RTRIM(DEVEDOR))) IN ('NAO','NÃO') THEN 1 ELSE 0 END) as votantes_adimplentes
            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
            GROUP BY CRO ORDER BY CRO
        """)
        rows = db3.execute(sql_cro).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]

        return EleicaoResponse(
            total=len(resultados), tipo=tipo, nome=tipo_info["nome"], resultados=resultados,
        )

    # --- CPFs duplicados (multi-CRO) ---
    if tipo == "cpf-duplicados":
        conditions = [
            "e.CPF IS NOT NULL",
            "e.CPF <> ''",
            "e.CPF <> '111.111.111-11'",
            "e.SITUACAO = 'ATIVO'",
            "e.TIPO_INSCRICAO NOT IN ('SECUNDÁRIA','SECUNDÁRIA PROVISÓRIA','SECUNDÁRIA DE PROVISÓRIA')",
        ]
        params = {}
        if cro:
            conditions.append("e.CRO = :cro")
            params["cro"] = cro.upper()

        where = " AND ".join(conditions)
        sql = text(f"""
            SELECT e.CPF, e.CRO, e.CATEGORIA, e.INSCRICAO, e.NOME_COMPLETO,
                e.TIPO_INSCRICAO, e.SITUACAO, e.DETALHE_SITUACAO,
                e.ELEITOR AS VOTANTE, e.DEVEDOR
            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa AS e
            WHERE {where}
                AND e.CPF IN (
                    SELECT CPF FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Completa
                    WHERE CPF IS NOT NULL AND CPF <> '' AND CPF <> '111.111.111-11'
                        AND SITUACAO = 'ATIVO'
                        AND TIPO_INSCRICAO NOT IN ('SECUNDÁRIA','SECUNDÁRIA PROVISÓRIA','SECUNDÁRIA DE PROVISÓRIA')
                    GROUP BY CPF HAVING COUNT(DISTINCT CRO) > 1
                )
            ORDER BY e.CPF, e.CRO
        """)
        rows = db3.execute(sql, params).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return EleicaoResponse(
            total=len(resultados), tipo=tipo, nome=tipo_info["nome"], resultados=resultados,
        )

    # --- Delegados eleitores (lista suplementar) ---
    if tipo == "delegados":
        conditions = ["e.CPF IS NOT NULL", "e.CPF <> ''", "e.CPF <> '111.111.111-11'"]
        params = {}
        if cro:
            conditions.append("e.CRO = :cro")
            params["cro"] = cro.upper()

        where = " AND ".join(conditions)
        sql = text(f"""
            SELECT e.DATA_DIRETORIA_CRO, e.CRO, e.CATEGORIA, e.INSCRICAO,
                e.NOME_COMPLETO, e.CPF, e.ELEITOR AS VOTANTE, e.DEVEDOR,
                e.EMAIL, e.TIPO_EMAIL_UTILIZADO, e.OUTROS_EMAILS,
                e.CELULAR_ATUALIZADO, e.OUTROS_TELEFONES,
                e.DATA_GERACAO, e.HORA_GERACAO
            FROM CFO_CWS.dbo.Cons_Eleicoes_Lista_Suplementar AS e
            WHERE {where}
            ORDER BY e.CRO, e.NOME_COMPLETO, e.INSCRICAO
        """)
        rows = db3.execute(sql, params).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return EleicaoResponse(
            total=len(resultados), tipo=tipo, nome=tipo_info["nome"], resultados=resultados,
        )

    raise HTTPException(status_code=500, detail="Tipo não implementado.")
