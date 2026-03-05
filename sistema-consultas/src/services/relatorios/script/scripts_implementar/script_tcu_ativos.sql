/***** CRO/AP *****/
-- DECLARE
-- @dataFim DATE = '2022-12-31'

	select 'AP' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ap.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ap.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ap.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ap.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ap.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ap.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ap.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'AP' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ap.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ap.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ap.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ap.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ap.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ap.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ap.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
		select 'AC' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ac.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ac.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ac.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ac.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ac.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ac.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ac.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'AC' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ac.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ac.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ac.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ac.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ac.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ac.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ac.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
		select 'AL' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_al.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_al.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_al.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_al.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_al.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_al.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_al.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'AL' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_al.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_al.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_al.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_al.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_al.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_al.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_al.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA

--
	UNION
--
	select 'AM' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_am.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_am.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_am.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_am.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_am.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_am.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_am.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'AM' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_am.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_am.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_am.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_am.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_am.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_am.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_am.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'BA' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ba.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ba.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ba.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ba.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ba.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ba.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ba.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'BA' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ba.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ba.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ba.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ba.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ba.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ba.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ba.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'CE' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ce.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ce.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ce.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ce.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ce.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ce.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ce.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'CE' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ce.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ce.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ce.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ce.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ce.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ce.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ce.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'DF' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_df.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_df.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_df.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_df.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_df.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_df.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_df.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'DF' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_df.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_df.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_df.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_df.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_df.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_df.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_df.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'ES' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_es.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_es.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_es.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_es.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_es.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_es.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_es.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'ES' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_es.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_es.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_es.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_es.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_es.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_es.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_es.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'GO' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_go.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_go.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_go.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_go.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_go.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_go.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_go.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'GO' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_go.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_go.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_go.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_go.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_go.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_go.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_go.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MA' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ma.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ma.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ma.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ma.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ma.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ma.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ma.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MA' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ma.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ma.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ma.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ma.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ma.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ma.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ma.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MG' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_mg.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_mg.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_mg.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_mg.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_mg.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_mg.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_mg.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MG' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_mg.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_mg.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_mg.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_mg.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_mg.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_mg.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_mg.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MS' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ms.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ms.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ms.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ms.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ms.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ms.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ms.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MS' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ms.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ms.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ms.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ms.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ms.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ms.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ms.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MT' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_mt.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_mt.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_mt.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_mt.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_mt.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_mt.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_mt.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'MT' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_mt.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_mt.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_mt.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_mt.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_mt.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_mt.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_mt.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PA' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pa.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pa.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pa.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pa.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pa.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pa.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pa.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PA' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pa.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pa.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pa.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pa.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pa.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pa.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pa.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PB' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pb.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pb.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pb.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pb.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pb.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pb.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pb.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PB' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pb.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pb.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pb.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pb.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pb.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pb.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pb.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PE' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pe.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pe.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pe.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pe.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pe.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pe.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pe.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PE' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pe.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pe.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pe.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pe.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pe.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pe.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pe.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PI' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pi.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pi.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pi.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pi.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pi.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pi.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pi.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PI' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pi.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pi.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pi.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pi.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pi.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pi.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pi.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PR' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pr.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pr.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pr.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pr.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pr.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pr.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pr.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'PR' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_pr.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_pr.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_pr.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_pr.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_pr.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_pr.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_pr.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RJ' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rj.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rj.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rj.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rj.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rj.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rj.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rj.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RJ' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rj.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rj.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rj.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rj.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rj.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rj.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rj.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RN' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rn.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rn.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rn.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rn.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rn.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rn.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rn.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RN' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rn.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rn.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rn.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rn.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rn.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rn.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rn.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RO' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ro.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ro.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ro.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ro.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ro.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ro.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ro.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RO' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_ro.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_ro.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_ro.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_ro.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_ro.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_ro.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_ro.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RR' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rr.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rr.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rr.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rr.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rr.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rr.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rr.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RR' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rr.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rr.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rr.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rr.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rr.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rr.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rr.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RS' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rs.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rs.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rs.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rs.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rs.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rs.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rs.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'RS' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_rs.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_rs.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_rs.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_rs.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_rs.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_rs.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_rs.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'SC' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_sc.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_sc.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_sc.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_sc.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_sc.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_sc.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_sc.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'SC' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_sc.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_sc.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_sc.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_sc.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_sc.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_sc.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_sc.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'SE' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_se.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_se.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_se.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_se.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_se.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_se.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_se.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'SE' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_se.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_se.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_se.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_se.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_se.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_se.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_se.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'SP' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_sp.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_sp.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_sp.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_sp.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_sp.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_sp.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_sp.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'SP' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_sp.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_sp.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_sp.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_sp.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_sp.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_sp.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_sp.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'TO' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_to.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_to.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_to.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_to.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_to.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_to.Cadastro.PessoasFisicas as pf on pf.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_to.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA
--
	UNION
--
	select 'TO' as CRO
		, SIGLA
		, COUNT(xx.NumeroRegistro) AS TOTAL
	FROM (
			SELECT cat.Sigla AS SIGLA
				, reg.NumeroRegistro
				, p.NomeRazaoSocial as PROFISSIONAL
				, st.Nome as SITUACAO
				, rsi.DataInicioSituacao
				, convert(char,rsi.DataInicioSituacao,103) as DT_INICIO
				, RSI.idregistro
				, rsi.IdSituacao
			FROM cro_to.Registro.RegistrosSituacoes as rsi
			join (  SELECT RSI.idregistro, max(rsi.DataInicioSituacao) as data_max
					FROM cro_to.Registro.RegistrosSituacoes as rsi
					group by RSI.idregistro) as RSI2 on rsi2.IdRegistro = rsi.IdRegistro and rsi2.data_max = rsi.DataInicioSituacao
			join cro_to.Registro.Situacoes as st on st.IdSituacao = rsi.IdSituacao
			join cro_to.Registro.Registros as reg on reg.IdRegistro = rsi.idregistro
			join cro_to.Cadastro.Pessoas as p on p.IdPessoa = reg.IdPessoa
			join cro_to.Cadastro.PessoasJuridicas as pj on pj.IdPessoa = p.IdPessoa -- /* RECUPERA APENAS PESSOAS FISICAS */
			join cro_to.Registro.Categorias as cat on cat.IdCategoria = reg.IdCategoria
			WHERE RSI2.data_max  <= @dataFim  -- limitando a data de inicio do REGISTRO no CFO.
			--WHERE CAT.Sigla = 'CD'
		) AS XX
	WHERE XX.SITUACAO = 'ATIVO'
	GROUP BY XX.SIGLA

GO


