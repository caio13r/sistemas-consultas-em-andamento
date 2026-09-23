from .database import SessionLocal
from .models.servico import Servico
from .models.permission import Permission


SERVICOS_DATA = [
    {
        "nome": "Visão integrada",
        "slug": "consulta-integrada",
        "descricao": "Busca unificada de profissionais em múltiplas fontes",
        "icone": "Search",
        "ordem": 1,
        "permissao_nome": "view_consulta_integrada",
        "scope_type": "global",
    },
    {
        "nome": "Auditorias",
        "slug": "consulta-auditorias",
        "descricao": "Consulta e acompanhamento de auditorias",
        "icone": "Assignment",
        "ordem": 2,
        "permissao_nome": "view_consulta_auditoria",
        "scope_type": "cfo",
    },
    {
        "nome": "Fiscalização",
        "slug": "consulta-fiscalizacao",
        "descricao": "Dados de fiscalização profissional",
        "icone": "Gavel",
        "ordem": 3,
        "permissao_nome": "view_consulta_fiscalizacao",
        "scope_type": "cfo",
    },
    {
        "nome": "Identidade",
        "slug": "consulta-identidade",
        "descricao": "Verificação de identidade profissional",
        "icone": "Badge",
        "ordem": 4,
        "permissao_nome": "view_consulta_identidade",
        "scope_type": "global",
    },
    {
        "nome": "Estatística",
        "slug": "consulta-estatistica",
        "descricao": "Dados estatísticos e dashboards",
        "icone": "BarChart",
        "ordem": 5,
        "permissao_nome": "view_consulta_estatistica",
        "scope_type": "global",
    },
    {
        "nome": "Prescrição",
        "slug": "consulta-prescricao",
        "descricao": "Consulta de prescrições e atribuições profissionais",
        "icone": "MedicalServices",
        "ordem": 6,
        "permissao_nome": "view_consulta_prescricao",
        "scope_type": "cfo",
    },
    {
        "nome": "SIGESP",
        "slug": "consulta-sigesp",
        "descricao": "Integração com sistema governamental SIGESP",
        "icone": "AccountBalance",
        "ordem": 7,
        "permissao_nome": "view_consulta_sigesp",
        "scope_type": "cfo",
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
        "nome": "Visão RFB",
        "slug": "consulta-rfb",
        "descricao": "Integração com Receita Federal do Brasil (CNPJ/CPF)",
        "icone": "Policy",
        "ordem": 12,
        "permissao_nome": "view_consulta_rfb",
        "scope_type": "cfo",
    },
]


_CANONICAL_SLUGS = {svc["slug"] for svc in SERVICOS_DATA}
_SYNC_FIELDS = ("nome", "descricao", "icone", "ordem", "permissao_nome", "scope_type")


def _sync_servico_fields(existing_svc: Servico, svc: dict, scope: str) -> bool:
    """Atualiza registro existente conforme SERVICOS_DATA. Retorna True se alterou."""
    changed = False
    for key in _SYNC_FIELDS:
        new_val = svc.get(key) if key != "scope_type" else scope
        if getattr(existing_svc, key, None) != new_val:
            setattr(existing_svc, key, new_val)
            changed = True
    if not existing_svc.ativo:
        existing_svc.ativo = True
        changed = True
    return changed


def _deactivate_removed_servicos(db) -> int:
    """Desativa serviços legados que não fazem mais parte do catálogo oficial."""
    count = 0
    for svc in db.query(Servico).filter(Servico.ativo == True).all():
        if svc.slug not in _CANONICAL_SLUGS:
            svc.ativo = False
            count += 1
            print(f"  Serviço desativado: {svc.nome} ({svc.slug})")
    return count


def init_servicos_data():
    """Inicializa catálogo de serviços e suas permissões (idempotente, com sync)."""
    db = SessionLocal()
    try:
        updated_count = 0
        for svc in SERVICOS_DATA:
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
                desc = f"Acessar {svc['nome']}"
                if existing_perm.description != desc:
                    existing_perm.description = desc
                if existing_perm.scope_type != scope:
                    existing_perm.scope_type = scope

            existing_svc = db.query(Servico).filter(Servico.slug == svc["slug"]).first()
            if not existing_svc:
                servico = Servico(**svc)
                db.add(servico)
                print(f"  Serviço criado: {svc['nome']} ({svc['slug']})")
            else:
                if _sync_servico_fields(existing_svc, svc, scope):
                    updated_count += 1
                    print(f"  Serviço atualizado: {svc['nome']} ({svc['slug']})")

        deactivated = _deactivate_removed_servicos(db)
        db.commit()
        msg = "Catálogo de serviços inicializado com sucesso!"
        parts = []
        if updated_count:
            parts.append(f"{updated_count} atualizado(s)")
        if deactivated:
            parts.append(f"{deactivated} desativado(s)")
        if parts:
            msg += f" ({', '.join(parts)})"
        print(msg)

    except Exception as e:
        print(f"Erro ao inicializar serviços: {e}")
        db.rollback()
    finally:
        db.close()
