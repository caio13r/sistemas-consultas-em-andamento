SELECT DISTINCT
    (
        SELECT CadCon.UF
        FROM Cadastro.Conselhos AS CadCon
        WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
    ) AS [CRO],
    ReCat.Sigla AS [CATEG],
    ReRe.NumeroRegistro AS [Nº_INSC],
    CadPe.NomeRazaoSocial AS [NOME_PROFISSIONAL],
    CadPe.CPFCNPJ AS [CPF_PROFISSIONAL],
    CONVERT(VARCHAR, CadPeFis.DataNascimento, 103) AS [DT_NASC],
    ReTipInsc.Nome AS [TIPO_INSCRIÇÃO],
    ReSit.Nome AS [SITUAÇÃO],
    ReSitDet.Nome AS [DETALHE],
    CadCur.Nome AS [NOME_D0_CURSO],
    P2.NomeRazaoSocial AS [INSTITUIÇÃO_ENSINO],
    COALESCE(CadInsEn.Codigo, 'NÃO INFORMADO') AS [CÓDIGO_INTERNO_CFO_INSTITUIÇÃO_ENSINO],
    COALESCE(CadInsEnsCam.Nome, 'NÃO INFORMADO') AS [CAMPUS],
    COALESCE(CONVERT(VARCHAR, ReForAca.DataConclusao, 103), 'NÃO INFORMADO') AS [DATA_CONCLUSAO],
    COALESCE(CONVERT(VARCHAR, ReForAca.DataColacao, 103), 'NÃO INFORMADO') AS [DATA_COLAÇÃO],
    CONVERT(VARCHAR, ReForAca.DataAtualizacao, 103) AS [Data_Atualizacao]
FROM Registro.Registros AS ReRe
INNER JOIN Cadastro.Pessoas AS CadPe ON CadPe.IdPessoa = ReRe.IdPessoa
INNER JOIN Cadastro.PessoasFisicas AS CadPeFis ON CadPe.IdPessoa = CadPeFis.IdPessoa
INNER JOIN Registro.Categorias AS ReCat ON ReRe.IdCategoria = ReCat.IdCategoria
INNER JOIN Registro.TiposInscricoes AS ReTipInsc ON ReRe.IdTipoInscricao = ReTipInsc.IdTipoInscricao
INNER JOIN Registro.Situacoes AS ReSit ON ReRe.IdSituacao = ReSit.IdSituacao
INNER JOIN Registro.SituacoesDetalhes AS ReSitDet ON ReRe.IdSituacaoDetalhe = ReSitDet.IdSituacaoDetalhe
LEFT JOIN Registro.FormacoesAcademicas AS ReForAca ON ReForAca.IdPessoa = ReRe.IdPessoa
LEFT JOIN Cadastro.InstituicoesEnsinoCampus AS CadInsEnsCam ON CadInsEnsCam.IdInstituicaoEnsinoCampus = ReForAca.IdInstituicaoEnsinoCampus
LEFT JOIN Cadastro.InstituicoesEnsino AS CadInsEn ON CadInsEn.IdInstituicaoEnsino = CadInsEnsCam.IdInstituicaoEnsino
LEFT JOIN Cadastro.Cursos AS CadCur ON CadCur.IdCurso = ReForAca.IdCurso
LEFT JOIN Cadastro.Pessoas AS P2 ON P2.IdPessoa = CadInsEn.IdPessoa
WHERE CadPe.Ativo = 1
    AND ReSit.Nome = 'ATIVO'
    -- @CAT
    -- AND ReCat.Sigla = 'CD'
ORDER BY CadPe.NomeRazaoSocial;
