from .database import SessionLocal
from .models.permission import Menu, SubMenu


MENUS_DATA = [
    # ---- Página Inicial ----
    {
        "name": "Página Inicial",
        "url": "/",
        "icon": "Home",
        "description": "Página principal do sistema",
        "order": 0,
        "is_section": False,
        "permission_name": None,
        "submenus": [],
    },
    # ---- Seção: Serviços (separador) ----
    {
        "name": "Serviços",
        "url": None,
        "icon": None,
        "description": "Serviços disponíveis no sistema",
        "order": 1,
        "is_section": True,
        "permission_name": None,
        "submenus": [],
    },
    # ---- Serviços individuais ----
    {
        "name": "Visão integrada",
        "url": "/consulta-integrada",
        "icon": "Search",
        "description": "Busca unificada de profissionais em múltiplas fontes",
        "order": 2,
        "permission_name": "view_consulta_integrada",
        "submenus": [],
    },
    {
        "name": "Auditorias",
        "url": None,
        "icon": "Assignment",
        "description": "Consulta e gestão de auditorias",
        "order": 3,
        "permission_name": "view_consulta_auditoria",
        "submenus": [
            {"name": "Consulta Auditorias", "url": "/consulta-auditorias", "icon": "Assignment", "order": 0, "permission_name": "view_consulta_auditoria"},
            {"name": "Nova Auditoria", "url": "/nova-auditoria", "icon": "Add", "order": 1, "permission_name": "view_auditorias"},
            {"name": "Lista de Auditorias", "url": "/lista-auditorias", "icon": "ViewList", "order": 2, "permission_name": "view_auditorias"},
            {"name": "Aprovação", "url": "/aprovacao", "icon": "HowToReg", "order": 3, "permission_name": "approve_auditorias"},
            {"name": "Consulta Auditoria", "url": "/consulta-auditoria", "icon": "Search", "order": 4, "permission_name": "view_consulta_auditoria"},
        ],
    },
    {
        "name": "Fiscalização",
        "url": "/consulta-fiscalizacao",
        "icon": "Gavel",
        "description": "Dados de fiscalização profissional",
        "order": 4,
        "permission_name": "view_consulta_fiscalizacao",
        "submenus": [],
    },
    {
        "name": "Identidade",
        "url": "/consulta-identidade",
        "icon": "Badge",
        "description": "Verificação de identidade profissional",
        "order": 5,
        "permission_name": "view_consulta_identidade",
        "submenus": [],
    },
    {
        "name": "Estatística",
        "url": "/consulta-estatistica",
        "icon": "BarChart",
        "description": "Dados estatísticos e dashboards",
        "order": 6,
        "permission_name": "view_consulta_estatistica",
        "submenus": [],
    },
    {
        "name": "Prescrição",
        "url": "/consulta-prescricao",
        "icon": "MedicalServices",
        "description": "Consulta de prescrições e atribuições profissionais",
        "order": 7,
        "permission_name": "view_consulta_prescricao",
        "submenus": [],
    },
    {
        "name": "SIGESP",
        "url": "/consulta-sigesp",
        "icon": "AccountBalance",
        "description": "Integração com sistema governamental SIGESP",
        "order": 8,
        "permission_name": "view_consulta_sigesp",
        "submenus": [],
    },
    {
        "name": "Tabelas Centralizadas",
        "url": "/tabelas-centralizadas",
        "icon": "TableChart",
        "description": "Tabelas de referência (CFO, CRO, UFs, Categorias)",
        "order": 10,
        "permission_name": "view_tabelas_centralizadas",
        "submenus": [],
    },
    {
        "name": "Relatórios",
        "url": None,
        "icon": "Assessment",
        "description": "Relatórios e análises do sistema",
        "order": 11,
        "permission_name": "view_relatorios_diversos",
        "submenus": [
            {"name": "Relatórios Diversos", "url": "/relatorios-diversos", "icon": "Assessment", "order": 0, "permission_name": "view_relatorios_diversos"},
            {"name": "Relatório Adimplência", "url": "/relatorio-adimplencia", "icon": "BarChart", "order": 1, "permission_name": "view_relatorio_adimplencia"},
            {"name": "Relatório Auditoria", "url": "/relatorio-auditoria", "icon": "Assignment", "order": 2, "permission_name": "view_relatorio_auditoria"},
            {"name": "Relatório Financeiro", "url": "/relatorio-financeiro", "icon": "AccountBalance", "order": 3, "permission_name": "view_relatorio_financeiro"},
        ],
    },
    {
        "name": "Eleições Regionais",
        "url": "/eleicoes-regionais",
        "icon": "HowToVote",
        "description": "Módulo de eleições regionais dos conselhos",
        "order": 12,
        "permission_name": "view_eleicoes_regionais",
        "submenus": [],
    },
    {
        "name": "Visão RFB",
        "url": "/consulta-rfb",
        "icon": "Policy",
        "description": "Integração com Receita Federal do Brasil (CNPJ/CPF)",
        "order": 13,
        "permission_name": "view_consulta_rfb",
        "submenus": [],
    },
    {
        "name": "Documentos",
        "url": "/documentos",
        "icon": "Folder",
        "description": "Gestão de documentos",
        "order": 15,
        "permission_name": None,
        "submenus": [],
    },
    # ---- Seção: Administração ----
    {
        "name": "Administração",
        "url": None,
        "icon": "AdminPanelSettings",
        "description": "Painel administrativo do sistema",
        "order": 16,
        "is_section": True,
        "permission_name": None,
        "submenus": [
            {"name": "Usuários", "url": "/users", "icon": "People", "order": 0, "permission_name": "view_users"},
            {"name": "Adicionar Usuário", "url": "/add-user", "icon": "PersonAdd", "order": 1, "permission_name": "create_users"},
            {"name": "Solicitações", "url": "/admin/solicitacoes", "icon": "HowToReg", "order": 2, "permission_name": "manage_user_requests"},
            {"name": "Permissões", "url": "/permissions", "icon": "Security", "order": 3, "permission_name": "manage_permissions"},
            {"name": "Perfis (Roles)", "url": "/roles", "icon": "SupervisorAccount", "order": 4, "permission_name": "manage_roles"},
            {"name": "Labels / Menus", "url": "/menus", "icon": "ViewList", "order": 5, "permission_name": "manage_menus"},
            {"name": "Temas", "url": "/temas", "icon": "Settings", "order": 6, "permission_name": None},
            {"name": "Catálogo de Serviços", "url": "/servicos", "icon": "Folder", "order": 7, "permission_name": None},
            {"name": "Log de Alterações", "url": "/log-alteracoes", "icon": "Assignment", "order": 8, "permission_name": "manage_users"},
            {"name": "Log de Atividades", "url": "/log-atividades", "icon": "ViewList", "order": 9, "permission_name": "manage_users"},
            {"name": "Documentacao", "url": "/documentacao", "icon": "Assignment", "order": 10, "permission_name": "manage_users"},
            {"name": "Backup", "url": "/backup-config", "icon": "Storage", "order": 11, "permission_name": "manage_users"},
        ],
    },
]


