-- DECLARE
-- @dataInicio DATE = '01-01-2022',
-- @dataFim DATE = '31-12-2022'

SELECT
       'AC' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) AS DataCriacaoProcesso,
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_AC.Processo.Processos AS PRC
    INNER JOIN cro_AC.Processo.TiposProcessos AS TP ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_AC.Processo.ClassificacoesProcessos AS CP ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_AC.Processo.Etapas AS ETP ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_AC.Processo.ProcessosAdministrativos AS PADM ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_AC.Cadastro.Pessoas AS PREL  ON PREL.IdPessoa = PADM.IdPessoaRelator -- PESSOA RELATOR
	LEFT JOIN cro_AC.Cadastro.Pessoas AS PINST  ON PINST.IdPessoa = PADM.IdPessoaInstrutor -- PESSOA INSTRUTOR
    LEFT JOIN cro_AC.Processo.ProcessosPessoasPrincipais AS PP ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AC.Processo.ProcessosPessoasSecundarias AS PS ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AC.Cadastro.Pessoas AS PRINC ON PRINC.IdPessoa = PP.IdPessoa -- PESSOA PRINCIPAL
	LEFT JOIN cro_AC.Registro.Registros AS REGPRINC ON REGPRINC.IdPessoa = PP.IdPessoa -- PESSOA PRINCIPAL REGISTRO
	LEFT JOIN cro_AC.Registro.Categorias AS CATPRINC ON CATPRINC.IdCategoria = REGPRINC.IdCategoria -- PESSOA PRINCIPAL CATEGORIA
	LEFT JOIN cro_AC.Cadastro.Pessoas AS SEC ON SEC.IdPessoa = PS.IdPessoa -- PESSOA SENCUNDÁRIA
	LEFT JOIN cro_AC.Registro.Registros AS REGSEC ON REGSEC.IdPessoa = PS.IdPessoa -- PESSOA SECUNDÁRIA REGISTRO
	LEFT JOIN cro_AC.Registro.Categorias AS CATSEC ON CATSEC.IdCategoria = REGSEC.IdCategoria -- PESSOA PRINCIPAL CATEGORIA
    LEFT JOIN cro_AC.Processo.ProcessosAndamentos AS PUA ON PUA.IdProcesso = PRC.IdProcesso -- PROCESSO ÚLTIMO ANDAMENTO
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (   SELECT MAX(PUA1.DataHora)
               FROM cro_AC.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_AC.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_AC.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_AC.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_AC.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_AC.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_AC.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_AC.Processo.ProcessosAndamentosDocumentos AS PAD ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_AC.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_AC.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_AC.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_AC.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_AC.Processo.SituacoesProcessos AS SP  ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_AC.Processo.ProcessosJuridicos AS PRJ ON PRJ.IdProcesso = PRC.IdProcesso
	--WHERE PRC.DataCriacaoProcesso >= @dataInicio
	--AND PRC.DataCriacaoProcesso <= @dataFim
	WHERE PRC.DataCriacaoProcesso >= @dataInicio
	AND PRC.DataCriacaoProcesso <= @dataFim

UNION

/***** CRO/AL *****/
SELECT
       'AL' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_AL.Processo.Processos AS PRC
    INNER JOIN cro_AL.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_AL.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_AL.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_AL.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_AL.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_AL.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_AL.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AL.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AL.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_AL.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_AL.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_AL.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_AL.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_AL.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_AL.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_AL.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_AL.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_AL.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_AL.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_AL.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_AL.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_AL.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_AL.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_AL.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_AL.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_AL.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_AL.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_AL.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_AL.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/AM *****/
SELECT
       'AM' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_AM.Processo.Processos AS PRC
    INNER JOIN cro_AM.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_AM.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_AM.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_AM.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_AM.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_AM.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_AM.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AM.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AM.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_AM.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_AM.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_AM.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_AM.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_AM.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_AM.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_AM.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_AM.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_AM.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_AM.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_AM.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_AM.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_AM.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_AM.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_AM.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_AM.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_AM.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_AM.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_AM.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_AM.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/AP *****/
SELECT
       'AP' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_AP.Processo.Processos AS PRC
    INNER JOIN cro_AP.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_AP.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_AP.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_AP.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_AP.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_AP.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_AP.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AP.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_AP.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_AP.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_AP.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_AP.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_AP.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_AP.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_AP.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_AP.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_AP.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_AP.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_AP.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_AP.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_AP.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_AP.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_AP.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_AP.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_AP.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_AP.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_AP.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_AP.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_AP.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/BA *****/
SELECT
       'BA' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_BA.Processo.Processos AS PRC
    INNER JOIN cro_BA.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_BA.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_BA.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_BA.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_BA.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_BA.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_BA.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_BA.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_BA.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_BA.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_BA.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_BA.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_BA.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_BA.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_BA.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_BA.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_BA.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_BA.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_BA.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_BA.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_BA.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_BA.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_BA.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_BA.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_BA.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_BA.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_BA.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_BA.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_BA.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/CE *****/
SELECT
       'CE' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_CE.Processo.Processos AS PRC
    INNER JOIN cro_CE.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_CE.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_CE.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_CE.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_CE.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_CE.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_CE.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_CE.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_CE.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_CE.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_CE.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_CE.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_CE.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_CE.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_CE.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_CE.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_CE.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_CE.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_CE.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_CE.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_CE.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_CE.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_CE.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_CE.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_CE.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_CE.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_CE.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_CE.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_CE.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/DF *****/
SELECT
       'DF' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_DF.Processo.Processos AS PRC
    INNER JOIN cro_DF.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_DF.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_DF.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_DF.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_DF.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_DF.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_DF.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_DF.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_DF.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_DF.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_DF.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_DF.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_DF.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_DF.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_DF.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_DF.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_DF.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_DF.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_DF.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_DF.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_DF.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_DF.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_DF.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_DF.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_DF.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_DF.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_DF.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_DF.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_DF.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/ES *****/
SELECT
       'ES' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_ES.Processo.Processos AS PRC
    INNER JOIN cro_ES.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_ES.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_ES.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_ES.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_ES.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_ES.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_ES.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_ES.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_ES.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_ES.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_ES.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_ES.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_ES.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_ES.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_ES.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_ES.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_ES.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_ES.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_ES.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_ES.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_ES.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_ES.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_ES.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_ES.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_ES.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_ES.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_ES.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_ES.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_ES.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/GO *****/
SELECT
       'GO' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_GO.Processo.Processos AS PRC
    INNER JOIN cro_GO.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_GO.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_GO.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_GO.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_GO.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_GO.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_GO.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_GO.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_GO.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_GO.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_GO.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_GO.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_GO.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_GO.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_GO.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_GO.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_GO.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_GO.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_GO.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_GO.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_GO.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_GO.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_GO.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_GO.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_GO.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_GO.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_GO.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_GO.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_GO.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/MA *****/
SELECT
       'MA' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_MA.Processo.Processos AS PRC
    INNER JOIN cro_MA.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_MA.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_MA.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_MA.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_MA.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_MA.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_MA.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MA.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MA.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MA.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MA.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_MA.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MA.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MA.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_MA.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_MA.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_MA.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_MA.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_MA.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_MA.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_MA.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_MA.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_MA.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_MA.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_MA.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_MA.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_MA.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_MA.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_MA.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/MG *****/
SELECT
       'MG' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_MG.Processo.Processos AS PRC
    INNER JOIN cro_MG.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_MG.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_MG.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_MG.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_MG.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_MG.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_MG.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MG.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MG.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MG.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MG.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_MG.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MG.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MG.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_MG.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_MG.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_MG.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_MG.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_MG.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_MG.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_MG.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_MG.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_MG.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_MG.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_MG.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_MG.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_MG.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_MG.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_MG.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/MS *****/
SELECT
       'MS' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_MS.Processo.Processos AS PRC
    INNER JOIN cro_MS.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_MS.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_MS.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_MS.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_MS.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_MS.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_MS.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MS.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MS.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MS.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MS.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_MS.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MS.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MS.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_MS.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_MS.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_MS.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_MS.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_MS.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_MS.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_MS.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_MS.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_MS.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_MS.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_MS.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_MS.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_MS.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_MS.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_MS.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/MT *****/
SELECT
       'MT' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_MT.Processo.Processos AS PRC
    INNER JOIN cro_MT.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_MT.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_MT.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_MT.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_MT.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_MT.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_MT.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MT.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_MT.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MT.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_MT.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_MT.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MT.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_MT.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_MT.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_MT.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_MT.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_MT.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_MT.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_MT.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_MT.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_MT.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_MT.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_MT.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_MT.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_MT.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_MT.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_MT.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_MT.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/PA *****/
SELECT
       'PA' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_PA.Processo.Processos AS PRC
    INNER JOIN cro_PA.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_PA.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_PA.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_PA.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_PA.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_PA.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_PA.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PA.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PA.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PA.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PA.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_PA.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PA.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PA.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_PA.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_PA.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_PA.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_PA.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_PA.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_PA.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_PA.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_PA.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_PA.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_PA.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_PA.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_PA.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_PA.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_PA.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_PA.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/PB *****/
SELECT
       'PB' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_PB.Processo.Processos AS PRC
    INNER JOIN cro_PB.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_PB.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_PB.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_PB.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_PB.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_PB.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_PB.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PB.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PB.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PB.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PB.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_PB.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PB.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PB.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_PB.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_PB.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_PB.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_PB.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_PB.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_PB.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_PB.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_PB.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_PB.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_PB.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_PB.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_PB.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_PB.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_PB.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_PB.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/PE *****/
SELECT
       'PE' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_PE.Processo.Processos AS PRC
    INNER JOIN cro_PE.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_PE.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_PE.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_PE.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_PE.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_PE.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_PE.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PE.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PE.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PE.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PE.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_PE.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PE.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PE.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_PE.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_PE.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_PE.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_PE.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_PE.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_PE.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_PE.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_PE.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_PE.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_PE.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_PE.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_PE.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_PE.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_PE.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_PE.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/PI *****/
SELECT
       'PI' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_PI.Processo.Processos AS PRC
    INNER JOIN cro_PI.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_PI.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_PI.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_PI.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_PI.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_PI.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_PI.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PI.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PI.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PI.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PI.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_PI.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PI.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PI.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_PI.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_PI.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_PI.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_PI.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_PI.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_PI.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_PI.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_PI.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_PI.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_PI.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_PI.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_PI.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_PI.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_PI.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_PI.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/PR *****/
SELECT
       'PR' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_PR.Processo.Processos AS PRC
    INNER JOIN cro_PR.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_PR.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_PR.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_PR.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_PR.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_PR.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_PR.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PR.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_PR.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PR.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_PR.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_PR.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PR.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_PR.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_PR.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_PR.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_PR.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_PR.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_PR.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_PR.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_PR.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_PR.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_PR.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_PR.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_PR.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_PR.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_PR.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_PR.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_PR.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/RJ *****/
SELECT
       'RJ' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_RJ.Processo.Processos AS PRC
    INNER JOIN cro_RJ.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_RJ.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_RJ.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_RJ.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_RJ.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_RJ.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_RJ.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RJ.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RJ.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RJ.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RJ.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_RJ.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RJ.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RJ.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_RJ.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_RJ.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_RJ.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_RJ.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_RJ.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_RJ.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_RJ.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_RJ.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_RJ.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_RJ.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_RJ.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_RJ.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_RJ.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_RJ.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_RJ.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/RN *****/
SELECT
       'RN' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_RN.Processo.Processos AS PRC
    INNER JOIN cro_RN.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_RN.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_RN.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_RN.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_RN.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_RN.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_RN.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RN.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RN.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RN.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RN.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_RN.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RN.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RN.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_RN.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_RN.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_RN.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_RN.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_RN.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_RN.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_RN.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_RN.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_RN.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_RN.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_RN.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_RN.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_RN.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_RN.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_RN.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/RO *****/
SELECT
       'RO' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_RO.Processo.Processos AS PRC
    INNER JOIN cro_RO.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_RO.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_RO.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_RO.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_RO.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_RO.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_RO.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RO.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RO.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RO.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RO.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_RO.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RO.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RO.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_RO.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_RO.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_RO.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_RO.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_RO.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_RO.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_RO.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_RO.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_RO.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_RO.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_RO.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_RO.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_RO.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_RO.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_RO.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/RR *****/
SELECT
       'RR' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_RR.Processo.Processos AS PRC
    INNER JOIN cro_RR.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_RR.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_RR.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_RR.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_RR.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_RR.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_RR.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RR.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RR.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RR.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RR.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_RR.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RR.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RR.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_RR.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_RR.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_RR.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_RR.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_RR.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_RR.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_RR.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_RR.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_RR.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_RR.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_RR.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_RR.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_RR.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_RR.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_RR.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/RS *****/
SELECT
       'RS' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_RS.Processo.Processos AS PRC
    INNER JOIN cro_RS.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_RS.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_RS.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_RS.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_RS.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_RS.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_RS.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RS.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_RS.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RS.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_RS.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_RS.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RS.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_RS.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_RS.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_RS.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_RS.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_RS.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_RS.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_RS.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_RS.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_RS.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_RS.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_RS.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_RS.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_RS.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_RS.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_RS.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_RS.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/SC *****/
SELECT
       'SC' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_SC.Processo.Processos AS PRC
    INNER JOIN cro_SC.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_SC.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_SC.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_SC.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_SC.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_SC.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_SC.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_SC.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_SC.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_SC.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_SC.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_SC.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_SC.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_SC.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_SC.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_SC.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_SC.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_SC.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_SC.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_SC.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_SC.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_SC.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_SC.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_SC.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_SC.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_SC.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_SC.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_SC.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_SC.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/SE *****/
SELECT
       'SE' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_SE.Processo.Processos AS PRC
    INNER JOIN cro_SE.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_SE.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_SE.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_SE.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_SE.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_SE.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_SE.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_SE.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_SE.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_SE.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_SE.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_SE.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_SE.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_SE.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_SE.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_SE.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_SE.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_SE.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_SE.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_SE.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_SE.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_SE.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_SE.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_SE.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_SE.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_SE.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_SE.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_SE.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_SE.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/SP *****/
SELECT
       'SP' AS CRO,
	   PRC.NumeroProcesso COLLATE Latin1_General_CI_AI,
	   TP.Nome COLLATE Latin1_General_CI_AI AS TipoProcesso,
	   CP.Nome COLLATE Latin1_General_CI_AI AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto COLLATE Latin1_General_CI_AI,
	   PREL.NomeRazaoSocial COLLATE Latin1_General_CI_AI AS Relator,
	   PINST.NomeRazaoSocial COLLATE Latin1_General_CI_AI AS Instrutor,
	   CONCAT(CATPRINC.Sigla COLLATE Latin1_General_CI_AI, ' - ', REGPRINC.NumeroRegistro COLLATE Latin1_General_CI_AI, ' - ', PRINC.NomeRazaoSocial COLLATE Latin1_General_CI_AI) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla COLLATE Latin1_General_CI_AI, ' - ', REGSEC.NumeroRegistro COLLATE Latin1_General_CI_AI, ' - ', SEC.NomeRazaoSocial COLLATE Latin1_General_CI_AI) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome COLLATE Latin1_General_CI_AI AS UltimoAndamento,
	   DOCI.NumeroDocumento COLLATE Latin1_General_CI_AI AS NumeroUltimoAutoInfracao,
	   MUA.Nome COLLATE Latin1_General_CI_AI AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso COLLATE Latin1_General_CI_AI, ' ', TP.Nome COLLATE Latin1_General_CI_AI, ' | ', CP.Nome COLLATE Latin1_General_CI_AI) AS NumeroProcessoTipoClassificacao,
	   SP.Nome COLLATE Latin1_General_CI_AI AS UltimaSituacaoProcesso,
	   PRJ.Comarca COLLATE Latin1_General_CI_AI,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno COLLATE Latin1_General_CI_AI,
	   ETP.Nome COLLATE Latin1_General_CI_AI AS EtapaProcesso
