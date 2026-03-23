import React, { useState } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
  TextField,
} from '@mui/material';
import { Search as SearchIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

const TIPOS = [
  { value: 'conselheiros',         label: 'Conselheiros',           needsDate: false, needsExercicio: false },
  { value: 'atas_colegiados',      label: 'Atas de Colegiados',     needsDate: true,  needsExercicio: false },
  { value: 'balanco_financeiro',   label: 'Balanço Financeiro',     needsDate: true,  needsExercicio: false },
  { value: 'balancete',            label: 'Balancete',              needsDate: true,  needsExercicio: false },
  { value: 'balanco_orcamentario', label: 'Balanço Orçamentário',   needsDate: true,  needsExercicio: false },
  { value: 'balanco_patrimonial',  label: 'Balanço Patrimonial',    needsDate: true,  needsExercicio: false },
  { value: 'contratos',            label: 'Contratos',              needsDate: true,  needsExercicio: false },
  { value: 'contratos_aditivos',   label: 'Contratos / Aditivos',   needsDate: true,  needsExercicio: false },
  { value: 'convenios',            label: 'Convênios',              needsDate: true,  needsExercicio: false },
  { value: 'licitacoes',           label: 'Licitações',             needsDate: true,  needsExercicio: false },
  { value: 'aquisicoes',           label: 'Aquisições',             needsDate: true,  needsExercicio: false },
  { value: 'passagens_aereas',     label: 'Passagens Aéreas',       needsDate: true,  needsExercicio: false },
  { value: 'diarias',              label: 'Diárias e Deslocamentos', needsDate: true,  needsExercicio: false },
  { value: 'execucao_financeira',  label: 'Execução Financeira',    needsDate: true,  needsExercicio: false },
  { value: 'plano_contas',         label: 'Plano de Contas',        needsDate: false, needsExercicio: true },
  { value: 'estatistica_acesso',   label: 'Estatística de Acesso',  needsDate: true,  needsExercicio: false },
];

export default function DadosAbertos() {
  const [tipo, setTipo] = useState('conselheiros');
  const [dataInicio, setDataInicio] = useState('');
  const [dataTermino, setDataTermino] = useState('');
  const [exercicio, setExercicio] = useState('');
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });

  const tipoConfig = TIPOS.find(t => t.value === tipo);
  const needsDate = tipoConfig?.needsDate ?? false;
  const needsExercicio = tipoConfig?.needsExercicio ?? false;

  const handleSearch = async () => {
    // Validar campos obrigatórios
    if (needsDate && (!dataInicio || !dataTermino)) {
      setSnackbar({ open: true, message: 'Informe a data inicial e final (MM/AAAA)', severity: 'warning' });
      return;
    }
    if (needsExercicio && !exercicio) {
      setSnackbar({ open: true, message: 'Informe o exercício/ano (AAAA)', severity: 'warning' });
      return;
    }

    setLoading(true); setSearched(true);
    try {
      const params: Record<string, string> = { tipo };
      if (needsDate) {
        params.data_inicio = dataInicio;
        params.data_termino = dataTermino;
      }
      if (needsExercicio) {
        params.exercicio = exercicio;
      }
      const res = await api.get('/dados-abertos/buscar', { params });
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

      <Grid container spacing={2} sx={{ mb: 2 }} alignItems="center">
        <Grid item xs={12} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>Tipo de Dados</InputLabel>
            <Select value={tipo} label="Tipo de Dados" onChange={e => { setTipo(e.target.value); setResultados([]); setSearched(false); }}>
              {TIPOS.map(t => <MenuItem key={t.value} value={t.value}>{t.label}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>

        {needsDate && (
          <>
            <Grid item xs={6} sm={3} md={2}>
              <TextField
                fullWidth size="small" label="Início (MM/AAAA)"
                placeholder="01/2024"
                value={dataInicio} onChange={e => setDataInicio(e.target.value)}
              />
            </Grid>
            <Grid item xs={6} sm={3} md={2}>
              <TextField
                fullWidth size="small" label="Término (MM/AAAA)"
                placeholder="12/2024"
                value={dataTermino} onChange={e => setDataTermino(e.target.value)}
              />
            </Grid>
          </>
        )}

        {needsExercicio && (
          <Grid item xs={6} sm={3} md={2}>
            <TextField
              fullWidth size="small" label="Exercício (AAAA)"
              placeholder="2024"
              value={exercicio} onChange={e => setExercicio(e.target.value)}
            />
          </Grid>
        )}

        <Grid item xs={6} sm={2} md={2}>
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
