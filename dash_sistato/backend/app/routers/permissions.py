from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import select
from typing import List
from ..database import get_db
from ..models import Permission, Role, User, Menu, SubMenu, role_permissions
from ..schemas.permission import PermissionCreate, PermissionUpdate, Permission as PermissionSchema, RolePermissionCreate
from ..core.auth import get_current_active_user, check_permission, get_user_permissions, get_user_roles

router = APIRouter(prefix="/permissions", tags=["permissions"])

@router.get("/", response_model=List[PermissionSchema])
def get_permissions(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista todas as permissões"""
    permissions = db.query(Permission).offset(skip).limit(limit).all()
    return permissions

@router.post("/", response_model=PermissionSchema)
def create_permission(
    permission: PermissionCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Cria uma nova permissão"""
    # Verifica se já existe uma permissão com o mesmo nome
    existing_permission = db.query(Permission).filter(Permission.name == permission.name).first()
    if existing_permission:
        raise HTTPException(status_code=400, detail="Permission with this name already exists")
    
    # Verifica se o menu ou submenu existe (se especificado)
    if permission.menu_id:
        menu = db.query(Menu).filter(Menu.id == permission.menu_id).first()
        if not menu:
            raise HTTPException(status_code=404, detail="Menu not found")
    
    if permission.submenu_id:
        submenu = db.query(SubMenu).filter(SubMenu.id == permission.submenu_id).first()
        if not submenu:
            raise HTTPException(status_code=404, detail="Submenu not found")
    
    db_permission = Permission(**permission.dict())
    db.add(db_permission)
    db.commit()
    db.refresh(db_permission)
    return db_permission

@router.get("/{permission_id}", response_model=PermissionSchema)
def get_permission(
    permission_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Obtém uma permissão específica"""
    permission = db.query(Permission).filter(Permission.id == permission_id).first()
    if permission is None:
        raise HTTPException(status_code=404, detail="Permission not found")
    return permission

@router.put("/{permission_id}", response_model=PermissionSchema)
def update_permission(
    permission_id: int,
    permission: PermissionUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Atualiza uma permissão"""
    db_permission = db.query(Permission).filter(Permission.id == permission_id).first()
    if db_permission is None:
        raise HTTPException(status_code=404, detail="Permission not found")
    
    # Verifica se o novo nome já existe (se estiver sendo alterado)
    if permission.name and permission.name != db_permission.name:
        existing_permission = db.query(Permission).filter(Permission.name == permission.name).first()
        if existing_permission:
            raise HTTPException(status_code=400, detail="Permission with this name already exists")
    
    for field, value in permission.dict(exclude_unset=True).items():
        setattr(db_permission, field, value)
    
    db.commit()
    db.refresh(db_permission)
    return db_permission

@router.delete("/{permission_id}")
def delete_permission(
    permission_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Deleta uma permissão"""
    db_permission = db.query(Permission).filter(Permission.id == permission_id).first()
    if db_permission is None:
        raise HTTPException(status_code=404, detail="Permission not found")
    
    db.delete(db_permission)
    db.commit()
    return {"message": "Permission deleted successfully"}

# Associações de roles com permissões
@router.post("/{permission_id}/roles/")
def assign_permission_to_role(
    permission_id: int,
    role_permission: RolePermissionCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Associa uma permissão a um role com suporte ao campo granted"""
    # Verifica se a permissão existe
    permission = db.query(Permission).filter(Permission.id == permission_id).first()
    if permission is None:
        raise HTTPException(status_code=404, detail="Permission not found")

    # Verifica se o role existe
    role = db.query(Role).filter(Role.id == role_permission.role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")

    # Verifica se a associação já existe
    existing = db.execute(
        select(role_permissions)
        .where(
            role_permissions.c.role_id == role_permission.role_id,
            role_permissions.c.permission_id == permission_id
        )
    ).first()

    if existing:
        # Update granted se já existe
        db.execute(
            role_permissions.update()
            .where(
                role_permissions.c.role_id == role_permission.role_id,
                role_permissions.c.permission_id == permission_id
            )
            .values(granted=role_permission.granted)
        )
    else:
        # Insert nova associação
        db.execute(
            role_permissions.insert().values(
                role_id=role_permission.role_id,
                permission_id=permission_id,
                granted=role_permission.granted
            )
        )

    db.commit()
    return {"message": "Permission assigned to role successfully", "granted": role_permission.granted}

@router.delete("/{permission_id}/roles/{role_id}/")
def remove_permission_from_role(
    permission_id: int,
    role_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Remove uma permissão de um role"""
    # Verifica se a permissão existe
    permission = db.query(Permission).filter(Permission.id == permission_id).first()
    if permission is None:
        raise HTTPException(status_code=404, detail="Permission not found")
    
    # Verifica se o role existe
    role = db.query(Role).filter(Role.id == role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    
    # Verifica se a associação existe
    if permission not in role.permissions:
        raise HTTPException(status_code=400, detail="Role does not have this permission")
    
    role.permissions.remove(permission)
    db.commit()
    return {"message": "Permission removed from role successfully"}

@router.get("/{permission_id}/roles/")
def get_roles_by_permission(
    permission_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista todos os roles de uma permissão específica"""
    permission = db.query(Permission).filter(Permission.id == permission_id).first()
    if permission is None:
        raise HTTPException(status_code=404, detail="Permission not found")
    
    roles = db.query(Role).filter(Role.permissions.contains(permission)).all()
    return [
        {
            "id": role.id,
            "name": role.name,
            "description": role.description
        }
        for role in roles
    ]

# Endpoint para obter permissões do usuário atual
@router.get("/me/permissions")
def get_my_permissions(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Obtém as permissões do usuário atual"""
    permissions = get_user_permissions(db, current_user.id)
    roles = get_user_roles(db, current_user.id)
    
    return {
        "permissions": permissions,
        "roles": roles,
        "user_id": current_user.id,
        "username": current_user.username
    } 