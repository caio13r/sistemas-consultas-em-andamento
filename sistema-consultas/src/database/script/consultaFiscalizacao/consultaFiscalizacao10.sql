SELECT
    NF.[CRO],
    NF.[Nome_Fiscal],
    NF.[CPF]
FROM CFO_CWS.dbo.vw_Cons_Nomes_Fiscais AS NF
WHERE [CRO] = @CRO_UF
ORDER BY [CRO], [Nome_Fiscal]