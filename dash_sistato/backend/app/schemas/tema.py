from pydantic import BaseModel
from datetime import datetime
from typing import Optional

class TemaBase(BaseModel):
    nome: str
    descricao: str
    categoria: str
    status: str

class TemaCreate(TemaBase):
    pass

class TemaUpdate(BaseModel):
    nome: Optional[str] = None
    descricao: Optional[str] = None
    categoria: Optional[str] = None
    status: Optional[str] = None

class TemaResponse(TemaBase):
    id: int
    created_at: datetime
    updated_at: datetime

    class Config:
        orm_mode = True 