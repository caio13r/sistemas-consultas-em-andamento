SELECT
    'CFO' AS [CFO],
    'Situacoes' AS [Tabela],
    ReSit.CodigoIntegracaoFederal,
    ReSit.Nome,
    IIF(ReSit.Profissional = 1, 'PJ', 'PF') AS [Tipo_Pessoa]
FROM [cfo_br].Registro.Situacoes AS ReSit
WHERE ReSit.EmUso = 1
ORDER BY ReSit.Nome, [Tipo_Pessoa]
