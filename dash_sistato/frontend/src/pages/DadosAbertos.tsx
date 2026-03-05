import React, { useState } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
} from '@mui/material';
import { Search as SearchIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

const TIPOS = [
  { value: 'conselheiros', label: 'Conselheiros' },
  { value: 'despesas', label: 'Despesas' },
  { value: 'receitas', label: 'Receitas' },
  { value: 'contratos', label: 'Contratos' },
  { value: 'licitacoes', label: 'Licitações' },
  { value: 'servidores', label: 'Servidores' },
  { value: 'diarias', label: 'Diárias' },
];

export default function DadosAbertos() {
  const [tipo, setTipo] = useState('conselheiros');
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });

  const handleSearch = async () => {
    setLoading(true); setSearched(true);
    try {
      const res = await api.get(`/dados-abertos/buscar?tipo=${tipo}`);
      setResultados(res.data.resultados); setTotal(res.data.total);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Dados Abertos</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Dados do Portal de Transparência do CFO (API Implanta).
      </Typography>
      <Divider sx={{ mb: 3 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>Tipo de Dados</InputLabel>
            <Select value={tipo} label="Tipo de Dados" onChange={e => setTipo(e.target.value)}>
              {TIPOS.map(t => <MenuItem key={t.value} value={t.value}>{t.label}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading} sx={{ height: 40 }}>
            Buscar
          </Button>
        </Grid>
      </Grid>
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
