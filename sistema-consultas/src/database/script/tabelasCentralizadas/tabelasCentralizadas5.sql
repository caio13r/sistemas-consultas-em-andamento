SELECT
    'CFO' AS [CFO],
    'ClassificacoesEmpresas' AS [Tabela],
    ReClaEm.CodigoIntegracaoFederal,
    ReClaEm.Nome
FROM [cfo_br].Registro.ClassificacoesEmpresas AS ReClaEm
WHERE ReClaEm.Ativo = 1