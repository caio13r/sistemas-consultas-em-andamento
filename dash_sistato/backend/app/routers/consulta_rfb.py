"""
Consulta RFB - Consulta CPF/CNPJ na Receita Federal
Fonte: DB3 (verificação base CFO), DB1 (log de auditoria)
Verifica se CPF/CNPJ existe na base CFO antes de consultar a RFB.
"""
from fastapi import APIRouter, Depends, Query, HTTPException, Request
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db1, get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel
from datetime import datetime
import logging
import time

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/consulta-rfb", tags=["consulta-rfb"])


class RFBConsultaResponse(BaseModel):
    tipo_consulta: str
    documento: str
    existe_base_cfo: bool
    inscricao_cfo: Optional[str] = None
    nome_cfo: Optional[str] = None
    cro_cfo: Optional[str] = None
    situacao_cfo: Optional[str] = None


class RFBHistoricoResponse(BaseModel):
    total: int
    resultados: List[dict]


def _verificar_cpf_base_cfo(db3: Session, cpf: str) -> dict:
    """Verifica se CPF existe na base do CFO (SQL Server)"""
    query = text("""
        SELECT TOP 1
            CPF, NomeSocial AS Nome, NULL AS Inscricao, NULL AS CRO, NULL AS Situacao
        FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PF_Dados_Pessoais] pf
        WHERE REPLACE(REPLACE(REPLACE(pf.CPF, '.', ''), '-', ''), '/', '') = :cpf
    """)
    row = db3.execute(query, {"cpf": cpf}).mappings().first()
    if row:
        return {"existe": True, **dict(row)}
    return {"existe": False}


def _verificar_cnpj_base_cfo(db3: Session, cnpj: str) -> dict:
    """Verifica se CNPJ existe na base do CFO (SQL Server)"""
    query = text("""
        SELECT TOP 1
            CNPJ, RazaoSocial AS Nome, Inscricao, Cro AS CRO, Situacao
        FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Dados_da_Empresa] pj
        WHERE REPLACE(REPLACE(REPLACE(pj.CNPJ, '.', ''), '-', ''), '/', '') = :cnpj
    """)
    row = db3.execute(query, {"cnpj": cnpj}).mappings().first()
    if row:
        return {"existe": True, **dict(row)}
    return {"existe": False}


def _registrar_auditoria(db1: Session, user: User, dados: dict):
    """Registra a consulta na tabela de auditoria (MySQL)"""
    insert_sql = text("""
        INSERT INTO tbl_rfb_auditoria (
            usuario_id, usuario_nome, usuario_email, usuario_grupo,
            tipo_consulta, documento_consultado,
            existe_base_cfo, inscricao_cfo, nome_cfo, cro_cfo, situacao_cfo,
            sucesso, mensagem_erro, tempo_resposta_ms,
            ip_origem, data_hora
        ) VALUES (
            :usuario_id, :usuario_nome, :usuario_email, :usuario_grupo,
            :tipo_consulta, :documento_consultado,
            :existe_base_cfo, :inscricao_cfo, :nome_cfo, :cro_cfo, :situacao_cfo,
            :sucesso, :mensagem_erro, :tempo_resposta_ms,
            :ip_origem, :data_hora
        )
    """)
    db1.execute(insert_sql, dados)
    db1.commit()


@router.get("/consultar", response_model=RFBConsultaResponse)
def consultar_rfb(
    documento: str = Query(..., description="CPF ou CNPJ para consulta"),
    request: Request = None,
    db1: Session = Depends(get_db1),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_rfb")),
):
    """Consulta CPF ou CNPJ - verifica na base CFO e registra auditoria"""
    import re
    doc_limpo = re.sub(r"[^0-9]", "", documento)
    inicio = time.time()

    if len(doc_limpo) == 11:
        tipo = "CPF"
        resultado = _verificar_cpf_base_cfo(db3, doc_limpo)
    elif len(doc_limpo) == 14:
        tipo = "CNPJ"
        resultado = _verificar_cnpj_base_cfo(db3, doc_limpo)
    else:
        raise HTTPException(status_code=400, detail="Documento inválido. Informe CPF (11 dígitos) ou CNPJ (14 dígitos).")

    tempo_ms = int((time.time() - inicio) * 1000)

    # Registrar auditoria no DB1
    try:
        _registrar_auditoria(db1, current_user, {
            "usuario_id": current_user.id,
            "usuario_nome": current_user.full_name or current_user.username,
            "usuario_email": current_user.email,
            "usuario_grupo": "",
            "tipo_consulta": tipo,
            "documento_consultado": doc_limpo,
            "existe_base_cfo": resultado.get("existe", False),
            "inscricao_cfo": resultado.get("Inscricao"),
            "nome_cfo": resultado.get("Nome"),
            "cro_cfo": resultado.get("CRO"),
            "situacao_cfo": resultado.get("Situacao"),
            "sucesso": True,
            "mensagem_erro": None,
            "tempo_resposta_ms": tempo_ms,
            "ip_origem": request.client.host if request and request.client else None,
            "data_hora": datetime.now(),
        })
    except Exception as e:
        logger.warning(f"Erro ao registrar auditoria RFB: {e}")

    return RFBConsultaResponse(
        tipo_consulta=tipo,
        documento=documento,
        existe_base_cfo=resultado.get("existe", False),
        inscricao_cfo=resultado.get("Inscricao"),
        nome_cfo=resultado.get("Nome"),
        cro_cfo=resultado.get("CRO"),
        situacao_cfo=resultado.get("Situacao"),
    )


@router.get("/historico", response_model=RFBHistoricoResponse)
def historico_consultas(
    limit: int = Query(50, le=200),
    db1: Session = Depends(get_db1),
    current_user: User = Depends(check_permission("view_consulta_rfb")),
):
    """Histórico de consultas RFB do usuário"""
    query_sql = text("""
        SELECT
            tipo_consulta, documento_consultado, existe_base_cfo,
            inscricao_cfo, nome_cfo, cro_cfo, situacao_cfo,
            sucesso, tempo_resposta_ms, data_hora
        FROM tbl_rfb_auditoria
        WHERE usuario_id = :user_id
        ORDER BY data_hora DESC
        LIMIT :limit
    """)
    rows = db1.execute(query_sql, {"user_id": current_user.id, "limit": limit}).mappings().all()
    resultados = [
        {k: str(v) if v is not None else None for k, v in dict(r).items()}
        for r in rows
    ]

    return RFBHistoricoResponse(total=len(resultados), resultados=resultados)
