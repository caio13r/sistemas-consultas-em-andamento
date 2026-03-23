from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List
from ..database import get_db
from ..models import Role, User, Permission, role_permissions
from ..schemas.permission import RoleCreate, RoleUpdate, Role as RoleSchema, UserRoleCreate
from ..core.auth import get_current_active_user, check_permission

router = APIRouter(prefix="/roles", tags=["roles"])

@router.get("/", response_model=List[RoleSchema])
def get_roles(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista todos os roles"""
    roles = db.query(Role).offset(skip).limit(limit).all()
    return roles

@router.post("/", response_model=RoleSchema)
def create_role(
    role: RoleCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_roles"))
):
    """Cria um novo role"""
    # Verifica se já existe um role com o mesmo nome
    existing_role = db.query(Role).filter(Role.name == role.name).first()
    if existing_role:
        raise HTTPException(status_code=400, detail="Role with this name already exists")
    
    db_role = Role(**role.dict())
    db.add(db_role)
    db.commit()
    db.refresh(db_role)
    return db_role

@router.get("/{role_id}/", response_model=RoleSchema)
def get_role(
    role_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Obtém um role específico"""
    role = db.query(Role).filter(Role.id == role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    return role

@router.put("/{role_id}/", response_model=RoleSchema)
def update_role(
    role_id: int,
    role: RoleUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_roles"))
):
    """Atualiza um role"""
    db_role = db.query(Role).filter(Role.id == role_id).first()
    if db_role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    
    # Verifica se o novo nome já existe (se estiver sendo alterado)
    if role.name and role.name != db_role.name:
        existing_role = db.query(Role).filter(Role.name == role.name).first()
        if existing_role:
            raise HTTPException(status_code=400, detail="Role with this name already exists")
    
    for field, value in role.dict(exclude_unset=True).items():
        setattr(db_role, field, value)
    
    db.commit()
    db.refresh(db_role)
    return db_role

@router.delete("/{role_id}/")
def delete_role(
    role_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_roles"))
):
    """Deleta um role"""
    db_role = db.query(Role).filter(Role.id == role_id).first()
    if db_role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    
    # Verifica se há usuários associados a este role
    users_with_role = db.query(User).filter(User.roles.contains(db_role)).count()
    if users_with_role > 0:
        raise HTTPException(status_code=400, detail="Cannot delete role with associated users")
    
    db.delete(db_role)
    db.commit()
    return {"message": "Role deleted successfully"}

# Associações de usuários com roles
@router.post("/{role_id}/users/")
def assign_user_to_role(
    role_id: int,
    user_role: UserRoleCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_roles"))
):
    """Associa um usuário a um role"""
    # Verifica se o role existe
    role = db.query(Role).filter(Role.id == role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    
    # Verifica se o usuário existe
    user = db.query(User).filter(User.id == user_role.user_id).first()
    if user is None:
        raise HTTPException(status_code=404, detail="User not found")
    
    # Verifica se a associação já existe
    if role in user.roles:
        raise HTTPException(status_code=400, detail="User already has this role")
    
    user.roles.append(role)
    db.commit()
    return {"message": "User assigned to role successfully"}

@router.delete("/{role_id}/users/{user_id}/")
def remove_user_from_role(
    role_id: int,
    user_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_roles"))
):
    """Remove um usuário de um role"""
    # Verifica se o role existe
    role = db.query(Role).filter(Role.id == role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    
    # Verifica se o usuário existe
    user = db.query(User).filter(User.id == user_id).first()
    if user is None:
        raise HTTPException(status_code=404, detail="User not found")
    
    # Verifica se a associação existe
    if role not in user.roles:
        raise HTTPException(status_code=400, detail="User does not have this role")
    
    user.roles.remove(role)
    db.commit()
    return {"message": "User removed from role successfully"}

@router.get("/{role_id}/permissions/")
def get_role_permissions(
    role_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista as permissões de um role com status granted"""
    role = db.query(Role).filter(Role.id == role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")

    rows = db.execute(
        select(Permission.id, Permission.name, Permission.description, Permission.action, Permission.resource, role_permissions.c.granted)
        .join(role_permissions, Permission.id == role_permissions.c.permission_id)
        .where(role_permissions.c.role_id == role_id)
    ).fetchall()

    return [
        {
            "id": r.id,
            "name": r.name,
            "description": r.description,
            "action": r.action,
            "resource": r.resource,
            "granted": r.granted,
        }
        for r in rows
    ]


@router.get("/{role_id}/users/")
def get_users_by_role(
    role_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista todos os usuários de um role específico"""
    role = db.query(Role).filter(Role.id == role_id).first()
    if role is None:
        raise HTTPException(status_code=404, detail="Role not found")
    
    users = db.query(User).filter(User.roles.contains(role)).all()
    return [
        {
            "id": user.id,
            "username": user.username,
            "email": user.email,
            "full_name": user.full_name,
            "is_active": user.is_active
        }
        for user in users
    ] 