from sqlalchemy import Column, Integer, String, Boolean, DateTime, ForeignKey, Table, Text
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from ..database import Base

# Tabela de associação entre roles e permissions
role_permissions = Table(
    'role_permissions',
    Base.metadata,
    Column('id', Integer, primary_key=True, index=True),
    Column('role_id', Integer, ForeignKey('roles.id'), nullable=False),
    Column('permission_id', Integer, ForeignKey('permissions.id'), nullable=False),
    Column('granted', Boolean, default=True),
    Column('created_at', DateTime(timezone=True), server_default=func.now())
)

# Tabela de associação entre users e roles
user_roles = Table(
    'user_roles',
    Base.metadata,
    Column('id', Integer, primary_key=True, index=True),
    Column('user_id', Integer, ForeignKey('users.id'), nullable=False),
    Column('role_id', Integer, ForeignKey('roles.id'), nullable=False),
    Column('created_at', DateTime(timezone=True), server_default=func.now())
)

# Tabela de permissões diretas do usuário (override)
user_permissions = Table(
    'user_permissions',
    Base.metadata,
    Column('id', Integer, primary_key=True, index=True),
    Column('user_id', Integer, ForeignKey('users.id'), nullable=False),
    Column('permission_id', Integer, ForeignKey('permissions.id'), nullable=False),
    Column('granted', Boolean, default=True),
    Column('created_at', DateTime(timezone=True), server_default=func.now())
)

class Menu(Base):
    __tablename__ = "menus"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), nullable=False)
    url = Column(String(255))
    icon = Column(String(255))
    description = Column(Text)
    order = Column(Integer, default=0)
    disable = Column(Boolean, default=False)
    permission_name = Column(String(255), nullable=True)  # permissão necessária para ver o menu
    is_section = Column(Boolean, default=False)  # True = separador/seção (ex: "Serviços", "Administração")
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relacionamentos
    submenus = relationship("SubMenu", back_populates="menu", cascade="all, delete-orphan", order_by="SubMenu.order")
    permissions = relationship("Permission", back_populates="menu")

class SubMenu(Base):
    __tablename__ = "submenus"

    id = Column(Integer, primary_key=True, index=True)
    menu_id = Column(Integer, ForeignKey("menus.id"), nullable=False)
    name = Column(String(255), nullable=False)
    url = Column(String(255))
    icon = Column(String(255))
    description = Column(Text)
    order = Column(Integer, default=0)
    disable = Column(Boolean, default=False)
    permission_name = Column(String(255), nullable=True)  # permissão necessária para ver o submenu
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relacionamentos
    menu = relationship("Menu", back_populates="submenus")
    permissions = relationship("Permission", back_populates="submenu")

class Role(Base):
    __tablename__ = "roles"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), unique=True, index=True, nullable=False)
    description = Column(Text)
    parent_role_id = Column(Integer, ForeignKey("roles.id"), nullable=True)
    level = Column(Integer, default=1)  # 1=view, 2=edit, 3=approve, 4=admin
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relacionamentos
    users = relationship("User", secondary=user_roles, back_populates="roles")
    permissions = relationship("Permission", secondary=role_permissions, back_populates="roles")
    parent_role = relationship("Role", remote_side="Role.id", backref="child_roles")

class Permission(Base):
    __tablename__ = "permissions"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), unique=True, index=True, nullable=False)
    description = Column(Text)
    action = Column(String(50), nullable=False)  # view, edit, delete, export, approve, etc.
    resource = Column(String(255), nullable=False)  # menu ou submenu
    menu_id = Column(Integer, ForeignKey("menus.id"), nullable=True)
    submenu_id = Column(Integer, ForeignKey("submenus.id"), nullable=True)
    scope_type = Column(String(20), default="global")  # "global", "cfo", "cro"
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relacionamentos
    menu = relationship("Menu", back_populates="permissions")
    submenu = relationship("SubMenu", back_populates="permissions")
    roles = relationship("Role", secondary=role_permissions, back_populates="permissions")