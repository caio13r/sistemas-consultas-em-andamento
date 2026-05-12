import React, { useState } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Snackbar, Alert,
  Grid, FormControl, InputLabel, Select, MenuItem, Chip, IconButton,
  Tooltip, Divider, Tabs, Tab, Paper,
} from '@mui/material';
import { Search as SearchIcon, Clear as ClearIcon, Visibility as ViewIcon, Print as PrintIcon, FileDownload as DownloadIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

interface Profissional {
  nome: string | null;
  cpf: string | null;
  cro: string | null;
  categoria: string | null;
  inscricao: string | null;
  tipo_inscricao: string | null;
  situacao: string | null;
  detalhe: string | null;
  situacao_financeira: string | null;
  id_registro: string | number | null;
}

interface Empresa {
  razao_social: string | null;
  nome_fantasia: string | null;
  cnpj: string | null;
  cro: string | null;
  categoria: string | null;
  inscricao: string | null;
  situacao: string | null;
  detalhe: string | null;
  situacao_financeira: string | null;
  logradouro: string | null;
  municipio: string | null;
  uf: string | null;
  telefone: string | null;
  email: string | null;
  id_registro: string | number | null;
}

const UF_LIST = [
  'AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT',
  'PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO',
];

const CATEGORIAS = [
  { value: 'CD', label: 'CD - Cirurgião-Dentista' },
  { value: 'TPD', label: 'TPD - Técnico em Prótese Dentária' },
  { value: 'THD', label: 'THD - Técnico em Higiene Dental' },
  { value: 'ASB', label: 'ASB - Auxiliar de Saúde Bucal' },
  { value: 'TSB', label: 'TSB - Técnico em Saúde Bucal' },
  { value: 'APD', label: 'APD - Auxiliar de Prótese Dentária' },
  { value: 'EPAO', label: 'EPAO - Especialista Patologia Oral' },
  { value: 'LB', label: 'LB - Laboratório' },
  { value: 'ECIPO', label: 'ECIPO' },
];

const situacaoColor: Record<string, 'success' | 'error' | 'warning' | 'default'> = {
  'Ativo': 'success', 'Inativo': 'error', 'Suspenso': 'warning', 'Cancelado': 'error',
};

export default function ConsultaIntegrada() {
  const [tab, setTab] = useState(0);
  const [filters, setFilters] = useState({
    nome: '', inscricao: '', cpf: '', email: '', telefone: '', cro: '', categoria: '', cnpj: '',
  });
  const [resultadosPF, setResultadosPF] = useState<Profissional[]>([]);
  const [resultadosPJ, setResultadosPJ] = useState<Empresa[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [detailOpen, setDetailOpen] = useState<any>(null);
  const [detailLoading, setDetailLoading] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  const handleSearch = async () => {
    const hasFilter = Object.values(filters).some(v => v.trim() !== '');
    if (!hasFilter) {
      setSnackbar({ open: true, message: 'Preencha pelo menos um campo de busca.', severity: 'info' });
      return;
    }
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      Object.entries(filters).forEach(([k, v]) => { if (v.trim()) params.append(k, v.trim()); });

      if (tab === 0) {
        const res = await api.get(`/consulta-integrada/profissionais?${params}`);
        setResultadosPF(res.data.resultados); setTotal(res.data.total); setResultadosPJ([]);
      } else {
        const res = await api.get(`/consulta-integrada/empresas?${params}`);
        setResultadosPJ(res.data.resultados); setTotal(res.data.total); setResultadosPF([]);
      }
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultadosPF([]); setResultadosPJ([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ nome: '', inscricao: '', cpf: '', email: '', telefone: '', cro: '', categoria: '', cnpj: '' });
    setResultadosPF([]); setResultadosPJ([]); setTotal(0); setSearched(false); setDetailOpen(null);
  };

  const handleViewDetail = async (idRegistro: string | number) => {
    setDetailLoading(true);
    try {
      const res = await api.get(`/consulta-integrada/profissionais/${idRegistro}`);
      setDetailOpen(res.data);
    } catch (e: any) {
      setSnackbar({ open: true, message: 'Erro ao carregar detalhes', severity: 'error' });
    } finally { setDetailLoading(false); }
  };

  const handlePrint = () => {
    const printEl = document.getElementById('modal-detail-print');
    if (!printEl) return;
    const win = window.open('', '_blank');
    if (!win) {
      setSnackbar({ open: true, message: 'Permita pop-ups para imprimir.', severity: 'warning' });
      return;
    }
    const lgpdPrint = '<div class="lgpd" style="background:#e3f2fd;padding:10px;margin-bottom:15px;border-left:4px solid #1976d2;font-size:11px;"><strong>Aviso LGPD:</strong> Os dados exibidos são de uso restrito (Lei 13.709/2018). O usuário é responsável pelo uso das informações consultadas.</div>';
    win.document.write(`
      <html><head><title>Detalhes do Profissional</title>
      <style>body{font-family:Arial,sans-serif;padding:20px;font-size:12px;}
      .lgpd{background:#e3f2fd;padding:10px;margin-bottom:15px;border-left:4px solid #1976d2;font-size:11px;}
      .section{font-weight:bold;margin-top:15px;margin-bottom:5px;}
      .MuiTypography-subtitle2{color:#666;font-size:11px;}
      .MuiTypography-body2{margin-bottom:8px;}</style>
      </head><body>${lgpdPrint}${printEl.innerHTML}</body></html>
    `);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 250);
  };

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Integrada - Visão Nacional</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Quanto mais preciso os dados de busca, melhor e mais rápido será o resultado. Máximo 2000 resultados.
      </Typography>

      <Tabs value={tab} onChange={(_, v) => { setTab(v); handleClear(); }} sx={{ mb: 2 }}>
        <Tab label="Pessoa Física (Profissional)" />
        <Tab label="Pessoa Jurídica (Empresa)" />
      </Tabs>

      <Divider sx={{ mb: 2 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>CRO (Estado)</InputLabel>
            <Select value={filters.cro} label="CRO (Estado)" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>Categoria</InputLabel>
            <Select value={filters.categoria} label="Categoria" onChange={e => setFilters(p => ({ ...p, categoria: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {CATEGORIAS.map(c => <MenuItem key={c.value} value={c.value}>{c.label}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={12} sm={6} md={6}>
          <TextField fullWidth size="small" label={tab === 0 ? "Nome do profissional" : "Razão social / Nome fantasia"} value={filters.nome} onChange={e => setFilters(p => ({ ...p, nome: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Inscrição" value={filters.inscricao} onChange={e => setFilters(p => ({ ...p, inscricao: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label={tab === 0 ? "CPF" : "CNPJ"} value={tab === 0 ? filters.cpf : filters.cnpj} onChange={e => setFilters(p => ({ ...p, [tab === 0 ? 'cpf' : 'cnpj']: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="E-mail" value={filters.email} onChange={e => setFilters(p => ({ ...p, email: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Telefone" value={filters.telefone} onChange={e => setFilters(p => ({ ...p, telefone: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
      </Grid>

      <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
        <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
        <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
        {searched && (resultadosPF.length > 0 || resultadosPJ.length > 0) && (
          <Button
            variant="outlined"
            color="success"
            startIcon={<DownloadIcon />}
            disabled={exporting}
            onClick={async () => {
              setExporting(true);
              try {
                const data = tab === 0
                  ? resultadosPF.map(p => ({ ...p }))
                  : resultadosPJ.map(e => ({ ...e }));
                const columns = tab === 0
                  ? [
                      { key: 'nome', label: 'Nome' }, { key: 'cpf', label: 'CPF' },
                      { key: 'cro', label: 'CRO' }, { key: 'categoria', label: 'Categoria' },
                      { key: 'inscricao', label: 'Inscrição' }, { key: 'tipo_inscricao', label: 'Tipo' },
                      { key: 'situacao', label: 'Situação' }, { key: 'situacao_financeira', label: 'Sit. Financeira' },
                    ]
                  : [
                      { key: 'razao_social', label: 'Razão Social' }, { key: 'cnpj', label: 'CNPJ' },
                      { key: 'cro', label: 'CRO' }, { key: 'inscricao', label: 'Inscrição' },
                      { key: 'situacao', label: 'Situação' }, { key: 'municipio', label: 'Município' },
                      { key: 'uf', label: 'UF' }, { key: 'telefone', label: 'Telefone' },
                    ];
                await exportService.exportGenericExcel({
                  data,
                  columns,
                  title: tab === 0 ? 'Consulta Integrada - Profissionais' : 'Consulta Integrada - Empresas',
                  filename: tab === 0 ? 'consulta_integrada_pf' : 'consulta_integrada_pj',
                });
                setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
              } catch (err) {
                setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
              } finally { setExporting(false); }
            }}
          >
            {exporting ? 'Exportando...' : 'Exportar Excel'}
          </Button>
        )}
      </Box>

      <Divider sx={{ mb: 2 }} />

      {loading ? <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
      : searched && (
        <>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {total > 0 ? `${total} resultado(s)${total > 2000 ? ' (exibindo 2000)' : ''}` : 'Nenhum resultado'}
          </Typography>

          {/* Tabela PF */}
          {tab === 0 && resultadosPF.length > 0 && (
            <TableContainer sx={{ maxHeight: 'calc(100vh - 450px)' }}>
              <Table stickyHeader size="small">
                <TableHead><TableRow>
                  <TableCell sx={{ fontWeight: 'bold' }}>Nome</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CRO</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Categoria</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Inscrição</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CPF</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Tipo</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Situação</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Sit. Financeira</TableCell>
                  <TableCell align="center" sx={{ fontWeight: 'bold' }}>Ações</TableCell>
                </TableRow></TableHead>
                <TableBody>
                  {resultadosPF.map((p, i) => (
                    <TableRow key={i} hover>
                      <TableCell>{p.nome || '-'}</TableCell>
                      <TableCell>{p.cro || '-'}</TableCell>
                      <TableCell>{p.categoria || '-'}</TableCell>
                      <TableCell>{p.inscricao || '-'}</TableCell>
                      <TableCell>{p.cpf || '-'}</TableCell>
                      <TableCell>{p.tipo_inscricao || '-'}</TableCell>
                      <TableCell><Chip label={p.situacao || '-'} color={situacaoColor[p.situacao || ''] || 'default'} size="small" variant="outlined" /></TableCell>
                      <TableCell>{p.situacao_financeira || '-'}</TableCell>
                      <TableCell align="center">
                        {p.id_registro && (
                          <Tooltip title="Ver detalhes">
                            <IconButton size="small" color="primary" onClick={() => handleViewDetail(p.id_registro!)}>
                              <ViewIcon />
                            </IconButton>
                          </Tooltip>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}

          {/* Tabela PJ */}
          {tab === 1 && resultadosPJ.length > 0 && (
            <TableContainer sx={{ maxHeight: 'calc(100vh - 450px)' }}>
              <Table stickyHeader size="small">
                <TableHead><TableRow>
                  <TableCell sx={{ fontWeight: 'bold' }}>Razão Social</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CNPJ</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CRO</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Inscrição</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Situação</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Município</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>UF</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Telefone</TableCell>
                </TableRow></TableHead>
                <TableBody>
                  {resultadosPJ.map((e, i) => (
                    <TableRow key={i} hover>
                      <TableCell>{e.razao_social || '-'}</TableCell>
                      <TableCell>{e.cnpj || '-'}</TableCell>
                      <TableCell>{e.cro || '-'}</TableCell>
                      <TableCell>{e.inscricao || '-'}</TableCell>
                      <TableCell><Chip label={e.situacao || '-'} color={situacaoColor[e.situacao || ''] || 'default'} size="small" variant="outlined" /></TableCell>
                      <TableCell>{e.municipio || '-'}</TableCell>
                      <TableCell>{e.uf || '-'}</TableCell>
                      <TableCell>{e.telefone || '-'}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </>
      )}

      {/* Modal de detalhes PF */}
      {detailOpen && (
        <Box sx={{ position: 'fixed', top: 0, left: 0, right: 0, bottom: 0, bgcolor: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999 }} onClick={() => setDetailOpen(null)}>
          <Box sx={{ bgcolor: 'background.paper', borderRadius: 2, p: 4, maxWidth: 750, width: '90%', maxHeight: '85vh', overflow: 'auto' }} onClick={e => e.stopPropagation()}>
            <Typography variant="h6" gutterBottom>Detalhes do Profissional</Typography>

            {/* Aviso LGPD */}
            <Box sx={{ bgcolor: 'info.light', color: 'info.contrastText', p: 1.5, borderRadius: 1, mb: 2, borderLeft: '4px solid', borderColor: 'primary.main' }}>
              <Typography variant="caption" sx={{ fontWeight: 'bold', display: 'block' }}>Aviso de Responsabilidade - LGPD</Typography>
              <Typography variant="caption">
                Os dados exibidos são de uso restrito e devem ser utilizados em conformidade com a Lei Geral de Proteção de Dados (Lei 13.709/2018). 
                O acesso indevido, divulgação não autorizada ou uso inadequado das informações pode constituir infração administrativa e crime. 
                O usuário é o único responsável pelo uso dos dados consultados.
              </Typography>
            </Box>

            <Divider sx={{ mb: 2 }} />

            {detailLoading ? <CircularProgress /> : (
              <Box id="modal-detail-print">
                {/* 1. Dados do Profissional */}
                <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: 'primary.main', mb: 1.5 }}>Dados do Profissional</Typography>
                  <Grid container spacing={2}>
                    {[
                      ['Nome', detailOpen.nome], ['CRO', detailOpen.cro], ['Categoria', detailOpen.categoria], ['Inscrição', detailOpen.inscricao],
                      ['CPF', detailOpen.cpf], ['Tipo de Inscrição', detailOpen.tipo_inscricao], ['Situação', detailOpen.situacao], ['Detalhe', detailOpen.detalhe],
                      ['Situação Financeira', detailOpen.situacao_financeira],
                    ].map(([label, value], i) => (
                      <Grid item xs={6} sm={4} key={i}>
                        <Typography variant="caption" color="text.secondary" component="div">{label}</Typography>
                        <Typography variant="body2">{value || '-'}</Typography>
                      </Grid>
                    ))}
                  </Grid>
                </Paper>

                {/* 2. Dados Pessoais */}
                <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: 'primary.main', mb: 1.5 }}>Dados Pessoais</Typography>
                  <Grid container spacing={2}>
                    {[
                      ['Data de Nascimento', detailOpen.data_nascimento], ['CPF', detailOpen.cpf], ['Gênero', detailOpen.genero],
                      ['Estado Civil', detailOpen.estado_civil], ['Naturalidade', detailOpen.naturalidade], ['Nacionalidade', detailOpen.nacionalidade],
                      ['Nome Social', detailOpen.nome_social ? detailOpen.nome_social : 'Sem nome social cadastrado'], ['Nome da Mãe', detailOpen.nome_mae], ['Nome do Pai', detailOpen.nome_pai],
                      ['E-mail', (Array.isArray(detailOpen.email) ? detailOpen.email.join(', ') : detailOpen.email) || '-'],
                    ].map(([label, value], i) => (
                      <Grid item xs={6} sm={4} key={i}>
                        <Typography variant="caption" color="text.secondary" component="div">{label}</Typography>
                        <Typography variant="body2">{value || '-'}</Typography>
                      </Grid>
                    ))}
                  </Grid>
                </Paper>

                {/* 3. Formações, Especialidades e Habilitações */}
                <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: 'primary.main', mb: 1.5 }}>Formações, Especialidades e Habilitações</Typography>
                  {detailOpen.formacoes?.length > 0 ? (
                    detailOpen.formacoes.map((f: any, i: number) => (
                      <Box key={i} sx={{ mb: i < detailOpen.formacoes.length - 1 ? 2 : 0 }}>
                        <Grid container spacing={2}>
                          {[
                            ['Curso', f.Curso || f.curso],
                            ['Instituição de Ensino', f.InstituicaoDeEnsino || f.instituicao_de_ensino],
                            ['Data de Conclusão', f.DataDeConclusao || f.data_de_conclusao],
                            ['Data de Colação', f.DataDeColacao || f.data_de_colacao],
                            ['Especialidades', f.Especialidades || f.especialidades],
                            ['Habilitação', f.Habilitacao || f.habilitacao],
                          ].map(([label, val], j) => (
                            <Grid item xs={6} sm={4} key={j}>
                              <Typography variant="caption" color="text.secondary" component="div">{label}</Typography>
                              <Typography variant="body2">{val || '-'}</Typography>
                            </Grid>
                          ))}
                        </Grid>
                      </Box>
                    ))
                  ) : (
                    <Typography variant="body2" color="text.secondary">Não há formação cadastrada.</Typography>
                  )}
                </Paper>

                {/* 4. Endereço e Contato */}
                <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: 'primary.main', mb: 1.5 }}>Endereço e Contato</Typography>
                  <Grid container spacing={2}>
                    {[
                      ['Tipo do Endereço', detailOpen.tipo_endereco], ['CEP', detailOpen.cep], ['Logradouro', detailOpen.logradouro],
                      ['Bairro', detailOpen.bairro], ['Número', detailOpen.numero], ['Município', detailOpen.municipio],
                      ['UF', detailOpen.uf], ['Complemento', detailOpen.complemento ? detailOpen.complemento : 'Sem complemento cadastrado'],
                      ['Telefone', (detailOpen.telefone || '').toString().replace(/,/g, ', ')],
                      ['E-mail', (Array.isArray(detailOpen.email) ? detailOpen.email.join(', ') : detailOpen.email) || '-'],
                      ['Rede Social', detailOpen.rede_social ? detailOpen.rede_social : 'Sem rede social cadastrada'],
                    ].map(([label, value], i) => (
                      <Grid item xs={6} sm={4} key={i}>
                        <Typography variant="caption" color="text.secondary" component="div">{label}</Typography>
                        <Typography variant="body2">{value || '-'}</Typography>
                      </Grid>
                    ))}
                  </Grid>
                </Paper>

                {/* 5. Responsabilidades */}
                <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: 'primary.main', mb: 1.5 }}>Responsabilidades</Typography>
                  {detailOpen.responsabilidades_tecnicas?.length > 0 ? (
                    detailOpen.responsabilidades_tecnicas.map((r: any, i: number) => (
                      <Box key={i} sx={{ mb: i < detailOpen.responsabilidades_tecnicas.length - 1 ? 2 : 0 }}>
                        <Grid container spacing={2}>
                          {[
                            ['Tipo da Responsabilidade', r.tipo],
                            ['Razão Social da Empresa', r.razao_social],
                            ['Nome Fantasia da Empresa', r.nome_fantasia],
                            ['CNPJ', r.cnpj],
                            ['Categoria da Empresa', r.categoria],
                            ['Registro da Empresa', r.registro],
                            ['Data Início', r.data_inicio],
                            ['Data Término', r.data_termino],
                          ].map(([label, val], j) => (
                            <Grid item xs={6} sm={4} key={j}>
                              <Typography variant="caption" color="text.secondary" component="div">{label}</Typography>
                              <Typography variant="body2">{val || '-'}</Typography>
                            </Grid>
                          ))}
                        </Grid>
                      </Box>
                    ))
                  ) : (
                    <Typography variant="body2" color="text.secondary">Não há responsabilidades cadastradas.</Typography>
                  )}
                </Paper>

                {/* 6. Processos de Especialidade / Habilitação (Via SISDOC) */}
                <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
                  <Typography variant="subtitle1" sx={{ fontWeight: 'bold', color: 'primary.main', mb: 1.5 }}>Processos de Especialidade / Habilitação (Via SISDOC)</Typography>
                  {detailOpen.processos_especialidade?.length > 0 ? (
                    detailOpen.processos_especialidade.map((p: any, i: number) => (
                      <Box key={i} sx={{ mb: 2, p: 1.5, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
                        <Grid container spacing={2}>
                          {[
                            ['Número do Processo', p.numero_processo],
                            ['Assunto', p.assunto],
                            ['Classificação', p.classificacao],
                            ['Etapa', p.etapa],
                            ['Andamento', p.andamento],
                            ['Data do Andamento', p.data_andamento],
                          ].map(([label, val], j) => (
                            <Grid item xs={6} sm={4} key={j}>
                              <Typography variant="caption" color="text.secondary" component="div">{label}</Typography>
                              <Typography variant="body2">{val || '-'}</Typography>
                            </Grid>
                          ))}
                        </Grid>
                      </Box>
                    ))
                  ) : (
                    <Typography variant="body2" color="text.secondary">Não há processos cadastrados.</Typography>
                  )}
                </Paper>
              </Box>
            )}

            <Box sx={{ mt: 3, display: 'flex', justifyContent: 'center', gap: 2, flexWrap: 'wrap' }}>
              <Button variant="contained" startIcon={<PrintIcon />} onClick={handlePrint} sx={{ minWidth: 140 }}>
                Imprimir
              </Button>
              <Button variant="outlined" onClick={() => setDetailOpen(null)}>Fechar</Button>
            </Box>
          </Box>
        </Box>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
