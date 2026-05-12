"""
Contatos de Fiscalização - Lista editável de coordenadores/contatos dos CROs.
Visualização: view_consulta_fiscalizacao
Edição: edit_consulta_fiscalizacao ou manage_users (admin)
"""
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from pydantic import BaseModel
from typing import Optional, List
from ..database import get_db
from ..models import User
from ..models.contato_fiscalizacao import ContatoFiscalizacao
from ..core.auth import check_permission, check_any_permission

router = APIRouter(prefix="/contatos-fiscalizacao", tags=["contatos-fiscalizacao"])


class ContatoResponse(BaseModel):
    id: int
    cro: str
    nome: str
    email: Optional[str] = None
    telefone_contato: Optional[str] = None
    telefone_whatsapp: Optional[str] = None

    class Config:
        from_attributes = True


class ContatoCreate(BaseModel):
    cro: str
    nome: str
    email: Optional[str] = None
    telefone_contato: Optional[str] = None
    telefone_whatsapp: Optional[str] = None


class ContatoUpdate(BaseModel):
    cro: Optional[str] = None
    nome: Optional[str] = None
    email: Optional[str] = None
    telefone_contato: Optional[str] = None
    telefone_whatsapp: Optional[str] = None


# Dados iniciais (seed)
CONTATOS_SEED = [
    {"cro": "AC", "nome": "Priscila Maia de Souza", "email": "fiscalizacao.croac@gmail.com", "telefone_contato": "(68) 99971-5295", "telefone_whatsapp": "(68) 99971-5295"},
    {"cro": "AL", "nome": "Arthur Eric Costa Wanderley", "email": "fiscalizacao@croal.org.br", "telefone_contato": "(82) 98880-7800", "telefone_whatsapp": "(82) 98880-7800"},
    {"cro": "AM", "nome": "Cristiane Zaranza Maquiné", "email": "fiscalizacao@croam.org.br", "telefone_contato": "(92) 3622-7109", "telefone_whatsapp": "(92) 99618-8148"},
    {"cro": "AP", "nome": "Nidaulino Ferreira Távora", "email": "fiscalizacao@croap.org.br", "telefone_contato": "(96) 3223-9409", "telefone_whatsapp": "(96) 98414-9773"},
    {"cro": "BA", "nome": "Naiadja De Santana Cerqueira", "email": "naiadja.cerqueira@croba.org.br", "telefone_contato": "(75) 99135-7177", "telefone_whatsapp": "(75) 99135-7177"},
    {"cro": "CE", "nome": "Ilana Mara Barbosa de Oliveira", "email": "ilana@cro-ce.org.br", "telefone_contato": "(85) 2222-0610", "telefone_whatsapp": "(85) 98819-8023"},
    {"cro": "DF", "nome": "Paloma Alves Souza de Jesus", "email": "fiscalizacao@cro-df.org.br", "telefone_contato": "(61) 3035-1888", "telefone_whatsapp": "(61) 99909-6075"},
    {"cro": "ES", "nome": "Vanderson Luiz Costa", "email": "vanderson@croes.org.br", "telefone_contato": "(27) 3022-4750", "telefone_whatsapp": "(27) 99648-8457"},
    {"cro": "GO", "nome": "Aline da Silva Santos", "email": "fiscalizacao@crogo.org.br", "telefone_contato": "(62) 4006-7509", "telefone_whatsapp": "(62) 98134-6589"},
    {"cro": "MA", "nome": "Leilza Cardoso da Paz", "email": "supervisao.fiscalizacao@croma.org.br", "telefone_contato": "(98) 3227-1920", "telefone_whatsapp": "(98) 98334-0807"},
    {"cro": "MG", "nome": "Breno Costa", "email": "breno.costa@cromg.org.br", "telefone_contato": "(31) 99951-9791", "telefone_whatsapp": "(31) 99951-9791"},
    {"cro": "MS", "nome": "Elenilda Ribeiro Dourado", "email": "fiscaldourados@croms.org.br", "telefone_contato": "(67) 99955-9422", "telefone_whatsapp": "(67) 99955-9422"},
    {"cro": "MT", "nome": "Gilmar Pereira Batista", "email": "supervisorfiscal@cromt.org.br", "telefone_contato": "(65) 99913-2416", "telefone_whatsapp": "(65) 99913-2416"},
    {"cro": "PA", "nome": "Renato Batista Neri", "email": "fiscalizacaogeral@cropa.org.br", "telefone_contato": "(91) 3205-1608", "telefone_whatsapp": "(91) 99392-2622"},
    {"cro": "PB", "nome": "Cariles Silva de Oliveira", "email": "fiscalizacao@cropb.org.br", "telefone_contato": "(83) 3513-0202", "telefone_whatsapp": "(83) 98680-0112"},
    {"cro": "PE", "nome": "Juliana Rafaelle couto Silva Fonseca", "email": "juliana.couto@cro-pe.org.br", "telefone_contato": "(81) 99164-8611", "telefone_whatsapp": "(81) 99164-8611"},
    {"cro": "PI", "nome": "Francisco Xavier Pereira Filho", "email": "franciscoxavier2018x@gmail.com", "telefone_contato": "(89) 99405-3894", "telefone_whatsapp": "(89) 99405-3894"},
    {"cro": "PR", "nome": "Daniele Costa Bocheko", "email": "etica2@cropr.org.br", "telefone_contato": "(41) 3025-9500", "telefone_whatsapp": "(41) 99951-4247"},
    {"cro": "RJ", "nome": "Daniel Pignatari Mahet Rodrigues", "email": "daniel.pignatari@cro-rj.org.br", "telefone_contato": "(21) 99248-7867", "telefone_whatsapp": "(21) 99248-7867"},
    {"cro": "RN", "nome": "Francisco Damião Alves Leite", "email": "fiscalizacao@crorn.org.br", "telefone_contato": "(84) 99999-7140", "telefone_whatsapp": "(84) 98186-8318"},
    {"cro": "RO", "nome": "Lorran Michel Azuim Bergamo de Lima", "email": "lorranlimaodontolegal@gmail.com", "telefone_contato": "(69) 99224-5460", "telefone_whatsapp": "(69) 99601-0010"},
    {"cro": "RR", "nome": "Marilene de Sousa Lima", "email": "administracao@crorr.org.br", "telefone_contato": "(95) 3224-7288", "telefone_whatsapp": "(95) 99169-2066"},
    {"cro": "RS", "nome": "Julio Cesar Sanfelice", "email": "juliosan@crors.org.br", "telefone_contato": "(51) 3026-1784", "telefone_whatsapp": "(51) 99644-9391"},
    {"cro": "SC", "nome": "Flademir Adauto Da Silva", "email": "flademir@crosc.org.br", "telefone_contato": "(49) 3323-0301", "telefone_whatsapp": ""},
    {"cro": "SE", "nome": "Ana Claudia Conceição Correia Nascimento", "email": "fiscalizacao@crose.org.br", "telefone_contato": "(79) 9882-2283", "telefone_whatsapp": "(79) 9882-2283"},
    {"cro": "SP", "nome": "Claudia Santi Cardoso Garrido", "email": "claudia.garrido@crosp.org.br", "telefone_contato": "(11) 3549-5558", "telefone_whatsapp": "(11) 99631-2257"},
    {"cro": "TO", "nome": "Ângelo Taverny", "email": "fiscalizacao@croto.org.br", "telefone_contato": "(63) 3214-4335", "telefone_whatsapp": "(63) 9995-3377"},
]


