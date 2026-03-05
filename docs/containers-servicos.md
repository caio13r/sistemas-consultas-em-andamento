# Containers e Serviços

Este documento descreve como subir e gerenciar os containers Docker do projeto dashsisato e lista portas e status dos serviços.

## Resumo dos serviços

### Dash Sistato

| Serviço      | URL / Porta           | Descrição                    |
|-------------|------------------------|------------------------------|
| Frontend    | http://localhost:5174 | Interface React (Vite)       |
| Backend API | http://localhost:8002 | API FastAPI                  |
| PostgreSQL  | localhost:5434        | Banco de dados               |

### Sistema de Consultas

| Serviço   | URL / Porta    | Descrição        |
|-----------|-----------------|------------------|
| App PHP   | http://localhost:8082 | Aplicação PHP (Apache/Nginx) |
| Redis     | localhost:6379  | Cache/sessões (senha: `teste123`) |

> **Nota:** A aplicação do sistema-consultas usa a porta **8082** (em vez de 8080) para evitar conflito com outros projetos na máquina.

---

## Como subir os containers

### Windows (PowerShell)

Use `;` para encadear comandos (não use `&&` em versões antigas do PowerShell).

**Dash Sistato:**

```powershell
cd c:\Users\style\Documents\dashsisato\dash_sistato
docker-compose up -d --build
```

**Sistema de Consultas:**

```powershell
cd c:\Users\style\Documents\dashsisato\sistema-consultas
docker-compose up -d --build
```

### Linux / macOS (bash)

```bash
# Dash Sistato
cd dash_sistato
docker-compose up -d --build

# Sistema de Consultas
cd sistema-consultas
docker-compose up -d --build
```

---

## Verificar status

Listar containers em execução:

```bash
docker ps
```

Apenas os projetos dashsisato:

```bash
docker ps --filter "name=dash_sistato" --filter "name=sistema-consultas"
```

---

## Parar os serviços

**Dash Sistato:**

```powershell
cd dash_sistato
docker-compose down
```

**Sistema de Consultas:**

```powershell
cd sistema-consultas
docker-compose down
```

---

## Conflitos de porta

Se alguma porta já estiver em uso:

- **8080** – Usada pelo sistema-consultas por padrão; no projeto está configurada como **8082** no `docker-compose.yml`.
- **5434** – PostgreSQL do dash_sistato.
- **6379** – Redis do sistema-consultas. Outros Redis no mesmo host podem usar 6380, 6381, etc.

Ajustes de portas são feitos nos arquivos `docker-compose.yml` de cada subprojeto.

---

## Requisitos

- Docker Desktop (ou Docker Engine + Docker Compose) instalado e em execução.
- Para build do **sistema-consultas**, a primeira execução pode levar vários minutos (build da imagem PHP com extensões).

Ver também: [README da documentação](./README.md) | [Dash Sistato](./dash_sistato.md) | [Sistema de Consultas](./sistema-consultas.md)
