import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Snackbar, Alert,
  Paper, Chip, alpha,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
  OpenInNew as OpenIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

const getSeverity = (qty: number) => {
  if (qty === -2) return { color: '#FF6F00', label: 'Timeout' };
  if (qty < 0) return { color: '#9E9E9E', label: 'Erro' };
  if (qty === 0) return { color: '#2E7D32', label: 'OK' };
  if (qty <= 10) return { color: '#2E7D32', label: 'Baixo' };
  if (qty <= 100) return { color: '#E65100', label: 'Médio' };
  return { color: '#C62828', label: 'Alto' };
};

export default function RelatorioAuditoria() {
  const navigate = useNavigate();
  const [cro, setCro] = useState('');
  const [resultados, setResultados] = useState<any[]>([]);
  const [croLabel, setCroLabel] = useState('');
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });

  const handleSearch = async () => {
    if (!cro) {
      setSnackbar({ open: true, message: 'Selecione um CRO/UF para gerar o relatório.', severity: 'warning' });
      return;
    }
    setLoading(true); setSearched(true); setResultados([]);
    try {
      const res = await api.get(`/relatorios/auditoria-resumo?cro=${cro}`);
      setResultados(res.data.resultados || []);
      setCroLabel(res.data.cro || 'TODOS');
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao gerar relatório', severity: 'error' });
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setCro(''); setResultados([]); setSearched(false);
  };

  const handleExport = async () => {
    if (resultados.length === 0) return;
    setExporting(true);
    try {
      const cols = [
        { key: 'tipo', label: 'Tipo de Auditoria' },
        { key: 'quantidade', label: 'Inconsistências' },
      ];
      await exportService.exportGenericExcel({
        data: resultados.map(r => ({ ...r })),
        columns: cols,
        title: `Relatório de Auditoria - ${croLabel}`,
        filename: `relatorio_auditoria_${croLabel}`,
      });
      setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
    } catch {
      setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
    } finally { setExporting(false); }
  };

  const dataRows = resultados.filter(r => r.codigo !== '_total');
  const totalRow = resultados.find(r => r.codigo === '_total');

  return (
    <PageContainer>
      {/* Header */}
      <Box sx={{ mb: 4 }}>
        <Typography variant="h5">Relatório de Auditoria</Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
          Panorama consolidado de inconsistências cadastrais por tipo de auditoria.
        </Typography>
      </Box>

      {/* Filters */}
      <Paper variant="outlined" sx={{ p: 2.5, mb: 3, bgcolor: 'rgba(141,15,18,0.015)' }}>
        <Grid container spacing={2} alignItems="center">
          <Grid item xs={6} sm={3} md={2}>
            <FormControl fullWidth size="small">
              <InputLabel>CRO</InputLabel>
              <Select value={cro} label="CRO" onChange={e => setCro(e.target.value)}>
                <MenuItem value="" disabled>Selecione</MenuItem>
                {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
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
        <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', py: 8, gap: 2 }}>
          <CircularProgress size={40} />
          <Typography variant="body2" color="text.secondary">
            Analisando {resultados.length > 0 ? resultados.length : '29'} tipos de auditoria...
          </Typography>
        </Box>
      )}

      {/* Results */}
      {!loading && searched && (
        <>
          {/* Results header */}
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <Typography variant="subtitle2" color="text.secondary">
                Auditoria - CRO: {croLabel}
              </Typography>
              <Chip
                label={`${dataRows.length} tipo${dataRows.length !== 1 ? 's' : ''} analisado${dataRows.length !== 1 ? 's' : ''}`}
                size="small" color="primary" variant="outlined"
              />
              {totalRow && (
                <Chip
                  label={`${totalRow.quantidade.toLocaleString('pt-BR')} inconsistências`}
                  size="small"
                  sx={{
                    fontWeight: 700,
                    bgcolor: alpha(getSeverity(totalRow.quantidade).color, 0.08),
                    color: getSeverity(totalRow.quantidade).color,
                    border: `1px solid ${alpha(getSeverity(totalRow.quantidade).color, 0.2)}`,
                  }}
                />
              )}
            </Box>
            {dataRows.length > 0 && (
              <Button variant="outlined" color="success" size="small" startIcon={<DownloadIcon />}
                disabled={exporting} onClick={handleExport}>
                {exporting ? 'Exportando...' : 'Excel'}
              </Button>
            )}
          </Box>

          {/* Table */}
          {dataRows.length > 0 ? (
            <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
              <TableContainer sx={{ maxHeight: 'calc(100vh - 380px)' }}>
                <Table stickyHeader size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell sx={{ width: 50 }}>#</TableCell>
                      <TableCell>Tipo de Auditoria</TableCell>
                      <TableCell align="center" sx={{ width: 100 }}>Severidade</TableCell>
                      <TableCell align="right" sx={{ width: 140 }}>Inconsistências</TableCell>
                      <TableCell align="center" sx={{ width: 80 }}>Detalhe</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {dataRows.map((r, i) => {
                      const sev = getSeverity(r.quantidade);
                      return (
                        <TableRow key={r.codigo} hover>
                          <TableCell>
                            <Typography variant="caption" color="text.secondary">{i + 1}</Typography>
                          </TableCell>
                          <TableCell>
                            <Typography variant="body2" sx={{ fontWeight: 500 }}>{r.tipo}</Typography>
                          </TableCell>
                          <TableCell align="center">
                            <Chip
                              label={sev.label}
                              size="small"
                              sx={{
                                fontWeight: 700, fontSize: '0.7rem', minWidth: 60,
                                bgcolor: alpha(sev.color, 0.08), color: sev.color,
                                border: `1px solid ${alpha(sev.color, 0.2)}`,
                              }}
                            />
                          </TableCell>
                          <TableCell align="right">
                            <Typography variant="body2" sx={{ fontWeight: 700, color: sev.color }}>
                              {r.quantidade >= 0 ? r.quantidade.toLocaleString('pt-BR') : r.quantidade === -2 ? 'Timeout' : 'Erro'}
                            </Typography>
                          </TableCell>
                          <TableCell align="center">
                            {r.quantidade > 0 && (
                              <Button
                                size="small" variant="text" color="primary"
                                sx={{ minWidth: 'auto', p: 0.5 }}
                                onClick={() => navigate('/consulta-auditorias')}
                              >
                                <OpenIcon fontSize="small" />
                              </Button>
                            )}
                          </TableCell>
                        </TableRow>
                      );
                    })}

                    {/* Total row */}
                    {totalRow && (
                      <TableRow sx={{
                        bgcolor: 'rgba(141,15,18,0.04)',
                        '& td': { fontWeight: 700, borderTop: '2px solid rgba(141,15,18,0.15)' },
                      }}>
                        <TableCell />
                        <TableCell>
                          <Typography variant="body2" sx={{ fontWeight: 800, color: 'primary.main' }}>
                            TOTAL
                          </Typography>
                        </TableCell>
                        <TableCell />
                        <TableCell align="right">
                          <Typography variant="body2" sx={{ fontWeight: 800, color: 'primary.main', fontSize: '0.95rem' }}>
                            {totalRow.quantidade.toLocaleString('pt-BR')}
                          </Typography>
                        </TableCell>
                        <TableCell />
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </TableContainer>
            </Paper>
          ) : (
            <Paper variant="outlined" sx={{ p: 6, textAlign: 'center' }}>
              <Typography color="text.secondary">Nenhum resultado encontrado.</Typography>
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
