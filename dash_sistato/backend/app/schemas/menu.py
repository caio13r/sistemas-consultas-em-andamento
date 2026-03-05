from pydantic import BaseModel
from typing import Optional, List

class MenuItemBase(BaseModel):
    nome: str
    referencial: str
    grupo: str
    descricao: str
    disable: bool = False
    fk_label: Optional[int] = None

class MenuItemCreate(MenuItemBase):
    pass

class MenuItemResponse(MenuItemBase):
    id_label: int
    children: List["MenuItemResponse"] = []

    class Config:
        from_attributes = True

MenuItemResponse.update_forward_refs() 