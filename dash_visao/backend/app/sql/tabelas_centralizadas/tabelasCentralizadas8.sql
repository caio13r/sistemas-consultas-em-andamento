SELECT
    'CFO' AS [CFO],
    'Especialidades' AS [Tabela],
    ReEs.CodigoIntegracaoFederal,
    ReEs.Nome
FROM [cfo_br].Registro.Especialidades AS ReEs
WHERE ReEs.Ativo = 1