FROM cro_SP.Processo.Processos AS PRC
    INNER JOIN cro_SP.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_SP.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_SP.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_SP.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_SP.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_SP.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_SP.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_SP.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_SP.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_SP.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_SP.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_SP.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_SP.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_SP.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_SP.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_SP.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_SP.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_SP.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_SP.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_SP.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_SP.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_SP.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_SP.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_SP.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_SP.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_SP.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_SP.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_SP.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_SP.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim

UNION

/***** CRO/TO *****/
SELECT
       'TO' AS CRO,
	   PRC.NumeroProcesso,
	   TP.Nome AS TipoProcesso,
	   CP.Nome AS ClassificacaoProcesso,
	   CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103),
	   PRC.Assunto,
	   PREL.NomeRazaoSocial AS Relator,
	   PINST.NomeRazaoSocial AS Instrutor,
	   CONCAT(CATPRINC.Sigla, ' - ', REGPRINC.NumeroRegistro, ' - ', PRINC.NomeRazaoSocial) AS PessoaPrincipal,
	   CONCAT(CATSEC.Sigla, ' - ', REGSEC.NumeroRegistro, ' - ', SEC.NomeRazaoSocial) AS PessoaSecundaria,
	   CONVERT(VARCHAR, PUA.DataHora, 103) AS DataUltimoAndamento,
	   UAD.Nome AS UltimoAndamento,
	   DOCI.NumeroDocumento AS NumeroUltimoAutoInfracao,
	   MUA.Nome AS MotivoUltimoAndamento,
	   CONCAT(PRC.NumeroProcesso, ' ', TP.Nome, ' | ', CP.Nome) AS NumeroProcessoTipoClassificacao,
	   SP.Nome AS UltimaSituacaoProcesso,
	   PRJ.Comarca,
	   PRJ.Causa AS ValorCausa,
	   PRC.NumeroProcessoExterno,
	   ETP.Nome AS EtapaProcesso
