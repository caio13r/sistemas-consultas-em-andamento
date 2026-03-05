import React, { useState } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Snackbar, Alert,
  Grid, FormControl, InputLabel, Select, MenuItem, Divider, Tabs, Tab,
} from '@mui/material';
import { Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

export default function ConsultaIdentidade() {
  const [tab, setTab] = useState(0);
  const [filters, setFilters] = useState({ tipo_busca: 'nome', valor: '', cro_uf: '', cpf: '', inscricao: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  const handleSearch = async () => {
    setLoading(true); setSearched(true);
    try {
      if (tab === 0) {
        // Busca via API de Identidade
        if (!filters.valor) {
          setSnackbar({ open: true, message: 'Informe o valor de busca.', severity: 'info' });
          setLoading(false); return;
        }
        const params = new URLSearchParams();
        params.append('tipo_busca', filters.tipo_busca);
        params.append('valor', filters.valor);
        const res = await api.get(`/consulta-identidade/buscar?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total);
      } else {
        // Carteirinhas despachadas
        const params = new URLSearchParams();
        if (filters.cro_uf) params.append('cro_uf', filters.cro_uf);
        if (filters.cpf) params.append('cpf', filters.cpf);
        if (filters.inscricao) params.append('inscricao', filters.inscricao);
        const res = await api.get(`/consulta-identidade/carteirinhas-despachadas?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total);
      }
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ tipo_busca: 'nome', valor: '', cro_uf: '', cpf: '', inscricao: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Identidade</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Verificação de identidade profissional e carteirinhas despachadas.
      </Typography>

      <Tabs value={tab} onChange={(_, v) => { setTab(v); handleClear(); }} sx={{ mb: 2 }}>
        <Tab label="Busca por Identidade" />
        <Tab label="Carteirinhas Despachadas" />
      </Tabs>
      <Divider sx={{ mb: 2 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        {tab === 0 && (
          <>
            <Grid item xs={6} sm={4} md={3}>
              <FormControl fullWidth size="small"><InputLabel>Tipo de Busca</InputLabel>
                <Select value={filters.tipo_busca} label="Tipo de Busca" onChange={e => setFilters(p => ({ ...p, tipo_busca: e.target.value }))}>
                  <MenuItem value="nome">Nome</MenuItem>
                  <MenuItem value="cpf">CPF</MenuItem>
                  <MenuItem value="ar">AR (Aviso de Recebimento)</MenuItem>
                </Select>
              </FormControl>
            </Grid>
            <Grid item xs={12} sm={6} md={4}>
              <TextField fullWidth size="small" label="Valor de busca" value={filters.valor}
                onChange={e => setFilters(p => ({ ...p, valor: e.target.value }))}
                onKeyDown={e => e.key === 'Enter' && handleSearch()} />
            </Grid>
          </>
        )}
        {tab === 1 && (
          <>
            <Grid item xs={6} sm={4} md={3}>
              <FormControl fullWidth size="small"><InputLabel>CRO (UF)</InputLabel>
                <Select value={filters.cro_uf} label="CRO (UF)" onChange={e => setFilters(p => ({ ...p, cro_uf: e.target.value }))}>
                  <MenuItem value="">Todos</MenuItem>
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
            <Grid item xs={6} sm={4} md={3}>
              <TextField fullWidth size="small" label="CPF" value={filters.cpf}
                onChange={e => setFilters(p => ({ ...p, cpf: e.target.value }))}
                onKeyDown={e => e.key === 'Enter' && handleSearch()} />
            </Grid>
            <Grid item xs={6} sm={4} md={3}>
              <TextField fullWidth size="small" label="Inscrição" value={filters.inscricao}
                onChange={e => setFilters(p => ({ ...p, inscricao: e.target.value }))}
                onKeyDown={e => e.key === 'Enter' && handleSearch()} />
            </Grid>
          </>
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
                await exportService.exportGenericExcel({
                  data: resultados.map(r => ({ ...r })),
                  columns: cols,
                  title: tab === 0 ? 'Consulta Identidade - Busca' : 'Consulta Identidade - Carteirinhas Despachadas',
                  filename: tab === 0 ? 'consulta_identidade' : 'carteirinhas_despachadas',
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
