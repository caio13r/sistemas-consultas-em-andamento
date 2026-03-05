SELECT
    'CFO' AS [CFO],
    'SituacoesDetalhes' AS [Tabela],
    ReSitDet.CodigoIntegracaoFederal,
    ReSitDet.Nome,
    (   SELECT
            ISNULL(STRING_AGG(ReSit.Nome, ' | ') WITHIN GROUP (ORDER BY ReSit.Nome), '')
        FROM [cfo_br].Registro.SituacoesDetalhesSituacoes AS ReSitDetSit
            INNER JOIN [cfo_br].Registro.Situacoes AS ReSit
                ON ReSitDetSit.IdSituacao = ReSit.IdSituacao
        WHERE ReSitDet.IdSituacaoDetalhe = ReSitDetSit.IdSituacaoDetalhe
        AND ReSit.Profissional = 0
    ) AS [Situacoes_PF],
    (   SELECT
            ISNULL(STRING_AGG(ReSit.Nome, ' | ') WITHIN GROUP (ORDER BY ReSit.Nome), '')
        FROM [cfo_br].Registro.SituacoesDetalhesSituacoes AS ReSitDetSit
            INNER JOIN [cfo_br].Registro.Situacoes AS ReSit
                ON ReSitDetSit.IdSituacao = ReSit.IdSituacao
        WHERE ReSitDet.IdSituacaoDetalhe = ReSitDetSit.IdSituacaoDetalhe
        AND ReSit.Profissional = 1
    ) AS [Situacoes_PJ]
FROM [cfo_br].Registro.SituacoesDetalhes AS ReSitDet
WHERE ReSitDet.EmUso = 1