"""
SSO - Autenticação via Keycloak (OpenID Connect)
Fluxo: Frontend redireciona ao Keycloak → usuário autentica → Keycloak redireciona
de volta com code → Frontend troca code por token via este endpoint → JWT local emitido.
"""
import os
import logging
import requests as http_client
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from pydantic import BaseModel
from ..database import get_db
from ..models import User
from ..core.auth import create_access_token, get_user_permissions, get_user_roles, get_password_hash
from ..schemas.permission import LoginResponse
from datetime import timedelta

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/sso", tags=["sso"])

KEYCLOAK_URL = os.getenv("KEYCLOAK_URL", "https://sso.cfo.org.br")
KEYCLOAK_INTERNAL_URL = os.getenv("KEYCLOAK_INTERNAL_URL", KEYCLOAK_URL)
KEYCLOAK_REALM = os.getenv("KEYCLOAK_REALM", "cfo")
KEYCLOAK_CLIENT_ID = os.getenv("KEYCLOAK_CLIENT_ID", "visao")

# URL pública: usada pelo frontend (browser)
# URL interna: usada pelo backend (server-to-server)
TOKEN_URL = f"{KEYCLOAK_INTERNAL_URL}/realms/{KEYCLOAK_REALM}/protocol/openid-connect/token"
USERINFO_URL = f"{KEYCLOAK_INTERNAL_URL}/realms/{KEYCLOAK_REALM}/protocol/openid-connect/userinfo"


class SSOCallbackRequest(BaseModel):
    code: str
    redirect_uri: str


@router.get("/config")
def sso_config():
    """Retorna configuração pública do SSO para o frontend."""
    if not KEYCLOAK_URL or not KEYCLOAK_CLIENT_ID:
        raise HTTPException(status_code=404, detail="SSO não configurado")
    return {
        "auth_url": f"{KEYCLOAK_URL}/realms/{KEYCLOAK_REALM}/protocol/openid-connect/auth",
        "client_id": KEYCLOAK_CLIENT_ID,
        "realm": KEYCLOAK_REALM,
    }


@router.post("/callback", response_model=LoginResponse)
def sso_callback(data: SSOCallbackRequest, db: Session = Depends(get_db)):
    """Troca o authorization code do Keycloak por um token local."""
    # 1. Trocar code por access_token no Keycloak
    try:
        resp = http_client.post(TOKEN_URL, data={
            "grant_type": "authorization_code",
            "client_id": KEYCLOAK_CLIENT_ID,
            "code": data.code,
            "redirect_uri": data.redirect_uri,
        }, timeout=10)
    except http_client.RequestException as e:
        logger.error(f"SSO: erro ao conectar ao Keycloak: {e}")
        raise HTTPException(status_code=502, detail="Não foi possível conectar ao servidor de autenticação.")

    if resp.status_code != 200:
        logger.warning(f"SSO: Keycloak retornou {resp.status_code}: {resp.text[:300]}")
        raise HTTPException(status_code=401, detail="Código de autorização inválido ou expirado.")

    kc_tokens = resp.json()
    kc_access_token = kc_tokens.get("access_token")
    if not kc_access_token:
        raise HTTPException(status_code=502, detail="Resposta inválida do servidor de autenticação.")

    # 2. Buscar informações do usuário no Keycloak
    try:
        userinfo_resp = http_client.get(USERINFO_URL, headers={
            "Authorization": f"Bearer {kc_access_token}"
        }, timeout=10)
    except http_client.RequestException as e:
        logger.error(f"SSO: erro ao buscar userinfo: {e}")
        raise HTTPException(status_code=502, detail="Erro ao obter dados do usuário.")

    if userinfo_resp.status_code != 200:
        raise HTTPException(status_code=401, detail="Token do Keycloak inválido.")

    userinfo = userinfo_resp.json()
    email = userinfo.get("email", "").lower().strip()
    username = userinfo.get("preferred_username", "").strip()
    full_name = userinfo.get("name", "").strip()

    if not email:
        raise HTTPException(status_code=400, detail="E-mail não disponível no perfil do Keycloak.")

    # 3. Buscar ou criar usuário local
    user = db.query(User).filter(User.email == email).first()
    if not user:
        # Tenta por username
        user = db.query(User).filter(User.username == username).first()

    if not user:
        # Cria usuário automaticamente via SSO
        user = User(
            username=username or email.split("@")[0],
            email=email,
            full_name=full_name or username,
            hashed_password=get_password_hash(os.urandom(32).hex()),
            is_active=True,
            is_superuser=False,
        )
        db.add(user)
        db.commit()
        db.refresh(user)
        logger.info(f"SSO: novo usuário criado via Keycloak: {email}")
    elif not user.is_active:
        raise HTTPException(status_code=403, detail="Usuário inativo. Contacte o administrador.")

    # 4. Emitir JWT local
    access_token = create_access_token(
        data={"sub": user.username},
        expires_delta=timedelta(minutes=30),
    )

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
            "is_superuser": user.is_superuser,
        },
    )
