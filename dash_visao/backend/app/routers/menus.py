from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session, joinedload
from pydantic import BaseModel
from typing import List
from ..database import get_db
from ..models import Menu, SubMenu, User
from ..schemas.permission import MenuCreate, MenuUpdate, Menu as MenuSchema, SubMenuCreate, SubMenuUpdate, SubMenu as SubMenuSchema, MenuResponse
from ..core.auth import get_current_active_user, check_permission

router = APIRouter(prefix="/menus", tags=["menus"])


class ReorderItem(BaseModel):
    id: int
    order: int


@router.get("/", response_model=List[MenuResponse])
def get_menus(
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista todos os menus de topo com submenus aninhados"""
    menus = db.query(Menu).options(joinedload(Menu.submenus)).order_by(Menu.order).all()
    return menus

@router.post("/", response_model=MenuSchema)
def create_menu(
    menu: MenuCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Cria um novo menu"""
    db_menu = Menu(**menu.dict())
    db.add(db_menu)
    db.commit()
    db.refresh(db_menu)
    return db_menu


# Endpoints de reordenação (DEVEM vir antes de /{menu_id}/ para evitar conflito de rota)
@router.put("/reorder/")
def reorder_menus(
    items: List[ReorderItem],
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Reordena menus principais (batch update de order)"""
    for item in items:
        db_menu = db.query(Menu).filter(Menu.id == item.id).first()
        if db_menu:
            db_menu.order = item.order
    db.commit()
    return {"message": "Menus reordenados com sucesso"}


@router.get("/{menu_id}/", response_model=MenuSchema)
def get_menu(
    menu_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Obtém um menu específico"""
    menu = db.query(Menu).filter(Menu.id == menu_id).first()
    if menu is None:
        raise HTTPException(status_code=404, detail="Menu not found")
    return menu

@router.put("/{menu_id}/", response_model=MenuSchema)
def update_menu(
    menu_id: int,
    menu: MenuUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Atualiza um menu"""
    db_menu = db.query(Menu).filter(Menu.id == menu_id).first()
    if db_menu is None:
        raise HTTPException(status_code=404, detail="Menu not found")
    
    for field, value in menu.dict(exclude_unset=True).items():
        setattr(db_menu, field, value)
    
    db.commit()
    db.refresh(db_menu)
    return db_menu

@router.delete("/{menu_id}/")
def delete_menu(
    menu_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Deleta um menu"""
    db_menu = db.query(Menu).filter(Menu.id == menu_id).first()
    if db_menu is None:
        raise HTTPException(status_code=404, detail="Menu not found")
    
    db.delete(db_menu)
    db.commit()
    return {"message": "Menu deleted successfully"}

# Submenus
@router.get("/{menu_id}/submenus/", response_model=List[SubMenuSchema])
def get_submenus(
    menu_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Lista todos os submenus de um menu"""
    submenus = db.query(SubMenu).filter(SubMenu.menu_id == menu_id).all()
    return submenus

@router.post("/{menu_id}/submenus/", response_model=SubMenuSchema)
def create_submenu(
    menu_id: int,
    submenu: SubMenuCreate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Cria um novo submenu"""
    # Verifica se o menu existe
    menu = db.query(Menu).filter(Menu.id == menu_id).first()
    if menu is None:
        raise HTTPException(status_code=404, detail="Menu not found")

    # Remove menu_id do submenu se estiver presente, pois já temos da URL
    submenu_data = submenu.dict()
    submenu_data.pop("menu_id", None)  # Remove se existir
    submenu_data["menu_id"] = menu_id  # Usa o menu_id da URL

    db_submenu = SubMenu(**submenu_data)
    db.add(db_submenu)
    db.commit()
    db.refresh(db_submenu)
    return db_submenu


@router.put("/{menu_id}/submenus/reorder/")
def reorder_submenus(
    menu_id: int,
    items: List[ReorderItem],
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Reordena submenus de um menu (batch update de order)"""
    menu = db.query(Menu).filter(Menu.id == menu_id).first()
    if menu is None:
        raise HTTPException(status_code=404, detail="Menu not found")

    for item in items:
        db_submenu = db.query(SubMenu).filter(SubMenu.id == item.id, SubMenu.menu_id == menu_id).first()
        if db_submenu:
            db_submenu.order = item.order
    db.commit()
    return {"message": "Submenus reordenados com sucesso"}


@router.get("/submenus/{submenu_id}/", response_model=SubMenuSchema)
def get_submenu(
    submenu_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(get_current_active_user)
):
    """Obtém um submenu específico"""
    submenu = db.query(SubMenu).filter(SubMenu.id == submenu_id).first()
    if submenu is None:
        raise HTTPException(status_code=404, detail="Submenu not found")
    return submenu

@router.put("/submenus/{submenu_id}/", response_model=SubMenuSchema)
def update_submenu(
    submenu_id: int,
    submenu: SubMenuUpdate,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Atualiza um submenu"""
    db_submenu = db.query(SubMenu).filter(SubMenu.id == submenu_id).first()
    if db_submenu is None:
        raise HTTPException(status_code=404, detail="Submenu not found")
    
    for field, value in submenu.dict(exclude_unset=True).items():
        setattr(db_submenu, field, value)
    
    db.commit()
    db.refresh(db_submenu)
    return db_submenu

@router.delete("/submenus/{submenu_id}/")
def delete_submenu(
    submenu_id: int,
    db: Session = Depends(get_db),
    current_user: User = Depends(check_permission("manage_menus"))
):
    """Deleta um submenu"""
    db_submenu = db.query(SubMenu).filter(SubMenu.id == submenu_id).first()
    if db_submenu is None:
        raise HTTPException(status_code=404, detail="Submenu not found")

    db.delete(db_submenu)
    db.commit()
    return {"message": "Submenu deleted successfully"} 