_NEW_MENUS = []


def _ensure_new_menus(db):
    """Insere ou atualiza menus novos em bases já populadas (idempotente)"""
    for menu_data in _NEW_MENUS:
        exists = db.query(Menu).filter(Menu.url == menu_data["url"]).first()
        if not exists:
            db_menu = Menu(**menu_data)
            db.add(db_menu)
        else:
            for key, val in menu_data.items():
                setattr(exists, key, val)
    db.commit()


def _sync_menu_names(db):
    """Atualiza nomes de menus existentes conforme MENUS_DATA (por URL)."""
    for menu_data in MENUS_DATA:
        url = menu_data.get("url")
        if not url:
            continue
        menu = db.query(Menu).filter(Menu.url == url).first()
        if menu and menu.name != menu_data["name"]:
            old_name = menu.name
            menu.name = menu_data["name"]
            print(f"  Menu atualizado: {old_name} -> {menu_data['name']} ({url})")
    db.commit()


def _sync_submenus(db):
    """Sincroniza submenus existentes com MENUS_DATA (corrige nomes, URLs, ordem)."""
    for menu_data in MENUS_DATA:
        submenus_data = menu_data.get("submenus", [])
        if not submenus_data:
            continue
        parent = db.query(Menu).filter(Menu.name == menu_data["name"]).first()
        if not parent:
            continue
        existing_subs = db.query(SubMenu).filter(SubMenu.menu_id == parent.id).all()
        existing_by_order = {s.order: s for s in existing_subs}
        for sub_data in submenus_data:
            order = sub_data["order"]
            if order in existing_by_order:
                sub = existing_by_order[order]
                changed = False
                for key in ("name", "url", "icon", "permission_name"):
                    if key in sub_data and getattr(sub, key, None) != sub_data[key]:
                        setattr(sub, key, sub_data[key])
                        changed = True
                if changed:
                    print(f"  Submenu atualizado: {sub.name} -> {sub_data['name']} ({sub_data['url']})")
            else:
                new_sub = SubMenu(menu_id=parent.id, **sub_data)
                db.add(new_sub)
                print(f"  Submenu criado: {sub_data['name']} ({sub_data['url']})")
    db.commit()


def init_menus_data():
    """Inicializa menus e submenus no banco de dados"""
    db = SessionLocal()
    try:
        existing_count = db.query(Menu).count()
        if existing_count > 0:
            # Inserir menus novos que ainda não existem (idempotente)
            _ensure_new_menus(db)
            _sync_menu_names(db)
            # Sincronizar submenus existentes com definição atual
            _sync_submenus(db)
            print(f"Menus já existem ({existing_count} encontrados). Verificação de novos menus/submenus concluída.")
            return

        for menu_data in MENUS_DATA:
            submenus_data = menu_data.pop("submenus", [])
            db_menu = Menu(**menu_data)
            db.add(db_menu)
            db.flush()

            for sub_data in submenus_data:
                sub_data["menu_id"] = db_menu.id
                db_submenu = SubMenu(**sub_data)
                db.add(db_submenu)

        db.commit()
        print("Menus inicializados com sucesso!")

    except Exception as e:
        print(f"Erro ao inicializar menus: {e}")
        db.rollback()
    finally:
        db.close()
