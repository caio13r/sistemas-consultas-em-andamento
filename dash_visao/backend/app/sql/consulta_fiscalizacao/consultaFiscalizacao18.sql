SELECT
    CONVERT(VARCHAR, ':inicio', 103) AS [Data_Inicio_Termo],
    CONVERT(VARCHAR, ':termino', 103) AS [Data_Fim_Termo],
	CONVERT(VARCHAR(6), (SELECT CadCon.UF
		FROM :banco.Cadastro.Conselhos AS CadCon
		WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
	)) AS [CRO],
	ISNULL(CadPeFis.Idade, 'TOTAL') AS [Idade],
	COUNT(CASE WHEN ReCat.Sigla = 'APD' THEN 1 END) AS APD,
	COUNT(CASE WHEN ReCat.Sigla = 'ASB' THEN 1 END) AS ASB,
	COUNT(CASE WHEN ReCat.Sigla = 'CD'  THEN 1 END) AS CD ,
	COUNT(CASE WHEN ReCat.Sigla = 'TPD' THEN 1 END) AS TPD,
	COUNT(CASE WHEN ReCat.Sigla = 'TSB' THEN 1 END) AS TSB,
	COUNT(*) AS [Total_geral]
FROM :banco.Registro.Registros AS ReRe												-- ReRe
	INNER JOIN :banco.Registro.Categorias AS ReCat									-- ReRe - ReCat
		ON ReRe.IdCategoria = ReCat.IdCategoria -- [CD] - [TPD] - [TSB] - [ASB] - [APD]
	INNER JOIN :banco.Cadastro.Pessoas AS CadPe									-- ReRe - CadPe
		ON ReRe.IdPessoa = CadPe.IdPessoa -- Caminho / filtro (apenas ativos e PF)
		AND CadPe.Ativo = 1
		AND CadPe.TipoPessoaFisica = 1
	INNER JOIN (
		SELECT 
			ISNULL(
				CONVERT(VARCHAR(8), 
					DATEDIFF(YEAR, CadPeFis.DataNascimento, GETDATE()) - 
					CASE 
						WHEN MONTH(CadPeFis.DataNascimento) > MONTH(GETDATE()) OR 
							(MONTH(CadPeFis.DataNascimento) = MONTH(GETDATE()) AND DAY(CadPeFis.DataNascimento) > DAY(GETDATE())) 
						THEN 1 
						ELSE 0 
					END
				), 'Sem data'
			) AS Idade,
			CadPeFis.IdPessoa
		FROM :banco.Cadastro.PessoasFisicas AS CadPeFis
	) AS CadPeFis															-- ReRe - CadPe - CadPeFis
		ON CadPe.IdPessoa = CadPeFis.IdPessoa -- [Idade]
	INNER JOIN :banco.Fiscalizacao.VisitasFiscalizadosRegistros AS FisVisFisReg	-- ReRe - FisVisFisReg
		ON ReRe.IdRegistro = FisVisFisReg.IdRegistro -- Filtro (apenas fiscalizados)
	INNER JOIN :banco.Fiscalizacao.VisitasFiscalizados AS FisVisFis				-- ReRe - FisVisFisReg - FisVisFis
		ON FisVisFisReg.IdVisitaFiscalizado = FisVisFis.IdVisitaFiscalizado -- Caminho
	INNER JOIN :banco.Fiscalizacao.Visitas AS FisVis								-- ReRe - FisVisFisReg - FisVisFis - FisVis
		ON FisVisFis.IdVisita = FisVis.IdVisita -- Filtro (data)
WHERE FisVis.DataVisita BETWEEN ':inicio' AND ':termino'
GROUP BY ROLLUP (CadPeFis.Idade)
ORDER BY
	[CRO],
	CASE
		WHEN [Idade] IS NULL THEN 4
		WHEN LEN([Idade]) = 3 THEN 2
		WHEN LEN([Idade]) = 2 THEN 1
		ELSE 0
	END,
	[Idade]
;