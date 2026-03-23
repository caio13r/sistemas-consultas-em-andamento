# Sistema de Auditoria com Permissões Dinâmicas

Sistema completo de auditoria com controle de permissões dinâmicas, menus flexíveis e autenticação JWT para CFO e 27 CROs.

## 🚀 Características Principais

### 🔐 Sistema de Permissões
- **Permissões Granulares**: Controle fino de ações (view, edit, delete, export, approve, manage)
- **Roles Múltiplos**: Usuários podem ter múltiplos perfis simultaneamente
- **Menus Dinâmicos**: Interface se adapta automaticamente às permissões do usuário
- **Escalabilidade**: Suporte para 27 CROs + CFO com níveis hierárquicos

### 🏗️ Arquitetura
- **Backend**: FastAPI + PostgreSQL + SQLAlchemy
- **Frontend**: React + TypeScript + Material-UI
- **Autenticação**: JWT com refresh tokens
- **Docker**: Containerização completa
- **Middleware**: Verificação automática de permissões

## 📋 Estrutura do Banco de Dados

### Tabelas Principais

#### `users`
- Usuários do sistema com múltiplos roles
- Autenticação JWT
- Controle de atividade

#### `roles`
- Perfis de usuário (TI, Fiscalização, Financeiro, etc.)
- Descrições e hierarquias

#### `permissions`
- Permissões granulares por ação e recurso
- Associação com menus/submenus

#### `menus` e `submenus`
- Estrutura de navegação dinâmica
- Ícones, URLs e ordenação

#### `role_permissions` e `user_roles`
- Tabelas de associação many-to-many
- Controle de permissões por role

## 🔧 Instalação e Configuração

### Pré-requisitos
- Docker e Docker Compose
- Node.js 16+
- Python 3.8+

### 1. Clone o repositório
```bash
git clone <repository-url>
cd dashpython-2
```

### 2. Configure as variáveis de ambiente
```bash
cp env.example .env
# Edite o arquivo .env com suas configurações
```

### 3. Execute com Docker
```bash
docker-compose up -d
```

### 4. Inicialize o banco de dados
```bash
# O sistema inicializa automaticamente com dados de exemplo
# Usuário admin: admin@cfo.gov.br / admin123
```

## 🎯 Como Usar

### 1. Login Inicial
- Acesse: `http://localhost:3000`
- Login: `admin@cfo.gov.br`
- Senha: `admin123`

### 2. Gerenciamento de Permissões

#### Criar um Novo Role
1. Acesse **Administração > Permissões**
2. Clique em **Novo Perfil**
3. Defina nome e descrição
4. Configure as permissões desejadas

#### Exemplo de Roles Pré-configurados:
- **Administrador**: Acesso total
- **TI**: Gestão de usuários e permissões
- **Fiscalização**: Auditorias e consultas
- **Financeiro**: Relatórios financeiros
- **Gestor**: Aprovações e gestão
- **Visualizador**: Apenas visualização

### 3. Gerenciamento de Menus

#### Criar um Novo Menu
1. Acesse **Administração > Menus**
2. Clique em **Novo Menu**
3. Configure:
   - Nome do menu
   - URL de destino
   - Ícone (Material-UI)
   - Ordem de exibição

#### Criar Submenus
1. Selecione um menu existente
2. Clique em **Adicionar** na seção Submenus
3. Configure nome, URL e ordem

### 4. Atribuir Permissões

#### Para Menus/Submenus
1. Crie permissões específicas:
   - `view_menu_name` - Visualizar menu
   - `edit_menu_name` - Editar menu
   - `manage_menu_name` - Gerenciar menu

#### Para Funcionalidades
1. Crie permissões por ação:
   - `view_users` - Visualizar usuários
   - `create_users` - Criar usuários
   - `edit_users` - Editar usuários
   - `delete_users` - Excluir usuários
   - `export_reports` - Exportar relatórios

### 5. Associar Usuários a Roles
1. Acesse **Administração > Lista de Usuários**
2. Edite um usuário
3. Selecione os roles desejados
4. Salve as alterações

## 🔌 APIs Disponíveis

### Autenticação
```bash
POST /token
# Retorna: token, permissions, roles, user data
```

### Menus Dinâmicos
```bash
GET /api/menu
# Retorna menu baseado nas permissões do usuário
```

### Gerenciamento de Permissões
```bash
GET    /api/permissions          # Listar permissões
POST   /api/permissions          # Criar permissão
PUT    /api/permissions/{id}     # Atualizar permissão
DELETE /api/permissions/{id}     # Excluir permissão
```

