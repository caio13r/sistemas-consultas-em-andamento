SELECT
    CONVERT(VARCHAR, ':inicio', 103) AS [Data_Inicio_Termo],
    CONVERT(VARCHAR, ':termino', 103) AS [Data_Fim_Termo],
    (
        SELECT CadCon.UF
        FROM :banco.Cadastro.Conselhos AS CadCon
        WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
    ) AS [CRO],
    ISNULL(C.Sigla, 'TODAS') AS [Categoria],
    ISNULL(SUM(TPT.[PF / PJ sem inscrição]), 0) AS [PF_PJ_Sem_Inscricao],
    ISNULL(SUM(TPT.[PJ sem Responsável Técnico]), 0) AS [PJ_Sem_Responsavel_Tecnico],
    ISNULL(SUM(TPT.[Ausência de identificação na comunicação e divulgação]), 0) AS [Ausencia_de_Identificacao_na_Comunicacao_e_divulgacao],
    ISNULL(SUM(TPT.[Divulgar especialidade sem registro no CFO]), 0) AS [Divulgar_Especialidade_Sem_Registro_no_CFO],
    ISNULL(SUM(TPT.[Anúncio, propaganda e publicidade irregular]), 0) AS [Anuncio_Propaganda_e_Publicidade_Irregular],
    ISNULL(SUM(TPT.[Exercício irregular]), 0) AS [Exercicio_Irregular],
    ISNULL(SUM(TPT.[Exercício ilegal]), 0) AS [Exercicio_Ilegal],
    ISNULL(SUM(TPT.[Acobertamento de exercício ilegal]), 0) AS [Acobertamento_de_exercicio_ilegal],
    ISNULL(SUM(TPT.[Outro:]), 0) AS [Outros]
FROM (SELECT Sigla FROM cfo_br.Registro.Categorias) AS C
LEFT JOIN (
    SELECT
        PivotData.Categoria,
        PivotData.[PF / PJ sem inscrição],
        PivotData.[PJ sem Responsável Técnico],
        PivotData.[Ausência de identificação na comunicação e divulgação],
        PivotData.[Divulgar especialidade sem registro no CFO],
        PivotData.[Anúncio, propaganda e publicidade irregular],
        PivotData.[Exercício irregular],
        PivotData.[Exercício ilegal],
        PivotData.[Acobertamento de exercício ilegal],
        PivotData.[Outro:]
    FROM (
        SELECT
            ReCat.Sigla AS [Categoria],
            ForForGruPerOp.Titulo AS [Irregularidade],
            COUNT(DocDoc.IdDocumento) AS [Qtd]
        FROM :banco.Documento.Documentos AS DocDoc
        INNER JOIN :banco.Documento.SituacoesDocumentos AS DocSitDoc
            ON DocDoc.IdSituacaoDocumento = DocSitDoc.IdSituacaoDocumento
        INNER JOIN :banco.Fiscalizacao.VisitasFiscalizadosPreenchimentosDocumentos AS FisVisFisPreeDoc
            ON DocDoc.IdDocumento = FisVisFisPreeDoc.IdDocumento
        INNER JOIN :banco.Fiscalizacao.VisitasFiscalizadosPreenchimentos AS FisVisFisPree
            ON FisVisFisPreeDoc.IdVisitaFiscalizadoPreenchimento = FisVisFisPree.IdVisitaFiscalizadoPreenchimento
        INNER JOIN :banco.Fiscalizacao.VisitasFiscalizadosRegistros AS FisVisFisReg
            ON FisVisFisPree.IdVisitaFiscalizado = FisVisFisReg.IdVisitaFiscalizado
        INNER JOIN :banco.Registro.Registros AS ReRe
            ON FisVisFisReg.IdRegistro = ReRe.IdRegistro
        INNER JOIN :banco.Registro.Categorias AS ReCat
            ON ReRe.IdCategoria = ReCat.IdCategoria
        INNER JOIN :banco.Formulario.PreenchimentosRespostas AS ForPreeRes
            ON FisVisFisPree.IdPreenchimento = ForPreeRes.IdPreenchimento
        INNER JOIN :banco.Formulario.PreenchimentosRespostasOpcoes AS ForPreeResOp
            ON ForPreeRes.IdPreenchimentoResposta = ForPreeResOp.IdPreenchimentoResposta
        INNER JOIN :banco.Formulario.FormulariosGruposPerguntasOpcoes AS ForForGruPerOp
            ON ForPreeResOp.IdFormularioGrupoPerguntaOpcao = ForForGruPerOp.IdFormularioGrupoPerguntaOpcao
        INNER JOIN :banco.Formulario.FormulariosGruposPerguntas AS ForForGruPer
            ON ForForGruPerOp.IdFormularioGrupoPergunta = ForForGruPer.IdFormularioGrupoPergunta
        INNER JOIN :banco.Formulario.FormulariosGrupos AS ForForGru
            ON ForForGruPer.IdFormularioGrupo = ForForGru.IdFormularioGrupo
        WHERE DocDoc.DataCriacao BETWEEN ':inicio' AND ':termino'
            AND DocSitDoc.Nome != 'CANCELADO'
            AND ForForGru.Titulo LIKE '%TIPO(S) DE IRREGULARIDADE%'
        GROUP BY
            ReCat.Sigla,
            ForForGruPerOp.Titulo
    ) AS T
    PIVOT (
        SUM(T.Qtd)
        FOR T.[Irregularidade] IN (
            [PF / PJ sem inscrição],
            [PJ sem Responsável Técnico],
            [Ausência de identificação na comunicação e divulgação],
            [Divulgar especialidade sem registro no CFO],
            [Anúncio, propaganda e publicidade irregular],
            [Exercício irregular],
            [Exercício ilegal],
            [Acobertamento de exercício ilegal],
            [Outro:]
        )
    ) AS PivotData
) TPT ON C.Sigla = TPT.Categoria
WHERE TPT.Categoria LIKE ':categoria'
GROUP BY ROLLUP(C.Sigla)
ORDER BY
    CRO,
    IIF(C.Sigla IS NULL, 1, 0),
    C.Sigla;
