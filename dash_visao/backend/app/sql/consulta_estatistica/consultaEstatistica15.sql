SELECT
    RE.CRO,
    RE.Especialidade,
    RE.Masculino,
    RE.Feminino,
    RE.TOTAL
FROM CFO_CWS.dbo.vw_Cons_Registro_Especialidades_Tecnicas AS RE
WHERE [CRO] = @CRO_UF
ORDER BY
    CASE WHEN RE.CRO = 'BRASIL' THEN 1 END,
    RE.CRO,
    CASE WHEN RE.Especialidade = 'TOTAL' THEN 1 END,
    RE.Especialidade