### Gerenciamento de Roles
```bash
GET    /api/roles                # Listar roles
POST   /api/roles                # Criar role
PUT    /api/roles/{id}           # Atualizar role
DELETE /api/roles/{id}           # Excluir role
```

### Gerenciamento de Menus
```bash
GET    /api/menus                # Listar menus
POST   /api/menus                # Criar menu
PUT    /api/menus/{id}           # Atualizar menu
DELETE /api/menus/{id}           # Excluir menu
```

## 🛡️ Middleware de Permissões

### Decorators Disponíveis

```python
# Verificar permissão específica
@check_permission("view_users")

# Verificar qualquer uma das permissões
@check_any_permission(["view_users", "edit_users"])

# Verificar todas as permissões
@check_all_permissions(["view_users", "edit_users"])
```

### Exemplo de Uso
```python
@router.get("/users")
def get_users(current_user: User = Depends(check_permission("view_users"))):
    return {"users": []}
```

## 🎨 Frontend - Verificação de Permissões

### Hook de Permissões
```typescript
const { user } = useAuth();

const hasPermission = (permissionName: string) => {
  return user?.permissions?.includes(permissionName) || user?.is_superuser;
};
```

### Renderização Condicional
```typescript
{hasPermission('export_reports') && (
  <Button onClick={handleExport}>
    Exportar Relatório
  </Button>
)}
```

## 📊 Estrutura de Permissões Recomendada

### Para CFO
- `manage_all` - Acesso total ao sistema
- `manage_users` - Gerenciar usuários
- `manage_roles` - Gerenciar perfis
- `manage_permissions` - Gerenciar permissões
- `manage_menus` - Gerenciar menus

### Para CROs
- `view_auditorias` - Visualizar auditorias
- `create_auditorias` - Criar auditorias
- `edit_auditorias` - Editar auditorias
- `approve_auditorias` - Aprovar auditorias
- `export_reports` - Exportar relatórios

### Para TI
- `manage_users` - Gerenciar usuários
- `manage_roles` - Gerenciar perfis
- `manage_permissions` - Gerenciar permissões
- `manage_menus` - Gerenciar menus

## 🔄 Fluxo de Funcionamento

1. **Login**: Usuário faz login e recebe token + permissões
2. **Menu Dinâmico**: Frontend carrega menu baseado nas permissões
3. **Navegação**: Usuário só vê páginas que tem permissão
4. **Ações**: Botões/funcionalidades são exibidos conforme permissões
5. **API**: Backend valida permissões em cada endpoint

## 🚀 Deploy

### Produção
```bash
# Configure variáveis de ambiente
export SECRET_KEY="sua-chave-secreta"
export DATABASE_URL="postgresql://user:pass@host:port/db"

# Execute
docker-compose -f docker-compose.prod.yml up -d
```

### Desenvolvimento
```bash
# Backend
cd backend
pip install -r requirements.txt
uvicorn app.main:app --reload

# Frontend
cd frontend
npm install
npm run dev
```

## 📝 Logs e Monitoramento

### Logs do Sistema
```bash
# Ver logs do backend
docker-compose logs backend

# Ver logs do frontend
docker-compose logs frontend

# Ver logs do banco
docker-compose logs db
```

### Monitoramento de Permissões
- Todas as verificações de permissão são logadas
- Tentativas de acesso negado são registradas
- Auditoria completa de ações dos usuários

## 🔧 Manutenção

### Backup do Banco
```bash
docker-compose exec db pg_dump -U postgres appdb > backup.sql
```

### Restore do Banco
```bash
docker-compose exec -T db psql -U postgres appdb < backup.sql
```

### Atualização de Permissões
```bash
# Via API
curl -X POST /api/permissions \
  -H "Authorization: Bearer token" \
  -d '{"name": "new_permission", "action": "view", "resource": "new_feature"}'
```

## 🆘 Suporte

### Problemas Comuns

1. **Menu não aparece**: Verifique se o usuário tem permissão `view_menu_name`
2. **Botão não aparece**: Verifique se o usuário tem permissão específica
3. **Erro 403**: Usuário não tem permissão para a ação
4. **Token expirado**: Faça login novamente

### Contato
- Email: suporte@cfo.gov.br
- Documentação: [Link para docs]
- Issues: [Link para issues]

---

**Desenvolvido para CFO - Sistema de Auditoria Integrado** 