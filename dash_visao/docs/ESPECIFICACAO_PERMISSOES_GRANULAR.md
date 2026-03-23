# Especificação: Sistema de Permissões Granular

## Documento de Referência para Implementação

Este documento descreve o sistema de permissões atual e as melhorias necessárias para torná-lo mais granular, mantendo o modelo baseado em grupos (roles).

---

## 1. Sistema Atual

### 1.1 Arquitetura (RBAC - Role-Based Access Control)

```
Usuário → Roles (grupos) → Permissões → Acesso a páginas/recursos
```

### 1.2 Modelo de Dados Atual

**User** (`users`)

- `id`, `username`, `email`, `full_name`, `hashed_password`
- `is_active`, `is_superuser`
- `created_at`, `updated_at`, `last_activity`
- Relacionamento: `roles` (N:N via `user_roles`)

**Role** (`roles`)

- `id`, `name`, `description`
- `created_at`, `updated_at`
- Relacionamento: `users` (N:N), `permissions` (N:N via `role_permissions`)

**Permission** (`permissions`)

- `id`, `name`, `description`
- `action` (view, create, edit, delete, manage, approve, export)
- `resource` (users, auditorias, consulta_integrada, relatorio_financeiro, etc.)
- `menu_id`, `submenu_id` (opcional - vínculo com menus)
- `created_at`, `updated_at`

**Tabelas de Associação:**

- `user_roles`: user_id, role_id
- `role_permissions`: role_id, permission_id, **granted** (Boolean, default True) — *campo existente mas subutilizado*

### 1.3 Fluxo de Autorização Atual

1. Login retorna: `permissions[]`, `roles[]`, `user`
2. Backend: `check_permission("nome_permissao")` em endpoints
3. Menu dinâmico: filtra menus/submenus por permissões com `action == "view"`
4. Superuser: bypassa todas as verificações
5. Frontend: `PrivateRoute` verifica apenas autenticação (não permissão por rota)

### 1.4 Limitações Identificadas

- Sem permissão direta ao usuário (apenas via roles)
- Sem hierarquia entre roles
- Sem níveis de autorização explícitos
- Sem escopo/contexto (departamento, organização, região)
- Sem suporte a usuários com apenas um tipo de autorização
- Campo `granted` em `role_permissions` não utilizado para negação
- Frontend não protege rotas por permissão

---

## 2. Melhorias Propostas (Modelo de Grupos + Granularidade)

### 2.1 Princípio: Manter o modelo de grupos e concatenar camadas

O modelo **User → Roles → Permissions** permanece como base. Todas as melhorias são camadas adicionadas em cima.

---

## 3. Especificação Detalhada por Funcionalidade

### 3.1 Hierarquia de Roles (Roles que herdam de outros Roles)

**Objetivo:** Um role pode herdar permissões de outro role pai.

**Alterações no modelo:**

| Entidade | Campo            | Tipo                              | Descrição                        |
|----------|------------------|-----------------------------------|----------------------------------|
| Role     | `parent_role_id` | Integer, FK(roles.id), nullable   | Role pai do qual este herda      |

**Regra de cálculo de permissões:**

- Permissões do role = permissões próprias (role_permissions) + permissões herdadas do parent_role (recursivo)
- Cuidar com ciclos: parent_role_id não pode formar loop

**Exemplo:**

```
Visualizador (base)
    └── Analista (herda Visualizador)
        └── Gestor (herda Analista)
```

---

### 3.2 Permissões Diretas ao Usuário

**Objetivo:** Conceder ou revogar permissões individuais ao usuário, independente dos roles.

**Nova tabela:** `user_permissions`

| Coluna         | Tipo                      | Descrição                                  |
|----------------|---------------------------|--------------------------------------------|
| id             | Integer, PK               |                                            |
| user_id        | Integer, FK(users.id), not null |                                        |
| permission_id  | Integer, FK(permissions.id), not null |                               |
| granted        | Boolean, default True     | True = concede, False = revoga explicitamente |
| created_at     | DateTime                  |                                            |

