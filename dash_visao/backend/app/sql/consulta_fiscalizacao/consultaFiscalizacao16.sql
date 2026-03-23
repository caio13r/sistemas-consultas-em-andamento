SELECT
    CONVERT(VARCHAR, ':inicio', 103) AS [Data_Inicio_Termo],
    CONVERT(VARCHAR, ':termino', 103) AS [Data_Fim_Termo],
    (SELECT CadCon.UF
        FROM :banco.Cadastro.Conselhos AS CadCon
        WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
    ) AS [CRO],
    ISNULL(D.[Origem], 'TOTAL') AS [Origem],
    SUM(D.[Quantidade_Denuncias]) AS [Quantidade_Denuncias],
    SUM(D.[Denuncias_Anonimas]) AS [Denuncias_Anonimas],
    SUM(D.[Denuncias_Identificadas]) AS [Denuncias_Identificadas]
FROM (
    SELECT
	    CASE
		    WHEN T.Origem = 'SISDOC' THEN 'SISDOC (Painel_Denuncias)'
		    ELSE T.Origem
	    END AS [Origem],
	    ISNULL(COUNT(FiDe.IdDenuncia), 0) AS [Quantidade_Denuncias],
	    ISNULL(SUM(CASE WHEN FiDe.DenunciaAnonima = 1 THEN 1 ELSE 0 END), 0) AS [Denuncias_Anonimas],
	    ISNULL(SUM(CASE WHEN FiDe.DenunciaAnonima = 0 THEN 1 ELSE 0 END), 0) AS [Denuncias_Identificadas]
    FROM ( VALUES ('ServiçosOnLine'), ('SISDOC') ) AS T (Origem)
	    LEFT JOIN :banco.Fiscalizacao.Denuncias AS FiDe
		    ON FiDe.OrigemDenuncia = T.Origem
		    AND FiDe.DataCriacao BETWEEN ':inicio' AND ':termino'
    GROUP BY T.Origem

    UNION

    SELECT
        'SISDOC (Protocolo_Denuncia)' AS [Origem],
        ISNULL(COUNT(DoDo.IdDocumento), 0) AS [Quantidade de Den�ncias],
        0 AS [Denuncias_Anonimas],
        0 AS [Denuncias_Identificadas]
    FROM :banco.Documento.Documentos AS DoDo
        INNER JOIN :banco.Documento.TiposDocumentos AS DoTipDo
            ON DoDo.IdTipoDocumento = DoTipDo.IdTipoDocumento
            AND DoTipDo.Sigla = 'DEN'
    WHERE DoDo.OrigemCriacaoModulo IS NOT NULL
        AND DoDo.DataCriacao BETWEEN ':inicio' AND ':termino'
) AS D
GROUP BY ROLLUP(D.[Origem])
ORDER BY
    [CRO],
    IIF(D.[Origem] IS NULL, 1, 0),
    D.[Origem]