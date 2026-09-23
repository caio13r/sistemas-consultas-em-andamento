"""
Consulta Integrada - Visão Nacional de Profissionais e Empresas
Fonte: DB3 (SQL Server - CFO_CWS)
Views:
  PF: Cons_Visao_Nacional_PF_Dados_do_Profissional, _Dados_Pessoais, _Endereco_e_Contato, _Formacoes, _Responsabilidade_Tecnica
  PJ: Cons_Visao_Nacional_PJ_Dados_da_Empresa, _Endereco_e_Contato, _Responsabilidade_Tecnica
"""
from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy import text
from sqlalchemy.orm import Session
from typing import Optional, List, Union
from ..database import get_db3
from ..models import User
from ..core.auth import get_current_active_user, check_permission
from pydantic import BaseModel

router = APIRouter(prefix="/consulta-integrada", tags=["consulta-integrada"])

MAX_RESULTS = 2000

UF_LIST = [
    "AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO",
    "MA", "MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR",
    "RJ", "RN", "RO", "RR", "RS", "SC", "SE", "SP", "TO"
]

CATEGORIAS = ["CD", "TPD", "THD", "ASB", "TSB", "APD", "EPAO", "LB", "ECIPO"]


class ProfissionalResult(BaseModel):
    nome: Optional[str] = None
    cpf: Optional[str] = None
    cro: Optional[str] = None
    categoria: Optional[str] = None
    inscricao: Optional[str] = None
    data_inscricao: Optional[str] = None
    tipo_inscricao: Optional[str] = None
    situacao: Optional[str] = None
    detalhe: Optional[str] = None
    situacao_financeira: Optional[str] = None
    id_registro: Optional[Union[str, int]] = None


class ProfissionalSearchResponse(BaseModel):
    total: int
    resultados: List[ProfissionalResult]


class ProfissionalDetalhe(BaseModel):
    # Dados do profissional
    nome: Optional[str] = None
    cpf: Optional[str] = None
    cro: Optional[str] = None
    categoria: Optional[str] = None
    inscricao: Optional[str] = None
    tipo_inscricao: Optional[str] = None
    situacao: Optional[str] = None
    detalhe: Optional[str] = None
    situacao_financeira: Optional[str] = None
    data_inscricao: Optional[str] = None
    data_situacao: Optional[str] = None
    # Dados pessoais
    data_nascimento: Optional[str] = None
    genero: Optional[str] = None
    nome_mae: Optional[str] = None
    nome_pai: Optional[str] = None
    estado_civil: Optional[str] = None
    nacionalidade: Optional[str] = None
    naturalidade: Optional[str] = None
    identidade: Optional[str] = None
    orgao_emissor: Optional[str] = None
    uf_rg: Optional[str] = None
    data_emissao_rg: Optional[str] = None
    nome_social: Optional[str] = None
    # Contato
    email: Optional[str] = None
    telefone: Optional[str] = None
    rede_social: Optional[str] = None
    tipo_endereco: Optional[str] = None
    logradouro: Optional[str] = None
    numero: Optional[str] = None
    complemento: Optional[str] = None
    bairro: Optional[str] = None
    municipio: Optional[str] = None
    uf: Optional[str] = None
    cep: Optional[str] = None
    # Formação
    formacoes: Optional[List[dict]] = None
    # Responsabilidade Técnica (empresas)
    responsabilidades_tecnicas: Optional[List[dict]] = None
    # Processos de Especialidade/Habilitação (SISDOC)
    processos_especialidade: Optional[List[dict]] = None


class EmpresaResult(BaseModel):
    razao_social: Optional[str] = None
    nome_fantasia: Optional[str] = None
    cnpj: Optional[str] = None
    cro: Optional[str] = None
    categoria: Optional[str] = None
    inscricao: Optional[str] = None
    situacao: Optional[str] = None
    detalhe: Optional[str] = None
    situacao_financeira: Optional[str] = None
    logradouro: Optional[str] = None
    municipio: Optional[str] = None
    uf: Optional[str] = None
    telefone: Optional[str] = None
    email: Optional[str] = None
    id_registro: Optional[Union[str, int]] = None


class EmpresaSearchResponse(BaseModel):
    total: int
    resultados: List[EmpresaResult]


