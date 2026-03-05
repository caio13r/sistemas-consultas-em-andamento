"""
Consulta Identidade - Verificação de identidade profissional
Fonte: API Identidade (http://192.168.161.165:8082) + DB1 (carteirinhas_despachadas_cro)
"""
from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db1
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel
import os
import requests
import logging

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/consulta-identidade", tags=["consulta-identidade"])

API_IDENTIDADE_URL = os.getenv("API_IDENTIDADE_URL", "http://192.168.161.165:8082")


class IdentidadeSearchResponse(BaseModel):
    total: int
    resultados: List[dict]


@router.get("/buscar", response_model=IdentidadeSearchResponse)
def buscar_identidade(
    tipo_busca: str = Query(..., description="Tipo: nome, cpf, ar"),
    valor: str = Query(..., description="Valor de busca"),
    page: int = Query(1, ge=1),
    page_size: int = Query(50, le=200),
    current_user: User = Depends(check_permission("view_consulta_identidade")),
):
    """Busca identidade profissional via API externa"""
    try:
        endpoint_map = {
            "nome": f"{API_IDENTIDADE_URL}/api/consulta/identidade/nome",
            "cpf": f"{API_IDENTIDADE_URL}/api/consulta/identidade/cpf",
            "ar": f"{API_IDENTIDADE_URL}/api/consulta/identidade/ar",
        }

        if tipo_busca not in endpoint_map:
            raise HTTPException(status_code=400, detail="tipo_busca deve ser: nome, cpf ou ar")

        url = endpoint_map[tipo_busca]
        response = requests.get(
            url,
            params={
                "searchValue": valor,
                "page_number": page,
                "page_amount": page_size,
            },
            timeout=30,
        )
        response.raise_for_status()
        data = response.json()

        resultados = data if isinstance(data, list) else data.get("data", data.get("resultados", []))
        total = len(resultados) if isinstance(resultados, list) else 0

        return IdentidadeSearchResponse(total=total, resultados=resultados)

    except requests.exceptions.ConnectionError:
        raise HTTPException(status_code=503, detail="API de Identidade indisponível. Verifique a conexão de rede.")
    except requests.exceptions.Timeout:
        raise HTTPException(status_code=504, detail="Timeout ao consultar API de Identidade.")
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Erro ao consultar identidade: {e}")
        raise HTTPException(status_code=500, detail=f"Erro ao consultar identidade: {str(e)}")


@router.get("/carteirinhas-despachadas", response_model=IdentidadeSearchResponse)
def carteirinhas_despachadas(
    cro_uf: Optional[str] = Query(None, description="UF do CRO"),
    cpf: Optional[str] = Query(None),
    inscricao: Optional[str] = Query(None),
    db1: Session = Depends(get_db1),
    current_user: User = Depends(check_permission("view_consulta_identidade")),
):
    """Carteirinhas despachadas nos CROs (DB1)"""
    conditions = []
    params = {}

    if cro_uf:
        conditions.append("cro_uf = :cro_uf")
        params["cro_uf"] = cro_uf.upper()
    if cpf:
        cpf_limpo = cpf.replace(".", "").replace("-", "")
        conditions.append("REPLACE(REPLACE(cpf, '.', ''), '-', '') LIKE :cpf")
        params["cpf"] = f"%{cpf_limpo}%"
    if inscricao:
        conditions.append("inscricao LIKE :inscricao")
        params["inscricao"] = f"%{inscricao}%"

    if not conditions:
        return IdentidadeSearchResponse(total=0, resultados=[])

    where = " AND ".join(conditions)

    query_sql = text(f"""
        SELECT ar, inscricao, data_despacho, cro_uf, cpf, consta_api, usuario_adicionou, motivo
        FROM carteirinhas_despachadas_cro
        WHERE {where}
        ORDER BY data_despacho DESC
        LIMIT 1000
    """)
    rows = db1.execute(query_sql, params).mappings().all()
    resultados = [
        {k: str(v) if v is not None else None for k, v in dict(r).items()}
        for r in rows
    ]

    return IdentidadeSearchResponse(total=len(resultados), resultados=resultados)
