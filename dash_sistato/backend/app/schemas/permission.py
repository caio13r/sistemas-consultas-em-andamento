from pydantic import BaseModel
from typing import List, Optional
from datetime import datetime

# Schemas para Menu
class MenuBase(BaseModel):
    name: str
    url: Optional[str] = None
    icon: Optional[str] = None
    description: Optional[str] = None
    order: int = 0
    disable: bool = False
    permission_name: Optional[str] = None
    is_section: bool = False

class MenuCreate(MenuBase):
    pass

class MenuUpdate(BaseModel):
    name: Optional[str] = None
    url: Optional[str] = None
    icon: Optional[str] = None
    description: Optional[str] = None
    order: Optional[int] = None
    disable: Optional[bool] = None
    permission_name: Optional[str] = None
    is_section: Optional[bool] = None

class Menu(MenuBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

# Schemas para SubMenu
class SubMenuBase(BaseModel):
    name: str
    url: Optional[str] = None
    icon: Optional[str] = None
    description: Optional[str] = None
    order: int = 0
    disable: bool = False
    permission_name: Optional[str] = None

class SubMenuCreate(SubMenuBase):
    menu_id: Optional[int] = None  # Opcional, será fornecido via URL

class SubMenuUpdate(BaseModel):
    menu_id: Optional[int] = None
    name: Optional[str] = None
    url: Optional[str] = None
    icon: Optional[str] = None
    description: Optional[str] = None
    order: Optional[int] = None
    disable: Optional[bool] = None
    permission_name: Optional[str] = None

class SubMenu(SubMenuBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

# Schemas para Role
class RoleBase(BaseModel):
    name: str
    description: Optional[str] = None
    parent_role_id: Optional[int] = None
    level: int = 1

class RoleCreate(RoleBase):
    pass

class RoleUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    parent_role_id: Optional[int] = None
    level: Optional[int] = None

class Role(RoleBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

# Schemas para Permission
class PermissionBase(BaseModel):
    name: str
    description: Optional[str] = None
    action: str
    resource: str
    menu_id: Optional[int] = None
    submenu_id: Optional[int] = None
    scope_type: Optional[str] = "global"

class PermissionCreate(PermissionBase):
    pass

class PermissionUpdate(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    action: Optional[str] = None
    resource: Optional[str] = None
    menu_id: Optional[int] = None
    submenu_id: Optional[int] = None
    scope_type: Optional[str] = None

class Permission(PermissionBase):
    id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    class Config:
        from_attributes = True

# Schemas para associações
class RolePermissionCreate(BaseModel):
    role_id: int
    permission_id: int
    granted: bool = True

class UserRoleCreate(BaseModel):
    user_id: int
    role_id: int

# Schemas para resposta de menu dinâmico
class SubMenuResponse(BaseModel):
    id: int
    name: str
    url: Optional[str] = None
    icon: Optional[str] = None
    order: int
    disable: bool = False
    permission_name: Optional[str] = None

    class Config:
        from_attributes = True

class MenuResponse(BaseModel):
    id: int
    name: str
    url: Optional[str] = None
    icon: Optional[str] = None
    description: Optional[str] = None
    order: int
    disable: bool = False
    permission_name: Optional[str] = None
    is_section: bool = False
    submenus: List[SubMenuResponse] = []

    class Config:
        from_attributes = True

# Schema para resposta de permissões do usuário
class UserPermissionsResponse(BaseModel):
    permissions: List[str]
    roles: List[str]
    menu: List[MenuResponse]

# Schemas para permissões diretas do usuário
class UserPermissionCreate(BaseModel):
    user_id: int
    permission_id: int
    granted: bool = True

class UserPermissionResponse(BaseModel):
    id: int
    user_id: int
    permission_id: int
    permission_name: str
    granted: bool

# Schema para login com permissões
class LoginResponse(BaseModel):
    access_token: str
    token_type: str
    permissions: List[str]
    roles: List[str]
    user: dict 