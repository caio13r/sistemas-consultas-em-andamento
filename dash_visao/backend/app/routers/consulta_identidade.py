"""
Consulta Identidade - Verificação de identidade profissional
Fontes: API Identidade (192.168.161.165:8082), API id.cfo.org.br, DB3, DB1
"""
import re
from datetime import datetime, timedelta
from fastapi import APIRouter, Depends, Query, HTTPException, Body
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db1, get_db3, fix_row_encoding
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel
import os
import requests
import logging

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/consulta-identidade", tags=["consulta-identidade"])

API_IDENTIDADE_URL = os.getenv("API_IDENTIDADE_URL", "http://192.168.161.165:8082")
API_IDENTIDADE_TOKEN = os.getenv("API_IDENTIDADE_TOKEN", "")
API_CFO_ID_URL = os.getenv("API_CFO_ID_URL", "https://id.cfo.org.br")

_DATE_RE = re.compile(r"^\d{4}-\d{2}-\d{2}$")


class IdentidadeResponse(BaseModel):
    total: int
    tipo: str
    nome: str
    resultados: List[dict]


def _fmt_date_api(date_str: str, time: str = "00:00:00") -> str:
    """Converte YYYY-MM-DD para dd/mm/yyyy HH:MM:SS (formato da API)."""
    d = datetime.strptime(date_str, "%Y-%m-%d")
    return f"{d.day:02d}/{d.month:02d}/{d.year} {time}"


def _call_api(path: str, params: dict, list_key: str = "list") -> list:
    """Chama API de identidade e retorna lista de resultados."""
    params["token"] = API_IDENTIDADE_TOKEN
    url = f"{API_IDENTIDADE_URL}{path}"
    try:
        resp = requests.get(url, params=params, timeout=60)
        if resp.status_code not in (200, 201):
            raw = resp.text[:500]
            logger.warning(f"API Identidade {resp.status_code} em {path}: {raw}")
            try:
                body = resp.json()
            except Exception:
                body = None
            if isinstance(body, list) and len(body) > 0:
                return body
            if isinstance(body, dict):
                extracted = body.get(list_key, body.get("data", body.get("resultados", [])))
                if isinstance(extracted, list) and len(extracted) > 0:
                    return extracted
                if body.get("identidade") or body.get("message"):
                    return []
            if resp.status_code == 401:
                raise HTTPException(status_code=403, detail="Token sem permissão para este recurso na API de Identidade. Contacte o administrador da API.")
            raise HTTPException(status_code=502, detail=f"Erro na API de Identidade ({resp.status_code}): {raw[:200]}")
        data = resp.json()
        if isinstance(data, list):
            return data
        return data.get(list_key, data.get("data", data.get("resultados", [])))
    except requests.exceptions.ConnectionError:
        raise HTTPException(status_code=503, detail="API de Identidade indisponível.")
    except requests.exceptions.Timeout:
        raise HTTPException(status_code=504, detail="Timeout ao consultar API de Identidade.")


