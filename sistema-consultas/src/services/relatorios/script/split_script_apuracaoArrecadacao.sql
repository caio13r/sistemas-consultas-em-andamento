SELECT '@cro_uf' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Convênio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Convênio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Convênio 3'
		ELSE 'Não Identificado'
	END,
	CV.Codigo AS CodigoConvenio, 
	COUNT(PARI.OcorrenciaArquivoRetornoCodigo) AS NumeroLiquidacao,
	SUM (PARI.ValorTarifaBancaria ) AS ValorLiquidacao,
	SUM (PARI.ValorPagamento) AS ValorPago

FROM cro_@cro_uf.Financeiro.ProcessamentoArquivosRetornosItens AS PARI
	INNER JOIN cro_@cro_uf.Financeiro.Convenios AS CV 
		ON CV.IdConvenio = PARI.IdConvenio
WHERE PARI.OcorrenciaArquivoRetornoCodigo = 06
	AND PARI.DataCredito >= @dataInicio AND PARI.DataCredito < @dataFim
	AND PARI.MotivoRecusaProcessamento NOT LIKE '%Processamento pagamento desfeito%'
	GROUP BY CV.VariacaoCarteira, CV.Codigo
