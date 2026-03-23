from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import select
from typing import List
from ..database import get_db
from ..models import User, Permission, user_permissions
from ..schemas.permission import UserPermissionCreate, UserPermissionResponse
from ..core.auth import get_current_active_user, check_permission

router = APIRouter(prefix="/user-permissions", tags=["user-permissions"])


@router.get("/{user_id}/", response_model=List[UserPermissionResponse])
def get_user_direct_permissions(
    user_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Lista permissões diretas de um usuário"""
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="User not found")

    rows = db.execute(
        select(
            user_permissions.c.id,
            user_permissions.c.user_id,
            user_permissions.c.permission_id,
            Permission.name.label("permission_name"),
            user_permissions.c.granted,
        )
        .join(Permission, Permission.id == user_permissions.c.permission_id)
        .where(user_permissions.c.user_id == user_id)
    ).fetchall()

    return [
        UserPermissionResponse(
            id=row.id,
            user_id=row.user_id,
            permission_id=row.permission_id,
            permission_name=row.permission_name,
            granted=row.granted,
        )
        for row in rows
    ]


@router.post("/", response_model=dict)
def assign_direct_permission(
    data: UserPermissionCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Concede ou nega uma permissão direta a um usuário"""
    user = db.query(User).filter(User.id == data.user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="User not found")

    permission = db.query(Permission).filter(Permission.id == data.permission_id).first()
    if not permission:
        raise HTTPException(status_code=404, detail="Permission not found")

    # Verifica se já existe
    existing = db.execute(
        select(user_permissions)
        .where(
            user_permissions.c.user_id == data.user_id,
            user_permissions.c.permission_id == data.permission_id
        )
    ).first()

    if existing:
        db.execute(
            user_permissions.update()
            .where(
                user_permissions.c.user_id == data.user_id,
                user_permissions.c.permission_id == data.permission_id
            )
            .values(granted=data.granted)
        )
    else:
        db.execute(
            user_permissions.insert().values(
                user_id=data.user_id,
                permission_id=data.permission_id,
                granted=data.granted
            )
        )

    db.commit()
    return {"message": "Direct permission updated", "granted": data.granted}


@router.delete("/{user_id}/{permission_id}/")
def remove_direct_permission(
    user_id: int,
    permission_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_permissions"))
):
    """Remove uma permissão direta (override) de um usuário"""
    existing = db.execute(
        select(user_permissions)
        .where(
            user_permissions.c.user_id == user_id,
            user_permissions.c.permission_id == permission_id
        )
    ).first()

    if not existing:
        raise HTTPException(status_code=404, detail="Direct permission not found")

    db.execute(
        user_permissions.delete().where(
            user_permissions.c.user_id == user_id,
            user_permissions.c.permission_id == permission_id
        )
    )
    db.commit()
    return {"message": "Direct permission removed"}
