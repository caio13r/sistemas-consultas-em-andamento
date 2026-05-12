# Documentacao do Sistema - Administracao

## Visao Geral

Este repositorio contem dois sistemas principais desenvolvidos para a **CFO (Coordenacao de Fiscalizacao de Orgaos)** e os **27 CROs (Conselhos Regionais)**:

1. **dash_visao** - Sistema moderno de dashboard com gestao de usuarios, permissoes dinamicas, auditorias e consultas
2. **sistema-consultas** - Sistema legado em PHP para consultas integradas e relatorios

---

## Arquitetura Geral

```
                    +-------------------+
                    |    Navegador      |
                    +--------+----------+
                             |
              +--------------+--------------+
              |                             |
    +---------v----------+       +----------v---------+
    |  Frontend React    |       |  Sistema PHP       |
    |  (Vite + MUI)      |       |  (Apache)          |
    |  Porta: 5174       |       |  Porta: 8082       |
    +---------+----------+       +----------+----------+
              |                             |
    +---------v----------+                  |
    |  Backend FastAPI   |                  |
    |  Porta: 8002       |                  |
    +---------+----------+                  |
              |                             |
    +---------v-----------------------------v----------+
    |              Camada de Dados                     |
    |                                                  |
    |  PostgreSQL (appdb)     - Porta 5434             |
    |  MySQL (Locaweb)        - db_sistema_consultas   |
    |  MySQL (WSCFO)          - Webservice SISCAF      |
    |  SQL Server (Implanta)  - CFO_CWS                |
    |  MySQL                  - db_prescricao          |
    |  MySQL                  - identity_professional  |
    |  Redis                  - Cache/Sessoes          |
    +--------------------------------------------------+
```

---

## Tecnologias Utilizadas

### Backend (dash_visao)

| Tecnologia | Versao | Finalidade |
|------------|--------|------------|
| Python | 3.11 | Linguagem principal |
| FastAPI | 0.104.1 | Framework web/API REST |
| Uvicorn | 0.24.0 | Servidor ASGI |
| SQLAlchemy | 2.0.23 | ORM (mapeamento objeto-relacional) |
| Alembic | 1.12.1 | Migracoes de banco de dados |
| Pydantic | 2.5.2 | Validacao de dados e schemas |
| Python-jose | - | Geracao e validacao de JWT |
| Passlib + bcrypt | - | Hash de senhas |
| Redis | 5.0.1 | Cache e filas |
| Celery | 5.3.6 | Tarefas assincronas |

### Frontend (dash_visao)

| Tecnologia | Versao | Finalidade |
|------------|--------|------------|
| React | 18.2.0 | Biblioteca de UI |
| TypeScript | 5.2.2 | Tipagem estatica |
| Vite | 5.1.0 | Build tool e dev server |
| Material-UI (MUI) | 5.15.10 | Biblioteca de componentes |
| Tailwind CSS | 3.4.1 | Framework CSS utilitario |
| Axios | 1.6.7 | Cliente HTTP |
| React Router DOM | 6.22.1 | Roteamento SPA |
| Notistack | 3.0.2 | Notificacoes toast |
| Lucide React | 0.344.0 | Icones |
| @dnd-kit | - | Drag and drop |

### Sistema Legado (sistema-consultas)

| Tecnologia | Versao | Finalidade |
|------------|--------|------------|
| PHP | 8.3 | Linguagem principal |
| Apache | 2 | Servidor web |
| Redis | 6.2 | Cache de sessoes e consultas |
| Composer | - | Gerenciador de dependencias |
| ODBC / SQL Server drivers | - | Conexao com SQL Server |

### Infraestrutura

| Tecnologia | Finalidade |
|------------|------------|
| Docker + Docker Compose | Containerizacao e orquestracao |
| PostgreSQL 15 | Banco de dados principal (dash_visao) |
| MySQL | Bancos auxiliares (4 instancias) |
| SQL Server | Banco Implanta (CFO_CWS) |
| Nginx | Proxy reverso do frontend |

---

## Bancos de Dados

O sistema utiliza **6 bancos de dados** diferentes para atender a diferentes fontes de informacao:

