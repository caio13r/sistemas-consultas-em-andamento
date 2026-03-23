SELECT
	(SELECT CadCon.UF
		FROM :banco.Cadastro.Conselhos AS CadCon
		WHERE CadCon.IdConselho = '00000000-0000-0000-0000-000000000001'
	) AS [CRO],
    CadPe.NomeRazaoSocial AS [Nome_Fiscal],
    CadPe.CPFCNPJ AS [CPF],
    IIF(EXISTS (
            SELECT 1 
            FROM :banco.Cadastro.PessoasUnidades AS CadPeUn
            WHERE   CadPe.IdPessoa = CadPeUn.IdPessoa
                AND CadPeUn.Ativo = 1
        ), 'Sim', 'Não'
    ) AS [Usuario_Sistema]
FROM :banco.Cadastro.Pessoas AS CadPe
    INNER JOIN :banco.Fiscalizacao.Fiscais AS FisFi
        ON CadPe.IdPessoa = FisFi.IdPessoa
WHERE CadPe.Ativo = 1
ORDER BY CadPe.NomeRazaoSocial;