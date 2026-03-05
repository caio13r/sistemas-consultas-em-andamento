import React, { useState } from 'react';
import {
  Typography, Box, Divider, Grid, Card, CardContent, CardActionArea,
  Button, Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  CircularProgress, Snackbar, Alert, FormControl, InputLabel, Select, MenuItem,
  Paper, Tabs, Tab,
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

const CATEGORIAS = ['CD','TPD','THD','ASB','TSB','APD','EPAO','LB','ECIPO'];
const SITUACOES = ['Ativo','Inativo','Suspenso','Cancelado'];

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
];

export default function RelatoriosDiversos() {
  const [selected, setSelected] = useState<RelatorioDef | null>(null);
  const [filterUf, setFilterUf] = useState('');
  const [filterCategoria, setFilterCategoria] = useState('');
  const [filterSituacao, setFilterSituacao] = useState('');
  const [data, setData] = useState<any[]>([]);
  const [resumoData, setResumoData] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });

  const handleGenerate = async () => {
    if (!selected) return;
    setLoading(true);
    setData([]);
    setResumoData(null);

    try {
      const params = new URLSearchParams();
      if (filterUf && selected.filters.includes('uf')) params.append('uf', filterUf);
      if (filterCategoria && selected.filters.includes('categoria')) params.append('categoria', filterCategoria);
      if (filterSituacao && selected.filters.includes('situacao')) params.append('situacao', filterSituacao);

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
        <Button variant="text" startIcon={<BackIcon />} onClick={() => { setSelected(null); setData([]); setResumoData(null); }}>
          Voltar
        </Button>
        <Typography variant="h5">{selected.nome}</Typography>
      </Box>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>{selected.descricao}</Typography>
      <Divider sx={{ mb: 3 }} />

      {/* Filtros */}
      {selected.filters.length > 0 && (
        <Grid container spacing={2} sx={{ mb: 3 }}>
          {selected.filters.includes('uf') && (
            <Grid item xs={6} sm={3}>
              <FormControl fullWidth size="small">
                <InputLabel>UF</InputLabel>
                <Select value={filterUf} label="UF" onChange={e => setFilterUf(e.target.value)}>
                  <MenuItem value="">Todos</MenuItem>
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
                  {CATEGORIAS.map(c => <MenuItem key={c} value={c}>{c}</MenuItem>)}
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
                  {SITUACOES.map(s => <MenuItem key={s} value={s}>{s}</MenuItem>)}
                </Select>
              </FormControl>
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
