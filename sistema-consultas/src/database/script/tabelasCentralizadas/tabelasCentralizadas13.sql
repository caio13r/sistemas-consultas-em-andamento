SELECT
	'CFO' AS [CRO],
    'MotivosFiscalizacoes' AS [Tabela],
    FisMoFis.CodigoIntegracaoFederal AS [Codigo_Integracao_Federal],
	FisMoFis.Nome AS [Motivo_Fiscalizacao],
    FisClaFis.Nome AS [Classificacao_Fiscalizacao]
FROM cfo_br.Fiscalizacao.MotivosFiscalizacoes AS FisMoFis
    INNER JOIN cfo_br.Fiscalizacao.ClassificacoesFiscalizacoes AS FisClaFis
        ON FisMoFis.IdClassificacaoFiscalizacao = FisClaFis.IdClassificacaoFiscalizacao
WHERE FisMoFis.Ativo = 1
ORDER BY
    [CRO],
    [Motivo_Fiscalizacao]