@router.get("/profissionais", response_model=ProfissionalSearchResponse)
def buscar_profissionais(
    nome: Optional[str] = Query(None),
    inscricao: Optional[str] = Query(None),
    cpf: Optional[str] = Query(None),
    email: Optional[str] = Query(None),
    telefone: Optional[str] = Query(None),
    cro: Optional[str] = Query(None, description="Sigla do CRO (ex: SP, RJ)"),
    categoria: Optional[str] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_integrada")),
):
    """Busca profissionais na visão nacional (DB3 - SQL Server)"""
    conditions = []
    params = {}

    if cro:
        conditions.append("dp.CroSigla = :cro")
        params["cro"] = cro.upper()
    if categoria:
        conditions.append("dp.CategoriaSigla = :categoria")
        params["categoria"] = categoria.upper()
    if nome:
        conditions.append("dp.Nome LIKE :nome")
        params["nome"] = f"%{nome}%"
    if inscricao:
        conditions.append("dp.Inscricao LIKE :inscricao")
        params["inscricao"] = f"%{inscricao}%"
    if cpf:
        cpf_limpo = cpf.replace(".", "").replace("-", "").replace("/", "")
        conditions.append("REPLACE(REPLACE(REPLACE(dp.CPF, '.', ''), '-', ''), '/', '') LIKE :cpf")
        params["cpf"] = f"%{cpf_limpo}%"
    if email:
        emails = [e.strip() for e in email.split(",") if e.strip()]
        if len(emails) == 1:
            conditions.append("EXISTS (SELECT 1 FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato dp2 WHERE dp2.IdRegistro = dp.IdRegistro AND dp2.Email LIKE :email_0)")
            params["email_0"] = f"%{emails[0]}%"
        elif emails:
            or_parts = []
            for i, e in enumerate(emails):
                or_parts.append(f"dp2.Email LIKE :email_{i}")
                params[f"email_{i}"] = f"%{e}%"
            conditions.append(f"EXISTS (SELECT 1 FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato dp2 WHERE dp2.IdRegistro = dp.IdRegistro AND ({' OR '.join(or_parts)}))")
    if telefone:
        tel_limpo = telefone.replace("(", "").replace(")", "").replace("-", "").replace(" ", "")
        conditions.append("EXISTS (SELECT 1 FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato dp2 WHERE dp2.IdRegistro = dp.IdRegistro AND REPLACE(REPLACE(REPLACE(REPLACE(dp2.Telefone, '(', ''), ')', ''), '-', ''), ' ', '') LIKE :telefone)")
        params["telefone"] = f"%{tel_limpo}%"

    where = " AND ".join(conditions) if conditions else "1=1"
    order_by = (
        """
        CASE
            WHEN TRY_CONVERT(bigint, NULLIF(REPLACE(REPLACE(REPLACE(dp.Inscricao, '.', ''), '-', ''), '/', ''), '')) IS NULL THEN 1
            ELSE 0
        END,
        TRY_CONVERT(bigint, NULLIF(REPLACE(REPLACE(REPLACE(dp.Inscricao, '.', ''), '-', ''), '/', ''), '')) DESC,
        dp.Nome
        """
        if cro and categoria
        else "dp.Nome"
    )

    # Count
    count_sql = text(f"""
        SELECT COUNT(*) FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional dp
        WHERE {where}
    """)
    total = db3.execute(count_sql, params).scalar() or 0

    # Results
    query_sql = text(f"""
        SELECT TOP {MAX_RESULTS}
            dp.Nome AS nome,
            dp.CPF AS cpf,
            dp.CroSigla AS cro,
            dp.CategoriaSigla AS categoria,
            dp.Inscricao AS inscricao,
            CONVERT(varchar(10), dp.DataInscricao, 103) AS data_inscricao,
            dp.TipoDeInscricao AS tipo_inscricao,
            dp.Situacao AS situacao,
            dp.DetalheSituacao AS detalhe,
            dp.SituacaoFinanceira AS situacao_financeira,
            dp.IdRegistro AS id_registro
        FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional dp
        WHERE {where}
        ORDER BY {order_by}
    """)
    rows = db3.execute(query_sql, params).mappings().all()

    resultados = [ProfissionalResult(**dict(r)) for r in rows]
    return ProfissionalSearchResponse(total=total, resultados=resultados)


