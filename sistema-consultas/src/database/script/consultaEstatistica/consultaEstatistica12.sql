SELECT
    [CRO],
    [UF],
    [Localidade],
    [CD] ,
    [TPD],
    [TSB],
    [APD],
    [ASB],
    TOTAL
FROM CFO_CWS.dbo.vw_Cons_Total_Enderecos_Residenciais_Ativos_Localidade
WHERE [CRO] = @CRO_UF 
ORDER BY
    IIF ([CRO] = 'BRASIL', 1, 0),
    [CRO],
    IIF ([UF] = 'TOTAL', 1, 0),
    [UF],
    [Localidade]