**Regra de cálculo final:**

```
permissões_efetivas = 
  permissões_dos_roles (incluindo hierarquia)
  + permissões_diretas com granted=True
  - permissões_diretas com granted=False
```

- Se um user_permission com granted=False existir, essa permissão NÃO é concedida, mesmo que venha do role.
- Se um user_permission com granted=True existir, concede a permissão mesmo que o role não a tenha.

**Endpoints necessários:**

- POST /users/{user_id}/permissions — atribuir permissão direta
- DELETE /users/{user_id}/permissions/{permission_id} — remover permissão direta
- GET /users/{user_id}/permissions — listar permissões diretas do usuário

---

### 3.3 Níveis de Autorização nos Roles

**Objetivo:** Cada role tem um nível numérico que representa o "teto" de ações permitidas.

**Alterações no modelo:**

| Entidade | Campo  | Tipo              | Descrição                           |
|----------|--------|-------------------|-------------------------------------|
| Role     | `level`| Integer, default 1| 1=view, 2=edit, 3=approve, 4=admin  |

**Níveis sugeridos:**

- 1: Apenas visualização
- 2: Visualização + criação/edição
- 3: Visualização + edição + aprovação
- 4: Administração completa

**Uso:** O level pode ser usado para validações de negócio (ex: "aprovador precisa ter level >= 3") ou para documentação. As permissões concretas continuam definidas em `role_permissions` e `permissions`.

---

### 3.4 Utilização do Campo `granted` em role_permissions

**Objetivo:** Permitir negação explícita de permissão em um role.

**Comportamento:**

- `granted=True`: permissão concedida pelo role
- `granted=False`: permissão explicitamente NEGADA pelo role

**Regra:** Ao calcular permissões do role, incluir apenas aquelas com `granted=True`. Se `granted=False`, não conceder essa permissão (mesmo que venha de role pai na hierarquia — a negação tem precedência no role atual).

**Alteração na lógica:** A função `get_user_permissions` (ou equivalente) deve considerar `granted` ao percorrer `role_permissions`.

---

### 3.5 Usuários com Apenas Um Tipo de Autorização

**Objetivo:** Alguns usuários devem ter apenas UM role (ex: apenas visualizador).

**Alterações no modelo:**

| Entidade | Campo               | Tipo                    | Descrição                    |
|----------|---------------------|-------------------------|------------------------------|
| User     | `authorization_mode`| String, default "multi_role" | "single_role" ou "multi_role" |

**Regras:**

- Se `authorization_mode = "single_role"`: usuário só pode ter exatamente 1 role.
- Validação: ao atribuir role, se single_role e já tiver 1 role, bloquear ou substituir.
- Se `authorization_mode = "multi_role"`: comportamento atual (múltiplos roles).

**Validação nos endpoints:**

- POST /roles/{role_id}/users/ — verificar authorization_mode antes de adicionar
- Ao criar usuário: definir authorization_mode conforme necessidade

---

### 3.6 Escopo/Contexto (ABAC - Attribute-Based)

**Objetivo:** Restringir acesso por departamento, organização ou região.

**Alterações no modelo - User:**

| Campo            | Tipo                                | Descrição                    |
|------------------|-------------------------------------|------------------------------|
| department_id    | Integer, FK(departments.id), nullable | Departamento do usuário   |
| organization_id  | Integer, FK(organizations.id), nullable | Organização do usuário   |
| region_id        | Integer, FK(regions.id), nullable   | Região do usuário            |

**Alterações opcionais - Permission ou nova tabela permission_scopes:**

| Campo       | Tipo    | Descrição                                      |
|-------------|---------|------------------------------------------------|
| scope_type  | String  | "global", "department", "organization", "region" |
| scope_value | Integer, nullable | ID do departamento/org/região se aplicável |

