"""
Consulta Auditoria - Análises e auditorias dos CROs
Fonte: DB3 (SQL Server - CFO_CWS)
Views: vw_Rel_ativo_CPF_CNPJ_invalido, vw_Rel_PreCadastrado_com_inscricao,
       Cons_Provisorios_Vencidos, vw_Cons_Inscricao_Principal_Em_Mais_De_Um_CRO,
       vw_Cons_Inscricoes_Isentas, vw_Cons_Profissionais_idade_*,
       vw_Cons_PessoaFisica_CPF_Duplicado, vw_Cons_Filial_Sem_Matriz, etc.
"""
from fastapi import APIRouter, Depends, Query
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List
from ..database import get_db3
from ..models import User
from ..core.auth import check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-auditorias", tags=["consulta-auditorias"])

# Catálogo de tipos de auditoria com suas views e colunas
AUDIT_TYPES = {
    "cpf_cnpj_invalido": {
        "nome": "CPF/CNPJ Inválido",
        "view": "CFO_CWS.dbo.vw_Rel_ativo_CPF_CNPJ_invalido",
        "columns": "CRO, Categoria, Inscricao, [Nome/Razao_Social] as NomeRazaoSocial, [CPF/CNPJ] as CPFCNPJ, Tipo_Inscricao, Situacao, Detalhe",
        "limit": 500,
    },
    "pre_cadastrado_com_inscricao": {
        "nome": "Pré-cadastrado com Inscrição",
        "view": "CFO_CWS.dbo.vw_Rel_PreCadastrado_com_inscricao",
        "columns": "CRO, Nome, Categoria, [Tipo Inscrição] AS TipoInscricao, Inscrição AS Inscricao, Situação AS Situacao, Detalhe, [CPF / CNPJ] AS CPFCNPJ, [Data Situação] AS DataSituacao",
        "limit": 2000,
    },
    "provisorios_vencidos": {
        "nome": "Provisórios Vencidos",
        "view": "CFO_CWS.dbo.Cons_Provisorios_Vencidos",
        "columns": "CRO, Categoria, Inscricao, Nome, CPF, Tipo_Inscricao, Situacao, Detalhe, Data_Colacao_Grau, Data_Conclusao, Validade_Provisoria, Data_Situacao_Registro_Atual",
        "limit": 2000,
    },
    "inscricao_mais_de_um_cro": {
        "nome": "Inscrição Principal em Mais de Um CRO",
        "view": "CFO_CWS.dbo.vw_Cons_Inscricao_Principal_Em_Mais_De_Um_CRO",
        "columns": "NOME_1, CPF, CRO_1, CATE_1, INSC_1, TIPO_INSCRICAO_1, SITUACAO_1, DETALHE_1, DATA_INSC_1, CRO_2, CATE_2, INSC_2, TIPO_INSCRICAO_2, SITUACAO_2, DETALHE_2, DATA_INSC_2",
        "limit": 1000,
    },
    "inscricoes_isentas": {
        "nome": "Inscrições Isentas",
        "view": "CFO_CWS.dbo.vw_Cons_Inscricoes_Isentas",
        "columns": "*",
        "limit": 2000,
    },
    "profissionais_idade_inferior_20": {
        "nome": "Profissionais com Idade Inferior a 20 anos",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_idade_inferior_20",
        "columns": "*",
        "limit": 2000,
    },
    "profissionais_idade_superior_80": {
        "nome": "Profissionais com Idade Superior a 80 anos",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_idade_superior_80",
        "columns": "*",
        "limit": 2000,
    },
    "cpf_duplicado": {
        "nome": "CPF Duplicado (Pessoa Física)",
        "view": "CFO_CWS.dbo.vw_Cons_PessoaFisica_CPF_Duplicado",
        "columns": "*",
        "limit": 2000,
    },
    "cpf_duplicado_uma_categoria": {
        "nome": "CPF Duplicado em Uma Categoria",
        "view": "CFO_CWS.dbo.vw_Cons_Profissional_CPF_Duplicado_uma_Categoria",
        "columns": "*",
        "limit": 2000,
    },
    "filial_sem_matriz": {
        "nome": "Filial Sem Matriz",
        "view": "CFO_CWS.dbo.vw_Cons_Filial_Sem_Matriz",
        "columns": "*",
        "limit": 2000,
    },
    "rt_mais_de_uma_empresa": {
        "nome": "RT em Mais de Uma Empresa",
        "view": "CFO_CWS.dbo.vw_Cons_RT_Em_Mais_De_Uma_Empresa",
        "columns": "*",
        "limit": 2000,
    },
    "empresa_ativa_sem_rt": {
        "nome": "Empresa Ativa Sem RT",
        "view": "CFO_CWS.dbo.vw_Cons_Empresa_Ativa_Sem_RT",
        "columns": "*",
        "limit": 2000,
    },
    "caducados_registro_outro_estado": {
        "nome": "Caducados com Registro em Outro Estado",
        "view": "CFO_CWS.dbo.vw_Cons_Caducados_Com_Registro_Em_Outro_Estado",
        "columns": "*",
        "limit": 2000,
    },
    "identidades_digitais_emitidas": {
        "nome": "Identidades Digitais Emitidas por CRO (Consolidado)",
        "view": "CFO_CWS.dbo.vw_Cons_Identidades_Digitais_Emitidas_Por_CRO_Consolidado",
        "columns": "*",
        "limit": 2000,
    },
    "provisorios_vencidos_detalhado": {
        "nome": "Provisórios com validade expirada (detalhado)",
        "view": "CFO_CWS.dbo.Cons_Provisorios_Vencidos",
        "columns": "*",
        "limit": 2000,
    },
    "secundaria_sem_origem_ativa": {
        "nome": "Secundários ativos sem origem ativa",
        "view": "CFO_CWS.dbo.vw_Cons_Secundaria_Sem_Origem_Ativa",
        "columns": "*",
        "limit": 2000,
    },
    "profissionais_sem_data_colacao": {
        "nome": "CDS sem data de colação",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_Sem_Data_Colacao",
        "columns": "*",
        "limit": 2000,
    },
    "sem_email_correspondencia": {
        "nome": "Sem e-mail de correspondência",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_e_Empresas_Em_Atividade_Sem_o_Email_De_Correspondencia",
        "columns": "*",
        "limit": 2000,
    },
    "sem_data_inscricao": {
        "nome": "Sem data de inscrição",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_Empresas_Sem_Data_Inscricao",
        "columns": "*",
        "limit": 2000,
    },
    "usuarios_implanta": {
        "nome": "Usuários do Sistema Implanta",
        "view": "CFO_CWS.dbo.vw_Cons_Usuarios_Ativos_Inativos",
        "columns": "*",
        "limit": 2000,
    },
    "profissionais_nome_social": {
        "nome": "Profissionais com Nome Social",
        "view": "CFO_CWS.dbo.vw_Cons_Profissional_Com_Nome_Social",
        "columns": "*",
        "limit": 2000,
    },
    "pessoas_com_dda": {
        "nome": "Pessoas com DDA",
        "view": "CFO_CWS.dbo.vw_Cons_Pessoas_Com_DDA",
        "columns": "*",
        "limit": 2000,
    },
    "idade_remissao": {
        "nome": "Profissionais com idade para remissão",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_Com_Idade_Remissao",
        "columns": "*",
        "limit": 2000,
    },
    "multiplos_registros": {
        "nome": "Múltiplos Registros",
        "view": "CFO_CWS.dbo.vw_Cons_Multiplos_Registros",
        "columns": "*",
        "limit": 2000,
    },
    "parcelas_vencidas": {
        "nome": "Parcelas vencidas e não pagas",
        "view": "CFO_CWS.dbo.vw_Cons_Parcelamentos_Vencidos_Sem_Pagar",
        "columns": "*",
        "limit": 2000,
    },
    "sem_data_registro_federal": {
        "nome": "Sem data de inscrição CFO",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_Empresas_Sem_Data_Registro_Federal",
        "columns": "*",
        "limit": 2000,
    },
    "sem_celular_valido": {
        "nome": "Sem celular válido",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_e_Empresas_Em_Atividade_Sem_Celular_Valido",
        "columns": "*",
        "limit": 2000,
    },
    "tipo_temporario": {
        "nome": "Profissionais tipo temporário",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_Em_Atividade_Do_Tipo_Temporario",
        "columns": "*",
        "limit": 2000,
    },
    "detalhe_militar_isento": {
        "nome": "Detalhe militar isento",
        "view": "CFO_CWS.dbo.vw_Cons_Profissionais_Em_Atividade_Com_Detalhe_Militar_Isento",
        "columns": "*",
        "limit": 2000,
    },
}


