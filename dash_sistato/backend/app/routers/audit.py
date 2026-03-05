from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from sqlalchemy.orm import Session
from typing import List
from app.database import get_db
from app.models.audit import Theme, Audit, Request, Document, Communication, RequestStatus
from app.schemas.audit import (
    Theme as ThemeSchema,
    ThemeCreate as ThemeCreateSchema,
    Audit as AuditSchema,
    AuditCreate as AuditCreateSchema,
    Request as RequestSchema,
    RequestCreate as RequestCreateSchema,
    Document as DocumentSchema,
    DocumentCreate as DocumentCreateSchema,
    Communication as CommunicationSchema,
    CommunicationCreate as CommunicationCreateSchema
)
from app.core.auth import get_current_user
from app.models.user import User
import os
from datetime import datetime
import shutil

router = APIRouter(
    prefix="/audit",
    tags=["audit"]
)

# Theme routes
@router.post("/themes", response_model=ThemeSchema)
def create_theme(
    theme: ThemeCreateSchema,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    db_theme = Theme(**theme.dict())
    db.add(db_theme)
    db.commit()
    db.refresh(db_theme)
    return db_theme

@router.get("/themes", response_model=List[ThemeSchema])
def get_themes(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    themes = db.query(Theme).offset(skip).limit(limit).all()
    return themes

# Audit routes
@router.post("/audits", response_model=AuditSchema)
def create_audit(
    audit: AuditCreateSchema,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    db_audit = Audit(**audit.dict(), created_by=current_user.id)
    db.add(db_audit)
    db.commit()
    db.refresh(db_audit)
    return db_audit

@router.get("/audits", response_model=List[AuditSchema])
def get_audits(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    audits = db.query(Audit).offset(skip).limit(limit).all()
    return audits

# Request routes
@router.post("/requests", response_model=RequestSchema)
def create_request(
    request: RequestCreateSchema,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    db_request = Request(**request.dict())
    db.add(db_request)
    db.commit()
    db.refresh(db_request)
    return db_request

@router.get("/requests", response_model=List[RequestSchema])
def get_requests(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    requests = db.query(Request).offset(skip).limit(limit).all()
    return requests

@router.put("/requests/{request_id}/status", response_model=RequestSchema)
def update_request_status(
    request_id: int,
    status: RequestStatus,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    db_request = db.query(Request).filter(Request.id == request_id).first()
    if not db_request:
        raise HTTPException(status_code=404, detail="Request not found")
    
    db_request.status = status
    db.commit()
    db.refresh(db_request)
    return db_request

# Document routes
@router.post("/documents", response_model=DocumentSchema)
async def upload_document(
    file: UploadFile = File(...),
    audit_id: int = None,
    request_id: int = None,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    if not audit_id and not request_id:
        raise HTTPException(status_code=400, detail="Either audit_id or request_id must be provided")

    # Create upload directory if it doesn't exist
    upload_dir = "uploads"
    os.makedirs(upload_dir, exist_ok=True)

    # Save file
    file_path = os.path.join(upload_dir, file.filename)
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    # Create document record
    document = Document(
        audit_id=audit_id,
        request_id=request_id,
        filename=file.filename,
        file_path=file_path,
        file_type=file.content_type,
        file_size=os.path.getsize(file_path)
    )
    db.add(document)
    db.commit()
    db.refresh(document)
    return document

@router.get("/documents", response_model=List[DocumentSchema])
def get_documents(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    documents = db.query(Document).offset(skip).limit(limit).all()
    return documents

# Communication routes
@router.post("/communications", response_model=CommunicationSchema)
def create_communication(
    communication: CommunicationCreateSchema,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    db_communication = Communication(
        **communication.dict(),
        sender_id=current_user.id
    )
    db.add(db_communication)
    db.commit()
    db.refresh(db_communication)
    return db_communication

@router.get("/communications", response_model=List[CommunicationSchema])
def get_communications(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    communications = db.query(Communication).offset(skip).limit(limit).all()
    return communications

@router.put("/communications/{communication_id}/read")
def mark_communication_as_read(
    communication_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_user)
):
    db_communication = db.query(Communication).filter(Communication.id == communication_id).first()
    if not db_communication:
        raise HTTPException(status_code=404, detail="Communication not found")
    
    db_communication.is_read = True
    db.commit()
    return {"message": "Communication marked as read"} 