FROM cro_TO.Processo.Processos AS PRC
    INNER JOIN cro_TO.Processo.TiposProcessos AS TP
        ON TP.IdTipoProcesso = PRC.IdTipoProcesso
    INNER JOIN cro_TO.Processo.ClassificacoesProcessos AS CP
        ON CP.IdClassificacaoProcesso = PRC.IdClassificacaoProcesso
    INNER JOIN cro_TO.Processo.Etapas AS ETP
        ON ETP.IdEtapa = PRC.IdEtapa
    LEFT JOIN cro_TO.Processo.ProcessosAdministrativos AS PADM
        ON PADM.IdProcesso = PRC.IdProcesso
	LEFT JOIN cro_TO.Cadastro.Pessoas AS PREL -- PESSOA RELATOR
        ON PREL.IdPessoa = PADM.IdPessoaRelator
	LEFT JOIN cro_TO.Cadastro.Pessoas AS PINST -- PESSOA INSTRUTOR
        ON PINST.IdPessoa = PADM.IdPessoaInstrutor
    LEFT JOIN cro_TO.Processo.ProcessosPessoasPrincipais AS PP
        ON PP.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_TO.Processo.ProcessosPessoasSecundarias AS PS
        ON PS.IdProcesso = PRC.IdProcesso
    LEFT JOIN cro_TO.Cadastro.Pessoas AS PRINC -- PESSOA PRINCIPAL
        ON PRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_TO.Registro.Registros AS REGPRINC -- PESSOA PRINCIPAL REGISTRO
        ON REGPRINC.IdPessoa = PP.IdPessoa
	LEFT JOIN cro_TO.Registro.Categorias AS CATPRINC -- PESSOA PRINCIPAL CATEGORIA
        ON CATPRINC.IdCategoria = REGPRINC.IdCategoria
	LEFT JOIN cro_TO.Cadastro.Pessoas AS SEC -- PESSOA SENCUNDÁRIA
        ON SEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_TO.Registro.Registros AS REGSEC -- PESSOA SECUNDÁRIA REGISTRO
        ON REGSEC.IdPessoa = PS.IdPessoa
	LEFT JOIN cro_TO.Registro.Categorias AS CATSEC -- PESSOA PRINCIPAL CATEGORIA
        ON CATSEC.IdCategoria = REGSEC.IdCategoria
    LEFT JOIN cro_TO.Processo.ProcessosAndamentos AS PUA -- PROCESSO ÚLTIMO ANDAMENTO
        ON PUA.IdProcesso = PRC.IdProcesso
           AND CONVERT(VARCHAR, PUA.DataHora, 103) =
           (
               SELECT MAX(PUA1.DataHora)
               FROM cro_TO.Processo.ProcessosAndamentos PUA1
               WHERE PUA1.IdProcesso = PUA.IdProcesso
           )
    LEFT JOIN cro_TO.Processo.Andamentos AS UAD -- ULTIMO ANDAMENTO
        ON UAD.IdAndamento = PUA.IdAndamento
    LEFT JOIN cro_TO.Processo.MotivosAndamentos AS MUA -- MOTIVOS ÚLTIMO ANDAMENTO
        ON MUA.IdMotivoAndamento = PUA.IdMotivoAndamento
	LEFT JOIN cro_TO.Processo.ProcessosAndamentos AS PUI -- PROCESSO ÚLTIMA INFRAÇÃO
		ON PUI.IdProcesso = PRC.IdProcesso
		AND PUI.DataHora =
           (
				SELECT MAX(PUI1.DataHora)
				FROM cro_TO.Processo.ProcessosAndamentos PUI1
					INNER JOIN cro_TO.Processo.ProcessosAndamentosDocumentos AS PAD1
						ON PAD1.IdProcessoAndamento = PUI1.IdProcessoAndamento
					INNER JOIN cro_TO.Processo.Modelos AS PMD1 -- MODELO AUTO DE INFRAÇÃO
						ON PMD1.IdModelo = PAD1.IdModelo
						   AND PMD1.EnquadramentoLegalTipoDocumento = 'Auto de infração'
				WHERE PUI1.IdProcesso = PUI.IdProcesso
           )
    LEFT JOIN cro_TO.Processo.ProcessosAndamentosDocumentos AS PAD
        ON PAD.IdProcessoAndamento = PUI.IdProcessoAndamento
    LEFT JOIN cro_TO.Processo.Modelos AS PMD -- MODELO AUTO DE INFRAÇÃO
        ON PMD.IdModelo = PAD.IdModelo
           AND PMD.EnquadramentoLegalTipoDocumento = 'Auto de infração'
    LEFT JOIN cro_TO.Documento.Documentos AS DOCI -- DOCUMENTO AUTO DE INFRAÇÃO
        ON DOCI.IdDocumento = PAD.IdDocumento
    LEFT JOIN cro_TO.Processo.ProcessosHistoricoSituacoes AS PHS -- ÚLTIMA SITUAÇÃO DO PROCESSO
        ON PHS.IdProcesso = PRC.IdProcesso
           AND PHS.DataCriacao =
           (
               SELECT MAX(PHS1.DataCriacao)
               FROM cro_TO.Processo.ProcessosHistoricoSituacoes PHS1
               WHERE PHS1.IdProcessoHistoricoSituacao = PHS.IdProcessoHistoricoSituacao
           )
    LEFT JOIN cro_TO.Processo.SituacoesProcessos AS SP
        ON SP.IdSituacaoProcesso = PHS.IdSituacaoProcesso
    LEFT JOIN cro_TO.Processo.ProcessosJuridicos AS PRJ
        ON PRJ.IdProcesso = PRC.IdProcesso
	WHERE CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) >= @dataInicio
	AND CONVERT(VARCHAR, PRC.DataCriacaoProcesso, 103) <= @dataFim
	----
GO


