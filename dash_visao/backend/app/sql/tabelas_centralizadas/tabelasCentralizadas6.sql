SELECT
    'CFO' AS [CFO],
    'Cursos' AS [Tabela],
    CadCur.CodigoIntegracaoFederal,
    CadCur.Nome
FROM [cfo_br].Cadastro.Cursos AS CadCur
WHERE CadCur.Ativo = 1