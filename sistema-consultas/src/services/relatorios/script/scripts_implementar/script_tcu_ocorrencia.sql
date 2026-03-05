-- DECLARE
-- @dataInicio DATE = '2022-01-01',
-- @dataFim DATE = '2022-12-31'


SELECT
	'AC' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_AC.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_AC.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_AC.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_AC.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_AC.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_AC.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_AC.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_AC.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_AC.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa

WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO AL **/
SELECT
	'AL' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_AL.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_AL.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_AL.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_AL.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_AL.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_AL.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_AL.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_AL.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_AL.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO AM **/
SELECT
	'AM' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_AM.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_AM.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_AM.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_AM.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_AM.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_AM.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_AM.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_AM.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_AM.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO AP **/
SELECT
	'AP' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_AP.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_AP.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_AP.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_AP.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_AP.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_AP.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_AP.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_AP.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_AP.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO BA **/
SELECT
	'BA' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_BA.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_BA.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_BA.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_BA.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_BA.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_BA.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_BA.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_BA.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_BA.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO CE **/
SELECT
	'CE' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_CE.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_CE.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_CE.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_CE.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_CE.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_CE.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_CE.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_CE.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_CE.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO DF **/
SELECT
	'DF' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_DF.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_DF.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_DF.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_DF.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_DF.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_DF.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_DF.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_DF.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_DF.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO ES **/
SELECT
	'ES' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_ES.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_ES.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_ES.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_ES.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_ES.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_ES.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_ES.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_ES.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_ES.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO GO **/
SELECT
	'GO' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_GO.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_GO.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_GO.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_GO.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_GO.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_GO.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_GO.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_GO.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_GO.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO MA **/
SELECT
	'MA' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_MA.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_MA.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_MA.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_MA.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_MA.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_MA.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_MA.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_MA.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_MA.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO MG **/
SELECT
	'MG' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_MG.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_MG.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_MG.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_MG.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_MG.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_MG.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_MG.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_MG.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_MG.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO MS **/
SELECT
	'MS' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_MS.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_MS.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_MS.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_MS.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_MS.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_MS.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_MS.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_MS.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_MS.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO MT **/
SELECT
	'MT' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_MT.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_MT.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_MT.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_MT.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_MT.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_MT.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_MT.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_MT.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_MT.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO PA **/
SELECT
	'PA' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_PA.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_PA.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_PA.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_PA.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_PA.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_PA.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_PA.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_PA.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_PA.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO PB **/
SELECT
	'PB' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_PB.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_PB.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_PB.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_PB.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_PB.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_PB.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_PB.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_PB.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_PB.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO PE **/
SELECT
	'PE' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_PE.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_PE.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_PE.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_PE.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_PE.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_PE.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_PE.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_PE.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_PE.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO PI **/
SELECT
	'PI' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_PI.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_PI.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_PI.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_PI.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_PI.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_PI.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_PI.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_PI.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_PI.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO PR **/
SELECT
	'PR' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_PR.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_PR.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_PR.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_PR.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_PR.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_PR.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_PR.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_PR.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_PR.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO RJ **/
SELECT
	'RJ' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_RJ.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_RJ.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_RJ.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_RJ.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_RJ.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_RJ.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_RJ.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_RJ.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_RJ.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO RN **/
SELECT
	'RN' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_RN.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_RN.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_RN.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_RN.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_RN.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_RN.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_RN.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_RN.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_RN.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO RO **/
SELECT
	'RO' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_RO.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_RO.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_RO.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_RO.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_RO.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_RO.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_RO.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_RO.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_RO.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO RR **/
SELECT
	'RR' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_RR.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_RR.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_RR.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_RR.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_RR.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_RR.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_RR.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_RR.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_RR.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO RS **/
SELECT
	'RS' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_RS.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_RS.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_RS.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_RS.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_RS.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_RS.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_RS.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_RS.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_RS.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO SC **/
SELECT
	'SC' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_SC.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_SC.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_SC.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_SC.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_SC.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_SC.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_SC.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_SC.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_SC.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO SE **/
SELECT
	'SE' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_SE.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_SE.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_SE.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_SE.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_SE.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_SE.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_SE.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_SE.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_SE.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO SP **/
SELECT
	'SP' AS CRO,
	P.NomeRazaoSocial COLLATE Latin1_General_CI_AI,
	P.CPFCNPJ COLLATE Latin1_General_CI_AI,
	REG.NumeroRegistro COLLATE Latin1_General_CI_AI,
	CAT.Nome COLLATE Latin1_General_CI_AI AS Categoria,
	OCT.Nome COLLATE Latin1_General_CI_AI AS Tipo_ocorrencia,
	OCS.Nome COLLATE Latin1_General_CI_AI AS Situacao_ocorrencia,
	OCTD.Nome COLLATE Latin1_General_CI_AI AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao COLLATE Latin1_General_CI_AI AS Usuario_criou,
	OC.NomeUnidadeCriacao COLLATE Latin1_General_CI_AI AS Unidade_criou
FROM cro_SP.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_SP.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_SP.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_SP.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_SP.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_SP.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_SP.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_SP.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_SP.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim
	UNION

/** CRO TO **/
SELECT
	'TO' AS CRO,
	P.NomeRazaoSocial,
	P.CPFCNPJ,
	REG.NumeroRegistro,
	CAT.Nome AS Categoria,
	OCT.Nome AS Tipo_ocorrencia,
	OCS.Nome AS Situacao_ocorrencia,
	OCTD.Nome AS Detalhe_ocorrencia,
	CONVERT(VARCHAR, OC.DataInicio, 103) AS Dt_ini_ocorrencia,
	CONVERT(VARCHAR, OC.DataFim, 103) AS Dt_fim_ocorrencia,
	OC.NomeUsuarioCriacao AS Usuario_criou,
	OC.NomeUnidadeCriacao AS Unidade_criou
FROM cro_TO.Registro.RegistrosOcorrencias AS ROC
	INNER JOIN cro_TO.Ocorrencia.Ocorrencias AS OC
		ON OC.IdOcorrencia = ROC.IdOcorrencia
	INNER JOIN cro_TO.Ocorrencia.OcorrenciasTiposDetalhes AS OCTD
		ON OCTD.IdOcorrenciaTipoDetalhe = OC.IdOcorrenciaTipoDetalhe
	INNER JOIN cro_TO.Ocorrencia.OcorrenciasTipos AS OCT
		ON OCT.IdOcorrenciaTipo = OCTD.IdOcorrenciaTipo
	LEFT JOIN cro_TO.Ocorrencia.OcorrenciasAndamentos AS OCA
		ON OCA.IdOcorrencia = ROC.IdOcorrencia
	LEFT JOIN cro_TO.Ocorrencia.OcorrenciasSituacoes AS OCS
		ON OCS.IdOcorrenciaSituacao = OCA.IdOcorrenciaSituacao
	INNER JOIN cro_TO.Registro.Registros AS REG
		ON REG.IdRegistro = ROC.IdRegistro
	INNER JOIN cro_TO.Registro.Categorias AS CAT
		ON CAT.IdCategoria = REG.IdCategoria
	INNER JOIN cro_TO.Cadastro.Pessoas AS P
		ON P.IdPessoa = REG.IdPessoa
WHERE OCT.Nome = 'AUDITORIA - CFO'
	AND REG.DataInscricao >= @dataInicio
	AND REG.DataInscricao <= @dataFim

	----
GO


