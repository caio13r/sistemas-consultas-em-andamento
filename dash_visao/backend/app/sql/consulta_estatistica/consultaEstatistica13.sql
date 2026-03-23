SELECT
    [CRO],
    [Categoria],
    [Inscricao],
    [Nome],
    [CPF],
    [Tipo_Inscricao],
    [Situacao],
    [Detalhe],
    [Correspondencia],
    [Tipo_Endereco],
    [Data_Atualizacao],
    [Logradouro],
    [Numero],
    [Complemento],
    [Bairro],
    [Municipio],
    [UF],
    [CEP]
FROM CFO_CWS.dbo.vw_Cons_Enderecos_Comerciais_Atualizados_Localidade
WHERE [CRO] = @CRO_UF 
ORDER BY
    [CRO],
    [Nome],
    [Categoria]