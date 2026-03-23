from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session, joinedload
from typing import List
from ..database import get_db
from ..models.menu import MenuItem
from ..schemas.menu import MenuItemResponse

router = APIRouter()

@router.get("/menu-items", response_model=List[MenuItemResponse], tags=["menu"])
def get_menu_items(db: Session = Depends(get_db)):
    """
    Get all menu items from the database.
    This endpoint is public and does not require authentication.
    """
    # Retorna apenas menus de topo, cada um já com seus children (submenus)
    menu_items = db.query(MenuItem).options(joinedload(MenuItem.children)).filter(MenuItem.disable == False, MenuItem.fk_label == None).all()
    return menu_items 