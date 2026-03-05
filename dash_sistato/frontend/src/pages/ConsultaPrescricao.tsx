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

export default function ConsultaPrescricao() {
  const [tab, setTab] = useState(0);
  const [filters, setFilters] = useState({
    data_inicial: '', data_final: '', cd_nome: '', uf: '', tipo: '',
    paciente_nome: '', paciente_cpf: '',
  });
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

      if (tab === 0) {
        // Por Tipo (período obrigatório)
        if (!filters.data_inicial || !filters.data_final) {
          setSnackbar({ open: true, message: 'Informe data inicial e final.', severity: 'info' });
          setLoading(false); return;
        }
        params.append('data_inicial', filters.data_inicial);
        params.append('data_final', filters.data_final);
        const res = await api.get(`/consulta-prescricao/por-tipo?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total);
      } else if (tab === 1) {
        // Por Profissional
        if (!filters.cd_nome) {
          setSnackbar({ open: true, message: 'Informe o nome do profissional.', severity: 'info' });
          setLoading(false); return;
        }
        params.append('cd_nome', filters.cd_nome);
        const res = await api.get(`/consulta-prescricao/por-profissional?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total);
      } else if (tab === 2) {
        // Por Período
        if (!filters.data_inicial || !filters.data_final) {
          setSnackbar({ open: true, message: 'Informe data inicial e final.', severity: 'info' });
          setLoading(false); return;
        }
        params.append('data_inicial', filters.data_inicial);
        params.append('data_final', filters.data_final);
        if (filters.uf) params.append('uf', filters.uf);
        if (filters.tipo) params.append('tipo', filters.tipo);
        const res = await api.get(`/consulta-prescricao/por-periodo?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total);
      } else {
        // Por Paciente
        if (!filters.paciente_nome && !filters.paciente_cpf) {
          setSnackbar({ open: true, message: 'Informe nome ou CPF do paciente.', severity: 'info' });
          setLoading(false); return;
        }
        if (filters.paciente_nome) params.append('paciente_nome', filters.paciente_nome);
        if (filters.paciente_cpf) params.append('paciente_cpf', filters.paciente_cpf);
        const res = await api.get(`/consulta-prescricao/por-paciente?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total);
      }
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ data_inicial: '', data_final: '', cd_nome: '', uf: '', tipo: '', paciente_nome: '', paciente_cpf: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Prescrição</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Consulta de prescrições odontológicas (base db_prescricao).
      </Typography>

      <Tabs value={tab} onChange={(_, v) => { setTab(v); handleClear(); }} sx={{ mb: 2 }} variant="scrollable">
        <Tab label="Por Tipo" />
        <Tab label="Por Profissional" />
        <Tab label="Por Período" />
        <Tab label="Por Paciente" />
      </Tabs>
      <Divider sx={{ mb: 2 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        {(tab === 0 || tab === 2) && (
          <>
            <Grid item xs={6} sm={4} md={3}>
              <TextField fullWidth size="small" label="Data Inicial" type="date" InputLabelProps={{ shrink: true }}
                value={filters.data_inicial} onChange={e => setFilters(p => ({ ...p, data_inicial: e.target.value }))} />
            </Grid>
            <Grid item xs={6} sm={4} md={3}>
              <TextField fullWidth size="small" label="Data Final" type="date" InputLabelProps={{ shrink: true }}
                value={filters.data_final} onChange={e => setFilters(p => ({ ...p, data_final: e.target.value }))} />
            </Grid>
          </>
        )}
        {tab === 1 && (
          <Grid item xs={12} sm={6} md={4}>
            <TextField fullWidth size="small" label="Nome do Profissional (CD)"
              value={filters.cd_nome} onChange={e => setFilters(p => ({ ...p, cd_nome: e.target.value }))}
              onKeyDown={e => e.key === 'Enter' && handleSearch()} />
          </Grid>
        )}
        {tab === 2 && (
          <>
            <Grid item xs={6} sm={4} md={3}>
              <FormControl fullWidth size="small"><InputLabel>UF</InputLabel>
                <Select value={filters.uf} label="UF" onChange={e => setFilters(p => ({ ...p, uf: e.target.value }))}>
                  <MenuItem value="">Todos</MenuItem>
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
            <Grid item xs={6} sm={4} md={3}>
              <TextField fullWidth size="small" label="Tipo" value={filters.tipo}
                onChange={e => setFilters(p => ({ ...p, tipo: e.target.value }))} />
            </Grid>
          </>
        )}
        {tab === 3 && (
          <>
            <Grid item xs={6} sm={4} md={4}>
              <TextField fullWidth size="small" label="Nome do Paciente"
                value={filters.paciente_nome} onChange={e => setFilters(p => ({ ...p, paciente_nome: e.target.value }))}
                onKeyDown={e => e.key === 'Enter' && handleSearch()} />
            </Grid>
            <Grid item xs={6} sm={4} md={3}>
              <TextField fullWidth size="small" label="CPF do Paciente"
                value={filters.paciente_cpf} onChange={e => setFilters(p => ({ ...p, paciente_cpf: e.target.value }))}
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
                const tabNames = ['Por Tipo', 'Por Profissional', 'Por Periodo', 'Por Paciente'];
                const tabFiles = ['prescricao_por_tipo', 'prescricao_por_profissional', 'prescricao_por_periodo', 'prescricao_por_paciente'];
                await exportService.exportGenericExcel({
                  data: resultados.map(r => ({ ...r })),
                  columns: cols,
                  title: `Consulta Prescricao - ${tabNames[tab]}`,
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