def _safe_val(v):
    """Converte valores para string ou None (evita erro Pydantic com datetime/guid)"""
    if v is None:
        return None
    return str(v)


def _run_query(engine, sql_str: str, params: dict) -> list:
    """Executa uma query em conexão própria (para uso em threads paralelas)."""
    with engine.connect() as conn:
        rows = conn.execute(text(sql_str), params).mappings().all()
        return [{k: _safe_val(v) for k, v in dict(r).items()} for r in rows]


@router.get("/profissionais/{id_registro}", response_model=ProfissionalDetalhe)
def detalhe_profissional(
    id_registro: Union[str, int],
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_integrada")),
):
    """Detalhes completos de um profissional por IdRegistro (UUID ou int)"""
    from concurrent.futures import ThreadPoolExecutor
    from ..database import engine_db3

    params = {"id_registro": str(id_registro)}

    # Todas as queries rodam em paralelo — valida existência depois
    queries = {
        "prof": """
            SELECT TOP 1
                dp.Nome AS nome, dp.CPF AS cpf, dp.CroSigla AS cro,
                dp.CategoriaSigla AS categoria, dp.Inscricao AS inscricao,
                dp.TipoDeInscricao AS tipo_inscricao, dp.Situacao AS situacao,
                dp.DetalheSituacao AS detalhe, dp.SituacaoFinanceira AS situacao_financeira,
                dp.DataInscricao AS data_inscricao, dp.DataSituacao AS data_situacao
            FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_do_Profissional dp
            WHERE dp.IdRegistro = CAST(:id_registro AS UNIQUEIDENTIFIER)
        """,
        "pessoais": """
            SELECT TOP 1
                DataNascimento AS data_nascimento, Genero AS genero,
                NomeDaMae AS nome_mae, NomeDoPai AS nome_pai,
                EstadoCivil AS estado_civil, Nacionalidade AS nacionalidade,
                Naturalidade AS naturalidade, Identidade AS identidade,
                OrgaoEmissor AS orgao_emissor, UF AS uf_rg,
                DataEmissaoRG AS data_emissao_rg, NomeSocial AS nome_social
            FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Dados_Pessoais
            WHERE IdRegistro = CAST(:id_registro AS UNIQUEIDENTIFIER)
        """,
        "contato": """
            SELECT TOP 1
                Email AS email, Telefone AS telefone, RedeSocial AS rede_social,
                TipoEndereco AS tipo_endereco, Logradouro AS logradouro, Numero AS numero,
                Complemento AS complemento, Bairro AS bairro,
                Municipio AS municipio, UF AS uf, CEP AS cep
            FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Endereco_e_Contato
            WHERE IdRegistro = CAST(:id_registro AS UNIQUEIDENTIFIER)
        """,
        "formacoes": """
            SELECT InstituicaoDeEnsino, Curso, DataDeColacao, DataDeConclusao,
                   Especialidades, Habilitacao
            FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Formacoes
            WHERE IdRegistro = CAST(:id_registro AS UNIQUEIDENTIFIER)
        """,
        "resp_tec": """
            SELECT TipoResponsabilidade AS tipo,
                   RazaoSocialDaEmpresa AS razao_social,
                   NomeFantasiaDaEmpresa AS nome_fantasia,
                   CNPJ AS cnpj, CategoriaDaEmpresa AS categoria,
                   RegistroDaEmpresa AS registro,
                   DataInicio AS data_inicio, DataTermino AS data_termino
            FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Responsabilidade_Tecnica
            WHERE IdRegistro = CAST(:id_registro AS UNIQUEIDENTIFIER)
        """,
        "processos": """
            SELECT NumeroProcesso AS numero_processo, Assunto AS assunto,
                   Cassificacao AS classificacao, Etapa AS etapa,
                   Andamento AS andamento, DataAndamento AS data_andamento
            FROM CFO_CWS.dbo.Cons_Visao_Nacional_PF_Processo_de_Especialidade_ou_Habilitacao
            WHERE IdRegistro = CAST(:id_registro AS UNIQUEIDENTIFIER)
        """,
    }

    with ThreadPoolExecutor(max_workers=6) as pool:
        futures = {
            key: pool.submit(_run_query, engine_db3, sql, params)
            for key, sql in queries.items()
        }
        results = {key: f.result() for key, f in futures.items()}

    if not results["prof"]:
        raise HTTPException(status_code=404, detail="Profissional não encontrado")

    result = results["prof"][0]
    if results["pessoais"]:
        result.update(results["pessoais"][0])
    if results["contato"]:
        result.update(results["contato"][0])
    result["formacoes"] = results["formacoes"]
    result["responsabilidades_tecnicas"] = results["resp_tec"]
    result["processos_especialidade"] = results["processos"]

    return ProfissionalDetalhe(**result)


