import React, { useState, useEffect } from 'react';
import {
  Typography, Box, Divider, Grid, Card, CardContent, CardActionArea,
  Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  CircularProgress, Snackbar, Alert, FormControl, InputLabel, Select, MenuItem,
  Paper, Tabs, Tab, TextField,
} from '@mui/material';
import {
  Assessment as AssessmentIcon,
  FileDownload as DownloadIcon,
  ArrowBack as BackIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = [
  'AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT',
  'PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO',
];

const CATEGORIAS_FALLBACK = ['CD','TPD','THD','ASB','TSB','APD','EPAO','LB','ECIPO'];
const SITUACOES_FALLBACK = ['Ativo','Inativo','Suspenso','Cancelado'];

interface RelatorioDef {
  id: string;
  nome: string;
  descricao: string;
  endpoint: string;
  filters: string[];
}

const relatorios: RelatorioDef[] = [
  {
    id: 'prof_uf', nome: 'Profissionais por UF',
    descricao: 'Quantidade de profissionais agrupados por estado, categoria e situacao',
    endpoint: '/relatorios/profissionais-por-uf', filters: ['situacao', 'categoria'],
  },
  {
    id: 'prof_cat', nome: 'Profissionais por Categoria',
    descricao: 'Quantidade de profissionais agrupados por categoria e situacao',
    endpoint: '/relatorios/profissionais-por-categoria', filters: ['uf', 'situacao'],
  },
  {
    id: 'emp_uf', nome: 'Empresas por UF',
    descricao: 'Quantidade de empresas agrupadas por estado, categoria e situacao',
    endpoint: '/relatorios/empresas-por-uf', filters: ['situacao'],
  },
  {
    id: 'prof_form', nome: 'Profissional x Formacao',
    descricao: 'Relatorio de profissionais por instituicao de ensino e curso',
    endpoint: '/relatorios/profissional-x-formacao', filters: ['uf', 'categoria'],
  },
  {
    id: 'resumo', nome: 'Resumo Nacional',
    descricao: 'Visao geral com totais de profissionais e empresas por situacao',
    endpoint: '/relatorios/resumo-nacional', filters: [],
  },
  // --- Relatórios Financeiros ---
  {
    id: 'arrecadacao_bb', nome: 'Arrecadacao Banco do Brasil',
    descricao: 'Relatorio de arrecadacao do Banco do Brasil por convenio e CRO',
    endpoint: '/relatorios/arrecadacao-bb', filters: ['periodo'],
  },
  {
    id: 'tarifas_bb', nome: 'Tarifas Banco do Brasil',
    descricao: 'Relatorio de tarifas bancarias (registro, liquidacao, baixa) por convenio',
    endpoint: '/relatorios/tarifas-bb', filters: ['periodo'],
  },
  {
    id: 'pagamentos_diversos', nome: 'Pagamentos Diversos',
    descricao: 'Relatorio de pagamentos diversos por forma de pagamento e CRO',
    endpoint: '/relatorios/pagamentos-diversos', filters: ['periodo'],
  },
  {
    id: 'arrecadacao_selfpay', nome: 'Arrecadacao SelfPay/BkBank',
    descricao: 'Arrecadacao e tarifas de cartao SelfPay com split federal',
    endpoint: '/relatorios/arrecadacao-selfpay', filters: ['periodo'],
  },
  {
    id: 'processos_esp', nome: 'Processos de Especialidade',
    descricao: 'Processos de especialidade e habilitacao por CRO e periodo',
    endpoint: '/relatorios/processos-especialidade', filters: ['periodo', 'uf', 'etapa'],
  },
  {
    id: 'delegado_eleitor', nome: 'Delegado Eleitor',
    descricao: 'Lista de delegados eleitores com email e telefone por CRO',
    endpoint: '/relatorios/delegado-eleitor', filters: ['uf_obrigatorio'],
  },
];

export default function RelatoriosDiversos() {
  const [selected, setSelected] = useState<RelatorioDef | null>(null);
  const [filterUf, setFilterUf] = useState('');
  const [filterCategoria, setFilterCategoria] = useState('');
  const [filterSituacao, setFilterSituacao] = useState('');
  const [filterInicio, setFilterInicio] = useState('');
  const [filterTermino, setFilterTermino] = useState('');
  const [filterEtapa, setFilterEtapa] = useState('');
  const [data, setData] = useState<any[]>([]);
  const [resumoData, setResumoData] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });
  const [categorias, setCategorias] = useState<string[]>(CATEGORIAS_FALLBACK);
  const [situacoes, setSituacoes] = useState<string[]>(SITUACOES_FALLBACK);

  useEffect(() => {
    api.get('/relatorios/metadata/profissionais')
      .then(res => {
        if (res.data.categorias?.length) setCategorias(res.data.categorias);
        if (res.data.situacoes?.length) setSituacoes(res.data.situacoes);
      })
      .catch(() => { /* mantém fallback */ });
  }, []);

  const handleGenerate = async () => {
    if (!selected) return;
    const f = selected.filters;
    if (f.includes('periodo') && (!filterInicio || !filterTermino)) {
      setSnackbar({ open: true, message: 'Informe o periodo (inicio e termino).', severity: 'warning' }); return;
    }
    if (f.includes('uf_obrigatorio') && !filterUf) {
      setSnackbar({ open: true, message: 'Selecione uma UF.', severity: 'warning' }); return;
    }
    setLoading(true);
    setData([]);
    setResumoData(null);

    try {
      const params = new URLSearchParams();
      if (filterUf && (f.includes('uf') || f.includes('uf_obrigatorio'))) params.append('uf', filterUf);
      if (filterCategoria && f.includes('categoria')) params.append('categoria', filterCategoria);
      if (filterSituacao && f.includes('situacao')) params.append('situacao', filterSituacao);
      if (filterInicio && f.includes('periodo')) params.append('inicio', filterInicio);
      if (filterTermino && f.includes('periodo')) params.append('termino', filterTermino);
      if (filterEtapa && f.includes('etapa')) params.append('etapa', filterEtapa);
      if (filterUf && f.includes('periodo') && selected.id === 'processos_esp') params.append('estado', filterUf);

      const res = await api.get(`${selected.endpoint}?${params}`);

      if (selected.id === 'resumo') {
        setResumoData(res.data);
      } else {
        setData(Array.isArray(res.data) ? res.data : []);
      }
    } catch (err: any) {
      setSnackbar({ open: true, message: err.response?.data?.detail || 'Erro ao gerar relatorio', severity: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleExport = async () => {
    if (!selected || data.length === 0) return;
    setExporting(true);
    try {
      await exportService.exportGenericExcel({
        data,
        title: selected.nome,
        filename: selected.id,
      });
      setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
    } catch {
      setSnackbar({ open: true, message: 'Erro ao exportar', severity: 'error' });
    } finally {
      setExporting(false);
    }
  };

  const handleExportPdf = async () => {
    if (!selected || data.length === 0) return;
    setExporting(true);
    try {
      await exportService.exportGenericPdf({
        data,
        title: selected.nome,
        filename: selected.id,
      });
      setSnackbar({ open: true, message: 'PDF exportado com sucesso!', severity: 'success' });
    } catch {
      setSnackbar({ open: true, message: 'Erro ao exportar PDF', severity: 'error' });
    } finally {
      setExporting(false);
    }
  };

  // Listagem de relatorios
  if (!selected) {
    return (
      <PageContainer>
        <Typography variant="h5" gutterBottom>Relatorios Diversos</Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Selecione um relatorio para gerar. Todos os relatorios podem ser exportados em Excel e PDF.
        </Typography>
        <Divider sx={{ mb: 3 }} />
        <Grid container spacing={2}>
          {relatorios.map((r) => (
            <Grid item xs={12} sm={6} md={4} key={r.id}>
              <Card sx={{ '&:hover': { boxShadow: 6, transform: 'translateY(-2px)', transition: 'all 0.2s' } }}>
                <CardActionArea onClick={() => setSelected(r)} sx={{ p: 2 }}>
                  <CardContent>
                    <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
                      <AssessmentIcon sx={{ mr: 1, color: 'primary.main' }} />
                      <Typography variant="subtitle1" sx={{ fontWeight: 'bold' }}>{r.nome}</Typography>
                    </Box>
                    <Typography variant="body2" color="text.secondary">{r.descricao}</Typography>
                  </CardContent>
                </CardActionArea>
              </Card>
            </Grid>
          ))}
        </Grid>
      </PageContainer>
    );
  }

  // Relatorio selecionado
  return (
    <PageContainer>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 2, gap: 2 }}>
        <Button variant="text" startIcon={<BackIcon />} onClick={() => { setSelected(null); setData([]); setResumoData(null); setFilterUf(''); setFilterCategoria(''); setFilterSituacao(''); setFilterInicio(''); setFilterTermino(''); setFilterEtapa(''); }}>
          Voltar
        </Button>
        <Typography variant="h5">{selected.nome}</Typography>
      </Box>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>{selected.descricao}</Typography>
      <Divider sx={{ mb: 3 }} />

      {/* Filtros */}
      {selected.filters.length > 0 && (
        <Grid container spacing={2} sx={{ mb: 3 }}>
          {(selected.filters.includes('uf') || selected.filters.includes('uf_obrigatorio')) && (
            <Grid item xs={6} sm={3}>
              <FormControl fullWidth size="small">
                <InputLabel>UF</InputLabel>
                <Select value={filterUf} label="UF" onChange={e => setFilterUf(e.target.value)}>
                  {!selected.filters.includes('uf_obrigatorio') && <MenuItem value="">Todos</MenuItem>}
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
          )}
          {selected.filters.includes('categoria') && (
            <Grid item xs={6} sm={3}>
              <FormControl fullWidth size="small">
                <InputLabel>Categoria</InputLabel>
                <Select value={filterCategoria} label="Categoria" onChange={e => setFilterCategoria(e.target.value)}>
                  <MenuItem value="">Todas</MenuItem>
                  {categorias.map(c => <MenuItem key={c} value={c}>{c}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
          )}
          {selected.filters.includes('situacao') && (
            <Grid item xs={6} sm={3}>
              <FormControl fullWidth size="small">
                <InputLabel>Situacao</InputLabel>
                <Select value={filterSituacao} label="Situacao" onChange={e => setFilterSituacao(e.target.value)}>
                  <MenuItem value="">Todas</MenuItem>
                  {situacoes.map(s => <MenuItem key={s} value={s}>{s}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
          )}
          {selected.filters.includes('periodo') && (
            <>
              <Grid item xs={6} sm={3}>
                <TextField fullWidth size="small" label="Data Inicio" type="date" value={filterInicio}
                  onChange={e => setFilterInicio(e.target.value)} InputLabelProps={{ shrink: true }} />
              </Grid>
              <Grid item xs={6} sm={3}>
                <TextField fullWidth size="small" label="Data Termino" type="date" value={filterTermino}
                  onChange={e => setFilterTermino(e.target.value)} InputLabelProps={{ shrink: true }} />
              </Grid>
            </>
          )}
          {selected.filters.includes('etapa') && (
            <Grid item xs={6} sm={3}>
              <TextField fullWidth size="small" label="Etapa do Processo" value={filterEtapa}
                onChange={e => setFilterEtapa(e.target.value)} />
            </Grid>
          )}
        </Grid>
      )}

      {/* Botoes */}
      <Box sx={{ display: 'flex', gap: 2, mb: 3, flexWrap: 'wrap' }}>
        <Button variant="contained" onClick={handleGenerate} disabled={loading}>
          {loading ? 'Gerando...' : 'Gerar Relatorio'}
        </Button>
        {data.length > 0 && (
          <>
            <Button variant="outlined" color="success" startIcon={<DownloadIcon />} onClick={handleExport} disabled={exporting}>
              Exportar Excel
            </Button>
            <Button variant="outlined" color="error" startIcon={<DownloadIcon />} onClick={handleExportPdf} disabled={exporting}>
              Exportar PDF
            </Button>
          </>
        )}
      </Box>

      {/* Loading */}
      {loading && (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
          <CircularProgress />
        </Box>
      )}

      {/* Resumo Nacional (caso especial) */}
      {selected.id === 'resumo' && resumoData && !loading && (
        <Grid container spacing={3}>
          <Grid item xs={12} sm={6}>
            <Paper sx={{ p: 3 }}>
              <Typography variant="h6" gutterBottom>
                Profissionais - Total: {resumoData.total_pf?.toLocaleString('pt-BR')}
              </Typography>
              <Divider sx={{ mb: 2 }} />
              {(resumoData.pf_por_situacao || []).map((item: any, idx: number) => (
                <Box key={idx} sx={{ display: 'flex', justifyContent: 'space-between', py: 1, borderBottom: '1px solid #f0f0f0' }}>
                  <Typography variant="body2">{item.Situacao || item.situacao || 'N/A'}</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {(item.total)?.toLocaleString('pt-BR')}
                  </Typography>
                </Box>
              ))}
            </Paper>
          </Grid>
          <Grid item xs={12} sm={6}>
            <Paper sx={{ p: 3 }}>
              <Typography variant="h6" gutterBottom>
                Empresas - Total: {resumoData.total_pj?.toLocaleString('pt-BR')}
              </Typography>
              <Divider sx={{ mb: 2 }} />
              {(resumoData.pj_por_situacao || []).map((item: any, idx: number) => (
                <Box key={idx} sx={{ display: 'flex', justifyContent: 'space-between', py: 1, borderBottom: '1px solid #f0f0f0' }}>
                  <Typography variant="body2">{item.Situacao || item.situacao || 'N/A'}</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {(item.total)?.toLocaleString('pt-BR')}
                  </Typography>
                </Box>
              ))}
            </Paper>
          </Grid>
        </Grid>
      )}

      {/* Tabela de dados */}
      {data.length > 0 && !loading && (
        <>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {data.length} registro(s) encontrado(s)
          </Typography>
          <TableContainer sx={{ maxHeight: 'calc(100vh - 400px)' }}>
            <Table stickyHeader size="small">
              <TableHead>
                <TableRow>
                  {Object.keys(data[0]).map(key => (
                    <TableCell key={key} sx={{ fontWeight: 'bold', textTransform: 'capitalize' }}>
                      {key.replace(/_/g, ' ')}
                    </TableCell>
                  ))}
                </TableRow>
              </TableHead>
              <TableBody>
                {data.map((row, idx) => (
                  <TableRow key={idx} hover>
                    {Object.values(row).map((val: any, cidx) => (
                      <TableCell key={cidx}>
                        {typeof val === 'number' ? val.toLocaleString('pt-BR') : (val ?? '-')}
                      </TableCell>
                    ))}
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>
        </>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