def seed_contatos(db: Session):
    """Popula tabela com dados iniciais se estiver vazia."""
    count = db.query(ContatoFiscalizacao).count()
    if count == 0:
        for c in CONTATOS_SEED:
            db.add(ContatoFiscalizacao(**c))
        db.commit()


@router.get("", response_model=List[ContatoResponse])
def list_contatos(
    cro: Optional[str] = None,
    current_user: User = Depends(check_permission("view_consulta_fiscalizacao")),
    db: Session = Depends(get_db),
):
    """Lista contatos de fiscalização. Faz seed na primeira chamada."""
    seed_contatos(db)
    query = db.query(ContatoFiscalizacao)
    if cro:
        query = query.filter(ContatoFiscalizacao.cro == cro.upper())
    return query.order_by(ContatoFiscalizacao.cro).all()


@router.post("", response_model=ContatoResponse, status_code=201)
def create_contato(
    data: ContatoCreate,
    current_user: User = Depends(check_any_permission(["edit_consulta_fiscalizacao", "manage_users"])),
    db: Session = Depends(get_db),
):
    """Cria um novo contato de fiscalização."""
    contato = ContatoFiscalizacao(
        cro=data.cro.upper().strip(),
        nome=data.nome.strip(),
        email=data.email.strip() if data.email else None,
        telefone_contato=data.telefone_contato.strip() if data.telefone_contato else None,
        telefone_whatsapp=data.telefone_whatsapp.strip() if data.telefone_whatsapp else None,
    )
    db.add(contato)
    db.commit()
    db.refresh(contato)
    return contato


@router.put("/{contato_id}", response_model=ContatoResponse)
def update_contato(
    contato_id: int,
    data: ContatoUpdate,
    current_user: User = Depends(check_any_permission(["edit_consulta_fiscalizacao", "manage_users"])),
    db: Session = Depends(get_db),
):
    """Atualiza um contato de fiscalização."""
    contato = db.query(ContatoFiscalizacao).filter(ContatoFiscalizacao.id == contato_id).first()
    if not contato:
        raise HTTPException(status_code=404, detail="Contato não encontrado")

    if data.cro is not None:
        contato.cro = data.cro.upper().strip()
    if data.nome is not None:
        contato.nome = data.nome.strip()
    if data.email is not None:
        contato.email = data.email.strip()
    if data.telefone_contato is not None:
        contato.telefone_contato = data.telefone_contato.strip()
    if data.telefone_whatsapp is not None:
        contato.telefone_whatsapp = data.telefone_whatsapp.strip()

    db.commit()
    db.refresh(contato)
    return contato


@router.delete("/{contato_id}", status_code=204)
def delete_contato(
    contato_id: int,
    current_user: User = Depends(check_any_permission(["edit_consulta_fiscalizacao", "manage_users"])),
    db: Session = Depends(get_db),
):
    """Remove um contato de fiscalização."""
    contato = db.query(ContatoFiscalizacao).filter(ContatoFiscalizacao.id == contato_id).first()
    if not contato:
        raise HTTPException(status_code=404, detail="Contato não encontrado")
    db.delete(contato)
    db.commit()