### 1. PostgreSQL - `appdb` (Banco Principal)

- **Porta**: 5434 (host) / 5432 (container)
- **Credenciais**: postgres/postgres
- **Finalidade**: Armazena todos os dados da aplicacao dash_visao
- **Tabelas principais**:
  - `users` - Usuarios do sistema
  - `roles` - Papeis/cargos
  - `permissions` - Permissoes granulares
  - `role_permissions` - Associacao papel-permissao
  - `user_roles` - Associacao usuario-papel
  - `user_permissions` - Permissoes diretas do usuario
  - `menus` / `submenus` - Navegacao dinamica
  - `audits` - Auditorias
  - `themes` - Temas de auditoria
  - `documents` - Documentos enviados
  - `activity_logs` - Log de atividades HTTP
  - `change_logs` - Log de alteracoes em entidades
  - `user_requests` - Solicitacoes de acesso
  - `servicos` - Servicos disponiveis
  - `password_reset_tokens` - Tokens de redefinicao de senha

### 2. MySQL (Locaweb) - `db_sistema_consultas`
- **Finalidade**: Dados de auditoria RFB, rotulos e cadastro

### 3. MySQL (WSCFO) - Webservice SISCAF
- **Finalidade**: Dados do webservice SISCAF

### 4. SQL Server (Implanta) - `CFO_CWS`
- **Finalidade**: Consultas integradas, auditoria e fiscalizacao

### 5. MySQL - `db_prescricao`
- **Finalidade**: Dados de prescricao

### 6. MySQL - `identity_professional`
- **Finalidade**: Dados de identidade profissional

---

## Sistema de Autenticacao

### JWT (JSON Web Token)

- **Algoritmo**: HS256
- **Expiracao**: 30 minutos (configuravel via `ACCESS_TOKEN_EXPIRE_MINUTES`)
- **Chave secreta**: Configuravel via variavel de ambiente `SECRET_KEY`
- **Armazenamento**: `localStorage` no navegador
- **Header**: `Authorization: Bearer <token>`

### Fluxo de Login

1. Usuario envia email/username + senha para `POST /api/token`
2. Backend valida credenciais com bcrypt
3. Token JWT e gerado e retornado
4. Frontend armazena token no `localStorage`
5. Todas as requisicoes subsequentes incluem o token no header
6. Em caso de resposta 401, o frontend redireciona para login

### Redefinicao de Senha

- `POST /api/forgot-password` - Envia email com token de reset
- `POST /api/reset-password` - Redefine a senha com o token

---

## Sistema de Permissoes e Papeis

### Modelo de Permissoes

Cada permissao possui:
- **name**: Nome unico (ex: `ver_consulta_integrada`)
- **action**: Tipo de acao (`view`, `edit`, `delete`, `export`, `approve`, `manage`)
- **resource**: Recurso associado
- **scope_type**: Escopo (`global`, `cfo`, `cro`)

### Hierarquia de Papeis (Roles)

Os papeis possuem relacao pai-filho com deteccao de ciclos:
- **Nivel 1**: Visualizacao
- **Nivel 2**: Edicao
- **Nivel 3**: Aprovacao
- **Nivel 4**: Administracao

### Calculo de Permissoes Efetivas

1. Coleta todos os papeis do usuario, incluindo heranca hierarquica
2. Aplica permissoes dos papeis (respeitando campo `granted` para negacoes)
3. Aplica permissoes diretas do usuario como sobrescrita
4. Resultado: `permissoes_concedidas - negadas_por_papel + sobrescritas_diretas`

### Decoradores de Protecao (Backend)

```python
@check_permission("nome_permissao")        # Exige uma permissao
@check_any_permission(["perm1", "perm2"])   # Exige pelo menos uma
@check_all_permissions(["perm1", "perm2"])  # Exige todas
@require_admin                              # Exige superusuario ou papel "Administrador"
```

### Componentes de Protecao (Frontend)

- `<PrivateRoute>` - Exige autenticacao
- `<ProtectedRoute permission="nome">` - Exige permissao especifica
- `<AdminRoute>` - Exige papel de administrador
- `hasPermission()` / `hasAnyPermission()` - Verificacao programatica

---

