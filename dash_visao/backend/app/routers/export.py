"""
Export Router - Endpoints para exportacao de dados em Excel e PDF.
Reutiliza as mesmas queries dos routers de consulta.
"""
from fastapi import APIRouter, Depends, Query, HTTPException
from fastapi.responses import StreamingResponse
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from datetime import datetime
import io
import logging

from ..database import get_db3, get_db
from ..models import User
from ..core.auth import get_current_active_user, check_any_permission
from ..lib.excel_export import generate_excel
from ..lib.pdf_export import generate_pdf_table, generate_pdf_detail

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/export", tags=["export"])

# ============================
# Helpers
# ============================

def _excel_response(excel_bytes: bytes, filename: str) -> StreamingResponse:
    """Cria uma StreamingResponse para download de arquivo Excel."""
    return StreamingResponse(
        io.BytesIO(excel_bytes),
        media_type="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


def _pdf_response(pdf_bytes: bytes, filename: str) -> StreamingResponse:
    """Cria uma StreamingResponse para download de arquivo PDF."""
    return StreamingResponse(
        io.BytesIO(pdf_bytes),
        media_type="application/pdf",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


# ============================
# Consulta Integrada - Export Excel
# ============================

PF_COLUMNS = [
    {"key": "nome", "label": "Nome"},
    {"key": "cpf", "label": "CPF"},
    {"key": "cro", "label": "CRO"},
    {"key": "categoria", "label": "Categoria"},
    {"key": "inscricao", "label": "Inscricao"},
    {"key": "tipo_inscricao", "label": "Tipo Inscricao"},
    {"key": "situacao", "label": "Situacao"},
    {"key": "detalhe", "label": "Detalhe"},
    {"key": "situacao_financeira", "label": "Sit. Financeira"},
]

PJ_COLUMNS = [
    {"key": "razao_social", "label": "Razao Social"},
    {"key": "nome_fantasia", "label": "Nome Fantasia"},
    {"key": "cnpj", "label": "CNPJ"},
    {"key": "cro", "label": "CRO"},
    {"key": "categoria", "label": "Categoria"},
    {"key": "inscricao", "label": "Inscricao"},
    {"key": "situacao", "label": "Situacao"},
    {"key": "municipio", "label": "Municipio"},
    {"key": "uf", "label": "UF"},
    {"key": "telefone", "label": "Telefone"},
    {"key": "email", "label": "Email"},
]


@router.get("/consulta-integrada/excel")
def export_consulta_integrada_excel(
    tipo: str = Query("pf", regex="^(pf|pj)$"),
    nome: Optional[str] = None,
    cpf: Optional[str] = None,
    cro: Optional[str] = None,
    uf: Optional[str] = None,
    categoria: Optional[str] = None,
    inscricao: Optional[str] = None,
    current_user: User = Depends(check_any_permission(["view_consulta_integrada", "export_relatorio_auditoria"])),
    db3: Session = Depends(get_db3),
):
    """Exporta resultados da Consulta Integrada para Excel."""
    if tipo == "pf":
        conditions = []
        params = {}
        if nome:
            conditions.append("Nome LIKE :nome")
            params["nome"] = f"%{nome}%"
        if cpf:
            clean = cpf.replace(".", "").replace("-", "")
            conditions.append("CPF LIKE :cpf")
            params["cpf"] = f"%{clean}%"
        if cro:
            conditions.append("CRO LIKE :cro")
            params["cro"] = f"%{cro}%"
        if uf:
            conditions.append("CRO LIKE :uf_prefix")
            params["uf_prefix"] = f"CRO-{uf}%"
        if categoria:
            conditions.append("Categoria = :categoria")
            params["categoria"] = categoria
        if inscricao:
            conditions.append("Inscricao LIKE :inscricao")
            params["inscricao"] = f"%{inscricao}%"

        where = " AND ".join(conditions) if conditions else "1=1"
        sql = f"""
            SELECT TOP 5000
                Nome as nome, CPF as cpf, CRO as cro,
                Categoria as categoria, Inscricao as inscricao,
                Tipo_Inscricao as tipo_inscricao, Situacao as situacao,
                Detalhe as detalhe, Situacao_Financeira as situacao_financeira
            FROM Cons_Visao_Nacional_PF_Dados_do_Profissional
            WHERE {where}
            ORDER BY Nome
        """
        result = db3.execute(text(sql), params)
        data = [dict(row._mapping) for row in result]
        excel_bytes = generate_excel(data, PF_COLUMNS, "Profissionais", "Consulta Integrada - Profissionais")
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        return _excel_response(excel_bytes, f"consulta_integrada_pf_{timestamp}.xlsx")

    else:  # PJ
        conditions = []
        params = {}
        if nome:
            conditions.append("(Razao_Social LIKE :nome OR Nome_Fantasia LIKE :nome)")
            params["nome"] = f"%{nome}%"
        if cpf:  # CNPJ
            clean = cpf.replace(".", "").replace("-", "").replace("/", "")
            conditions.append("CNPJ LIKE :cnpj")
            params["cnpj"] = f"%{clean}%"
        if cro:
            conditions.append("CRO LIKE :cro")
            params["cro"] = f"%{cro}%"
        if uf:
            conditions.append("CRO LIKE :uf_prefix")
            params["uf_prefix"] = f"CRO-{uf}%"

        where = " AND ".join(conditions) if conditions else "1=1"
        sql = f"""
            SELECT TOP 5000
                Razao_Social as razao_social, Nome_Fantasia as nome_fantasia,
                CNPJ as cnpj, CRO as cro, Categoria as categoria,
                Inscricao as inscricao, Situacao as situacao,
                Municipio as municipio, UF as uf,
                Telefone as telefone, Email as email
            FROM Cons_Visao_Nacional_PJ_Dados_da_Empresa
            WHERE {where}
            ORDER BY Razao_Social
        """
        result = db3.execute(text(sql), params)
        data = [dict(row._mapping) for row in result]
        excel_bytes = generate_excel(data, PJ_COLUMNS, "Empresas", "Consulta Integrada - Empresas")
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        return _excel_response(excel_bytes, f"consulta_integrada_pj_{timestamp}.xlsx")


# ============================
# Exportacao generica - recebe dados JSON e exporta Excel
# ============================

from pydantic import BaseModel


class GenericExportRequest(BaseModel):
    data: List[dict]
    columns: Optional[List[dict]] = None
    title: Optional[str] = "Exportacao"
    filename: Optional[str] = "exportacao"


@router.post("/generic/excel")
def export_generic_excel(
    request: GenericExportRequest,
    current_user: User = Depends(get_current_active_user),
):
    """
    Exportacao generica: o frontend envia os dados e colunas, o backend gera o Excel.
    Util para qualquer consulta que ja tem dados carregados no frontend.
    """
    if not request.data:
        raise HTTPException(status_code=400, detail="Nenhum dado para exportar.")

    excel_bytes = generate_excel(
        request.data,
        request.columns,
        "Dados",
        request.title,
    )
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"{request.filename}_{timestamp}.xlsx"
    return _excel_response(excel_bytes, filename)


# ============================
# Exportacao generica PDF
# ============================

class GenericPdfExportRequest(BaseModel):
    data: List[dict]
    columns: Optional[List[dict]] = None
    title: Optional[str] = "Relatorio"
    subtitle: Optional[str] = None
    filename: Optional[str] = "relatorio"
    orientation: Optional[str] = "portrait"


@router.post("/generic/pdf")
def export_generic_pdf(
    request: GenericPdfExportRequest,
    current_user: User = Depends(get_current_active_user),
):
    """
    Exportacao generica PDF: o frontend envia os dados e colunas, o backend gera o PDF.
    """
    if not request.data:
        raise HTTPException(status_code=400, detail="Nenhum dado para exportar.")

    pdf_bytes = generate_pdf_table(
        request.data,
        request.columns,
        request.title or "Relatorio",
        request.subtitle,
        request.orientation or "portrait",
    )
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"{request.filename}_{timestamp}.pdf"
    return _pdf_response(pdf_bytes, filename)


# ============================
# Exportacao de detalhes em PDF (ficha do profissional)
# ============================

class DetailPdfExportRequest(BaseModel):
    sections: List[dict]
    title: Optional[str] = "Detalhes"
    subtitle: Optional[str] = None
    filename: Optional[str] = "detalhes"


@router.post("/detail/pdf")
def export_detail_pdf(
    request: DetailPdfExportRequest,
    current_user: User = Depends(get_current_active_user),
):
    """
    Exporta uma ficha de detalhes em PDF (tipo ficha do profissional / cracha).
    Recebe secoes com titulo e campos [{label, value}].
    """
    pdf_bytes = generate_pdf_detail(
        request.sections,
        request.title or "Detalhes",
        request.subtitle,
    )
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"{request.filename}_{timestamp}.pdf"
    return _pdf_response(pdf_bytes, filename)
