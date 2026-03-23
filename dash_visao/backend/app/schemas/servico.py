from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime


class ServicoBase(BaseModel):
    nome: str
    slug: str
    descricao: Optional[str] = None
    icone: Optional[str] = None
    ativo: bool = True
    ordem: int = 0
    permissao_nome: str
    scope_type: Optional[str] = "global"


class ServicoCreate(ServicoBase):
    pass


class Servico(ServicoBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True


class ServicoListResponse(BaseModel):
    servicos: List[Servico]
    total: int