## Menus Dinamicos

Os menus sao gerados dinamicamente com base nas permissoes do usuario:

- Cada menu/submenu tem uma permissao associada
- A API `GET /api/menu` retorna apenas os itens que o usuario pode acessar
- Suporte a secoes, icones e ordenacao
- Gerenciamento via painel admin (`/admin/menus`)

---

## Modulos e Paginas do Sistema

### Administracao
| Pagina | Rota | Descricao |
|--------|------|-----------|
| Dashboard | `/dashboard` | Painel com estatisticas gerais |
| Usuarios | `/admin/users` | Gestao de usuarios (CRUD) |
| Papeis | `/admin/roles` | Gestao de papeis e hierarquia |
| Permissoes | `/admin/permissions` | Gestao de permissoes granulares |
| Menus | `/admin/menus` | Gerenciamento de menus dinamicos |
| Temas | `/admin/temas` | Temas de auditoria |
| Servicos | `/admin/servicos` | Servicos disponiveis |
| Log de Atividades | `/admin/logs` | Historico de requisicoes HTTP |
| Log de Alteracoes | `/admin/change-logs` | Historico de mudancas em entidades |
| Solicitacoes | `/admin/user-requests` | Aprovacao de pedidos de acesso |

### Consultas
| Pagina | Descricao |
|--------|-----------|
| Consulta Integrada | Consultas cruzadas em multiplos bancos |
| Consulta Identidade | Identidade profissional |
| Consulta RFB | Receita Federal (integracao por certificado) |
| Consulta Fiscalizacao | Dados de fiscalizacao |
| Consulta Auditoria | Dados de auditoria |
| Consulta Estatistica | Relatorios estatisticos |
| Consulta Prescricao | Dados de prescricao |
| Consulta SIGESP | Sistema SIGESP |
| Tabelas Centralizadas | Tabelas de referencia |
| Dados Abertos | Dados publicos |
| Eleicoes Regionais | Dados eleitorais dos CROs |

### Documentos
| Pagina | Descricao |
|--------|-----------|
| Documentos | Navegacao e busca de documentos |
| Upload | Envio de novos documentos |
| Validacao | Validacao de documentos (admin) |

### Relatorios
| Pagina | Descricao |
|--------|-----------|
| Adimplencia | Relatorio de adimplencia |
| Auditoria | Relatorios de auditoria |
| Financeiro | Relatorios financeiros |
| Diversos | Relatorios diversos |

### Acesso Publico (sem autenticacao)
| Pagina | Descricao |
|--------|-----------|
| Login | Autenticacao |
| Solicitar Acesso | Formulario de pedido de acesso |
| Status da Solicitacao | Consulta status do pedido |
| Esqueci a Senha | Recuperacao de senha |

---

## Fluxo de Solicitacao de Acesso

1. Usuario acessa `/solicitar-acesso` e preenche formulario
2. Seleciona servicos e permissoes desejadas
3. Sistema cria registro com status `pendente`
4. Administrador visualiza em `/admin/user-requests`
5. Status pode ser alterado para:
   - `em_analise` - Em analise
   - `aprovado` - Aprovado (usuario e criado automaticamente)
   - `rejeitado` - Rejeitado
   - `esclarecimento` - Solicitacao de mais informacoes

---

## Log e Auditoria

### Activity Log (Log de Atividades)

Middleware automatico que registra todas as requisicoes HTTP:
- Metodo HTTP (GET, POST, etc.)
- Caminho da rota
- Status code da resposta
- Endereco IP do cliente
- User-Agent do navegador
- Duracao da requisicao (ms)
- Detalhes de erro (se houver)
- Usuario autenticado (se houver)

### Change Log (Log de Alteracoes)

Registro de mudancas em entidades do sistema:
- Entidade alterada
- Tipo de operacao (criar, editar, deletar)
- Valores anteriores e novos
- Usuario responsavel
- Data/hora

---

## Endpoints da API (Principais)

### Autenticacao
```
POST   /api/token                    # Login
GET    /api/users/me                 # Usuario atual com permissoes
POST   /api/forgot-password          # Solicitar reset de senha
POST   /api/reset-password           # Redefinir senha
```

