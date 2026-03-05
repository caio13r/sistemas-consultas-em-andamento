SELECT 
	(SELECT CONS.UF FROM cro_@cro_uf.Cadastro.Conselhos AS CONS WHERE CONS.IdConselho = '00000000-0000-0000-0000-000000000001') AS CRO,
	C.Convenio,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS Quantidade,
	FORMAT(SUM (ISNULL(PARI.ValorTarifaBancaria, 0)), 'C', 'pt-br') AS ValorLiquidacao
FROM Convenios AS C
	LEFT JOIN cro_@cro_uf.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
			AND CV.Ativo = '1'
	LEFT JOIN cro_@cro_uf.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 06 -- LIQUIDAÇÃO
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
		--AND PARI.MotivoRecusaProcessamento NOT LIKE '%Processamento pagamento desfeito%'
GROUP BY C.Convenio, CV.Codigo