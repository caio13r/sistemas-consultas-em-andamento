--
--TOTALIZA AS TARIFAS DE REGISTRO PAGAS NO PERIODO DESEJADO PELA DATA DE PAGAMENTO
--

-- DECLARE
-- @dataInicio DATE = '2023-01-01',
-- @dataFim DATE = '2023-02-01'

SELECT 'AC' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ac.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ac.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'AL' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_al.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_al.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'AM' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_am.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_am.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'AP' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ap.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ap.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'BA' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ba.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ba.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'CE' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ce.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ce.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'DF' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_df.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_df.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'ES' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_es.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_es.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'GO' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_go.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_go.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'MA' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ma.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ma.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'MG' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_mg.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_mg.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'MS' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ms.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ms.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'MT' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_mt.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_mt.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'PA' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_pa.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_pa.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'PB' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_pb.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_pb.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'PE' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_pe.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_pe.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'PI' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_pi.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_pi.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'PR' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_pr.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_pr.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'RJ' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_rj.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_rj.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'RN' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_rn.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_rn.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'RO' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_ro.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_ro.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'RR' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_rr.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_rr.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'RS' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_rs.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_rs.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'SC' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_sc.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_sc.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'SE' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_se.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_se.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION
SELECT 'TO' AS CRO,
	"Convenio" = 
	CASE
		WHEN CV.VariacaoCarteira = 19 THEN 'Conv�nio 1'
		WHEN CV.VariacaoCarteira = 27 THEN 'Conv�nio 2'
		WHEN CV.VariacaoCarteira = 35 THEN 'Conv�nio 3'
		ELSE 'N�o identifcado'
	END,
	ISNULL(CV.Codigo, 0) AS CodigoConvenio, 
	COUNT(CASE WHEN PARI.OcorrenciaArquivoRetornoCodigo IS NOT NULL THEN 1 END) AS TotalRegistro,
	SUM (ISNULL(PARI.ValorTarifaBancaria, 0)) AS ValorRegistro
FROM Convenios AS C
	LEFT JOIN cro_to.Financeiro.Convenios AS CV 
		ON C.VariacaoCarteira = CV.VariacaoCarteira
		AND CV.Ativo = 1
	LEFT JOIN cro_to.Financeiro.ProcessamentoArquivosRetornosItens AS PARI 
		ON CV.IdConvenio = PARI.IdConvenio
		AND PARI.OcorrenciaArquivoRetornoCodigo = 02 
		AND PARI.DataPagamento >= @dataInicio AND PARI.DataPagamento < @dataFim
GROUP BY CV.VariacaoCarteira, CV.Codigo
UNION