### Usuarios
```
GET    /api/users                    # Listar usuarios
POST   /api/users                    # Criar usuario
PATCH  /api/users/me                 # Atualizar perfil
POST   /api/users/{id}/roles         # Atribuir papeis
POST   /api/users/{id}/permissions   # Atribuir permissoes diretas
```

### Permissoes e Papeis
```
GET    /api/permissions              # Listar permissoes
POST   /api/permissions              # Criar permissao
GET    /api/roles                    # Listar papeis
POST   /api/roles                    # Criar papel
```

### Menus
```
GET    /api/menu                     # Menu do usuario (filtrado por permissoes)
GET    /api/menus                    # Listar todos os menus (admin)
POST   /api/menus                    # Criar menu
GET    /api/submenus                 # Listar submenus
POST   /api/submenus                # Criar submenu
```

### Solicitacoes de Acesso
```
GET    /api/user-requests            # Listar solicitacoes
POST   /api/user-requests            # Criar solicitacao
PATCH  /api/user-requests/{id}/status # Atualizar status
```

### Documentos
```
GET    /api/documentos               # Listar documentos
POST   /api/documentos/upload        # Upload de documento
POST   /api/documentos/{id}/validate # Validar documento
```

### Consultas (exemplos)
```
GET    /api/consulta-integrada       # Consulta integrada
GET    /api/consulta-rfb             # Consulta Receita Federal
GET    /api/consulta-fiscalizacao    # Consulta fiscalizacao
GET    /api/consulta-estatistica     # Consulta estatistica
```

### Logs
```
GET    /api/activity-logs            # Log de atividades
```

### Exportacao
```
POST   /api/export/*                 # Exportar dados (CSV/Excel)
```

---

## Configuracao Docker

### Servicos (dash_visao)

```yaml
# docker-compose.yml
services:
  db:          # PostgreSQL 15 - Porta 5434
  backend:     # FastAPI (Python 3.11) - Porta 8002
  frontend:    # React + Nginx - Porta 5174
```

### Servicos (sistema-consultas)

```yaml
# docker-compose.yml
services:
  app:         # PHP 8.3 + Apache - Porta 8082
  redis:       # Redis 6.2 - Porta 6379
```

### Volumes Persistentes

- `postgres_data` - Dados do PostgreSQL
- `./backend:/app` - Codigo backend (hot reload)
- `./frontend:/app` - Codigo frontend (hot reload)

---

## Variaveis de Ambiente

### Backend (`.env`)

| Variavel | Descricao | Valor padrao |
|----------|-----------|--------------|
| `DATABASE_URL` | URL de conexao PostgreSQL | `postgresql://postgres:postgres@db:5432/appdb` |
| `SECRET_KEY` | Chave secreta para JWT | (deve ser alterada em producao) |
| `ALGORITHM` | Algoritmo JWT | `HS256` |
| `ACCESS_TOKEN_EXPIRE_MINUTES` | Expiracao do token | `30` |
| `BACKEND_HOST` | Host do servidor | `0.0.0.0` |
| `BACKEND_PORT` | Porta do servidor | `8000` |
| `REDIS_URL` | URL do Redis | `redis://localhost:6379/0` |
| `SMTP_HOST` | Servidor SMTP | `smtp.gmail.com` |
| `SMTP_PORT` | Porta SMTP | `587` |
| `SMTP_USER` | Email SMTP | (configurar) |
| `SMTP_PASSWORD` | Senha SMTP | (configurar) |
| `LOG_LEVEL` | Nivel de log | `INFO` |
| `ENVIRONMENT` | Ambiente | `development` |
| `DEBUG` | Modo debug | `True` |

### Frontend

| Variavel | Descricao |
|----------|-----------|
| `VITE_API_URL` | URL base da API backend |
| `PROXY_TARGET` | Destino do proxy reverso |

---

## Identidade Visual

### Paleta de Cores

| Cor | Codigo | Uso |
|-----|--------|-----|
| Burgundy (primaria) | `#7A1E26` | Elementos principais, botoes |
| Burgundy claro | `#9A2832` | Hover, destaques |
| Burgundy escuro | `#5C1519` | Texto, sombras |
| Creme (fundo) | `#FBF8F4` | Background geral |
| Dourado (destaque) | `#B88A56` | Acentos, badges |
| Wine (secundaria) | `#635962` | Elementos secundarios |