**Regra de verificação:**

- Ao checar permissão + recurso: além de ter a permission, validar se o recurso pertence ao mesmo department/organization/region do usuário.
- Se scope_type = "global": não aplicar filtro de escopo.
- Se scope_type = "department": recurso deve ter department_id igual ao do usuário.

**Nota:** Pode exigir criação de tabelas `departments`, `organizations`, `regions` se não existirem. Implementação pode ser em fases.

---

### 3.7 Proteção de Rotas no Frontend por Permissão

**Objetivo:** Ocultar/bloquear acesso a rotas conforme permissões do usuário.

**Alterações:**

- Criar componente `PermissionRoute` que recebe `requiredPermissions?: string[]` (ou `requiredAnyPermissions`).
- Usar `useAuth()` para acessar `permissions` do usuário.
- Se não tiver permissão: redirecionar para página 403 ou home.
- Aplicar em rotas sensíveis (ex: /permissions, /roles, /menus).

**Exemplo de uso:**

```tsx
<PermissionRoute requiredPermissions={["manage_permissions"]}>
  <Layout><Permissions /></Layout>
</PermissionRoute>
```

---

## 4. Ordem de Implementação Sugerida

| Fase | Item                               | Prioridade |
|------|------------------------------------|------------|
| 1    | Hierarquia de roles (`parent_role_id`) | Alta   |
| 2    | Permissões diretas ao usuário (`user_permissions`) | Alta |
| 3    | Usar `granted` em `role_permissions` na lógica | Média |
| 4    | Níveis em roles (`level`)          | Média      |
| 5    | `authorization_mode` no User       | Média      |
| 6    | Proteção de rotas no frontend (`PermissionRoute`) | Média |
| 7    | Escopo/contexto (department, organization, region) | Baixa |

---

## 5. Função get_user_permissions - Lógica Atualizada

A função central `get_user_permissions(db, user_id)` deve ser refatorada para:

1. Se `is_superuser`: retornar todas as permissões (ou lista especial).
2. Obter permissões dos roles do usuário (incluindo roles pais recursivamente, considerando `granted=True` em role_permissions).
3. Adicionar permissões de `user_permissions` com `granted=True`.
4. Remover permissões de `user_permissions` com `granted=False`.
5. Retornar conjunto final (sem duplicatas).

---

## 6. Migrações de Banco de Dados

- Criar migração para `user_permissions`.
- Criar migração para `parent_role_id` em `roles`.
- Criar migração para `level` em `roles`.
- Criar migração para `authorization_mode` em `users`.
- Criar migrações para escopo (department_id, organization_id, region_id em users e tabelas relacionadas) — se aplicável.

---

## 7. Arquivos Relevantes do Projeto

| Caminho | Descrição |
|---------|-----------|
| `backend/app/models/permission.py` | Modelos Permission, Role, user_roles, role_permissions |
| `backend/app/models/user.py` | Modelo User |
| `backend/app/core/auth.py` | get_user_permissions, check_permission, check_any_permission, check_all_permissions |
| `backend/app/routers/permissions.py` | CRUD permissões, associação role-permission |
| `backend/app/routers/roles.py` | CRUD roles, associação user-role |
| `backend/app/routers/dynamic_menu.py` | Menu baseado em permissões |
| `backend/app/init_permissions.py` | Dados iniciais (atualizar para incluir level, parent_role_id) |
| `frontend/src/components/PrivateRoute.tsx` | Base para PermissionRoute |
| `frontend/src/contexts/AuthContext.tsx` | Contexto com permissions |

---

## 8. Considerações Finais

- Manter retrocompatibilidade: usuários sem `authorization_mode` tratados como "multi_role".
- Roles sem `level` tratados como nível 1.
- Roles sem `parent_role_id` continuam funcionando como hoje.
- Testes: validar hierarquia, permissões diretas, negação e authorization_mode.
