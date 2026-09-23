import React, { useState, useEffect } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
  Card, CardContent, CardActionArea, IconButton,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';
import { formatColumnLabel } from '../utils/columnLabels';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

interface TipoEleicao { codigo: string; nome: string; }

const DESCRICOES: Record<string, string> = {
  'lista-completa': 'Lista completa de eleitores por CRO.',
  'estatisticas': 'Estatísticas eleitorais por CRO (ativos, votantes, devedores).',
  'cpf-duplicados': 'CPFs com inscrição ativa em mais de um CRO.',
  'delegados': 'Lista suplementar de delegados eleitores.',
};

const SEM_FILTROS = ['estatisticas'];

export default function EleicoesRegionais() {
  const [tipos, setTipos] = useState<TipoEleicao[]>([]);
  const [selectedTipo, setSelectedTipo] = useState<string | null>(null);
  const [filters, setFilters] = useState({ cro: '', nome: '', inscricao: '', cpf: '', email: '', celular: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [nomeConsulta, setNomeConsulta] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingTipos, setLoadingTipos] = useState(true);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    api.get('/eleicoes-regionais/tipos')
      .then(res => setTipos(res.data))
      .catch(() => {})
      .finally(() => setLoadingTipos(false));
  }, []);

  const handleCardClick = (codigo: string) => {
    setSelectedTipo(codigo);
    setFilters({ cro: '', nome: '', inscricao: '', cpf: '', email: '', celular: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ cro: '', nome: '', inscricao: '', cpf: '', email: '', celular: '' });
    setResultados([]); setTotal(0); setSearched(false); setNomeConsulta('');
  };

  const handleSearch = async () => {
    if (!selectedTipo) return;
    if (selectedTipo === 'lista-completa' && !filters.cro) {
      setSnackbar({ open: true, message: 'Selecione um CRO.', severity: 'warning' }); return;
    }
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      params.append('tipo', selectedTipo);
      if (filters.cro) params.append('cro', filters.cro);
      if (filters.nome) params.append('nome', filters.nome);
      if (filters.inscricao) params.append('inscricao', filters.inscricao);
      if (filters.cpf) params.append('cpf', filters.cpf);
      if (filters.email) params.append('email', filters.email);
      if (filters.celular) params.append('celular', filters.celular);
      const res = await api.get(`/eleicoes-regionais/buscar?${params}`);
      setResultados(res.data.resultados); setTotal(res.data.total); setNomeConsulta(res.data.nome);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ cro: '', nome: '', inscricao: '', cpf: '', email: '', celular: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];
  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || '';

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Eleições Regionais</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Consulta de dados eleitorais dos conselhos regionais.
      </Typography>
      <Divider sx={{ mb: 3 }} />

      {!selectedTipo && (
        <>
          {loadingTipos ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : (
            <Grid container spacing={3}>
              {tipos.map((tipo) => (
                <Grid item xs={12} sm={6} md={4} key={tipo.codigo}>
                  <Card sx={{
                    height: '100%', display: 'flex', flexDirection: 'column',
                    '&:hover': { boxShadow: 6, transform: 'translateY(-4px)', transition: 'all 0.3s ease-in-out' },
                  }}>
                    <CardActionArea onClick={() => handleCardClick(tipo.codigo)} sx={{ flexGrow: 1 }}>
                      <CardContent>
                        <Typography gutterBottom variant="h6" component="h2" sx={{ fontWeight: 'bold' }}>
                          {tipo.nome}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          {DESCRICOES[tipo.codigo] || 'Clique para consultar.'}
                        </Typography>
                      </CardContent>
                    </CardActionArea>
                  </Card>
                </Grid>
              ))}
            </Grid>
          )}
        </>
      )}

      {selectedTipo && (
        <>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
            <IconButton onClick={handleBack} size="small"><ArrowBackIcon /></IconButton>
            <Typography variant="h6">{selectedNome}</Typography>
          </Box>
          <Divider sx={{ mb: 2 }} />

          {!SEM_FILTROS.includes(selectedTipo) && (
            <Grid container spacing={2} sx={{ mb: 2 }}>
              <Grid item xs={6} sm={4} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>CRO</InputLabel>
                  <Select value={filters.cro} label="CRO" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
                    <MenuItem value="">Todos</MenuItem>
                    {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                  </Select>
                </FormControl>
              </Grid>
            </Grid>
          )}

          <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
            <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
            {searched && resultados.length > 0 && (
              <Button
                variant="outlined" color="success" startIcon={<DownloadIcon />} disabled={exporting}
                onClick={async () => {
                  setExporting(true);
                  try {
                    const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: formatColumnLabel(k) }));
                    await exportService.exportGenericExcel({
                      data: resultados.map(r => ({ ...r })),
                      columns: cols,
                      title: `Eleições Regionais - ${nomeConsulta || selectedNome}`,
                      filename: `eleicoes_${selectedTipo}`,
                    });
                    setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
                  } catch {
                    setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
                  } finally { setExporting(false); }
                }}
              >
                {exporting ? 'Exportando...' : 'Exportar Excel'}
              </Button>
            )}
          </Box>
          <Divider sx={{ mb: 2 }} />

          {loading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : searched && (
            <>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                <strong>{nomeConsulta}</strong> - {total} resultado(s)
              </Typography>
              {resultados.length > 0 && (
                <TableContainer sx={{ maxHeight: 'calc(100vh - 400px)' }}>
                  <Table stickyHeader size="small">
                    <TableHead>
                      <TableRow>
                        {columns.map(col => (
                          <TableCell key={col} sx={{ fontWeight: 'bold', whiteSpace: 'nowrap' }}>{formatColumnLabel(col)}</TableCell>
                        ))}
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {resultados.map((r, i) => (
                        <TableRow key={i} hover>
                          {columns.map(col => (
                            <TableCell key={col}>{r[col] != null ? String(r[col]) : '-'}</TableCell>
                          ))}
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              )}
            </>
          )}
        </>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
