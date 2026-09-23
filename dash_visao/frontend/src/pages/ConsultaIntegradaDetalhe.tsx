import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box, Typography, Button, CircularProgress, Alert, Chip,
  Accordion, AccordionSummary, AccordionDetails, Divider,
} from '@mui/material';
import {
  ExpandMore as ExpandMoreIcon,
  ArrowBack as ArrowBackIcon,
  Print as PrintIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

const situacaoColor: Record<string, any> = {
  Ativo: 'success', Inativo: 'error', Suspenso: 'warning', Cancelado: 'error',
};

const Field: React.FC<{ label: string; value?: string | null }> = ({ label, value }) => (
  <Box sx={{ mb: 1.5 }}>
    <Typography variant="caption" sx={{ fontWeight: 700, color: 'text.secondary', textTransform: 'uppercase', letterSpacing: '0.05em', fontSize: '0.68rem' }}>
      {label}
    </Typography>
    <Typography variant="body2" sx={{ mt: 0.25, color: value ? 'text.primary' : 'text.disabled', fontStyle: value ? 'normal' : 'italic' }}>
      {value || 'Não informado'}
    </Typography>
  </Box>
);

const Section: React.FC<{ title: string; defaultExpanded?: boolean; children: React.ReactNode }> = ({ title, defaultExpanded, children }) => (
  <Accordion defaultExpanded={defaultExpanded} disableGutters elevation={0}
    sx={{ border: '1px solid', borderColor: 'divider', mb: 1.5, borderRadius: '10px !important', '&:before': { display: 'none' } }}>
    <AccordionSummary expandIcon={<ExpandMoreIcon />}
      sx={{ borderRadius: '10px', px: 2.5, py: 0.5, bgcolor: 'rgba(122,30,38,0.03)', '&.Mui-expanded': { borderBottom: '1px solid', borderColor: 'divider' } }}>
      <Typography sx={{ fontWeight: 700, fontSize: '0.85rem', color: 'primary.main', textTransform: 'uppercase', letterSpacing: '0.06em' }}>
        {title}
      </Typography>
    </AccordionSummary>
    <AccordionDetails sx={{ px: 2.5, py: 2 }}>
      {children}
    </AccordionDetails>
  </Accordion>
);

const Grid: React.FC<{ children: React.ReactNode; cols?: number }> = ({ children, cols = 4 }) => (
  <Box sx={{ display: 'grid', gridTemplateColumns: `repeat(${cols}, 1fr)`, gap: 0, columnGap: 3 }}>
    {children}
  </Box>
);

export default function ConsultaIntegradaDetalhe() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!id) return;
    api.get(`/consulta-integrada/profissionais/${id}`)
      .then(res => setData(res.data))
      .catch(e => setError(e.response?.data?.detail || 'Erro ao carregar dados do profissional.'))
      .finally(() => setLoading(false));
  }, [id]);

  const handlePrint = () => {
    if (!data) return;
    const win = window.open('', '_blank', 'width=1100,height=800');
    if (!win) return;

    const f = (label: string, value: string | null | undefined) =>
      `<div class="field"><span class="field-label">${label}</span><span class="${value ? 'field-value' : 'field-empty'}">${value || 'Não informado'}</span></div>`;

    const section = (title: string, body: string) =>
      `<div class="section"><div class="section-title">${title}</div><div class="section-body">${body}</div></div>`;

    const grid = (fields: string, cols = 4) =>
      `<div class="grid-${cols}">${fields}</div>`;

    const formacoes = (data.formacoes || []).map((fo: any, i: number) =>
      `${i > 0 ? '<hr class="separator">' : ''}${grid([
        f('Curso', fo.Curso || fo.curso),
        f('Instituição de Ensino', fo.InstituicaoDeEnsino || fo.instituicao_de_ensino),
        f('Data de Conclusão', fo.DataDeConclusao || fo.data_de_conclusao),
        f('Data de Colação', fo.DataDeColacao || fo.data_de_colacao),
        f('Especialidades', fo.Especialidades || fo.especialidades),
        f('Habilitação', fo.Habilitacao || fo.habilitacao),
      ].join(''))}`
    ).join('') || '<span class="field-empty">Não há formação cadastrada.</span>';

    const resps = (data.responsabilidades_tecnicas || []).map((r: any, i: number) =>
      `${i > 0 ? '<hr class="separator">' : ''}${grid([
        f('Tipo', r.tipo), f('Razão Social', r.razao_social),
        f('Nome Fantasia', r.nome_fantasia), f('CNPJ', r.cnpj),
        f('Categoria', r.categoria), f('Registro', r.registro),
        f('Data Início', r.data_inicio), f('Data Término', r.data_termino),
      ].join(''))}`
    ).join('') || '<span class="field-empty">Não há responsabilidades cadastradas.</span>';

    const processos = (data.processos_especialidade || []).map((p: any, i: number) =>
      `${i > 0 ? '<hr class="separator">' : ''}${grid([
        f('Nº Processo', p.numero_processo), f('Assunto', p.assunto),
        f('Classificação', p.classificacao), f('Etapa', p.etapa),
        f('Andamento', p.andamento), f('Data do Andamento', p.data_andamento),
      ].join(''), 3)}`
    ).join('') || '<span class="field-empty">Não há processos cadastrados.</span>';

    win.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8">
      <title>Detalhes — ${data.nome || 'Profissional'}</title>
      <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
        h1 { font-size: 16px; margin: 0 0 4px 0; }
        .chips { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .chip { border: 1px solid #aaa; border-radius: 6px; padding: 1px 8px; font-size: 10px; font-weight: 600; }
        .lgpd { background: #f5f5f5; padding: 5px 10px; margin-bottom: 12px; border-left: 3px solid #7A1E26; font-size: 10px; color: #555; }
        .section { margin-bottom: 12px; border: 1px solid #ddd; border-radius: 6px; overflow: hidden; break-inside: avoid; }
        .section-title { background: #f8f4f4; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #7A1E26; padding: 5px 12px; border-bottom: 1px solid #ddd; }
        .section-body { padding: 8px 12px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0 14px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 14px; }
        .field { margin-bottom: 8px; }
        .field-label { font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #888; display: block; }
        .field-value { font-size: 11px; color: #222; }
        .field-empty { font-size: 11px; color: #bbb; font-style: italic; }
        .separator { border: none; border-top: 1px solid #eee; margin: 6px 0; }
      </style>
    </head><body>
      <h1>${data.nome || '—'}</h1>
      <div class="chips">
        ${data.cro ? `<span class="chip">CRO-${data.cro}</span>` : ''}
        ${data.categoria ? `<span class="chip">${data.categoria}</span>` : ''}
        ${data.inscricao ? `<span class="chip">Insc. ${data.inscricao}</span>` : ''}
        ${data.situacao ? `<span class="chip">${data.situacao}</span>` : ''}
      </div>
      <div class="lgpd"><strong>LGPD</strong> — Dados de uso restrito conforme Lei 13.709/2018. O usuário é responsável pelo uso das informações.</div>
      ${section('Dados do Profissional', grid([
        f('Nome', data.nome), f('CPF', data.cpf), f('CRO', data.cro), f('Categoria', data.categoria),
        f('Inscrição', data.inscricao), f('Tipo de Inscrição', data.tipo_inscricao),
        f('Situação', data.situacao), f('Detalhe', data.detalhe),
        f('Situação Financeira', data.situacao_financeira),
        f('Data da Inscrição', data.data_inscricao), f('Data da Situação', data.data_situacao),
      ].join('')))}
      ${section('Dados Pessoais', grid([
        f('Data de Nascimento', data.data_nascimento), f('Gênero', data.genero),
        f('Estado Civil', data.estado_civil), f('Naturalidade', data.naturalidade),
        f('Nacionalidade', data.nacionalidade), f('Nome Social', data.nome_social),
        f('Nome da Mãe', data.nome_mae), f('Nome do Pai', data.nome_pai),
        f('Identidade', data.identidade), f('Órgão Emissor', data.orgao_emissor),
        f('UF (RG)', data.uf_rg), f('Data Emissão RG', data.data_emissao_rg),
      ].join('')))}
      ${section('Endereço e Contato', grid([
        f('Tipo do Endereço', data.tipo_endereco), f('Logradouro', data.logradouro),
        f('Número', data.numero), f('Complemento', data.complemento),
        f('Bairro', data.bairro), f('Município', data.municipio),
        f('UF', data.uf), f('CEP', data.cep),
        f('Telefone', data.telefone ? data.telefone.replace(/,/g, ' | ') : null),
        f('E-mail', Array.isArray(data.email) ? data.email.join(' | ') : data.email),
        f('Rede Social', data.rede_social),
      ].join('')))}
      ${section('Formações, Especialidades e Habilitações', formacoes)}
      ${section('Responsabilidades Técnicas', resps)}
      ${section('Processos de Especialidade / Habilitação (SISDOC)', processos)}
    </body></html>`);
    win.document.close();
    setTimeout(() => { win.focus(); win.print(); win.close(); }, 400);
  };

  if (loading) {
    return (
      <PageContainer>
        <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', py: 10, gap: 2 }}>
          <CircularProgress color="primary" />
          <Typography color="text.secondary">Carregando dados do profissional...</Typography>
        </Box>
      </PageContainer>
    );
  }

  if (error || !data) {
    return (
      <PageContainer>
        <Button startIcon={<ArrowBackIcon />} onClick={() => navigate('/consulta-integrada')} sx={{ mb: 2, textTransform: 'none' }}>
          Voltar à busca
        </Button>
        <Alert severity="error">{error || 'Profissional não encontrado.'}</Alert>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      {/* Cabeçalho */}
      <Box sx={{ mb: 3 }} className="no-print">
        <Button startIcon={<ArrowBackIcon />} onClick={() => navigate('/consulta-integrada')} sx={{ textTransform: 'none', mb: 2 }}>
          Voltar à busca
        </Button>
        <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: 1 }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700, mb: 0.75 }}>{data.nome}</Typography>
            <Box sx={{ display: 'flex', gap: 0.75, flexWrap: 'wrap', alignItems: 'center' }}>
              {data.cro && <Chip label={`CRO-${data.cro}`} size="small" variant="outlined" />}
              {data.categoria && <Chip label={data.categoria} size="small" variant="outlined" />}
              {data.inscricao && <Chip label={`Insc. ${data.inscricao}`} size="small" variant="outlined" />}
              {data.situacao && <Chip label={data.situacao} size="small" color={situacaoColor[data.situacao] || 'default'} sx={{ fontWeight: 700 }} />}
            </Box>
          </Box>
          <Button startIcon={<PrintIcon />} onClick={handlePrint} variant="outlined" size="small" sx={{ textTransform: 'none' }}>
            Imprimir
          </Button>
        </Box>
      </Box>

      {/* LGPD */}
      <Box sx={{ px: 2, py: 1, bgcolor: '#f5f5f5', borderRadius: 1, borderLeft: '3px solid', borderColor: 'primary.main', mb: 2.5 }}>
        <Typography variant="caption" color="text.secondary">
          <strong>LGPD</strong> — Dados de uso restrito conforme Lei 13.709/2018. O usuário é responsável pelo uso das informações.
        </Typography>
      </Box>

      <Divider sx={{ mb: 2.5 }} className="no-print" />

      <Box id="detalhe-print-area">
      {/* 1. Dados do Profissional */}
      <Section title="Dados do Profissional" defaultExpanded>
        <Grid>
          <Field label="Nome" value={data.nome} />
          <Field label="CPF" value={data.cpf} />
          <Field label="CRO" value={data.cro} />
          <Field label="Categoria" value={data.categoria} />
          <Field label="Inscrição" value={data.inscricao} />
          <Field label="Tipo de Inscrição" value={data.tipo_inscricao} />
          <Field label="Situação" value={data.situacao} />
          <Field label="Detalhe" value={data.detalhe} />
          <Field label="Situação Financeira" value={data.situacao_financeira} />
          <Field label="Data da Inscrição" value={data.data_inscricao} />
          <Field label="Data da Situação" value={data.data_situacao} />
        </Grid>
      </Section>

      {/* 2. Dados Pessoais */}
      <Section title="Dados Pessoais">
        <Grid>
          <Field label="Data de Nascimento" value={data.data_nascimento} />
          <Field label="Gênero" value={data.genero} />
          <Field label="Estado Civil" value={data.estado_civil} />
          <Field label="Naturalidade" value={data.naturalidade} />
          <Field label="Nacionalidade" value={data.nacionalidade} />
          <Field label="Nome Social" value={data.nome_social} />
          <Field label="Nome da Mãe" value={data.nome_mae} />
          <Field label="Nome do Pai" value={data.nome_pai} />
          <Field label="Identidade" value={data.identidade} />
          <Field label="Órgão Emissor" value={data.orgao_emissor} />
          <Field label="UF (RG)" value={data.uf_rg} />
          <Field label="Data Emissão RG" value={data.data_emissao_rg} />
        </Grid>
      </Section>

      {/* 3. Endereço e Contato */}
      <Section title="Endereço e Contato">
        <Grid>
          <Field label="Tipo do Endereço" value={data.tipo_endereco} />
          <Field label="Logradouro" value={data.logradouro} />
          <Field label="Número" value={data.numero} />
          <Field label="Complemento" value={data.complemento} />
          <Field label="Bairro" value={data.bairro} />
          <Field label="Município" value={data.municipio} />
          <Field label="UF" value={data.uf} />
          <Field label="CEP" value={data.cep} />
          <Field label="Telefone" value={data.telefone ? data.telefone.replace(/,/g, ' | ') : null} />
          <Field label="E-mail" value={data.email ? (Array.isArray(data.email) ? data.email.join(' | ') : data.email) : null} />
          <Field label="Rede Social" value={data.rede_social} />
        </Grid>
      </Section>

      {/* 4. Formações, Especialidades e Habilitações */}
      <Section title="Formações, Especialidades e Habilitações">
        {data.formacoes?.length > 0 ? data.formacoes.map((f: any, i: number) => (
          <Box key={i} sx={{ mb: i < data.formacoes.length - 1 ? 2 : 0, pb: i < data.formacoes.length - 1 ? 2 : 0, borderBottom: i < data.formacoes.length - 1 ? '1px solid' : 'none', borderColor: 'divider' }}>
            <Grid>
              <Field label="Curso" value={f.Curso || f.curso} />
              <Field label="Instituição de Ensino" value={f.InstituicaoDeEnsino || f.instituicao_de_ensino} />
              <Field label="Data de Conclusão" value={f.DataDeConclusao || f.data_de_conclusao} />
              <Field label="Data de Colação" value={f.DataDeColacao || f.data_de_colacao} />
              <Field label="Especialidades" value={f.Especialidades || f.especialidades} />
              <Field label="Habilitação" value={f.Habilitacao || f.habilitacao} />
            </Grid>
          </Box>
        )) : (
          <Typography variant="body2" color="text.disabled" sx={{ fontStyle: 'italic' }}>Não há formação cadastrada.</Typography>
        )}
      </Section>

      {/* 5. Responsabilidades Técnicas */}
      <Section title="Responsabilidades Técnicas">
        {data.responsabilidades_tecnicas?.length > 0 ? data.responsabilidades_tecnicas.map((r: any, i: number) => (
          <Box key={i} sx={{ mb: i < data.responsabilidades_tecnicas.length - 1 ? 2 : 0, pb: i < data.responsabilidades_tecnicas.length - 1 ? 2 : 0, borderBottom: i < data.responsabilidades_tecnicas.length - 1 ? '1px solid' : 'none', borderColor: 'divider' }}>
            <Grid>
              <Field label="Tipo" value={r.tipo} />
              <Field label="Razão Social" value={r.razao_social} />
              <Field label="Nome Fantasia" value={r.nome_fantasia} />
              <Field label="CNPJ" value={r.cnpj} />
              <Field label="Categoria da Empresa" value={r.categoria} />
              <Field label="Registro da Empresa" value={r.registro} />
              <Field label="Data Início" value={r.data_inicio} />
              <Field label="Data Término" value={r.data_termino} />
            </Grid>
          </Box>
        )) : (
          <Typography variant="body2" color="text.disabled" sx={{ fontStyle: 'italic' }}>Não há responsabilidades cadastradas.</Typography>
        )}
      </Section>

      {/* 6. Processos de Especialidade / Habilitação */}
      <Section title="Processos de Especialidade / Habilitação (SISDOC)">
        {data.processos_especialidade?.length > 0 ? data.processos_especialidade.map((p: any, i: number) => (
          <Box key={i} sx={{ mb: i < data.processos_especialidade.length - 1 ? 2 : 0, pb: i < data.processos_especialidade.length - 1 ? 2 : 0, borderBottom: i < data.processos_especialidade.length - 1 ? '1px solid' : 'none', borderColor: 'divider' }}>
            <Grid cols={3}>
              <Field label="Nº Processo" value={p.numero_processo} />
              <Field label="Assunto" value={p.assunto} />
              <Field label="Classificação" value={p.classificacao} />
              <Field label="Etapa" value={p.etapa} />
              <Field label="Andamento" value={p.andamento} />
              <Field label="Data do Andamento" value={p.data_andamento} />
            </Grid>
          </Box>
        )) : (
          <Typography variant="body2" color="text.disabled" sx={{ fontStyle: 'italic' }}>Não há processos cadastrados.</Typography>
        )}
      </Section>

      </Box>

      <Box sx={{ mt: 2 }}>
        <Button startIcon={<ArrowBackIcon />} onClick={() => navigate('/consulta-integrada')} sx={{ textTransform: 'none' }}>
          Voltar à busca
        </Button>
      </Box>
    </PageContainer>
  );
}
