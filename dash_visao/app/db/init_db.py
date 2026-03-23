from sqlalchemy.orm import Session
from app.core.security import get_password_hash
from app.models.user import User, Role, Permission

def init_db(db: Session) -> None:
    # Criar roles padrão
    admin_role = Role(
        name="admin",
        description="Administrador do sistema"
    )
    user_role = Role(
        name="user",
        description="Usuário comum"
    )
    db.add(admin_role)
    db.add(user_role)
    db.commit()
    db.refresh(admin_role)
    db.refresh(user_role)

    # Criar permissões padrão
    permissions = [
        Permission(name="create_user", description="Criar usuários", role_id=admin_role.id),
        Permission(name="read_user", description="Ler usuários", role_id=admin_role.id),
        Permission(name="update_user", description="Atualizar usuários", role_id=admin_role.id),
        Permission(name="delete_user", description="Deletar usuários", role_id=admin_role.id),
        Permission(name="read_dashboard", description="Acessar dashboard", role_id=user_role.id),
    ]
    for permission in permissions:
        db.add(permission)
    db.commit()

    # Criar usuário admin padrão
    admin_user = User(
        email="admin@example.com",
        username="admin",
        hashed_password=get_password_hash("admin123"),
        full_name="Administrador",
        is_active=True,
        is_superuser=True,
    )
    admin_user.roles.append(admin_role)
    db.add(admin_user)
    db.commit()

    # Criar usuário padrão
    user = User(
        email="user@example.com",
        username="user",
        hashed_password=get_password_hash("user123"),
        full_name="Usuário Padrão",
        is_active=True,
        is_superuser=False,
    )
    user.roles.append(user_role)
    db.add(user)
    db.commit() 