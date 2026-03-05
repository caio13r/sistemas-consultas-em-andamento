/****** Script do comando SelectTopNRows de SSMS  ******/
SELECT [cro]
	,[ano_referencia]
	,COUNT(*) AS TotalAnuidades
	,COUNT( CASE WHEN [sit_pagamento_debito] IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS PAGO
	,COUNT( CASE WHEN [sit_pagamento_debito] = 'Não pago' THEN 1 ELSE NULL END) AS [Não pago]
	,COUNT( CASE WHEN [sit_pagamento_debito] = 'Pago a menor' THEN 1 ELSE NULL END) AS [Pago a menor]
	,CAST((CAST(COUNT( CASE WHEN [sit_pagamento_debito] IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS DECIMAL)/CAST(COUNT(*) AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência'
	,CAST((CAST(COUNT( CASE WHEN [sit_pagamento_debito] IN ('Pago a menor', 'Não pago') THEN 1 ELSE NULL END) AS DECIMAL)/CAST(COUNT(*) AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Indimplência'
  FROM [CFO_CWS].[dbo].[vw_RegistroProfissionaisInadimplentesTodos]
  WHERE [ano_referencia] = '@year_filter'
  AND	[tipo_debito] = 'ANUIDADE'
  AND	[categoria] = '@categoria_filter'
  GROUP BY [cro],[ano_referencia]
  ORDER BY 'Percentual Adimplência' DESC