### Tipografia

| Tipo | Fonte | Uso |
|------|-------|-----|
| Display | Instrument Serif | Titulos e cabecalhos |
| Corpo | Geist / Inter | Textos gerais |
| Monoespaco | JetBrains Mono | Codigo e dados tabulares |

---

## Como Subir o Sistema

### Pre-requisitos

- Docker e Docker Compose instalados
- Node.js 16+ (para desenvolvimento local do frontend)
- Python 3.11+ (para desenvolvimento local do backend)

### Passo a Passo

1. **Clonar o repositorio**
```bash
git clone <url-do-repositorio>
cd sistemas-consultas-em-andamento
```

2. **Subir o dash_visao**
```bash
cd dash_visao
docker-compose up -d
```
Isso inicia: PostgreSQL (5434), Backend FastAPI (8002), Frontend React (5174)

3. **Subir o sistema-consultas**
```bash
cd sistema-consultas
docker-compose up -d
```
Isso inicia: PHP + Apache (8082), Redis (6379)

4. **Acessar os sistemas**
   - Dashboard (dash_visao): http://localhost:5174
   - Sistema de Consultas: http://localhost:8082

### Inicializacao de Dados

Na primeira execucao, o backend dash_visao executa automaticamente:
- `init_db.py` - Cria tabelas e dados iniciais
- `init_menus.py` - Cria menus padrao
- `init_permissions.py` - Cria permissoes padrao
- `init_servicos.py` - Cria servicos padrao

---

## Estrutura de Diretorios

```
sistemas-consultas-em-andamento/
|
+-- dash_visao/
|   +-- backend/
|   |   +-- app/
|   |   |   +-- main.py              # Ponto de entrada FastAPI
|   |   |   +-- database.py          # Configuracao dos 6 bancos
|   |   |   +-- core/
|   |   |   |   +-- auth.py          # Autenticacao JWT + decoradores
|   |   |   |   +-- cors.py          # Configuracao CORS
|   |   |   +-- models/              # 13 modelos SQLAlchemy
|   |   |   +-- routers/             # 30+ routers de API
|   |   |   +-- schemas/             # 8 schemas Pydantic
|   |   |   +-- lib/                 # Middleware e utilitarios
|   |   |   +-- sql/                 # Scripts SQL
|   |   +-- requirements.txt
|   |   +-- Dockerfile
|   |   +-- .env
|   +-- frontend/
|   |   +-- src/
|   |   |   +-- App.tsx              # Componente raiz + tema MUI
|   |   |   +-- contexts/            # AuthContext (estado global)
|   |   |   +-- pages/               # 40+ paginas
|   |   |   +-- components/          # 20+ componentes reutilizaveis
|   |   |   +-- services/            # 7 servicos de API (Axios)
|   |   +-- package.json
|   |   +-- vite.config.ts
|   |   +-- tailwind.config.js
|   |   +-- Dockerfile
|   |   +-- nginx.conf
|   +-- docker-compose.yml
|
+-- sistema-consultas/
|   +-- src/
|   |   +-- public/index.php          # Roteador principal
|   |   +-- database/                 # 6 conexoes de banco
|   |   +-- services/                 # Modulos de consulta
|   |   +-- views/                    # Templates PHP
|   |   +-- lib/                      # Utilitarios
|   +-- docker-compose.yml
|   +-- Dockerfile
|
+-- docs/                             # Documentacao
```

---

## Consideracoes de Seguranca

- Senhas armazenadas com hash bcrypt (nunca em texto plano)
- Tokens JWT com expiracao de 30 minutos
- CORS configurado para origens especificas
- Middleware de log registra todas as requisicoes
- Sistema de permissoes granular com negacao explicita
- Interceptor no frontend redireciona para login em caso de 401
- Validacao de dados via Pydantic no backend
- Protecao contra ciclos na hierarquia de papeis

---

*Documentacao gerada em 28/04/2026 para o projeto sistemas-consultas-em-andamento.*
