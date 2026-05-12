import React, { useState } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Snackbar, Alert,
  Paper, Chip, Tabs, Tab, alpha,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';
import { formatColumnLabel } from '../utils/columnLabels';

const CATEGORIAS = ['Todos', 'CD', 'TPD', 'TSB', 'ASB', 'APD', 'EPAO', 'LB', 'ECIPO'];
const currentYear = new Date().getFullYear();
const ANOS = Array.from({ length: currentYear - 2014 }, (_, i) => currentYear - i);

const fmtNum = (v: any) => v != null ? Number(v).toLocaleString('pt-BR') : '-';
const fmtPerc = (v: any) => v != null ? `${Number(v).toFixed(2)}%` : '-';
const fmtBRL = (v: any) => v != null ? Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '-';

const PercBadge = ({ value, variant }: { value: any; variant: 'green' | 'red' }) => {
  const color = variant === 'green' ? '#2E7D32' : (Number(value) < 77 ? '#E65100' : '#C62828');
  return (
    <Chip
      label={fmtPerc(value)}
      size="small"
      sx={{
        fontWeight: 700, fontSize: '0.75rem',
        bgcolor: alpha(color, 0.08), color, border: `1px solid ${alpha(color, 0.2)}`,
      }}
    />
  );
};

export default function RelatorioAdimplencia() {
  const [tab, setTab] = useState(0);
  const [ano, setAno] = useState(currentYear);
  const [categoria, setCategoria] = useState('Todos');
  const [resultados, setResultados] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });

  const isValores = tab === 1;
  const endpoint = isValores ? '/relatorios/adimplencia-valores' : '/relatorios/adimplencia';

  // Top 5 CROs mais inadimplentes (Map: CRO -> posição 1-5)
  const top5Inadimplentes = React.useMemo(() => {
    const map = new Map<string, number>();
    resultados
      .filter(r => r.CRO !== 'BRASIL')
      .sort((a, b) => (Number(b.Perc_Inadimplente) || 0) - (Number(a.Perc_Inadimplente) || 0))
      .slice(0, 5)
      .forEach((r, i) => map.set(r.CRO, i + 1));
    return map;
  }, [resultados]);

  const handleSearch = async () => {
    setLoading(true); setSearched(true); setResultados([]);
    try {
      const params = new URLSearchParams();
      params.append('ano', String(ano));
      if (categoria !== 'Todos') params.append('categoria', categoria);
      const res = await api.get(`${endpoint}?${params}`);
      setResultados(res.data.resultados || []);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao gerar relatório', severity: 'error' });
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setAno(currentYear); setCategoria('Todos'); setResultados([]); setSearched(false);
  };

  const handleExport = async () => {
    if (resultados.length === 0) return;
    setExporting(true);
    try {
      const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: formatColumnLabel(k) }));
      const suffix = isValores ? 'Valores' : 'Quantitativo';
      await exportService.exportGenericExcel({
        data: resultados.map(r => ({ ...r })),
        columns: cols,
        title: `Relatório de Adimplência (${suffix}) - ${categoria} ${ano}`,
        filename: `adimplencia_${suffix.toLowerCase()}_${categoria}_${ano}`,
      });
      setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
    } catch {
      setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
    } finally { setExporting(false); }
  };

  return (
    <PageContainer>
      {/* Header */}
      <Box sx={{ mb: 4 }}>
        <Typography variant="h5">Relatório de Adimplência</Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
          Análise de adimplência por CRO, categoria e ano.
        </Typography>
      </Box>

      {/* Tabs */}
      <Tabs
        value={tab}
        onChange={(_, v) => { setTab(v); setResultados([]); setSearched(false); }}
        sx={{
          mb: 3,
          '& .MuiTab-root': { fontWeight: 600 },
          '& .MuiTabs-indicator': { height: 3, borderRadius: 2 },
        }}
      >
        <Tab label="Quantitativo" />
        <Tab label="Com Valores (R$)" />
      </Tabs>

      {/* Filters */}
      <Paper variant="outlined" sx={{ p: 2.5, mb: 3, bgcolor: 'rgba(141,15,18,0.015)' }}>
        <Grid container spacing={2} alignItems="center">
          <Grid item xs={6} sm={3} md={2}>
            <FormControl fullWidth size="small">
              <InputLabel>Ano</InputLabel>
              <Select value={ano} label="Ano" onChange={e => setAno(Number(e.target.value))}>
                {ANOS.map(a => <MenuItem key={a} value={a}>{a}</MenuItem>)}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={6} sm={3} md={2}>
            <FormControl fullWidth size="small">
              <InputLabel>Categoria</InputLabel>
              <Select value={categoria} label="Categoria" onChange={e => setCategoria(e.target.value)}>
                {CATEGORIAS.map(c => <MenuItem key={c} value={c}>{c}</MenuItem>)}
              </Select>
            </FormControl>
          </Grid>
          <Grid item>
            <Box sx={{ display: 'flex', gap: 1.5 }}>
              <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>
                Gerar
              </Button>
              <Button variant="outlined" color="secondary" startIcon={<ClearIcon />} onClick={handleClear}>
                Limpar
              </Button>
            </Box>
          </Grid>
        </Grid>
      </Paper>

      {/* Loading */}
      {loading && (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress size={40} />
        </Box>
      )}

      {/* Results */}
      {!loading && searched && (
        <>
          {/* Results header */}
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <Typography variant="subtitle2" color="text.secondary">
                {isValores ? 'Adimplência com Valores' : 'Adimplência Quantitativo'} - {categoria} {ano}
              </Typography>
              <Chip
                label={`${resultados.length} CRO${resultados.length !== 1 ? 's' : ''}`}
                size="small" color="primary" variant="outlined"
              />
            </Box>
            {resultados.length > 0 && (
              <Button variant="outlined" color="success" size="small" startIcon={<DownloadIcon />}
                disabled={exporting} onClick={handleExport}>
                {exporting ? 'Exportando...' : 'Excel'}
              </Button>
            )}
          </Box>

          {resultados.length > 0 ? (
            <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
              <TableContainer sx={{ maxHeight: 'calc(100vh - 380px)' }}>
                <Table stickyHeader size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell>CRO</TableCell>
                      <TableCell align="center">Ano</TableCell>
                      <TableCell align="right">Anuidades</TableCell>
                      <TableCell align="right">Pago</TableCell>
                      {isValores && <TableCell align="right">Valor Pago (est.)</TableCell>}
                      <TableCell align="center">% Adimplente</TableCell>
                      <TableCell align="center">% Inadimplente</TableCell>
                      <TableCell align="right">Não Pago</TableCell>
                      {isValores && <TableCell align="right">Valor Devido (Não Pago)</TableCell>}
                      <TableCell align="right">Pago a Menor</TableCell>
                      {isValores && <TableCell align="right">Valor Devido (Pago a Menor)</TableCell>}
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {resultados.map((r, i) => {
                      const isBrasil = r.CRO === 'BRASIL';
                      const inadRank = top5Inadimplentes.get(r.CRO);
                      return (
                        <TableRow key={i} hover sx={isBrasil ? {
                          bgcolor: 'rgba(141,15,18,0.04)',
                          '& td': { fontWeight: 700, borderTop: '2px solid rgba(141,15,18,0.15)' },
                        } : inadRank ? {
                          bgcolor: 'rgba(198,40,40,0.04)',
                        } : undefined}>
                          <TableCell sx={{ fontWeight: isBrasil ? 800 : 600, color: isBrasil ? 'primary.main' : 'inherit' }}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                              {r.CRO}
                              {inadRank && (
                                <Chip
                                  label={`#${inadRank}`}
                                  size="small"
                                  sx={{
                                    fontWeight: 800, fontSize: '0.65rem', height: 20, minWidth: 28,
                                    bgcolor: alpha('#C62828', 0.1), color: '#C62828',
                                    border: '1px solid rgba(198,40,40,0.25)',
                                  }}
                                />
                              )}
                            </Box>
                          </TableCell>
                          <TableCell align="center">{r.Ano}</TableCell>
                          <TableCell align="right">{fmtNum(r.Anuidades)}</TableCell>
                          <TableCell align="right">{fmtNum(r.Pago)}</TableCell>
                          {isValores && <TableCell align="right">{fmtBRL(r.Valor_Pago)}</TableCell>}
                          <TableCell align="center"><PercBadge value={r.Perc_Adimplente} variant="green" /></TableCell>
                          <TableCell align="center"><PercBadge value={r.Perc_Inadimplente} variant="red" /></TableCell>
                          <TableCell align="right">{fmtNum(r.Nao_Pago)}</TableCell>
                          {isValores && <TableCell align="right">{fmtBRL(r.Valor_Devido_Nao_Pago)}</TableCell>}
                          <TableCell align="right">{fmtNum(r.Pago_a_Menor)}</TableCell>
                          {isValores && <TableCell align="right">{fmtBRL(r.Valor_Devido_Pago_a_Menor)}</TableCell>}
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </TableContainer>
            </Paper>
          ) : (
            <Paper variant="outlined" sx={{ p: 6, textAlign: 'center' }}>
              <Typography color="text.secondary">Nenhum resultado encontrado para {categoria} em {ano}.</Typography>
            </Paper>
          )}
        </>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity} variant="filled" elevation={6}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
