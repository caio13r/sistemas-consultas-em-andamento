SELECT
    'CFO' AS [CFO],
    'Categorias' AS [Tabela],
    ReCat.CodigoIntegracaoFederal,
    ReCat.Nome,
    ReCat.Sigla
FROM [cfo_br].Registro.Categorias AS ReCat
WHERE ReCat.Ativo = 1