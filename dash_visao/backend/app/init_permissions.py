from sqlalchemy.orm import Session
from .database import SessionLocal
from .models import Menu, SubMenu, Role, Permission, User
from .core.auth import get_password_hash

def init_permissions_data():
    """Inicializa dados de permissões, menus e roles"""
    db = SessionLocal()
    try:
        # Criar roles básicos com níveis de hierarquia
        roles_data = [
            {"name": "Administrador", "description": "Acesso total ao sistema", "level": 4},
            {"name": "TI", "description": "Equipe de Tecnologia da Informação", "level": 4},
            {"name": "Fiscalização", "description": "Equipe de Fiscalização", "level": 2},
            {"name": "Financeiro", "description": "Equipe Financeira", "level": 2},
            {"name": "Cadastro", "description": "Equipe de Cadastro", "level": 2},
            {"name": "Gestor", "description": "Gestores de área", "level": 3},
            {"name": "Analista", "description": "Analistas", "level": 2},
            {"name": "Visualizador", "description": "Apenas visualização", "level": 1}
        ]

        for role_data in roles_data:
            existing_role = db.query(Role).filter(Role.name == role_data["name"]).first()
            if not existing_role:
                role = Role(**role_data)
                db.add(role)
            else:
                # Atualizar level se o role já existe
                if existing_role.level != role_data["level"]:
                    existing_role.level = role_data["level"]

        db.commit()

        # Configurar hierarquia: Analista herda de Visualizador, Gestor herda de Analista
        visualizador_role_ref = db.query(Role).filter(Role.name == "Visualizador").first()
        analista_role_ref = db.query(Role).filter(Role.name == "Analista").first()
        gestor_role_ref = db.query(Role).filter(Role.name == "Gestor").first()

        if analista_role_ref and visualizador_role_ref and not analista_role_ref.parent_role_id:
            analista_role_ref.parent_role_id = visualizador_role_ref.id

        if gestor_role_ref and analista_role_ref and not gestor_role_ref.parent_role_id:
            gestor_role_ref.parent_role_id = analista_role_ref.id

        db.commit()

        # NOTA: Menus e submenus agora são gerenciados pelo init_menus.py

        # Criar permissões básicas
        permissions_data = [
            # Permissões de usuários
            {"name": "view_users", "description": "Visualizar usuários", "action": "view", "resource": "users"},
            {"name": "create_users", "description": "Criar usuários", "action": "create", "resource": "users"},
            {"name": "edit_users", "description": "Editar usuários", "action": "edit", "resource": "users"},
            {"name": "delete_users", "description": "Excluir usuários", "action": "delete", "resource": "users"},
            
            # Permissões de roles
            {"name": "manage_roles", "description": "Gerenciar perfis", "action": "manage", "resource": "roles"},
            
            # Permissões de permissões
            {"name": "manage_permissions", "description": "Gerenciar permissões", "action": "manage", "resource": "permissions"},
            
            # Permissões de menus
            {"name": "manage_menus", "description": "Gerenciar menus", "action": "manage", "resource": "menus"},
            
            # Permissões de consultas
            {"name": "view_consulta_integrada", "description": "Visualizar consulta integrada", "action": "view", "resource": "consulta_integrada"},
            {"name": "edit_consulta_integrada", "description": "Editar consulta integrada", "action": "edit", "resource": "consulta_integrada"},
            {"name": "view_consulta_auditoria", "description": "Visualizar consulta auditoria", "action": "view", "resource": "consulta_auditoria"},
            {"name": "edit_consulta_auditoria", "description": "Editar consulta auditoria", "action": "edit", "resource": "consulta_auditoria"},
            {"name": "view_consulta_prescricao", "description": "Visualizar consulta prescrição", "action": "view", "resource": "consulta_prescricao"},
            {"name": "edit_consulta_prescricao", "description": "Editar consulta prescrição", "action": "edit", "resource": "consulta_prescricao"},
            
            # Permissões de auditorias
            {"name": "view_auditorias", "description": "Visualizar auditorias", "action": "view", "resource": "auditorias"},
            {"name": "create_auditorias", "description": "Criar auditorias", "action": "create", "resource": "auditorias"},
            {"name": "edit_auditorias", "description": "Editar auditorias", "action": "edit", "resource": "auditorias"},
            {"name": "delete_auditorias", "description": "Excluir auditorias", "action": "delete", "resource": "auditorias"},
            {"name": "approve_auditorias", "description": "Aprovar auditorias", "action": "approve", "resource": "auditorias"},
            
            # Permissões de relatórios
            {"name": "view_relatorio_adimplencia", "description": "Visualizar relatório adimplência", "action": "view", "resource": "relatorio_adimplencia"},
            {"name": "export_relatorio_adimplencia", "description": "Exportar relatório adimplência", "action": "export", "resource": "relatorio_adimplencia"},
            {"name": "view_relatorio_auditoria", "description": "Visualizar relatório auditoria", "action": "view", "resource": "relatorio_auditoria"},
            {"name": "export_relatorio_auditoria", "description": "Exportar relatório auditoria", "action": "export", "resource": "relatorio_auditoria"},
            {"name": "view_relatorio_financeiro", "description": "Visualizar relatório financeiro", "action": "view", "resource": "relatorio_financeiro"},
            {"name": "export_relatorio_financeiro", "description": "Exportar relatório financeiro", "action": "export", "resource": "relatorio_financeiro"},

            # Permissão de solicitações de usuário
            {"name": "manage_user_requests", "description": "Gerenciar solicitações de usuário", "action": "manage", "resource": "user_requests"},

            # Permissões de módulos de consulta
            {"name": "view_consulta_estatistica", "description": "Visualizar consulta estatística", "action": "view", "resource": "consulta_estatistica"},
            {"name": "view_consulta_fiscalizacao", "description": "Visualizar consulta fiscalização", "action": "view", "resource": "consulta_fiscalizacao"},
            {"name": "view_consulta_identidade", "description": "Visualizar consulta identidade", "action": "view", "resource": "consulta_identidade"},
            {"name": "manage_consulta_identidade", "description": "Gerenciar consulta identidade (registrar carteirinhas)", "action": "manage", "resource": "consulta_identidade"},
            {"name": "view_tabelas_centralizadas", "description": "Visualizar tabelas centralizadas", "action": "view", "resource": "tabelas_centralizadas"},
            {"name": "view_eleicoes_regionais", "description": "Visualizar eleições regionais", "action": "view", "resource": "eleicoes_regionais"},
            {"name": "view_consulta_rfb", "description": "Visualizar consulta RFB", "action": "view", "resource": "consulta_rfb"},
            {"name": "view_dados_abertos", "description": "Visualizar dados abertos", "action": "view", "resource": "dados_abertos"},
            {"name": "view_relatorios_diversos", "description": "Visualizar relatórios diversos", "action": "view", "resource": "relatorios_diversos"},
            {"name": "view_cracha", "description": "Visualizar e gerar crachá", "action": "view", "resource": "cracha"},
            {"name": "view_lai", "description": "Visualizar relatório LAI", "action": "view", "resource": "lai"},
            {"name": "manage_users", "description": "Gerenciar usuários (logs de atividade)", "action": "manage", "resource": "users"},

            # Permissões de documentos
            {"name": "view_documentos", "description": "Visualizar e fazer upload de documentos", "action": "view", "resource": "documentos"},
            {"name": "manage_documentos", "description": "Validar e excluir documentos", "action": "manage", "resource": "documentos"},
        ]
        
        for perm_data in permissions_data:
            existing_perm = db.query(Permission).filter(Permission.name == perm_data["name"]).first()
            if not existing_perm:
                permission = Permission(**perm_data)
                db.add(permission)
        
        db.commit()
        
        # Associar permissões aos roles
        admin_role = db.query(Role).filter(Role.name == "Administrador").first()
        ti_role = db.query(Role).filter(Role.name == "TI").first()
        fiscalizacao_role = db.query(Role).filter(Role.name == "Fiscalização").first()
        financeiro_role = db.query(Role).filter(Role.name == "Financeiro").first()
        gestor_role = db.query(Role).filter(Role.name == "Gestor").first()
        visualizador_role = db.query(Role).filter(Role.name == "Visualizador").first()
        
        # Administrador tem todas as permissões
        if admin_role:
            all_permissions = db.query(Permission).all()
            admin_role.permissions = all_permissions
        
        # TI tem permissões de gestão
        if ti_role:
            ti_permissions = db.query(Permission).filter(
                Permission.name.in_([
                    "manage_roles", "manage_permissions", "manage_menus",
                    "view_users", "create_users", "edit_users",
                    "manage_user_requests"
                ])
            ).all()
            ti_role.permissions = ti_permissions
        
        # Fiscalização tem permissões de auditoria
        if fiscalizacao_role:
            fiscalizacao_permissions = db.query(Permission).filter(
                Permission.name.in_([
                    "view_auditorias", "create_auditorias", "edit_auditorias",
                    "view_consulta_integrada", "view_consulta_auditoria",
                    "view_relatorio_auditoria", "export_relatorio_auditoria"
                ])
            ).all()
            fiscalizacao_role.permissions = fiscalizacao_permissions
        
        # Financeiro tem permissões financeiras
        if financeiro_role:
            financeiro_permissions = db.query(Permission).filter(
                Permission.name.in_([
                    "view_relatorio_financeiro", "export_relatorio_financeiro",
                    "view_relatorio_adimplencia", "export_relatorio_adimplencia"
                ])
            ).all()
            financeiro_role.permissions = financeiro_permissions
        
        # Gestor tem permissões de gestão
        if gestor_role:
            gestor_permissions = db.query(Permission).filter(
                Permission.name.in_([
                    "view_auditorias", "create_auditorias", "edit_auditorias",
                    "approve_auditorias", "view_relatorio_auditoria",
                    "view_users", "view_consulta_integrada", "view_consulta_auditoria"
                ])
            ).all()
            gestor_role.permissions = gestor_permissions
        
        # Visualizador tem apenas permissões de visualização
        if visualizador_role:
            view_permissions = db.query(Permission).filter(
                Permission.action == "view"
            ).all()
            visualizador_role.permissions = view_permissions
        
        db.commit()
        
        # Criar usuário administrador se não existir
        admin_user = db.query(User).filter(User.username == "admin").first()
        if not admin_user:
            admin_user = User(
                username="admin",
                email="admin@cfo.gov.br",
                full_name="Administrador do Sistema",
                hashed_password=get_password_hash("admin123"),
                is_active=True,
                is_superuser=True
            )
            db.add(admin_user)
            db.commit()
            
            # Associar role de administrador
            if admin_role:
                admin_user.roles.append(admin_role)
                db.commit()
        
        print("Dados de permissões inicializados com sucesso!")
        
    except Exception as e:
        print(f"Erro ao inicializar dados de permissões: {e}")
        db.rollback()
    finally:
        db.close()

if __name__ == "__main__":
    init_permissions_data() 