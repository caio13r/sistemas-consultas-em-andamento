DROP TABLE IF EXISTS #TempTotalAdimplencia;

DECLARE @Categoria AS VARCHAR(10)
SET @Categoria ='CD'
DECLARE @AnoReferencia AS VARCHAR(4)
SET @AnoReferencia ='2022'

SELECT
	TotalAdimplencia.CRO,
	TotalAdimplencia.TotalAnuidades,
	TotalAdimplencia.AnoReferencia,
	TotalAdimplencia.Pago,
	TotalAdimplencia.[Percentual Adimplência],
	TotalAdimplencia.[Não pago],
	TotalAdimplencia.[Pago a menor],
	TotalAdimplencia.[Percentual Inadimpl�ncia]
INTO #TempTotalAdimplencia
FROM
(SELECT
	'AC' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_AC.Registro.Registros AS R
	INNER JOIN cro_AC.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_AC.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_AC.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_AC.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_AC.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO
UNION

SELECT
	'AL' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_AL.Registro.Registros AS R
	INNER JOIN cro_AL.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_AL.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_AL.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_AL.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_AL.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'AM' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_AM.Registro.Registros AS R
	INNER JOIN cro_AM.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_AM.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_AM.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_AM.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_AM.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'AP' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_AP.Registro.Registros AS R
	INNER JOIN cro_AP.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_AP.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_AP.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_AP.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_AP.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'BA' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_BA.Registro.Registros AS R
	INNER JOIN cro_BA.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_BA.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_BA.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_BA.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_BA.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'CE' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_CE.Registro.Registros AS R
	INNER JOIN cro_CE.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_CE.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_CE.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_CE.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_CE.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'DF' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_DF.Registro.Registros AS R
	INNER JOIN cro_DF.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_DF.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_DF.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_DF.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_DF.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'ES' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_ES.Registro.Registros AS R
	INNER JOIN cro_ES.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_ES.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_ES.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_ES.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_ES.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'GO' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_GO.Registro.Registros AS R
	INNER JOIN cro_GO.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_GO.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_GO.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_GO.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_GO.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'MA' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_MA.Registro.Registros AS R
	INNER JOIN cro_MA.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_MA.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_MA.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_MA.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_MA.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'MG' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_MG.Registro.Registros AS R
	INNER JOIN cro_MG.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_MG.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_MG.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_MG.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_MG.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'MS' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_MS.Registro.Registros AS R
	INNER JOIN cro_MS.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_MS.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_MS.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_MS.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_MS.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'MT' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_MT.Registro.Registros AS R
	INNER JOIN cro_MT.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_MT.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_MT.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_MT.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_MT.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'PA' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_PA.Registro.Registros AS R
	INNER JOIN cro_PA.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_PA.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_PA.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_PA.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_PA.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'PB' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_PB.Registro.Registros AS R
	INNER JOIN cro_PB.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_PB.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_PB.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_PB.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_PB.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'PE' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_PE.Registro.Registros AS R
	INNER JOIN cro_PE.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_PE.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_PE.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_PE.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_PE.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'PI' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_PI.Registro.Registros AS R
	INNER JOIN cro_PI.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_PI.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_PI.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_PI.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_PI.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'PR' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_PR.Registro.Registros AS R
	INNER JOIN cro_PR.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_PR.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_PR.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_PR.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_PR.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'RJ' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_RJ.Registro.Registros AS R
	INNER JOIN cro_RJ.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_RJ.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_RJ.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_RJ.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_RJ.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'RN' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_RN.Registro.Registros AS R
	INNER JOIN cro_RN.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_RN.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_RN.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_RN.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_RN.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'RO' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_RO.Registro.Registros AS R
	INNER JOIN cro_RO.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_RO.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_RO.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_RO.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_RO.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'RR' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_RR.Registro.Registros AS R
	INNER JOIN cro_RR.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_RR.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_RR.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_RR.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_RR.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'RS' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_RS.Registro.Registros AS R
	INNER JOIN cro_RS.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_RS.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_RS.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_RS.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_RS.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'SC' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_SC.Registro.Registros AS R
	INNER JOIN cro_SC.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_SC.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_SC.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_SC.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_SC.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'SE' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_SE.Registro.Registros AS R
	INNER JOIN cro_SE.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_SE.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_SE.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_SE.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_SE.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'SP' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_SP.Registro.Registros AS R
	INNER JOIN cro_SP.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_SP.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_SP.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_SP.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_SP.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO

UNION

SELECT
	'TO' AS CRO,
	TotalCRO.Categoria,
	TotalCRO.TotalAnuidades,
	TotalCRO.AnoReferencia,
	TotalCRO.Pago,
	CAST((CAST(TotalCRO.Pago AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Adimplência',
	TotalCRO.[Não pago],
	TotalCRO.[Pago a menor],
	CAST((CAST(TotalCRO.[Não pago] + TotalCRO.[Pago a menor] AS DECIMAL)/CAST(TotalCRO.TotalAnuidades AS DECIMAL))*100 AS NUMERIC(10,2)) AS 'Percentual Inadimpl�ncia'
FROM
(SELECT
	C.Sigla AS Categoria,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual', 'Não pago', 'Pago a menor') THEN 1 ELSE NULL END) AS TotalAnuidades,
	D.AnoReferencia,
	COUNT(CASE WHEN SP.Nome IN ('Pago', 'Pago com desconto', 'Quitação manual') THEN 1 ELSE NULL END) AS Pago,
	COUNT(CASE WHEN SP.Nome IN ('Não pago') THEN 1 ELSE NULL END) AS 'Não pago',
	COUNT(CASE WHEN SP.Nome IN ('Pago a menor') THEN 1 ELSE NULL END) AS 'Pago a menor'
FROM cro_TO.Registro.Registros AS R
	INNER JOIN cro_TO.Registro.Categorias AS C
		ON C.IdCategoria = R.IdCategoria
	INNER JOIN cro_TO.Financeiro.DebitosRegistros AS DR
		ON DR.IdRegistro = R.IdRegistro
	INNER JOIN cro_TO.Financeiro.Debitos AS D
		ON D.IdDebito = DR.IdDebito
	INNER JOIN cro_TO.Financeiro.DebitosTipos AS DT
		ON DT.IdDebitoTipo = D.IdDebitoTipo
	INNER JOIN cro_TO.Financeiro.DebitosSituacoesPagamentos AS SP
		ON SP.IdDebitoSituacaoPagamento = D.IdDebitoSituacaoPagamento
wHERE DT.Nome = 'ANUIDADE'
	AND D.AnoReferencia = @AnoReferencia
	AND C.Sigla = @Categoria
GROUP BY C.Sigla, D.AnoReferencia) AS TotalCRO
) TotalAdimplencia

INSERT INTO #TempTotalAdimplencia
SELECT 
	'BR', 
	SUM(#TempTotalAdimplencia.TotalAnuidades),
	#TempTotalAdimplencia.AnoReferencia,
	SUM(#TempTotalAdimplencia.Pago),
	SUM(CAST(#TempTotalAdimplencia.[Percentual Adimplência] AS NUMERIC (10,2))) / COUNT(#TempTotalAdimplencia.CRO),
	SUM(#TempTotalAdimplencia.[Não pago]),
	SUM(#TempTotalAdimplencia.[Pago a menor]),
	SUM(CAST(#TempTotalAdimplencia.[Percentual Inadimpl�ncia] AS NUMERIC (10,2))) / COUNT(#TempTotalAdimplencia.CRO)
FROM #TempTotalAdimplencia
GROUP BY #TempTotalAdimplencia.AnoReferencia

SELECT
	TA.CRO,
	TA.TotalAnuidades,
	TA.AnoReferencia,
	TA.Pago,
	CONCAT(REPLACE(TA.[Percentual Adimplência], '.' , ','), '%') AS 'Percentual Adimplência',
	CONCAT(REPLACE(TA.[Percentual Inadimpl�ncia], '.', ','), '%') AS'Percentual Inadimpl�ncia',
	TA.[Não pago],
	TA.[Pago a menor]
FROM #TempTotalAdimplencia AS TA
