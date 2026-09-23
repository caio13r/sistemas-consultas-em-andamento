from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List
from ..database import get_db
from ..models import User, Role
from ..schemas.user import User as UserSchema, UserCreate, UserUpdate, UserUpdateMe, UserWithPermissions
from ..core.auth import get_password_hash, get_current_active_user, check_permission, require_admin, get_user_permissions, get_user_roles

router = APIRouter(
    prefix="/users",
    tags=["users"]
)

@router.get("", response_model=List[UserSchema])
def get_users(
    skip: int = 0, 
    limit: int = 100, 
    db: Session = Depends(get_db), 
    current_user: User = Depends(require_admin)
):
    return db.query(User).offset(skip).limit(limit).all()

@router.post("", response_model=UserSchema)
def create_user(
    user: UserCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("create_users")),
):
    if db.query(User).filter(User.email == user.email).first():
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Email já cadastrado")
    if db.query(User).filter(User.username == user.username).first():
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Nome de usuário já cadastrado")

    role_ids = user.role_ids or []
    user_data = user.model_dump(exclude={"password", "role_ids"})
    hashed_password = get_password_hash(user.password)
    db_user = User(**user_data, hashed_password=hashed_password)
    db.add(db_user)
    db.flush()

    if role_ids:
        roles = db.query(Role).filter(Role.id.in_(role_ids)).all()
        if len(roles) != len(set(role_ids)):
            raise HTTPException(status_code=400, detail="Um ou mais perfis informados são inválidos")
        db_user.roles = roles

    db.commit()
    db.refresh(db_user)
    return db_user

@router.get("/me", response_model=UserWithPermissions)
def get_me(current_user: User = Depends(get_current_active_user), db: Session = Depends(get_db)):
    permissions = get_user_permissions(db, current_user.id)
    roles = get_user_roles(db, current_user.id)

    # User.model_validate evita que roles (Role objects) sejam validados como List[str]
    user_base = UserSchema.model_validate(current_user)
    return UserWithPermissions(**user_base.model_dump(), permissions=permissions, roles=roles)

@router.patch("/me", response_model=UserSchema)
def update_me(
    user_update: UserUpdateMe, 
    db: Session = Depends(get_db), 
    current_user: User = Depends(get_current_active_user)
):
    db_user = current_user
    update_data = user_update.dict(exclude_unset=True)

    if "password" in update_data and update_data["password"]:
        db_user.hashed_password = get_password_hash(update_data.pop("password"))
    
    if "email" in update_data and update_data["email"] != db_user.email:
        if db.query(User).filter(User.email == update_data["email"]).first():
            raise HTTPException(status_code=400, detail="Email already registered")
    
    if "username" in update_data and update_data["username"] != db_user.username:
        if db.query(User).filter(User.username == update_data["username"]).first():
            raise HTTPException(status_code=400, detail="Username already registered")

    for key, value in update_data.items():
        setattr(db_user, key, value)

    db.commit()
    db.refresh(db_user)
    return db_user

@router.put("/{user_id}", response_model=UserSchema)
def update_user_by_admin(
    user_id: int, 
    user_update: UserUpdate, 
    db: Session = Depends(get_db), 
    current_user: User = Depends(require_admin)
):
    db_user = db.query(User).filter(User.id == user_id).first()
    if not db_user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="User not found")

    update_data = user_update.dict(exclude_unset=True)
    
    if "password" in update_data and update_data["password"]:
        update_data["hashed_password"] = get_password_hash(update_data.pop("password"))
    else:
        update_data.pop("password", None)

    for key, value in update_data.items():
        setattr(db_user, key, value)

    db.commit()
    db.refresh(db_user)
    return db_user

@router.delete("/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_user(
    user_id: int, 
    db: Session = Depends(get_db), 
    current_user: User = Depends(require_admin)
):
    db_user = db.query(User).filter(User.id == user_id).first()
    if not db_user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="User not found")
    
    db.delete(db_user)
    db.commit()
    return {"message": "User deleted successfully"}

@router.get("/roles", response_model=List[dict])
def get_roles(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user),
):
    """Lista todos os roles disponíveis"""
    roles = db.query(Role).all()
    return [
        {
            "id": role.id,
            "name": role.name,
            "description": role.description
        }
        for role in roles
    ]

@router.post("/first-admin", response_model=UserSchema)
def create_first_admin(
    user: UserCreate, 
    db: Session = Depends(get_db)
):
    if db.query(User).first():
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="First admin user already exists")
    if db.query(User).filter(User.email == user.email).first():
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Email already registered")
    if db.query(User).filter(User.username == user.username).first():
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Username already registered")
        
    user_data = user.model_dump(exclude={"password", "role_ids"})
    hashed_password = get_password_hash(user.password)
    db_user = User(**user_data, hashed_password=hashed_password, is_superuser=True)
    db.add(db_user)
    db.commit()
    db.refresh(db_user)
    return db_user