@router.get("/empresas", response_model=EmpresaSearchResponse)
def buscar_empresas(
    nome: Optional[str] = Query(None, description="Razão social ou nome fantasia"),
    cnpj: Optional[str] = Query(None),
    inscricao: Optional[str] = Query(None),
    cro: Optional[str] = Query(None),
    categoria: Optional[str] = Query(None),
    email: Optional[str] = Query(None),
    telefone: Optional[str] = Query(None),
    db3: Session = Depends(get_db3),
    current_user: User = Depends(check_permission("view_consulta_integrada")),
):
    """Busca empresas/PJ na visão nacional (DB3 - SQL Server)"""
    conditions = []
    params = {}

    if cro:
        conditions.append("LEFT(pj.CRO, 2) = :cro")
        params["cro"] = cro.upper()
    if categoria:
        conditions.append("pj.CategoriaSigla = :categoria")
        params["categoria"] = categoria.upper()
    if nome:
        conditions.append("(pj.RazaoSocial LIKE :nome OR pj.NomeFantasia LIKE :nome)")
        params["nome"] = f"%{nome}%"
    if cnpj:
        cnpj_limpo = cnpj.replace(".", "").replace("-", "").replace("/", "")
        conditions.append("REPLACE(REPLACE(REPLACE(pj.CNPJ, '.', ''), '-', ''), '/', '') LIKE :cnpj")
        params["cnpj"] = f"%{cnpj_limpo}%"
    if inscricao:
        conditions.append("pj.Inscricao LIKE :inscricao")
        params["inscricao"] = f"%{inscricao}%"
    if email:
        conditions.append("ec.Email LIKE :email")
        params["email"] = f"%{email}%"
    if telefone:
        conditions.append("ec.Telefone LIKE :telefone")
        params["telefone"] = f"%{telefone}%"

    where = " AND ".join(conditions) if conditions else "1=1"

    count_sql = text(f"""
        SELECT COUNT(*)
        FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Dados_da_Empresa] pj
        LEFT JOIN [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Endereco_e_Contato] ec ON pj.IdRegistro = ec.IdRegistro
        WHERE {where}
    """)
    total = db3.execute(count_sql, params).scalar() or 0

    query_sql = text(f"""
        SELECT TOP {MAX_RESULTS}
            pj.RazaoSocial AS razao_social,
            pj.NomeFantasia AS nome_fantasia,
            pj.CNPJ AS cnpj,
            LEFT(pj.CRO, 2) AS cro,
            pj.CategoriaSigla AS categoria,
            pj.Inscricao AS inscricao,
            pj.Situacao AS situacao,
            pj.DetalheSituacao AS detalhe,
            pj.SituacaoFinanceira AS situacao_financeira,
            ec.Logradouro AS logradouro,
            ec.Municipio AS municipio,
            ec.UF AS uf,
            ec.Telefone AS telefone,
            ec.Email AS email,
            pj.IdRegistro AS id_registro
        FROM [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Dados_da_Empresa] pj
        LEFT JOIN [CFO_CWS].[dbo].[Cons_Visao_Nacional_PJ_Endereco_e_Contato] ec ON pj.IdRegistro = ec.IdRegistro
        WHERE {where}
        ORDER BY pj.RazaoSocial
    """)
    rows = db3.execute(query_sql, params).mappings().all()
    resultados = [EmpresaResult(**dict(r)) for r in rows]
    return EmpresaSearchResponse(total=total, resultados=resultados)


@router.get("/ufs", response_model=List[str])
def listar_ufs(current_user: User = Depends(get_current_active_user)):
    return UF_LIST


@router.get("/categorias", response_model=List[str])
def listar_categorias(current_user: User = Depends(get_current_active_user)):
    return CATEGORIAS
