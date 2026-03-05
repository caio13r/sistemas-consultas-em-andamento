SELECT
    RE.Ano,
    RE.CRO,
    RE.Especialidade,
    RE.Masculino,
    RE.Feminino,
    RE.TOTAL
FROM CFO_CWS.dbo.Cons_Registro_Habilitacoes_Por_Ano AS RE
WHERE [CRO] = @CRO_UF
AND [Ano] = @Ano
ORDER BY
    CASE WHEN RE.Ano = 'TOTAL' THEN 1 END,
    RE.Ano,
    CASE WHEN RE.CRO = 'BRASIL' THEN 1 END,
    RE.CRO,
    CASE WHEN RE.Especialidade = 'TOTAL' THEN 1 END,
    RE.Especialidade