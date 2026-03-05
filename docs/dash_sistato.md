# Dash Sistato

Sistema de auditoria com controle de permissões dinâmicas, menus flexíveis e autenticação JWT para CFO e 27 CROs.

## Características principais

- **Permissões granulares**: view, edit, delete, export, approve, manage
- **Roles múltiplos**: usuários com vários perfis
- **Menus dinâmicos**: interface conforme permissões
- **Stack**: Backend FastAPI + PostgreSQL + SQLAlchemy; Frontend React + TypeScript + Material-UI; autenticação JWT

## Containers e portas

| Serviço   | Container               | Porta host | Porta container |
|-----------|-------------------------|------------|------------------|
| Frontend  | dash_sistato-frontend-1 | 5174       | 5173             |
| Backend   | dash_sistato-backend-1  | 8002       | 8000             |
| PostgreSQL| dash_sistato-db-1       | 5434       | 5432             |

- **Acesso ao frontend:** http://localhost:5174  
- **API:** http://localhost:8002  

## Subir com Docker

```powershell
cd dash_sistato
docker-compose up -d --build
```

Variáveis de ambiente do backend em `backend/.env`. O banco é criado automaticamente; usuário admin de exemplo: `admin@cfo.gov.br` / `admin123`.

## Desenvolvimento local (sem Docker)

**Backend:**

```bash
cd backend
pip install -r requirements.txt
uvicorn app.main:app --reload
```

**Frontend:**

```bash
cd frontend
npm install
npm run dev
```

## Estrutura do repositório

```
dash_sistato/
├── backend/     # FastAPI, SQLAlchemy
├── frontend/    # React, Vite, Material-UI
├── docker-compose.yml
└── README.md
```

## Documentação adicional

- Detalhes de permissões, APIs e banco: ver `dash_sistato/README.md`
- Visão geral dos containers: [Containers e Serviços](./containers-servicos.md)
