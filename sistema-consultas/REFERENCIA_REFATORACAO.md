# Referência para Refatoração - Sistema Consultas CFO

> **Uso:** Este repositório é usado como **referência** para migrar features de PHP para o sistema `dash_sistato` (Python/FastAPI + React/TypeScript).

---

## O que é

- **Origem:** `conselho-federal-de-odontologia/sistema-consultas` (PHP + MySQL)
- **Destino:** `dash_sistato` (FastAPI + React)
- **Branch:** `main`

---

## Estrutura principal

| Pasta | Conteúdo |
|-------|----------|
| `src/views/` | Views PHP: login, consultas (Identidade, Eleições, Prescrição, RFB, etc.), relatórios |
| `src/services/` | Serviços PHP por módulo (ex: `consulta-identidade/`) |
| `src/vendor/` | Dependências Composer |

---

## Como usar como referência

Ao refatorar, use este repositório para:

1. **Consultar o PHP** – cada view/service é a referência da feature a migrar
2. **Mapear labels/sub-labels** – ver estrutura de menus e permissões
3. **Entender fluxos** – formulários, filtros, tabelas, exportação

---

## Prompt rápido para a IA

Copie e cole quando precisar:

```
Use o repositório em c:\Users\style\Documents\dashsisato\sistema-consultas como referência para refatorar a feature [NOME]. Consulte os arquivos PHP correspondentes em src/views/ e src/services/ e migre para o dash_sistato.
```

Exemplo:

```
Use o repositório em c:\Users\style\Documents\dashsisato\sistema-consultas como referência para refatorar a Consulta Identidade. Consulte src/views/consultaIdentidade.php e src/services/consulta-identidade/ e migre para o dash_sistato.
```

---

*Pasta temporária para refatoração. Pode ser removida ao concluir.*
