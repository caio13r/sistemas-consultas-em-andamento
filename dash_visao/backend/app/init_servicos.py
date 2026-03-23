from .database import SessionLocal
from .models.servico import Servico
from .models.permission import Permission


SERVICOS_DATA = [
    {
        "nome": "Consulta Integrada",
        "slug": "consulta-integrada",
        "descricao": "Busca unificada de profissionais em múltiplas fontes",
        "icone": "Search",
        "ordem": 1,
        "permissao_nome": "view_consulta_integrada",
        "scope_type": "global",
    },
    {
        "nome": "Consulta Auditorias",
        "slug": "consulta-auditorias",
        "descricao": "Consulta e acompanhamento de auditorias",
        "icone": "Assignment",
        "ordem": 2,
        "permissao_nome": "view_consulta_auditoria",
        "scope_type": "cfo",
    },
    {
        "nome": "Consulta Fiscalização",
        "slug": "consulta-fiscalizacao",
        "descricao": "Dados de fiscalização profissional",
        "icone": "Gavel",
        "ordem": 3,
        "permissao_nome": "view_consulta_fiscalizacao",
        "scope_type": "cfo",
    },
    {
        "nome": "Consulta Identidade",
        "slug": "consulta-identidade",
        "descricao": "Verificação de identidade profissional",
        "icone": "Badge",
        "ordem": 4,
        "permissao_nome": "view_consulta_identidade",
        "scope_type": "global",
    },
    {
        "nome": "Consulta Estatística",
        "slug": "consulta-estatistica",
        "descricao": "Dados estatísticos e dashboards",
        "icone": "BarChart",
        "ordem": 5,
        "permissao_nome": "view_consulta_estatistica",
        "scope_type": "global",
    },
    {
        "nome": "Consulta Prescrição",
        "slug": "consulta-prescricao",
        "descricao": "Consulta de prescrições e atribuições profissionais",
        "icone": "MedicalServices",
        "ordem": 6,
        "permissao_nome": "view_consulta_prescricao",
        "scope_type": "cfo",
    },
    {
        "nome": "Consulta SIGESP",
        "slug": "consulta-sigesp",
        "descricao": "Integração com sistema governamental SIGESP",
        "icone": "AccountBalance",
        "ordem": 7,
        "permissao_nome": "view_consulta_sigesp",
        "scope_type": "cfo",
    },
    {
        "nome": "Dados Abertos",
        "slug": "dados-abertos",
        "descricao": "Disponibilização de dados abertos e transparência",
        "icone": "Public",
        "ordem": 8,
        "permissao_nome": "view_dados_abertos",
        "scope_type": "global",
    },
    {
        "nome": "Tabelas Centralizadas",
        "slug": "tabelas-centralizadas",
        "descricao": "Tabelas de referência (CFO, CRO, UFs, Categorias)",
        "icone": "TableChart",
        "ordem": 9,
        "permissao_nome": "view_tabelas_centralizadas",
        "scope_type": "global",
    },
    {
        "nome": "Relatórios Diversos",
        "slug": "relatorios-diversos",
        "descricao": "Geração de relatórios variados com exportação",
        "icone": "Assessment",
        "ordem": 10,
        "permissao_nome": "view_relatorios_diversos",
        "scope_type": "cfo",
    },
    {
        "nome": "Eleições Regionais",
        "slug": "eleicoes-regionais",
        "descricao": "Módulo de eleições regionais dos conselhos",
        "icone": "HowToVote",
        "ordem": 11,
        "permissao_nome": "view_eleicoes_regionais",
        "scope_type": "cfo",
    },
    {
        "nome": "Consulta RFB",
        "slug": "consulta-rfb",
        "descricao": "Integração com Receita Federal do Brasil (CNPJ/CPF)",
        "icone": "Policy",
        "ordem": 12,
        "permissao_nome": "view_consulta_rfb",
        "scope_type": "cfo",
    },
]


def init_servicos_data():
    """Inicializa catálogo de serviços e suas permissões"""
    db = SessionLocal()
    try:
        for svc in SERVICOS_DATA:
            # Criar permissão se não existe
            perm_name = svc["permissao_nome"]
            scope = svc.get("scope_type", "global")
            existing_perm = db.query(Permission).filter(Permission.name == perm_name).first()
            if not existing_perm:
                perm = Permission(
                    name=perm_name,
                    description=f"Acessar {svc['nome']}",
                    action="view",
                    resource=svc["slug"],
                    scope_type=scope,
                )
                db.add(perm)
            else:
                # Atualizar scope_type se já existe
                if existing_perm.scope_type != scope:
                    existing_perm.scope_type = scope

            # Criar serviço se não existe
            existing_svc = db.query(Servico).filter(Servico.slug == svc["slug"]).first()
            if not existing_svc:
                servico = Servico(**svc)
                db.add(servico)
            else:
                # Atualizar scope_type se já existe
                if existing_svc.scope_type != scope:
                    existing_svc.scope_type = scope

        db.commit()
        print("Catálogo de serviços inicializado com sucesso!")

    except Exception as e:
        print(f"Erro ao inicializar serviços: {e}")
        db.rollback()
    finally:
        db.close()
