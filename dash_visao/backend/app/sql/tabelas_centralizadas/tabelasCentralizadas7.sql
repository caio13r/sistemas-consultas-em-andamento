SELECT
    'CFO' AS [CFO],
    'DebitosTipos' AS [Tabela],
    FiDeTip.CodigoIntegracaoFederal,
    FiDeTip.Nome,
    FiDeTip.Sigla
FROM [cfo_br].Financeiro.DebitosTipos AS FiDeTip
WHERE FiDeTip.Ativo = 1