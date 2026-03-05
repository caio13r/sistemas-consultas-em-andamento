import React, { useState } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
} from '@mui/material';
import { Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

export default function EleicoesRegionais() {
  const [filters, setFilters] = useState({ cro: '', nome: '', inscricao: '', cpf: '', email: '', celular: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  const handleSearch = async () => {
    const hasFilter = Object.values(filters).some(v => v.trim() !== '');
    if (!hasFilter) {
      setSnackbar({ open: true, message: 'Preencha pelo menos um filtro.', severity: 'info' });
      return;
    }
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      Object.entries(filters).forEach(([k, v]) => { if (v.trim()) params.append(k, v.trim()); });
      const res = await api.get(`/eleicoes-regionais/buscar?${params}`);
      setResultados(res.data.resultados); setTotal(res.data.total);
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

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Eleições Regionais</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Consulta de dados eleitorais dos conselhos regionais.</Typography>
      <Divider sx={{ mb: 3 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>CRO</InputLabel>
            <Select value={filters.cro} label="CRO" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Nome" value={filters.nome}
            onChange={e => setFilters(p => ({ ...p, nome: e.target.value }))}
            onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Inscrição" value={filters.inscricao}
            onChange={e => setFilters(p => ({ ...p, inscricao: e.target.value }))}
            onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="CPF" value={filters.cpf}
            onChange={e => setFilters(p => ({ ...p, cpf: e.target.value }))}
            onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
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
                await exportService.exportGenericExcel({
                  data: resultados.map(r => ({ ...r })),
                  columns: cols,
                  title: 'Eleicoes Regionais',
                  filename: 'eleicoes_regionais',
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
