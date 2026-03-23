from pydantic import BaseModel, EmailStr
from typing import List, Optional
from datetime import datetime


# --- Items ---
class UserRequestItemCreate(BaseModel):
    servico_id: int
    permission_name: str


class UserRequestItemResponse(BaseModel):
    id: int
    servico_id: Optional[int] = None
    permission_name: Optional[str] = None
    approved: bool = True
    servico_nome: Optional[str] = None

    class Config:
        from_attributes = True


# --- Create (público, sem auth) ---
class UserRequestCreate(BaseModel):
    nome_completo: str
    email: EmailStr
    telefone: Optional[str] = None
    origem_tipo: str  # "cfo" ou "cro"
    organizacao: str
    departamento: Optional[str] = None
    justificativa: str
    outro: Optional[str] = None
    sugestao_desenvolvimento: Optional[str] = None
    items: List[UserRequestItemCreate] = []


# --- Response ---
class UserRequestResponse(BaseModel):
    id: int
    nome_completo: str
    email: str
    telefone: Optional[str] = None
    origem_tipo: str
    organizacao: str
    departamento: Optional[str] = None
    justificativa: str
    outro: Optional[str] = None
    sugestao_desenvolvimento: Optional[str] = None
    status: str
    admin_notes: Optional[str] = None
    reject_reason: Optional[str] = None
    clarification_message: Optional[str] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    resolved_at: Optional[datetime] = None
    items: List[UserRequestItemResponse] = []

    class Config:
        from_attributes = True


class UserRequestListResponse(BaseModel):
    requests: List[UserRequestResponse]
    total: int


# --- Status check (público) ---
class StatusCheckRequest(BaseModel):
    email: EmailStr


class StatusCheckResponse(BaseModel):
    email: str
    requests: List[UserRequestResponse]


# --- Admin actions ---
class UserRequestApprove(BaseModel):
    username: str
    password: str
    role_ids: List[int] = []
    admin_notes: Optional[str] = None


class UserRequestReject(BaseModel):
    reject_reason: str
    admin_notes: Optional[str] = None


class UserRequestClarification(BaseModel):
    clarification_message: str
    admin_notes: Optional[str] = None


class UserRequestItemModify(BaseModel):
    item_id: int
    approved: bool


class UserRequestModify(BaseModel):
    items: List[UserRequestItemModify]
    admin_notes: Optional[str] = None


# --- Stats ---
class UserRequestStats(BaseModel):
    pendente: int = 0
    em_analise: int = 0
    aprovado: int = 0
    rejeitado: int = 0
    esclarecimento: int = 0
    total: int = 0
