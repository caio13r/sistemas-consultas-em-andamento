# Documentação do Projeto Dashsisato

Repositório que reúne dois sistemas principais: **dash_sistato** (auditoria com permissões) e **sistema-consultas** (consultas integradas em PHP).

## Índice da documentação

| Documento | Descrição |
|----------|-----------|
| [Containers e Serviços](./containers-servicos.md) | Como subir os containers, portas e status dos serviços |
| [Dash Sistato](./dash_sistato.md) | Sistema de auditoria (FastAPI + React + PostgreSQL) |
| [Sistema de Consultas](./sistema-consultas.md) | API de consultas em PHP com Redis |

## Estrutura do repositório

```
dashsisato/
├── dash_sistato/          # Sistema de auditoria (backend FastAPI, frontend React)
├── sistema-consultas/     # Sistema de consultas (PHP + Redis)
└── docs/                  # Documentação (esta pasta)
```

## Pré-requisitos

- **Docker** e **Docker Compose**
- Para desenvolvimento local: Node.js 16+, Python 3.8+ (conforme cada subprojeto)

## Início rápido

1. **Subir todos os containers**  
   Ver [Containers e Serviços](./containers-servicos.md#como-subir-os-containers).

2. **Acessar os sistemas**
   - Dash Sistato (frontend): http://localhost:5174  
   - Sistema de Consultas: http://localhost:8082  

---

*Documentação gerada para o projeto dashsisato.*
