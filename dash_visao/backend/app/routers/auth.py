from fastapi import APIRouter, Depends, HTTPException, status, Form
from fastapi.security import OAuth2PasswordRequestForm
from sqlalchemy.orm import Session
from ..database import get_db
from ..models import User, PasswordResetToken
from ..core.auth import verify_password, create_access_token, get_password_hash, get_user_permissions, get_user_roles
from ..schemas.permission import LoginResponse
from ..lib.email_helper import send_password_reset_email
from datetime import timedelta, datetime, timezone
from pydantic import BaseModel, EmailStr
import secrets
import logging

logger = logging.getLogger(__name__)

router = APIRouter(
    prefix="",
    tags=["auth"]
)


@router.post("/token", response_model=LoginResponse)
def login_for_access_token(
    form_data: OAuth2PasswordRequestForm = Depends(),
    db: Session = Depends(get_db)
):
    # Aceita login por email ou username
    user = db.query(User).filter(
        (User.email == form_data.username) | (User.username == form_data.username)
    ).first()
    if not user or not verify_password(form_data.password, user.hashed_password):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="E-mail ou senha incorretos",
            headers={"WWW-Authenticate": "Bearer"},
        )

    if not user.is_active:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Inactive user"
        )

    access_token_expires = timedelta(minutes=30)
    access_token = create_access_token(
        data={"sub": user.username}, expires_delta=access_token_expires
    )

    # Obtém as permissões e roles do usuário
    permissions = get_user_permissions(db, user.id)
    roles = get_user_roles(db, user.id)

    return LoginResponse(
        access_token=access_token,
        token_type="bearer",
        permissions=permissions,
        roles=roles,
        user={
            "id": user.id,
            "username": user.username,
            "email": user.email,
            "full_name": user.full_name,
            "is_active": user.is_active,
            "is_superuser": user.is_superuser
        }
    )


# --- Recuperacao de Senha ---

class ForgotPasswordRequest(BaseModel):
    email: EmailStr


class ResetPasswordRequest(BaseModel):
    token: str
    new_password: str


@router.post("/forgot-password")
def forgot_password(request: ForgotPasswordRequest, db: Session = Depends(get_db)):
    """Solicita recuperacao de senha. Envia email com link de reset."""
    user = db.query(User).filter(User.email == request.email).first()

    # Sempre retorna sucesso para nao revelar se o email existe
    if not user:
        logger.info(f"Tentativa de recuperacao para email inexistente: {request.email}")
        return {"message": "Se o email estiver cadastrado, voce recebera um link de recuperacao."}

    # Invalida tokens anteriores do usuario
    db.query(PasswordResetToken).filter(
        PasswordResetToken.user_id == user.id,
        PasswordResetToken.used == False
    ).update({"used": True})

    # Gera novo token
    token = secrets.token_urlsafe(48)
    reset_token = PasswordResetToken(
        user_id=user.id,
        token=token,
        expires_at=datetime.now(timezone.utc) + timedelta(hours=1)
    )
    db.add(reset_token)
    db.commit()

    # Envia email
    send_password_reset_email(user.email, user.full_name or user.username, token)

    return {"message": "Se o email estiver cadastrado, voce recebera um link de recuperacao."}


@router.post("/reset-password")
def reset_password(request: ResetPasswordRequest, db: Session = Depends(get_db)):
    """Redefine a senha usando o token de recuperacao."""
    reset_token = db.query(PasswordResetToken).filter(
        PasswordResetToken.token == request.token,
        PasswordResetToken.used == False
    ).first()

    if not reset_token:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Token invalido ou ja utilizado."
        )

    if reset_token.expires_at < datetime.now(timezone.utc):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Token expirado. Solicite uma nova recuperacao de senha."
        )

    if len(request.new_password) < 6:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="A senha deve ter pelo menos 6 caracteres."
        )

    # Atualiza a senha do usuario
    user = db.query(User).filter(User.id == reset_token.user_id).first()
    if not user:
        raise HTTPException(status_code=404, detail="Usuario nao encontrado.")

    user.hashed_password = get_password_hash(request.new_password)
    reset_token.used = True
    db.commit()

    logger.info(f"Senha redefinida com sucesso para usuario {user.username}")
    return {"message": "Senha redefinida com sucesso. Voce ja pode fazer login."} 