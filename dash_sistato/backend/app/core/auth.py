from datetime import datetime, timedelta
from typing import Optional, List, Set
from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from jose import JWTError, jwt
from passlib.context import CryptContext
from sqlalchemy.orm import Session
from sqlalchemy import select
from ..database import get_db
from ..models import User, Role, Permission, role_permissions, user_permissions
import os

# Configurações de segurança
SECRET_KEY = os.getenv("SECRET_KEY", "your-secret-key-here")
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = 30

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
security = HTTPBearer()

def verify_password(plain_password: str, hashed_password: str) -> bool:
    return pwd_context.verify(plain_password, hashed_password)

def get_password_hash(password: str) -> str:
    return pwd_context.hash(password)

def create_access_token(data: dict, expires_delta: Optional[timedelta] = None):
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=15)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)
    return encoded_jwt

def verify_token(token: str) -> Optional[dict]:
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        return payload
    except JWTError:
        return None

def _get_role_hierarchy(db: Session, role: Role, visited: Set[int] = None) -> List[Role]:
    """Caminha a hierarquia de roles até a raiz, com proteção contra ciclos"""
    if visited is None:
        visited = set()

    hierarchy = [role]
    visited.add(role.id)

    if role.parent_role_id and role.parent_role_id not in visited:
        parent = db.query(Role).filter(Role.id == role.parent_role_id).first()
        if parent:
            hierarchy.extend(_get_role_hierarchy(db, parent, visited))

    return hierarchy

def get_user_permissions(db: Session, user_id: int) -> List[str]:
    """Obtém todas as permissões do usuário considerando hierarquia de roles,
    campo granted em role_permissions, e permissões diretas como override"""
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        return []

    # 1. Coletar todos os roles (diretos + herdados pela hierarquia)
    all_roles = []
    for role in user.roles:
        all_roles.extend(_get_role_hierarchy(db, role))

    # Deduplica mantendo a ordem
    seen_role_ids = set()
    unique_roles = []
    for r in all_roles:
        if r.id not in seen_role_ids:
            seen_role_ids.add(r.id)
            unique_roles.append(r)

    # 2. Para cada role, consultar role_permissions respeitando granted
    granted_permissions: Set[str] = set()
    denied_by_role: Set[str] = set()

    for role in unique_roles:
        rows = db.execute(
            select(Permission.name, role_permissions.c.granted)
            .join(Permission, Permission.id == role_permissions.c.permission_id)
            .where(role_permissions.c.role_id == role.id)
        ).fetchall()

        for perm_name, is_granted in rows:
            if is_granted:
                granted_permissions.add(perm_name)
            else:
                denied_by_role.add(perm_name)

    # Remove as negadas por role
    permissions = granted_permissions - denied_by_role

    # 3. Aplicar permissões diretas do usuário como override
    direct_rows = db.execute(
        select(Permission.name, user_permissions.c.granted)
        .join(Permission, Permission.id == user_permissions.c.permission_id)
        .where(user_permissions.c.user_id == user_id)
    ).fetchall()

    for perm_name, is_granted in direct_rows:
        if is_granted:
            permissions.add(perm_name)
        else:
            permissions.discard(perm_name)

    return list(permissions)

def get_user_roles(db: Session, user_id: int) -> List[str]:
    """Obtém todos os roles do usuário (incluindo roles herdados)"""
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        return []

    all_roles = []
    for role in user.roles:
        all_roles.extend(_get_role_hierarchy(db, role))

    seen = set()
    result = []
    for r in all_roles:
        if r.name not in seen:
            seen.add(r.name)
            result.append(r.name)
    return result

def check_role_level(required_level: int):
    """Dependency para verificar se o usuário tem um role com nível suficiente"""
    def level_checker(current_user: User = Depends(get_current_active_user), db: Session = Depends(get_db)):
        if current_user.is_superuser:
            return current_user

        max_level = 0
        for role in current_user.roles:
            hierarchy = _get_role_hierarchy(db, role)
            for r in hierarchy:
                if r.level and r.level > max_level:
                    max_level = r.level

        if max_level < required_level:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Insufficient role level"
            )
        return current_user
    return level_checker

def get_current_user(credentials: HTTPAuthorizationCredentials = Depends(security), db: Session = Depends(get_db)):
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )
    
    token = credentials.credentials
    payload = verify_token(token)
    if payload is None:
        raise credentials_exception
    
    username: str = payload.get("sub")
    if username is None:
        raise credentials_exception
    
    user = db.query(User).filter(User.username == username).first()
    if user is None:
        raise credentials_exception
    
    return user

def get_current_active_user(current_user: User = Depends(get_current_user)):
    if not current_user.is_active:
        raise HTTPException(status_code=400, detail="Inactive user")
    return current_user

def check_permission(required_permission: str):
    """Decorator para verificar permissões específicas"""
    def permission_checker(current_user: User = Depends(get_current_active_user), db: Session = Depends(get_db)):
        if current_user.is_superuser:
            return current_user

        perms = get_user_permissions(db, current_user.id)
        if required_permission not in perms:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Not enough permissions"
            )
        return current_user
    return permission_checker

def check_any_permission(required_permissions: List[str]):
    """Decorator para verificar se o usuário tem pelo menos uma das permissões"""
    def permission_checker(current_user: User = Depends(get_current_active_user), db: Session = Depends(get_db)):
        if current_user.is_superuser:
            return current_user

        perms = get_user_permissions(db, current_user.id)
        if not any(perm in perms for perm in required_permissions):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Not enough permissions"
            )
        return current_user
    return permission_checker

def check_all_permissions(required_permissions: List[str]):
    """Decorator para verificar se o usuário tem todas as permissões"""
    def permission_checker(current_user: User = Depends(get_current_active_user), db: Session = Depends(get_db)):
        if current_user.is_superuser:
            return current_user

        perms = get_user_permissions(db, current_user.id)
        if not all(perm in perms for perm in required_permissions):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Not enough permissions"
            )
        return current_user
    return permission_checker 