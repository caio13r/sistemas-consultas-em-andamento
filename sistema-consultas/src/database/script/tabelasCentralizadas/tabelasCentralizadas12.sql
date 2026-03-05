SELECT
    'CFO' AS [CFO],
    'TiposInscricoes' AS [Tabela],
    ReTipInsc.CodigoIntegracaoFederal,
    ReTipInsc.Nome,
    ReTipInsc.Sigla,
    IIF(Profissional = 1, 'PF', 'PJ') AS [Tipo_Pessoa]
FROM [cfo_br].Registro.TiposInscricoes AS ReTipInsc
WHERE ReTipInsc.Ativo = 1
