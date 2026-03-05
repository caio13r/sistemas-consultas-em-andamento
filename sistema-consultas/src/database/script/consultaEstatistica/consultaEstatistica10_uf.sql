WITH Total_Ativos AS (

	SELECT 
		DP.SiglaCRO AS 'CRO',
		ISNULL(DC.UF, 'NI') AS 'UF',
		DP.IdRegistro AS 'IDREGISTRO',
		DP.Sigla_Categoria AS 'CATEGORIA',
		ISNULL(DC.Município, 'NÃO INFORMADO') AS 'MUNICIPIO'
	
	FROM CFO_CWS.dbo.VN_Dados_Profissionais AS DP
		LEFT JOIN CFO_CWS.dbo.VN_Dados_Contato AS DC
			ON DP.IdRegistro = DC.IdRegistro
	WHERE 
		DP.Situação = 'ATIVO'
		AND DP.SiglaCRO = @CRO_UF
	
	GROUP BY
		DP.SiglaCRO,
		DP.IdRegistro,
		DP.Sigla_Categoria,
		DC.Município,
		DC.UF
		
	UNION ALL

	SELECT
		DE.CroSigla AS 'CRO',
		ISNULL(DCE.UF, 'NI') AS 'UF',
		DE.IdRegistro AS 'IDREGISTRO',
		DE.CategoriaSigla AS 'CATEGORIA',
		ISNULL(DCE.Municipio, 'NÃO INFORMADO')  AS 'MUNICIPIO'

	FROM CFO_CWS.dbo.Cons_Visao_Nacional_PJ_Dados_da_Empresa AS DE
		LEFT JOIN CFO_CWS.dbo.Cons_Visao_Nacional_PJ_Endereco_e_Contato AS DCE
			ON DCE.IdRegistro = DE.IdRegistro
	WHERE 
		DE.Situacao = 'ATIVO'
		AND DE.CroSigla = @CRO_UF

	GROUP BY
		DE.CroSigla,
		DE.IdRegistro,
		DE.CategoriaSigla,
		DCE.Municipio,
		DCE.UF
)

SELECT 
	 CRO,
	 UF,
	 MUNICIPIO AS 'LOCALIDADE (END. CORRESPONDENCIA)',
    ISNULL([CD], 0) AS [CD],
    ISNULL([TPD], 0) AS [TPD],
    ISNULL([TSB], 0) AS [TSB],
    ISNULL([ASB], 0) AS [ASB],
    ISNULL([APD], 0) AS [APD],
    ISNULL([EPAO], 0) AS [EPAO],
    ISNULL([LB], 0) AS [LB],
    ISNULL([ECIPO], 0) AS [ECIPO],
    ISNULL([CD], 0) + ISNULL([TPD], 0) + ISNULL([TSB], 0) + ISNULL([ASB], 0) + ISNULL([APD], 0) + ISNULL([EPAO], 0) + ISNULL([LB], 0) + ISNULL([ECIPO], 0) AS [TOTAL]
FROM 
	(
		SELECT 
			CRO,
			CATEGORIA,
			MUNICIPIO,
			UF,
			COUNT(IDREGISTRO) AS 'TOTAL'
		FROM Total_Ativos

		GROUP BY 
			CRO,
			CATEGORIA,
			MUNICIPIO,
			UF

	) AS SourceTable

PIVOT 
(
	SUM(TOTAL)
	FOR CATEGORIA IN ([CD], [TPD], [TSB], [APD], [ASB], [EPAO], [LB], [ECIPO])
) AS PivotTable

ORDER BY UF, MUNICIPIO