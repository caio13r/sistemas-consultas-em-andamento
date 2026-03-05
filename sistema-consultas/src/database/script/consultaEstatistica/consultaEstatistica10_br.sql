SELECT
    [CRO],
    [UF],
    [LOCALIDADE (END. CORRESPONDENCIA)],
    [CD],
    [TPD],
    [TSB],
    [ASB],
    [APD],
    [EPAO],
    [LB],
    [ECIPO],
    [TOTAL]
FROM CFO_CWS.dbo.Cons_Total_Ativos_Localidade
ORDER BY 
    IIF([CRO] = 'BRASIL', 1, 0),
    [CRO],
    IIF([UF] = 'TOTAL', 1, 0),
    [UF],
    [LOCALIDADE (END. CORRESPONDENCIA)]