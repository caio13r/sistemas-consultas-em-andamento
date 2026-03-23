SELECT
    'CFO' AS [CFO],
    'CapitaisSociaisFaixas' AS [Tabela],
    ReCaSoFa.CodigoIntegracaoFederal,
    ReCaSoFa.Nome,
    ReCaSoFa.Valor
FROM [cfo_br].Registro.CapitaisSociaisFaixas AS ReCaSoFa
WHERE ReCaSoFa.Ativo = 1