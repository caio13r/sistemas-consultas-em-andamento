import os
import re
import uuid
from datetime import datetime
from typing import Optional, List

from fastapi import APIRouter, Depends, HTTPException, UploadFile, File, Form, Query
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session
from pydantic import BaseModel

from app.database import get_db
from app.models.user import User
from app.models.documento import Documento
from app.core.auth import get_current_active_user, check_permission

router = APIRouter(prefix="/documentos", tags=["documentos"])

UPLOAD_DIR = "/app/uploads/documentos"
MAX_FILE_SIZE = 10 * 1024 * 1024  # 10MB
ALLOWED_EXTENSIONS = {
    "application/pdf", "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "image/png", "image/jpeg", "image/jpg",
}
ALLOWED_EXT_NAMES = {".pdf", ".doc", ".docx", ".xls", ".xlsx", ".png", ".jpg", ".jpeg"}

CATEGORIAS = ["oficio", "portaria", "ata", "relatorio", "outro"]


def sanitize_filename(filename: str) -> str:
    name, ext = os.path.splitext(filename)
    name = re.sub(r'[^\w\-.]', '_', name)
    return f"{name}_{uuid.uuid4().hex[:8]}{ext.lower()}"


# --- Pydantic schemas ---

class DocumentoOut(BaseModel):
    id: int
    titulo: str
    descricao: Optional[str]
    categoria: str
    filename: str
    file_type: str
    file_size: int
    status: str
    uploaded_by: int
    uploader_name: Optional[str] = None
    validated_by: Optional[int]
    validator_name: Optional[str] = None
    validation_date: Optional[datetime]
    validation_notes: Optional[str]
    cro: Optional[str]
    created_at: Optional[datetime]
    updated_at: Optional[datetime]

    class Config:
        from_attributes = True


class ValidacaoRequest(BaseModel):
    status: str  # aprovado ou rejeitado
    notes: Optional[str] = None


def _doc_to_out(doc: Documento) -> dict:
    return {
        "id": doc.id,
        "titulo": doc.titulo,
        "descricao": doc.descricao,
        "categoria": doc.categoria,
        "filename": doc.filename,
        "file_type": doc.file_type,
        "file_size": doc.file_size,
        "status": doc.status,
        "uploaded_by": doc.uploaded_by,
        "uploader_name": doc.uploader.full_name if doc.uploader else None,
        "validated_by": doc.validated_by,
        "validator_name": doc.validator.full_name if doc.validator else None,
        "validation_date": doc.validation_date,
        "validation_notes": doc.validation_notes,
        "cro": doc.cro,
        "created_at": doc.created_at,
        "updated_at": doc.updated_at,
    }


@router.get("/categorias")
def listar_categorias(
    current_user: User = Depends(check_permission("view_documentos")),
):
    return CATEGORIAS


@router.post("/upload", response_model=DocumentoOut)
async def upload_documento(
    file: UploadFile = File(...),
    titulo: str = Form(...),
    descricao: str = Form(None),
    categoria: str = Form(...),
    cro: str = Form(None),
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("view_documentos")),
):
    # Validar categoria
    if categoria not in CATEGORIAS:
        raise HTTPException(400, f"Categoria inválida. Opções: {', '.join(CATEGORIAS)}")

    # Validar extensão
    ext = os.path.splitext(file.filename or "")[1].lower()
    if ext not in ALLOWED_EXT_NAMES:
        raise HTTPException(400, f"Tipo de arquivo não permitido. Permitidos: {', '.join(ALLOWED_EXT_NAMES)}")

    # Ler conteúdo e validar tamanho
    content = await file.read()
    if len(content) > MAX_FILE_SIZE:
        raise HTTPException(400, "Arquivo excede o tamanho máximo de 10MB")

    # Validar content type
    if file.content_type and file.content_type not in ALLOWED_EXTENSIONS:
        raise HTTPException(400, "Tipo MIME não permitido")

    # Salvar arquivo
    safe_name = sanitize_filename(file.filename or "documento")
    os.makedirs(UPLOAD_DIR, exist_ok=True)
    file_path = os.path.join(UPLOAD_DIR, safe_name)

    with open(file_path, "wb") as f:
        f.write(content)

    # Criar registro no banco
    documento = Documento(
        titulo=titulo,
        descricao=descricao,
        categoria=categoria,
        filename=file.filename or safe_name,
        file_path=file_path,
        file_type=file.content_type or "application/octet-stream",
        file_size=len(content),
        status="pendente",
        uploaded_by=current_user.id,
        cro=cro,
    )
    db.add(documento)
    db.commit()
    db.refresh(documento)

    return _doc_to_out(documento)


@router.get("/pendentes", response_model=List[DocumentoOut])
def listar_pendentes(
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_documentos")),
):
    docs = (
        db.query(Documento)
        .filter(Documento.status == "pendente")
        .order_by(Documento.created_at.asc())
        .offset(skip)
        .limit(limit)
        .all()
    )
    return [_doc_to_out(d) for d in docs]


@router.get("/", response_model=List[DocumentoOut])
def listar_documentos(
    categoria: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    cro: Optional[str] = Query(None),
    search: Optional[str] = Query(None),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("view_documentos")),
):
    query = db.query(Documento)

    if categoria:
        query = query.filter(Documento.categoria == categoria)
    if status:
        query = query.filter(Documento.status == status)
    if cro:
        query = query.filter(Documento.cro == cro)
    if search:
        query = query.filter(Documento.titulo.ilike(f"%{search}%"))

    docs = query.order_by(Documento.created_at.desc()).offset(skip).limit(limit).all()
    return [_doc_to_out(d) for d in docs]


@router.get("/{documento_id}", response_model=DocumentoOut)
def obter_documento(
    documento_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("view_documentos")),
):
    doc = db.query(Documento).filter(Documento.id == documento_id).first()
    if not doc:
        raise HTTPException(404, "Documento não encontrado")
    return _doc_to_out(doc)


@router.get("/{documento_id}/download")
def download_documento(
    documento_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("view_documentos")),
):
    doc = db.query(Documento).filter(Documento.id == documento_id).first()
    if not doc:
        raise HTTPException(404, "Documento não encontrado")

    if not os.path.exists(doc.file_path):
        raise HTTPException(404, "Arquivo não encontrado no servidor")

    return FileResponse(
        path=doc.file_path,
        filename=doc.filename,
        media_type=doc.file_type,
    )


@router.put("/{documento_id}/validar", response_model=DocumentoOut)
def validar_documento(
    documento_id: int,
    body: ValidacaoRequest,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_documentos")),
):
    if body.status not in ("aprovado", "rejeitado"):
        raise HTTPException(400, "Status deve ser 'aprovado' ou 'rejeitado'")

    doc = db.query(Documento).filter(Documento.id == documento_id).first()
    if not doc:
        raise HTTPException(404, "Documento não encontrado")

    doc.status = body.status
    doc.validated_by = current_user.id
    doc.validation_date = datetime.utcnow()
    doc.validation_notes = body.notes
    db.commit()
    db.refresh(doc)

    return _doc_to_out(doc)


@router.delete("/{documento_id}")
def excluir_documento(
    documento_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_documentos")),
):
    doc = db.query(Documento).filter(Documento.id == documento_id).first()
    if not doc:
        raise HTTPException(404, "Documento não encontrado")

    # Remover arquivo físico
    if os.path.exists(doc.file_path):
        os.remove(doc.file_path)

    db.delete(doc)
    db.commit()

    return {"detail": "Documento excluído com sucesso"}
