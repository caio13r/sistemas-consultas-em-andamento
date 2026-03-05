SELECT
    CRO,
    CONVERT(VARCHAR, DataCredito, 103) AS Data_Credito,
    SUM(ValorBruto) AS Valor_Bruto,
    SUM(ValorLiquido - SplitFederal) AS Valor_CRO,
    SUM(ValorBruto - ValorLiquido) AS Tarifa_Cartao,
    SUM(SplitFederal) AS Split_Federal

FROM [CFO_CWS].[dbo].[vw_Cons_Relatorio_de_Arrecadacao_e_Tarifas_Selfpay]

WHERE DataCredito BETWEEN @DataInicio AND @DataFim

GROUP BY CRO, DataCredito

ORDER BY CRO, DataCredito 