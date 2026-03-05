SELECT
    'CFO' AS [CFO],
    'AtividadesEconomicas' AS [Tabela],
    CadAtEco.CodigoIntegracaoFederal,
    CadAtEco.DenominacaoCNAE
FROM [cfo_br].Cadastro.AtividadesEconomicas AS CadAtEco
WHERE CadAtEco.Ativo = 1