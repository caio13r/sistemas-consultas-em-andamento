SELECT
    'CFO' AS [CFO],
    'NaturezasJuridicas' AS [Tabela],
    CadNatJur.CodigoIntegracaoFederal,
    CadNatJur.CodigoCONCLA,
    CadNatJur.Descricao
FROM [cfo_br].Cadastro.NaturezasJuridicas AS CadNatJur
WHERE CadNatJur.Ativo = 1