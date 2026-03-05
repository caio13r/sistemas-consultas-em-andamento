select  MES_COBRANCA
		, case 
				when MES_COBRANCA = '01' then 'JANEIRO'
				when MES_COBRANCA = '02' then 'FEVEREIRO'
				when MES_COBRANCA = '03' then 'MARÇO'
				when MES_COBRANCA = '04' then 'ABRIL'
				when MES_COBRANCA = '05' then 'MAIO'
				when MES_COBRANCA = '06' then 'JUNHO'
				when MES_COBRANCA = '07' then 'JULHO'
				when MES_COBRANCA = '08' then 'AGOSTO'
				when MES_COBRANCA = '09' then 'SETEMBRO'
				when MES_COBRANCA = '10' then 'OUTUBRO'
				when MES_COBRANCA = '11' then 'NOVEMBRO'
				when MES_COBRANCA = '12' then 'DEZEMBRO'
				when MES_COBRANCA = '99' then 'TOTAL_ANO'
			end as MES_COBRA
		, _2022
		, _2023
		, DIF1 AS SEP
		, DIF_2023
		, DIF2 AS SEP
		, _2024
		, DIF2 AS SEP
		, DIF_2024
FROM
	(
		select MES_COBRANCA
				, TOTAL_2022 AS _2022
				, TOTAL_2023 AS _2023
				,'---' AS DIF1
				, sum(xx.TOTAL_2023-xx.TOTAL_2022) as DIF_2023
				,'---' AS DIF2
				, xx.TOTAL_2024 AS _2024
				, '---' AS DIF3
				, CASE WHEN sum(xx.TOTAL_2024-xx.TOTAL_2023) < 1 THEN 0 ELSE sum(xx.TOTAL_2024-xx.TOTAL_2023) END as DIF_2024
				--, sum(xx.TOTAL_2024-xx.TOTAL_2023) as DIFERENÇA_ANO_2
				--, (sum(xx.TOTAL_2023*100/xx.TOTAL_2022) -100) as percentua
		from (
				select AA.MES_COBRANCA
					, CASE WHEN CC.TOTAL_2022 IS NULL THEN 0 else CC.TOTAL_2022 END AS TOTAL_2022
					, CASE WHEN BB.TOTAL_2023 IS NULL THEN 0 else BB.TOTAL_2023 END AS TOTAL_2023
					, CASE WHEN DD.TOTAL_2024 IS NULL THEN 0 else DD.TOTAL_2024 END AS TOTAL_2024
				FROM 
					(   select  MES_COBRANCA, count(*) as TOTAL
						from CFO_CWS.dbo.cfo_id_cobranca 
						--
						where cro = @filtroUf
						--
						group by MES_COBRANCA
						) AS AA
				LEFT JOIN
					  ( select  MES_COBRANCA, count(*) AS TOTAL_2023
						from CFO_CWS.dbo.cfo_id_cobranca 
						where ANO_COBRANCA = '2023' 
						--
						and cro = @filtroUf
						--
						group by MES_COBRANCA
						) AS BB ON BB.MES_COBRANCA = AA.MES_COBRANCA
				LEFT JOIN
					  ( select  MES_COBRANCA, count(*) AS TOTAL_2022
						from CFO_CWS.dbo.cfo_id_cobranca 
						where ANO_COBRANCA = '2022' 
						--
						and cro = @filtroUf
						--
						group by MES_COBRANCA
						) AS CC ON CC.MES_COBRANCA = AA.MES_COBRANCA
				LEFT JOIN
					  ( select  MES_COBRANCA, count(*) AS TOTAL_2024
						from CFO_CWS.dbo.cfo_id_cobranca 
						where ANO_COBRANCA = '2024' 
						--
						and cro = @filtroUf
						--
						group by MES_COBRANCA
						) AS DD ON DD.MES_COBRANCA = AA.MES_COBRANCA
				) as xx
		group by xx.MES_COBRANCA, xx.TOTAL_2022, xx.TOTAL_2023, xx.TOTAL_2024) AS ZZ
	--
UNION 
	--
		SELECT 'TOTAL' AS MES_COBRANCA
				, 'TOTAL_ANO' as MES_COBRA
				, SUM (YY._2022) AS _2022
				, SUM(YY._2023) AS _2023
				, '' AS DIF1
				, sum(_2023-_2022) as DIF_2023
				, '' AS DIF2
				, SUM(YY._2024) AS _2024
				, '---' AS DIF3
				, CASE WHEN sum(_2024-_2023) < 1 THEN 0 ELSE sum(_2024-_2023) END as DIF_2024
				--, sum(_2024-_2023) as DIF_2024
		FROM (
				SELECT TOTAL_2022 AS _2022, '' AS _2023, '' AS _2024
				FROM (SELECT count(*) AS TOTAL_2022 
						from CFO_CWS.dbo.cfo_id_cobranca 
						where ANO_COBRANCA = '2022'
						--
						and cro = @filtroUf
						--
						) AS AA
				UNION 
				SELECT '' AS _2022, TOTAL_2023 AS _2023, '' AS _2024
				FROM (SELECT count(*) AS TOTAL_2023 
						from CFO_CWS.dbo.cfo_id_cobranca  
						where ANO_COBRANCA = '2023'
						--
						and cro = @filtroUf
						--
						) AS BB
				UNION 
				SELECT '' AS _2022, '' AS _2023, TOTAL_2024 AS _2024
				FROM (SELECT count(*) AS TOTAL_2024 
						from CFO_CWS.dbo.cfo_id_cobranca  
						where ANO_COBRANCA = '2024'
						--
						and cro = @filtroUf
						--
						) AS CC
				) AS YY
		--
ORDER BY MES_COBRANCA
--