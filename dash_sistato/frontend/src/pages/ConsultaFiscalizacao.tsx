import React, { useState } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert, Tabs, Tab,
} from '@mui/material';
import { Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
const CATEGORIAS = ['CD','TPD','THD','ASB','TSB','APD','EPAO'];

export default function ConsultaFiscalizacao() {
  const [tab, setTab] = useState(0);
  const [filters, setFilters] = useState({ cro: '', categoria: '', ano: '', pessoa: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  const handleSearch = async () => {
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      if (filters.cro) params.append('cro', filters.cro);
      if (filters.ano) params.append('ano', filters.ano);

      let endpoint = '/consulta-fiscalizacao/estatisticas';
      if (tab === 0) {
        if (filters.categoria) params.append('categoria', filters.categoria);
      } else if (tab === 1) {
        if (filters.pessoa) params.append('pessoa', filters.pessoa);
        endpoint = '/consulta-fiscalizacao/sem-inscricao';
      } else {
        endpoint = '/consulta-fiscalizacao/fiscais';
      }

      const res = await api.get(`${endpoint}?${params}`);
      setResultados(res.data.resultados); setTotal(res.data.total);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ cro: '', categoria: '', ano: '', pessoa: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Fiscalização</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Estatísticas de fiscalizações dos CROs.
      </Typography>

      <Tabs value={tab} onChange={(_, v) => { setTab(v); handleClear(); }} sx={{ mb: 2 }}>
        <Tab label="Estatísticas Gerais" />
        <Tab label="Sem Inscrição" />
        <Tab label="Quantidade de Fiscais" />
      </Tabs>
      <Divider sx={{ mb: 2 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>CRO</InputLabel>
            <Select value={filters.cro} label="CRO" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        {tab === 0 && (
          <Grid item xs={6} sm={4} md={3}>
            <FormControl fullWidth size="small"><InputLabel>Categoria</InputLabel>
              <Select value={filters.categoria} label="Categoria" onChange={e => setFilters(p => ({ ...p, categoria: e.target.value }))}>
                <MenuItem value="">Todas</MenuItem>
                {CATEGORIAS.map(c => <MenuItem key={c} value={c}>{c}</MenuItem>)}
              </Select>
            </FormControl>
          </Grid>
        )}
        {tab === 1 && (
          <Grid item xs={6} sm={4} md={3}>
            <FormControl fullWidth size="small"><InputLabel>Tipo Pessoa</InputLabel>
              <Select value={filters.pessoa} label="Tipo Pessoa" onChange={e => setFilters(p => ({ ...p, pessoa: e.target.value }))}>
                <MenuItem value="">Todos</MenuItem>
                <MenuItem value="PF SEM INSCRIÇÃO">PF Sem Inscrição</MenuItem>
                <MenuItem value="PJ SEM INSCRIÇÃO">PJ Sem Inscrição</MenuItem>
              </Select>
            </FormControl>
          </Grid>
        )}
        {tab !== 2 && (
          <Grid item xs={6} sm={4} md={3}>
            <TextField fullWidth size="small" label="Ano" value={filters.ano} onChange={e => setFilters(p => ({ ...p, ano: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
          </Grid>
        )}
      </Grid>

      <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
        <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
        <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
        {searched && resultados.length > 0 && (
          <Button
            variant="outlined"
            color="success"
            startIcon={<DownloadIcon />}
            disabled={exporting}
            onClick={async () => {
              setExporting(true);
              try {
                const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: k }));
                const tabNames = ['Estatisticas Gerais', 'Sem Inscricao', 'Quantidade de Fiscais'];
                const tabFiles = ['fiscalizacao_estatisticas', 'fiscalizacao_sem_inscricao', 'fiscalizacao_fiscais'];
                await exportService.exportGenericExcel({
                  data: resultados.map(r => ({ ...r })),
                  columns: cols,
                  title: `Consulta Fiscalizacao - ${tabNames[tab]}`,
                  filename: tabFiles[tab],
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
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>{total} resultado(s)</Typography>
          {resultados.length > 0 && (
            <TableContainer sx={{ maxHeight: 'calc(100vh - 400px)' }}>
              <Table stickyHeader size="small">
                <TableHead><TableRow>
                  {columns.map(col => <TableCell key={col} sx={{ fontWeight: 'bold', whiteSpace: 'nowrap' }}>{col}</TableCell>)}
                </TableRow></TableHead>
                <TableBody>
                  {resultados.map((r, i) => (
                    <TableRow key={i} hover>
                      {columns.map(col => <TableCell key={col}>{r[col] != null ? String(r[col]) : '-'}</TableCell>)}
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