# ---------------------------------------------------------------------------
# Catálogo de tipos
# ---------------------------------------------------------------------------
IDENT_TYPES = {
    # --- Grupo 1: Busca por identidade (API existente) ---
    "busca-identidade": {
        "nome": "Busca por Identidade",
        "grupo": "Identidade Policarbonato",
        "endpoint": "custom",
    },
    # --- Grupo 2: API Externa (192.168.161.165:8082) ---
    "descartadas": {
        "nome": "Identidades Descartadas",
        "grupo": "Identidade Policarbonato",
        "api_path": "/api/consulta/descartada",
        "params": ["periodo", "uf"],
    },
    "postagem-estatistica": {
        "nome": "Estatísticas de Postagem",
        "grupo": "Identidade Policarbonato",
        "api_path": "/api/consulta/postagem/estatistica/total",
        "list_key": "estatistico",
        "params": ["periodo"],
    },
    "postagem-detalhada": {
        "nome": "Postagens Detalhadas",
        "grupo": "Identidade Policarbonato",
        "api_path": "/api/consulta/postagem/identidade",
        "params": ["periodo", "uf"],
    },
    "perdidas": {
        "nome": "Identidades Perdidas",
        "grupo": "Identidade Policarbonato",
        "api_path": "/api/consulta/perda",
        "params": ["periodo", "uf"],
    },
    "retornadas": {
        "nome": "Identidades Retornadas",
        "grupo": "Identidade Policarbonato",
        "api_path": "/api/consulta/retornada",
        "params": ["periodo", "uf"],
    },
    # --- Grupo 3: DB3 (SQL Server) ---
    "emitidas-consolidado": {
        "nome": "Emitidas (Consolidado)",
        "grupo": "Identidade Policarbonato",
        "view": "CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Unicas_Emitidas",
        "columns": "*",
        "order": "CRO",
    },
    "emitidas-por-cro": {
        "nome": "Emitidas por CRO (Consolidado)",
        "grupo": "Identidade Policarbonato",
        "view": "CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_Consolidado",
        "columns": "*",
        "order": "CRO",
    },
    "emitidas-por-periodo": {
        "nome": "Emitidas por Período",
        "grupo": "Identidade Policarbonato",
        "view": "CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_ANO_MES",
        "columns": "CRO, ANO, MES, SUM(TOTAL_CD) AS TOTAL_CD, SUM(TOTAL_TSB) AS TOTAL_TSB, SUM(TOTAL_ASB) AS TOTAL_ASB, SUM(TOTAL_APD) AS TOTAL_APD, SUM(TOTAL_TPD) AS TOTAL_TPD, SUM(TOT_CRO_ANO_MES) AS TOTAL_ANO",
        "group_by": "CRO, ANO, MES",
        "order": "CRO, ANO DESC, MES DESC",
    },
    "cobranca": {
        "nome": "CFO ID Cobrança",
        "grupo": "CFO ID",
        "view": "CFO_CWS.dbo.cfo_id_cobranca",
        "columns": "CRO, CATEGORIA, INSC, PROFISSIONAL, convert(char, DATA_EMISSAO_ID, 103) AS DATA_EMISSAO_ID, STATUS",
        "order": "CRO, PROFISSIONAL",
    },
    "estatisticas-producao": {
        "nome": "Estatísticas Produção/Postagem/Entrega",
        "grupo": "Identidade Policarbonato",
        "endpoint": "custom_producao",
    },
    "evolucao-emissao": {
        "nome": "Evolução de Emissão",
        "grupo": "Identidade Policarbonato",
        "view": "CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Unicas_Emitidas_Por_CRO_ANO_MES",
        "columns": "*",
        "order": "CRO, ANO DESC, MES DESC",
    },
    # --- Grupo 4: DB1 (MySQL - carteirinhas) ---
    "carteirinhas-despachadas": {
        "nome": "Carteirinhas Despachadas",
        "grupo": "Identidade Policarbonato",
        "endpoint": "custom_carteirinhas",
    },
    "carteirinhas-por-periodo": {
        "nome": "Carteirinhas por Período",
        "grupo": "Identidade Policarbonato",
        "endpoint": "custom_carteirinhas_periodo",
    },
    "carteirinhas-estatisticas": {
        "nome": "Carteirinhas Estatísticas por CRO",
        "grupo": "Identidade Policarbonato",
        "endpoint": "custom_carteirinhas_stats",
    },
}


@router.get("/tipos")
def listar_tipos_identidade(
    current_user: User = Depends(check_permission("view_consulta_identidade")),
):
    """Lista todos os tipos de consulta de identidade, agrupados"""
    return [
        {"codigo": k, "nome": v["nome"], "grupo": v.get("grupo", "")}
        for k, v in IDENT_TYPES.items()
    ]


