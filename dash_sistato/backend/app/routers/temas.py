from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List
from ..database import get_db
from ..models import Tema
from ..schemas.tema import TemaCreate, TemaUpdate, TemaResponse
from ..core.auth import get_current_user

router = APIRouter(
    prefix="/temas",
    tags=["temas"],
    dependencies=[Depends(get_current_user)]
)

@router.get("/", response_model=List[TemaResponse])
def get_temas(
    skip: int = 0,
    limit: int = 100,
    db: Session = Depends(get_db)
):
    """Lista todos os temas de auditoria"""
    temas = db.query(Tema).offset(skip).limit(limit).all()
    return temas

@router.post("/", response_model=TemaResponse, status_code=status.HTTP_201_CREATED)
def create_tema(
    tema: TemaCreate,
    db: Session = Depends(get_db)
):
    """Cria um novo tema de auditoria"""
    db_tema = Tema(**tema.dict())
    db.add(db_tema)
    db.commit()
    db.refresh(db_tema)
    return db_tema

@router.get("/{tema_id}", response_model=TemaResponse)
def get_tema(
    tema_id: int,
    db: Session = Depends(get_db)
):
    """Obtém um tema específico por ID"""
    tema = db.query(Tema).filter(Tema.id == tema_id).first()
    if not tema:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tema não encontrado"
        )
    return tema

@router.put("/{tema_id}", response_model=TemaResponse)
def update_tema(
    tema_id: int,
    tema: TemaUpdate,
    db: Session = Depends(get_db)
):
    """Atualiza um tema existente"""
    db_tema = db.query(Tema).filter(Tema.id == tema_id).first()
    if not db_tema:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tema não encontrado"
        )
    
    for key, value in tema.dict(exclude_unset=True).items():
        setattr(db_tema, key, value)
    
    db.commit()
    db.refresh(db_tema)
    return db_tema

@router.delete("/{tema_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_tema(
    tema_id: int,
    db: Session = Depends(get_db)
):
    """Remove um tema"""
    db_tema = db.query(Tema).filter(Tema.id == tema_id).first()
    if not db_tema:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Tema não encontrado"
        )
    
    db.delete(db_tema)
    db.commit()
    return None 