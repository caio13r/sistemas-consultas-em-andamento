from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from typing import List
from ..database import get_db
from ..models import Menu, SubMenu, User, Permission
from ..schemas.permission import MenuResponse, SubMenuResponse, UserPermissionsResponse
from ..core.auth import get_current_active_user, get_user_permissions, get_user_roles

router = APIRouter(prefix="/menu", tags=["dynamic-menu"])

@router.get("/", response_model=UserPermissionsResponse)
def get_user_menu(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Obtém o menu dinâmico baseado nas permissões do usuário"""
    
    # Obtém as permissões e roles do usuário
    user_permissions = get_user_permissions(db, current_user.id)
    user_roles = get_user_roles(db, current_user.id)
    
    # Se for superuser, retorna todos os menus
    if current_user.is_superuser:
        menus = db.query(Menu).filter(Menu.disable == False).order_by(Menu.order).all()
        menu_response = []
        
        for menu in menus:
            submenus = db.query(SubMenu).filter(
                SubMenu.menu_id == menu.id,
                SubMenu.disable == False
            ).order_by(SubMenu.order).all()
            
            submenu_response = [
                SubMenuResponse(
                    id=submenu.id,
                    name=submenu.name,
                    url=submenu.url,
                    order=submenu.order
                )
                for submenu in submenus
            ]
            
            menu_response.append(MenuResponse(
                id=menu.id,
                name=menu.name,
                url=menu.url,
                icon=menu.icon,
                order=menu.order,
                submenus=submenu_response
            ))
        
        return UserPermissionsResponse(
            permissions=user_permissions,
            roles=user_roles,
            menu=menu_response
        )
    
    # Para usuários normais, filtra por permissões
    accessible_menus = []
    
    # Busca todos os menus ativos
    all_menus = db.query(Menu).filter(Menu.disable == False).order_by(Menu.order).all()
    
    for menu in all_menus:
        # Verifica se o usuário tem permissão para ver este menu
        menu_permissions = db.query(Permission).filter(
            Permission.menu_id == menu.id,
            Permission.action == "view"
        ).all()
        
        has_menu_access = False
        if not menu_permissions:  # Se não há permissões específicas, permite acesso
            has_menu_access = True
        else:
            for perm in menu_permissions:
                if perm.name in user_permissions:
                    has_menu_access = True
                    break
        
        if has_menu_access:
            # Busca submenus acessíveis
            accessible_submenus = []
            submenus = db.query(SubMenu).filter(
                SubMenu.menu_id == menu.id,
                SubMenu.disable == False
            ).order_by(SubMenu.order).all()
            
            for submenu in submenus:
                # Verifica se o usuário tem permissão para ver este submenu
                submenu_permissions = db.query(Permission).filter(
                    Permission.submenu_id == submenu.id,
                    Permission.action == "view"
                ).all()
                
                has_submenu_access = False
                if not submenu_permissions:  # Se não há permissões específicas, permite acesso
                    has_submenu_access = True
                else:
                    for perm in submenu_permissions:
                        if perm.name in user_permissions:
                            has_submenu_access = True
                            break
                
                if has_submenu_access:
                    accessible_submenus.append(SubMenuResponse(
                        id=submenu.id,
                        name=submenu.name,
                        url=submenu.url,
                        order=submenu.order
                    ))
            
            # Só adiciona o menu se tiver pelo menos um submenu acessível ou se não tiver submenus
            if accessible_submenus or not submenus:
                accessible_menus.append(MenuResponse(
                    id=menu.id,
                    name=menu.name,
                    url=menu.url,
                    icon=menu.icon,
                    order=menu.order,
                    submenus=accessible_submenus
                ))
    
    return UserPermissionsResponse(
        permissions=user_permissions,
        roles=user_roles,
        menu=accessible_menus
    )

@router.get("/check-permission/{permission_name}")
def check_specific_permission(
    permission_name: str,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Verifica se o usuário tem uma permissão específica"""
    if current_user.is_superuser:
        return {"has_permission": True}
    
    user_permissions = get_user_permissions(db, current_user.id)
    has_permission = permission_name in user_permissions
    
    return {
        "has_permission": has_permission,
        "permission_name": permission_name,
        "user_permissions": user_permissions
    }

@router.get("/check-any-permission")
def check_any_permission(
    permissions: str,  # Lista de permissões separadas por vírgula
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Verifica se o usuário tem pelo menos uma das permissões especificadas"""
    if current_user.is_superuser:
        return {"has_permission": True}
    
    permission_list = [p.strip() for p in permissions.split(",")]
    user_permissions = get_user_permissions(db, current_user.id)
    
    has_any_permission = any(perm in user_permissions for perm in permission_list)
    
    return {
        "has_permission": has_any_permission,
        "required_permissions": permission_list,
        "user_permissions": user_permissions
    } 