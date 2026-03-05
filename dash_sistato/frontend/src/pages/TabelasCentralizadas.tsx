import React, { useState, useEffect } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
} from '@mui/material';
import { Search as SearchIcon, Clear as ClearIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

export default function TabelasCentralizadas() {
  const [tipos, setTipos] = useState<any[]>([]);
  const [filters, setFilters] = useState({ tipo: 'ies', curso: '', nome: '', cro: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });

  useEffect(() => {
    api.get('/tabelas-centralizadas/tipos').then(res => setTipos(res.data)).catch(() => {});
  }, []);

  const handleSearch = async () => {
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      params.append('tipo', filters.tipo);
      if (filters.curso) params.append('curso', filters.curso);
      if (filters.nome) params.append('nome', filters.nome);
      if (filters.cro) params.append('cro', filters.cro);
      const res = await api.get(`/tabelas-centralizadas/buscar?${params}`);
      setResultados(res.data.resultados); setTotal(res.data.total);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => { setFilters({ tipo: 'ies', curso: '', nome: '', cro: '' }); setResultados([]); setTotal(0); setSearched(false); };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Tabelas Centralizadas</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Consulta de tabelas centralizadas (IES, Cursos, etc.).
      </Typography>
      <Divider sx={{ mb: 3 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>Tipo</InputLabel>
            <Select value={filters.tipo} label="Tipo" onChange={e => setFilters(p => ({ ...p, tipo: e.target.value }))}>
              {tipos.map(t => <MenuItem key={t.codigo} value={t.codigo}>{t.nome}</MenuItem>)}
              {tipos.length === 0 && <MenuItem value="ies">IES</MenuItem>}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>Regional (CRO)</InputLabel>
            <Select value={filters.cro} label="Regional (CRO)" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Curso" value={filters.curso}
            onChange={e => setFilters(p => ({ ...p, curso: e.target.value }))}
            onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Nome da Instituição" value={filters.nome}
            onChange={e => setFilters(p => ({ ...p, nome: e.target.value }))}
            onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
      </Grid>

      <Box sx={{ mb: 3, display: 'flex', gap: 2 }}>
        <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
        <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
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