@router.get("/buscar", response_model=IdentidadeResponse)
def buscar_identidade(
    tipo: str = Query(..., description="Tipo de consulta (ver /tipos)"),
    cro: Optional[str] = Query(None, description="CRO/UF"),
    tipo_busca: Optional[str] = Query(None, description="nome, cpf ou ar"),
    valor: Optional[str] = Query(None, description="Valor de busca"),
    inicio: Optional[str] = Query(None, description="Data início YYYY-MM-DD"),
    termino: Optional[str] = Query(None, description="Data término YYYY-MM-DD"),
    ano: Optional[str] = Query(None),
    mes: Optional[str] = Query(None),
    nome: Optional[str] = Query(None, description="Nome do profissional"),
    page: int = Query(1, ge=1),
    page_size: int = Query(100, le=1000000),
    db3: Session = Depends(get_db3),
    db1: Session = Depends(get_db1),
    current_user: User = Depends(check_permission("view_consulta_identidade")),
):
    """Busca dados de identidade por tipo"""
    if tipo not in IDENT_TYPES:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(IDENT_TYPES.keys())}")

    ident = IDENT_TYPES[tipo]

    # --- Busca por identidade (API existente) ---
    if ident.get("endpoint") == "custom":
        if not tipo_busca or not valor:
            raise HTTPException(status_code=400, detail="Parâmetros 'tipo_busca' e 'valor' são obrigatórios.")
        endpoint_map = {
            "nome": f"{API_IDENTIDADE_URL}/api/consulta/identidade/nome",
            "cpf": f"{API_IDENTIDADE_URL}/api/consulta/identidade/cpf",
            "ar": f"{API_IDENTIDADE_URL}/api/consulta/identidade/ar",
        }
        if tipo_busca not in endpoint_map:
            raise HTTPException(status_code=400, detail="tipo_busca deve ser: nome, cpf ou ar")
        try:
            # Cada endpoint espera um parâmetro de busca diferente
            param_map = {"nome": "name", "cpf": "cpf", "ar": "ar"}
            search_key = param_map.get(tipo_busca, "searchValue")
            api_params: dict = {search_key: valor.strip(), "page_number": page, "page_amount": page_size}
            if API_IDENTIDADE_TOKEN:
                api_params["token"] = API_IDENTIDADE_TOKEN
            resp = requests.get(
                endpoint_map[tipo_busca],
                params=api_params,
                timeout=30,
            )
            if resp.status_code not in (200, 201):
                raw = resp.text[:500]
                logger.warning(f"API Identidade {resp.status_code} para '{valor}': {raw}")
                try:
                    body = resp.json()
                except Exception:
                    body = None
                # Algumas APIs retornam dados mesmo com status != 200
                if isinstance(body, list) and len(body) > 0:
                    return IdentidadeResponse(total=len(body), tipo=tipo, nome=ident["nome"], resultados=body)
                if isinstance(body, dict):
                    extracted = body.get("data", body.get("resultados", body.get("list", [])))
                    if isinstance(extracted, list) and len(extracted) > 0:
                        return IdentidadeResponse(total=len(extracted), tipo=tipo, nome=ident["nome"], resultados=extracted)
                    # "Nenhuma identidade cadastrada" — retorna vazio
                    if body.get("identidade") or body.get("message"):
                        return IdentidadeResponse(total=0, tipo=tipo, nome=ident["nome"], resultados=[])
                # Erro real — repassa
                resp.raise_for_status()
            data = resp.json()
            resultados = data if isinstance(data, list) else data.get("data", data.get("resultados", []))
        except requests.exceptions.ConnectionError:
            raise HTTPException(status_code=503, detail="API de Identidade indisponível.")
        except requests.exceptions.Timeout:
            raise HTTPException(status_code=504, detail="Timeout ao consultar API de Identidade.")
        except requests.exceptions.HTTPError as e:
            logger.error(f"API Identidade erro {resp.status_code}: {resp.text[:300]}")
            raise HTTPException(status_code=502, detail=f"Erro na API de Identidade ({resp.status_code}).")
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    # --- Estatísticas produção/postagem/entrega (query complexa DB3) ---
    if ident.get("endpoint") == "custom_producao":
        params = {}
        cro_where = ""
        if cro:
            cro_where = "WHERE CRO = :cro"
            params["cro"] = cro.upper()

        sql = text(f"""
            SELECT TD.CFO_ID, YY.PRODUZIDAS,
                IIF(TD.CFO_ID = 0, 0, ((YY.PRODUZIDAS*100)/TD.CFO_ID)) as PERC_PROD,
                KK.POSTADAS,
                IIF(TD.CFO_ID = 0, 0, ((KK.POSTADAS*100)/TD.CFO_ID)) as PERC_POST,
                WW.ENTREGUES,
                IIF(TD.CFO_ID = 0, 0, ((WW.ENTREGUES*100)/TD.CFO_ID)) as PERC_ENTR,
                QQ.DEVOLVIDAS,
                IIF(TD.CFO_ID = 0, 0, ((QQ.DEVOLVIDAS*100)/TD.CFO_ID)) as PERC_DEV,
                (KK.POSTADAS - WW.ENTREGUES - QQ.DEVOLVIDAS) AS EM_TRANSITO,
                IIF(TD.CFO_ID = 0, 0, (((KK.POSTADAS - WW.ENTREGUES - QQ.DEVOLVIDAS)*100)/TD.CFO_ID)) as PERC_TRAM
            FROM (
                SELECT COUNT(*) AS CFO_ID FROM (
                    SELECT DISTINCT CRO, CATEGORIA, INSC, PROFISSIONAL, MIN(data_emissao_id) AS DT_EMISSAO_CFOID
                    FROM CFO_CWS.dbo.cfo_id_cobranca
                    {cro_where}
                    GROUP BY CRO, CATEGORIA, INSC, PROFISSIONAL
                ) AS tt
            ) AS TD,
            (SELECT COUNT(*) AS PRODUZIDAS FROM CFO_CWS.dbo.Identidade_coleta {cro_where}) AS YY,
            (SELECT COUNT(*) AS POSTADAS FROM CFO_CWS.dbo.Carga_postagem_ECT WHERE EVENTO = 'Postado' AND AR IN (SELECT AR FROM CFO_CWS.dbo.Identidade_coleta {cro_where})) AS KK,
            (SELECT COUNT(*) AS ENTREGUES FROM CFO_CWS.dbo.Carga_postagem_ECT WHERE EVENTO = 'Entregue' AND AR IN (SELECT AR FROM CFO_CWS.dbo.Identidade_coleta {cro_where})) AS WW,
            (SELECT COUNT(*) AS DEVOLVIDAS FROM CFO_CWS.dbo.Carga_postagem_ECT WHERE EVENTO = 'Distribuído ao remetente' AND AR IN (SELECT AR FROM CFO_CWS.dbo.Identidade_coleta {cro_where})) AS QQ
        """)
        rows = db3.execute(sql, params).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    # --- Carteirinhas despachadas (DB1) ---
    if ident.get("endpoint") == "custom_carteirinhas":
        conditions = []
        params = {}
        if cro:
            conditions.append("cro_uf = :cro_uf")
            params["cro_uf"] = cro.upper()
        if valor:
            cpf_limpo = valor.replace(".", "").replace("-", "")
            conditions.append("REPLACE(REPLACE(cpf, '.', ''), '-', '') LIKE :cpf")
            params["cpf"] = f"%{cpf_limpo}%"
        if not conditions:
            return IdentidadeResponse(total=0, tipo=tipo, nome=ident["nome"], resultados=[])
        where = " AND ".join(conditions)
        sql = text(f"""
            SELECT ar, inscricao, data_despacho, cro_uf, cpf, consta_api, usuario_adicionou, motivo
            FROM carteirinhas_despachadas_cro WHERE {where}
            ORDER BY data_despacho DESC LIMIT 1000
        """)
        rows = db1.execute(sql, params).mappings().all()
        resultados = [{k: str(v) if v is not None else None for k, v in dict(r).items()} for r in rows]
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    # --- Carteirinhas por período (DB1) ---
    if ident.get("endpoint") == "custom_carteirinhas_periodo":
        if not inicio or not termino:
            raise HTTPException(status_code=400, detail="Parâmetros 'início' e 'término' são obrigatórios.")
        conditions = ["data_despacho BETWEEN :inicio AND :termino"]
        params = {"inicio": inicio, "termino": termino}
        if cro:
            conditions.append("cro_uf = :cro_uf")
            params["cro_uf"] = cro.upper()
        where = " AND ".join(conditions)
        sql = text(f"""
            SELECT ar, inscricao, data_despacho, cro_uf, cpf, consta_api, usuario_adicionou, motivo
            FROM carteirinhas_despachadas_cro WHERE {where}
            ORDER BY data_despacho DESC
        """)
        rows = db1.execute(sql, params).mappings().all()
        resultados = [{k: str(v) if v is not None else None for k, v in dict(r).items()} for r in rows]
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    # --- Carteirinhas estatísticas (DB1) ---
    if ident.get("endpoint") == "custom_carteirinhas_stats":
        sql = text("""
            SELECT cro_uf, COUNT(*) AS total
            FROM carteirinhas_despachadas_cro
            GROUP BY cro_uf
            ORDER BY cro_uf
        """)
        rows = db1.execute(sql).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    # --- Tipos com API externa (192.168.161.165:8082) ---
    if "api_path" in ident:
        api_params = {"page_number": page, "page_amount": page_size}
        if "periodo" in ident.get("params", []):
            if inicio and termino:
                api_params["start_date"] = _fmt_date_api(inicio)
                api_params["end_date"] = _fmt_date_api(termino, "23:59:59")
            else:
                # Default: último mês
                end = datetime.now()
                start = end - timedelta(days=30)
                api_params["start_date"] = _fmt_date_api(start.strftime("%Y-%m-%d"))
                api_params["end_date"] = _fmt_date_api(end.strftime("%Y-%m-%d"), "23:59:59")
        if "uf" in ident.get("params", []) and cro:
            api_params["uf"] = cro.upper()

        list_key = ident.get("list_key", "list")
        resultados = _call_api(ident["api_path"], api_params, list_key)
        if not isinstance(resultados, list):
            resultados = []
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    # --- Tipos com view DB3 ---
    if "view" in ident:
        conditions = []
        params = {}
        if cro:
            conditions.append("CRO = :cro")
            params["cro"] = cro.upper()
        if ano:
            conditions.append("ANO = :ano")
            params["ano"] = ano
        if mes:
            conditions.append("MES = :mes")
            params["mes"] = mes
        if nome and len(nome.strip()) >= 3:
            conditions.append("PROFISSIONAL COLLATE Latin1_general_CI_AI LIKE :nome")
            params["nome"] = f"%{nome}%"

        where = " AND ".join(conditions) if conditions else "1=1"
        group_by = ident.get("group_by", "")
        group_clause = f"GROUP BY {group_by}" if group_by else ""

        sql = text(f"""
            SELECT {ident['columns']}
            FROM {ident['view']}
            WHERE {where}
            {group_clause}
            ORDER BY {ident['order']}
        """)
        rows = db3.execute(sql, params).mappings().all()
        resultados = [fix_row_encoding(dict(r)) for r in rows]
        return IdentidadeResponse(
            total=len(resultados), tipo=tipo, nome=ident["nome"], resultados=resultados,
        )

    raise HTTPException(status_code=500, detail="Tipo não implementado.")


# --- Endpoint POST: Registrar despacho de carteirinha ---
@router.post("/carteirinhas-registrar")
def registrar_carteirinha(
    ar: str = Body(...),
    inscricao: str = Body(...),
    cro_uf: str = Body(...),
    cpf: str = Body(...),
    motivo: Optional[str] = Body(""),
    db1: Session = Depends(get_db1),
    current_user: User = Depends(check_permission("manage_consulta_identidade")),
):
    """Registra despacho de carteirinha (DB1)"""
    sql = text("""
        INSERT INTO carteirinhas_despachadas_cro
        (ar, inscricao, data_despacho, cro_uf, cpf, consta_api, usuario_adicionou, motivo)
        VALUES (:ar, :inscricao, NOW(), :cro_uf, :cpf, 0, :usuario, :motivo)
    """)
    db1.execute(sql, {
        "ar": ar, "inscricao": inscricao, "cro_uf": cro_uf.upper(),
        "cpf": cpf, "usuario": current_user.email, "motivo": motivo,
    })
    db1.commit()
    return {"message": "Carteirinha registrada com sucesso."}
