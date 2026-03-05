# Plano: Sistema de Logs de Atividade dos Usuários

## Visão Geral
Implementar um sistema completo de logs que registra todas as ações dos usuários: login, logout, e acesso a cada página/serviço do sistema. Apenas administradores (grupo 0) poderão visualizar os logs.

---

## 1. Criar tabela `tbl_logs_atividade` (SQL Script)

**Arquivo:** `src/database/script/criar_tabela_logs_atividade.sql`

Campos:
- `id` BIGINT AUTO_INCREMENT PRIMARY KEY
- `usuario_id` INT NOT NULL (FK tbl_users)
- `usuario_nome` VARCHAR(255)
- `usuario_email` VARCHAR(255)
- `usuario_grupo` VARCHAR(50) (ex: "CFO", "CRO-SP")
- `usuario_subgrupo` VARCHAR(50) (ex: "Gestor", "TI")
- `tipo_acao` ENUM('login', 'logout', 'acesso_pagina') NOT NULL
- `rota_acessada` VARCHAR(255) (ex: "/consulta-rfb", "/users")
- `nome_pagina` VARCHAR(255) (nome legível da página)
- `metodo_http` VARCHAR(10) (GET, POST)
- `ip_origem` VARCHAR(45)
- `user_agent` TEXT
- `sessao_id` VARCHAR(128)
- `data_hora` DATETIME DEFAULT CURRENT_TIMESTAMP

Índices: `idx_usuario_id`, `idx_tipo_acao`, `idx_data_hora`, `idx_rota`

---

## 2. Criar classe `ActivityLog`

**Arquivo:** `src/lib/ActivityLog.php`

Classe estática com métodos:
- `registrar($tipoAcao, $rota = null, $nomePagina = null)` — Registra um log no banco (Database1). Captura automaticamente o usuário da sessão, IP, user_agent, session_id.
- `registrarLogin($userId, $userName, $userEmail, $grupo, $subgrupo)` — Chamado no login (antes da sessão estar configurada, recebe os dados por parâmetro).
- `registrarLogout()` — Chamado no logout.
- `registrarAcesso()` — Chamado automaticamente no header.php para cada página acessada.

Usa `Database1::getInstance()->getConnection()` com prepared statements (PDO).

---

## 3. Registrar Login

**Arquivo a modificar:** `src/lib/Users.php` (método `userLoginAuthotication`, ~linha 395-415)

Após o login bem-sucedido (depois de setar as sessões), chamar:
```php
ActivityLog::registrarLogin($logResult->id, $logResult->name, $logResult->email, $logResult->grupo, $logResult->subgrupo);
```

---

## 4. Registrar Logout

**Arquivo a modificar:** `src/includes/header.php` (linhas 91-96)

Antes de destruir a sessão, chamar:
```php
ActivityLog::registrarLogout();
```

---

## 5. Registrar Acesso a Páginas

**Arquivo a modificar:** `src/includes/header.php`

Após a verificação de sessão (depois da linha `$users = new Users();`), se o usuário estiver logado, chamar:
```php
if (Session::get('login') == TRUE) {
    ActivityLog::registrarAcesso();
}
```

Isso registra automaticamente cada página que o usuário visita, usando `$_SERVER['REDIRECT_URL']` como rota.

---

## 6. Criar página de visualização de logs (Admin)

**Arquivo:** `src/views/logs.php`

Funcionalidades:
- Acessível apenas por administradores (grupo 0)
- Tabela DataTables com todos os logs
- Filtros: usuário (select2), tipo de ação (login/logout/acesso), período (data início/fim), rota/página
- Colunas: Data/Hora, Usuário, Grupo, Subgrupo, Tipo de Ação, Página Acessada, IP, User Agent
- Paginação server-side ou limitar a últimos 5000 registros
- Botão para exportar em Excel (opcional/futuro)

---

## 7. Adicionar rota

**Arquivo a modificar:** `src/public/index.php`

Adicionar:
```php
Route::add('/logs', function () {
    require VIEWS_PATH . '/logs.php';
    exit;
}, ['get', 'post']);
```

---

## 8. Adicionar link no menu Admin

**Arquivo a modificar:** `src/includes/header.php`

No bloco do Admin Painel (dentro do `if (Session::get('grupo') == '0')`), adicionar link para "/logs" — "Logs de Atividade".

---

## Resumo dos Arquivos

| Ação | Arquivo |
|------|---------|
| CRIAR | `src/database/script/criar_tabela_logs_atividade.sql` |
| CRIAR | `src/lib/ActivityLog.php` |
| CRIAR | `src/views/logs.php` |
| EDITAR | `src/lib/Users.php` (adicionar log no login) |
| EDITAR | `src/includes/header.php` (log no logout + acesso a páginas + link menu) |
| EDITAR | `src/public/index.php` (adicionar rota /logs) |
