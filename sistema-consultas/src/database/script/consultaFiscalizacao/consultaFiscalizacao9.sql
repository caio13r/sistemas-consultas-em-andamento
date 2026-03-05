SELECT
    QF.[CRO],
    QF.[Qtd_Fiscais]
FROM CFO_CWS.dbo.vw_Cons_Qtd_Fiscais AS QF
WHERE [CRO] = @CRO_UF
ORDER BY
    IIF ([CRO] = 'BRASIL', 1, 0),
    [CRO]