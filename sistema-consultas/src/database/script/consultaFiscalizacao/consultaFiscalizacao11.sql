SELECT
    CONVERT(VARCHAR, ':inicio', 103) AS [Data_Inicio_Termo],
    CONVERT(VARCHAR, ':termino', 103) AS [Data_Fim_Termo],
    (SELECT CadCon.UF
     FROM :banco.Cadastro.Conselhos AS CadCon
     WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
    ) AS [CRO],
    ISNULL(A.Categoria, 'TODAS') AS [Categoria],
    YEAR(':termino') - 2 AS [Ano_Total_Ativos],
    A.Total_Ativos AS [Total_Ativos],
    YEAR(':termino') AS [Ano_Fiscalizacoes_Com_Termo],
    ISNULL(F.Quantidade_Fiscalizacoes, 0) AS [Fiscalizacoes_Com_Termo],
    ISNULL(IIF(
        A.Total_Ativos = 0,
        '0.00%',
        FORMAT(
            (CONVERT(DECIMAL(10, 2), F.Quantidade_Fiscalizacoes) / A.Total_Ativos) * 100,
            'N2'
        ) + '%'
    ), '0.00%') AS [Porcentagem_Fiscalizado_Com_Termo],
    ISNULL(F.Proativa, 0) AS [Fiscalizacoes_Proativas],
    ISNULL(F.Reativa, 0) AS [Fiscalizacoes_Reativas],
    ISNULL(F.Fiscalizacoes_Online, 0) AS [Fiscalizacoes_Online],
    ISNULL(F.Fiscalizacoes_Exercicio_Ilegal, 0) AS [Fiscalizacoes_Exercicio_Ilegal],
    ISNULL(F.Notificacoes, 0) AS [Notificacoes_Com_Indicios_de_Irregularidades]
FROM (
    SELECT
        ISNULL(ReCat.Sigla, 'TODAS') AS [Categoria],
        COUNT(ReRe.IdRegistro) AS [Total_Ativos]
    FROM :banco.Registro.Registros AS ReRe
        INNER JOIN :banco.Registro.Categorias AS ReCat
            ON ReRe.IdCategoria = ReCat.IdCategoria
        INNER JOIN :banco.Cadastro.Pessoas AS CadPe
            ON ReRe.IdPessoa = CadPe.IdPessoa
            AND CadPe.Ativo = 1
    OUTER APPLY (
        SELECT
            MAX(CASE WHEN ReSit.Nome = 'ATIVO' THEN ReReSit.DataInicioSituacao END) AS [Data_Ativo],
            MAX(CASE WHEN
                (ReSit.Nome = 'DESATIVADO' OR  ReSit.Nome = 'CANCELADO' OR ReSit.Nome = 'PRÉ-CADASTRO')
                THEN ReReSit.DataInicioSituacao END) AS [Data_Desativado]
        FROM :banco.Registro.RegistrosSituacoes AS ReReSit
            INNER JOIN :banco.Registro.Situacoes AS ReSit
                ON ReReSit.IdSituacao = ReSit.IdSituacao
        WHERE ReReSit.IdRegistro = ReRe.IdRegistro
            AND YEAR(ReReSit.DataInicioSituacao) <= YEAR(':termino') - 2
    ) AS RS
    WHERE (
        (RS.[Data_Ativo] IS NOT NULL AND RS.[Data_Desativado] IS NULL)
        OR RS.[Data_Ativo] > RS.[Data_Desativado]
    )
    GROUP BY ROLLUP(ReCat.Sigla)
) AS A
    LEFT JOIN (
        SELECT
            ISNULL(ReCat.Sigla, 'TODAS') AS [Categoria],
            COUNT(ReRe.IdRegistro) AS [Quantidade_Fiscalizacoes],
            COUNT(IIF(VFP.Nome = 'Proativo', 1, NULL)) AS [Proativa],
            COUNT(IIF(VFP.Nome = 'Reativo', 1, NULL)) AS [Reativa],
            SUM(ISNULL(FFGP.Fiscalizacoes_Online, 0)) AS [Fiscalizacoes_Online],
            SUM(ISNULL(FFGP.Fiscalizacoes_Exercicio_Ilegal, 0)) AS [Fiscalizacoes_Exercicio_Ilegal],
            SUM(ISNULL(FFGP.Notificacoes, 0)) AS Notificacoes
        FROM :banco.Registro.Registros AS ReRe
            INNER JOIN :banco.Registro.Categorias AS ReCat
                ON ReRe.IdCategoria = ReCat.IdCategoria
            INNER JOIN (
                SELECT
                    FisVisFisRe.IdRegistro,
                    FisVisFisPree.IdVisitaFiscalizadoPreenchimento,
                    ForPree.IdPreenchimento,
                    FisClaFis.Nome,
                    ROW_NUMBER() OVER(PARTITION BY FisVisFisPree.IdVisitaFiscalizado ORDER BY ForPree.DataAtualizacao DESC) AS Rn
                FROM :banco.Fiscalizacao.VisitasFiscalizadosRegistros AS FisVisFisRe
                    INNER JOIN :banco.Fiscalizacao.VisitasFiscalizados AS FisVisFis
                        ON FisVisFisRe.IdVisitaFiscalizado = FisVisFis.IdVisitaFiscalizado
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
                ON ReRe.IdRegistro = VFP.IdRegistro
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
                WHERE VFP.IdPreenchimento = ForPreeRes.IdPreenchimento
                    AND D.DataCriacao BETWEEN ':inicio' AND ':termino'
            ) AS FFGP
        GROUP BY ROLLUP(ReCat.Sigla)
    ) AS F
        ON A.Categoria = F.Categoria
WHERE
    A.categoria LIKE ':categoria'
ORDER BY
    IIF (A.Categoria = 'TODAS', 1, 0),
    A.Categoria
