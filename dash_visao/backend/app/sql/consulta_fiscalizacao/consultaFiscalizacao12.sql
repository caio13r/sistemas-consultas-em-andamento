SELECT
    CONVERT(VARCHAR, ':inicio', 103) AS [Data_Inicio_Termo],
    CONVERT(VARCHAR, ':termino', 103) AS [Data_Fim_Termo],
    (SELECT CadCon.UF
     FROM :banco.Cadastro.Conselhos AS CadCon
     WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
    ) AS [CRO],
    CASE 
        WHEN CadPe.TipoPessoaFisica = 1 THEN 'PF SEM INSCRIÇÃO'
        WHEN CadPe.TipoPessoaFisica = 0 THEN 'PJ SEM INSCRIÇÃO'
        ELSE 'TODAS'
    END AS [Pessoa],
    YEAR(':termino') AS [Ano_Fiscalizacoes_Com_Termo],
    COUNT(CadPe.IdPessoa) AS [Fiscalizacoes_Com_Termo],
    COUNT(IIF(VFP.Nome IN ('Proativo'), 1, NULL)) AS [Fiscalizacoes_Proativas],
    COUNT(IIF(VFP.Nome IN ('Reativo'), 1, NULL)) AS [Fiscalizacoes_Reativas],
    SUM(ISNULL(FFGP.Fiscalizacoes_Online, 0)) AS [Fiscalizacoes_Online],
    SUM(ISNULL(FFGP.Fiscalizacoes_Exercicio_Ilegal, 0)) AS [Fiscalizacoes_Exercicio_Ilegal],
    SUM(ISNULL(FFGP.Notificacoes, 0)) AS [Notificacoes_Com_Indicios_de_Irregularidades]
FROM :banco.Cadastro.Pessoas AS CadPe

    INNER JOIN (
        SELECT
            FisVisFis.IdPessoa,
            FisVisFisPree.IdVisitaFiscalizadoPreenchimento,
            ForPree.IdPreenchimento,
            FisClaFis.Nome,
            FisVis.DataVisita,
            ROW_NUMBER() OVER(PARTITION BY FisVisFisPree.IdVisitaFiscalizado ORDER BY ForPree.DataAtualizacao DESC) AS Rn
        FROM :banco.Fiscalizacao.VisitasFiscalizados AS FisVisFis
            INNER JOIN :banco.Fiscalizacao.Visitas AS FisVis
                ON FisVisFis.IdVisita = FisVis.IdVisita
            INNER JOIN :banco.Fiscalizacao.ClassificacoesFiscalizacoes AS FisClaFis
                ON FisVis.IdClassificacaoFiscalizacao = FisClaFis.IdClassificacaoFiscalizacao
            INNER JOIN :banco.Fiscalizacao.VisitasFiscalizadosPreenchimentos AS FisVisFisPree
                ON FisVisFis.IdVisitaFiscalizado = FisVisFisPree.IdVisitaFiscalizado
            INNER JOIN :banco.Formulario.Preenchimentos AS ForPree
                ON FisVisFisPree.IdPreenchimento = ForPree.IdPreenchimento
        WHERE ForPree.DataAtualizacao BETWEEN ':inicio' AND ':termino'
    ) AS VFP
        ON CadPe.IdPessoa = VFP.IdPessoa
        AND VFP.Rn = 1

    INNER JOIN (
        SELECT
            FisVisFisPreeDoc.IdVisitaFiscalizadoPreenchimento,
            DocDoc.IdSituacaoDocumento,
            DocDoc.DataCriacao,
            ROW_NUMBER() OVER(PARTITION BY FisVisFisPreeDoc.IdVisitaFiscalizadoPreenchimento ORDER BY DocDoc.DataCriacao DESC) AS Rn
        FROM :banco.Fiscalizacao.VisitasFiscalizadosPreenchimentosDocumentos AS FisVisFisPreeDoc
            INNER JOIN :banco.Documento.Documentos AS DocDoc
                ON FisVisFisPreeDoc.IdDocumento = DocDoc.IdDocumento
    ) AS D
        ON VFP.IdVisitaFiscalizadoPreenchimento = D.IdVisitaFiscalizadoPreenchimento
        AND D.Rn = 1
    INNER JOIN :banco.Documento.SituacoesDocumentos AS DocSitDoc
        ON D.IdSituacaoDocumento = DocSitDoc.IdSituacaoDocumento
        AND DocSitDoc.Nome != 'CANCELADO'

    OUTER APPLY (
        SELECT
            SUM(IIF(
                ForForGruPer.Titulo LIKE 'Indícios de irregularidades:' 
                AND ForPreeRes.Resposta = 'Sim', 1, 0
            )) AS [Notificacoes],
            SUM(IIF(
                ForForGruPer.Titulo LIKE '%se encontrava no local no ato fiscalizatório?%' 
                AND ForPreeRes.Resposta = 'Fiscalização on-line', 1, 0
            )) AS [Fiscalizacoes_Online],
            SUM(IIF(
                ForForGruPerOp.Titulo LIKE 'Exercício ilegal', 1, 0
            )) AS [Fiscalizacoes_Exercicio_Ilegal]
        FROM :banco.Formulario.PreenchimentosRespostas AS ForPreeRes
        INNER JOIN :banco.Formulario.FormulariosGruposPerguntas AS ForForGruPer
            ON ForPreeRes.IdFormularioGrupoPergunta = ForForGruPer.IdFormularioGrupoPergunta
        LEFT JOIN :banco.Formulario.PreenchimentosRespostasOpcoes AS ForPreeResOp
            ON ForPreeRes.IdPreenchimentoResposta = ForPreeResOp.IdPreenchimentoResposta
        LEFT JOIN :banco.Formulario.FormulariosGruposPerguntasOpcoes AS ForForGruPerOp
            ON ForPreeResOp.IdFormularioGrupoPerguntaOpcao = ForForGruPerOp.IdFormularioGrupoPerguntaOpcao
        WHERE 
            VFP.IdPreenchimento = ForPreeRes.IdPreenchimento
            AND D.DataCriacao BETWEEN ':inicio' AND ':termino'
    ) AS FFGP

WHERE CadPe.Ativo = 1
    AND NOT EXISTS (
        SELECT 1
        FROM :banco.Registro.Registros ReRe
        WHERE ReRe.IdPessoa = CadPe.IdPessoa
    )
    :tipoPessoaFisica

GROUP BY ROLLUP(CadPe.TipoPessoaFisica)
