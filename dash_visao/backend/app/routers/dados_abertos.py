"""
Dados Abertos - Portal de Transparência (API Implanta)
Fonte: API REST https://cfo-br.implanta.net.br/portaltransparencia/servico/api

Endpoints reais da API (mapeados do sistema-consultas PHP legado):
  Conselheiros, AtasColegiados, BalancoFinanceiro, Balancete,
  BalancoOrcamentario, Contratos, ContratosAditivos, Convenios,
  Licitacoes, RelacaoAquisicoes, PassagensAereas, DiariasDeslocamentos,
  BalancoPatrimonial, ExecucaoFinanceira, PlanoDeContas, EstatisticaAcessoModulo
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


# Mapeamento dos tipos para os endpoints reais da API Implanta
# e o tipo de parâmetro de data que cada um exige
ENDPOINTS = {
    "conselheiros":         {"path": "Conselheiros",         "date_param": None},
    "atas_colegiados":      {"path": "AtasColegiados",       "date_param": "data"},
    "balanco_financeiro":   {"path": "BalancoFinanceiro",    "date_param": "referencia"},
    "balancete":            {"path": "Balancete",            "date_param": "referencia"},
    "balanco_orcamentario": {"path": "BalancoOrcamentario",  "date_param": "referencia"},
    "contratos":            {"path": "Contratos",            "date_param": "vigencia"},
    "contratos_aditivos":   {"path": "ContratosAditivos",    "date_param": "vigencia"},
    "convenios":            {"path": "Convenios",            "date_param": "referencia"},
    "licitacoes":           {"path": "Licitacoes",           "date_param": "referencia"},
    "aquisicoes":           {"path": "RelacaoAquisicoes",    "date_param": "referencia"},
    "passagens_aereas":     {"path": "PassagensAereas",      "date_param": "referencia"},
    "diarias":              {"path": "DiariasDeslocamentos", "date_param": "referencia"},
    "balanco_patrimonial":  {"path": "BalancoPatrimonial",   "date_param": "referencia"},
    "execucao_financeira":  {"path": "ExecucaoFinanceira",   "date_param": "referencia"},
    "plano_contas":         {"path": "PlanoDeContas",        "date_param": "exercicio"},
    "estatistica_acesso":   {"path": "EstatisticaAcessoModulo", "date_param": "referencia"},
}


def _fetch_api(endpoint: str, params: dict = None) -> list:
    """Faz requisição autenticada à API de Dados Abertos (headers Chave/Senha)"""
    if not API_KEY or not API_PASS:
        raise HTTPException(
            status_code=503,
            detail="Credenciais da API de Dados Abertos não configuradas (API_KEY/API_PASS). Solicite as credenciais ao fornecedor Implanta."
        )

    url = f"{API_BASE_URL}/{endpoint}"
    if params:
        qs = "&".join(f"{k}={v}" for k, v in params.items())
        url = f"{url}?{qs}"
    headers = {
        "Accept": "application/json, text/json",
        "Chave": API_KEY,
        "Senha": API_PASS,
    }

    try:
        logger.info("DADOS_ABERTOS REQUEST: %s", url)
        logger.info("DADOS_ABERTOS HEADERS: %s", {k: v[:8] + '...' if k in ('Chave', 'Senha') else v for k, v in headers.items()})
        response = requests.get(url, headers=headers, timeout=30)
        logger.info("DADOS_ABERTOS RESPONSE: status=%s body=%s", response.status_code, response.text[:500])
        if response.status_code == 404:
            return []
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


@router.get("/tipos")
def listar_tipos(
    current_user: User = Depends(check_permission("view_dados_abertos")),
):
    """Lista os tipos de dados disponíveis e seus parâmetros"""
    return {k: {"date_param": v["date_param"]} for k, v in ENDPOINTS.items()}


@router.get("/conselheiros", response_model=DadosAbertosResponse)
def listar_conselheiros(
    current_user: User = Depends(check_permission("view_dados_abertos")),
):
    """Lista conselheiros do CFO (Portal Transparência)"""
    resultados = _fetch_api("Conselheiros")
    return DadosAbertosResponse(total=len(resultados), resultados=resultados)


@router.get("/buscar", response_model=DadosAbertosResponse)
def buscar_dados_abertos(
    tipo: str = Query(..., description="Tipo de dados (ver /tipos para lista completa)"),
    data_inicio: Optional[str] = Query(None, description="Data início (formato MM/YYYY)"),
    data_termino: Optional[str] = Query(None, description="Data término (formato MM/YYYY)"),
    exercicio: Optional[str] = Query(None, description="Exercício/ano (formato YYYY, apenas para plano_contas)"),
    current_user: User = Depends(check_permission("view_dados_abertos")),
):
    """Busca dados abertos por tipo, com parâmetros de data quando necessário"""

    if tipo not in ENDPOINTS:
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Tipos disponíveis: {list(ENDPOINTS.keys())}")

    ep = ENDPOINTS[tipo]
    params = {}
    date_param = ep["date_param"]

    if date_param == "referencia":
        if not data_inicio or not data_termino:
            raise HTTPException(status_code=400, detail="Informe data_início e data_término (formato MM/YYYY)")
        params["referenciaInicio"] = data_inicio
        params["referenciaTermino"] = data_termino

    elif date_param == "vigencia":
        if not data_inicio or not data_termino:
            raise HTTPException(status_code=400, detail="Informe data_início e data_término (formato MM/YYYY)")
        params["vigenciaInicio"] = data_inicio
        params["vigenciaTermino"] = data_termino

    elif date_param == "data":
        if not data_inicio or not data_termino:
            raise HTTPException(status_code=400, detail="Informe data_início e data_término (formato MM/YYYY)")
        params["dataInicio"] = data_inicio
        params["dataTermino"] = data_termino

    elif date_param == "exercicio":
        if not exercicio:
            raise HTTPException(status_code=400, detail="Informe o exercício/ano (formato YYYY)")
        params["exercicio"] = exercicio

    resultados = _fetch_api(ep["path"], params)
    return DadosAbertosResponse(total=len(resultados), resultados=resultados)