class AuditoriaSearchResponse(BaseModel):
    total: int
    tipo: str
    nome: str
    resultados: List[dict]


@router.get("/tipos")
def listar_tipos_auditoria(
    current_user: User = Depends(check_permission("view_consulta_auditoria")),
):
    """Lista todos os tipos de auditoria disponíveis"""
    return [
        {"codigo": k, "nome": v["nome"]}
        for k, v in AUDIT_TYPES.items()
    ]


@router.get("/buscar", response_model=AuditoriaSearchResponse)
def buscar_auditoria(
    tipo: str = Query(..., description="Tipo de auditoria (ver /tipos)"),
    cro: Optional[str] = Query(None, description="Filtrar por CRO (ex: SP, RJ)"),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_auditoria")),
):
    """Busca dados de auditoria por tipo e CRO"""
    if tipo not in AUDIT_TYPES:
        from fastapi import HTTPException
        raise HTTPException(status_code=400, detail=f"Tipo inválido. Use um de: {list(AUDIT_TYPES.keys())}")

    audit = AUDIT_TYPES[tipo]
    view = audit["view"]
    columns = audit["columns"]
    limit = audit["limit"]

    conditions = []
    params = {}

    if cro:
        conditions.append("CRO = :cro")
        params["cro"] = cro.upper()

    where = " AND ".join(conditions) if conditions else "1=1"

    count_sql = text(f"SELECT COUNT(*) FROM {view} WHERE {where}")
    total = db3.execute(count_sql, params).scalar() or 0

    query_sql = text(f"SELECT TOP {limit} {columns} FROM {view} WHERE {where}")
    rows = db3.execute(query_sql, params).mappings().all()
    resultados = [dict(r) for r in rows]

    return AuditoriaSearchResponse(
        total=total,
        tipo=tipo,
        nome=audit["nome"],
        resultados=resultados,
    )
