"""
Dados Abertos - Portal de Transparência (API Implanta)
Fonte: API REST https://cfo-br.implanta.net.br/portaltransparencia/servico/api
"""
from fastapi import APIRouter, Depends, Query, HTTPException
from typing import Optional, List
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel
import os
import requests
import logging

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/dados-abertos", tags=["dados-abertos"])

API_BASE_URL = os.getenv("API_DADOS_ABERTOS_URL", "https://cfo-br.implanta.net.br/portaltransparencia/servico/api")
API_KEY = os.getenv("API_KEY", "")
API_PASS = os.getenv("API_PASS", "")


class DadosAbertosResponse(BaseModel):
    total: int
    resultados: List[dict]


def _fetch_api(endpoint: str, params: dict = None) -> list:
    """Faz requisição autenticada à API de Dados Abertos"""
    url = f"{API_BASE_URL}/{endpoint}"
    headers = {}
    if API_KEY and API_PASS:
        headers["Authorization"] = f"Basic {API_KEY}:{API_PASS}"

    try:
        response = requests.get(url, params=params, headers=headers, timeout=30)
        response.raise_for_status()
        data = response.json()
        return data if isinstance(data, list) else data.get("data", data.get("resultados", []))
    except requests.exceptions.ConnectionError:
        raise HTTPException(status_code=503, detail="API de Dados Abertos indisponível.")
    except requests.exceptions.Timeout:
        raise HTTPException(status_code=504, detail="Timeout ao consultar API de Dados Abertos.")
    except Exception as e:
        logger.error(f"Erro ao consultar dados abertos: {e}")
        raise HTTPException(status_code=500, detail=f"Erro ao consultar dados abertos: {str(e)}")


@router.get("/conselheiros", response_model=DadosAbertosResponse)
def listar_conselheiros(
    current_user: User = Depends(check_permission("view_dados_abertos")),
):
    """Lista conselheiros do CFO (Portal Transparência)"""
    resultados = _fetch_api("Conselheiros")
    return DadosAbertosResponse(total=len(resultados), resultados=resultados)


@router.get("/buscar", response_model=DadosAbertosResponse)
def buscar_dados_abertos(
    tipo: str = Query(..., description="Tipo de dados: conselheiros, despesas, receitas, contratos"),
    current_user: User = Depends(check_permission("view_dados_abertos")),
):
    """Busca dados abertos por tipo"""
    endpoints = {
        "conselheiros": "Conselheiros",
        "despesas": "Despesas",
        "receitas": "Receitas",
        "contratos": "Contratos",
        "licitacoes": "Licitacoes",
        "servidores": "Servidores",
        "diarias": "Diarias",
    }

    if tipo not in endpoints:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use: {list(endpoints.keys())}")

    resultados = _fetch_api(endpoints[tipo])
    return DadosAbertosResponse(total=len(resultados), resultados=resultados)
