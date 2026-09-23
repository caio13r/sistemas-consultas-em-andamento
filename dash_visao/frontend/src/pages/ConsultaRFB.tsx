import React, { useState } from 'react';
import {
  Typography, Box, TextField, Button, CircularProgress, Divider,
  Grid, Card, CardContent, Chip, Snackbar, Alert, Table, TableBody,
  TableCell, TableContainer, TableHead, TableRow, Tabs, Tab,
} from '@mui/material';
import { Search as SearchIcon, History as HistoryIcon, FileDownload as DownloadIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

export default function ConsultaRFB() {
  const [tab, setTab] = useState(0);
  const [documento, setDocumento] = useState('');
  const [resultado, setResultado] = useState<any>(null);
  const [historico, setHistorico] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  const handleConsultar = async () => {
    if (!documento.trim()) {
      setSnackbar({ open: true, message: 'Informe o CPF ou CNPJ.', severity: 'info' });
      return;
    }
    setLoading(true);
    try {
      const res = await api.get(`/consulta-rfb/consultar?documento=${encodeURIComponent(documento)}`);
      setResultado(res.data);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao consultar', severity: 'error' });
      setResultado(null);
    } finally { setLoading(false); }
  };

  const handleHistorico = async () => {
    setLoading(true);
    try {
      const res = await api.get('/consulta-rfb/historico?limit=50');
      setHistorico(res.data.resultados);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao carregar histórico', severity: 'error' });
    } finally { setLoading(false); }
  };

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Visão RFB</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Verificação de CPF/CNPJ na base do CFO com registro de auditoria.
      </Typography>

      <Tabs value={tab} onChange={(_, v) => { setTab(v); if (v === 1) handleHistorico(); }} sx={{ mb: 2 }}>
        <Tab label="Consultar" />
        <Tab label="Histórico" />
      </Tabs>
      <Divider sx={{ mb: 3 }} />

      {tab === 0 && (
        <>
          <Grid container spacing={2} sx={{ mb: 3 }}>
            <Grid item xs={12} sm={6} md={4}>
              <TextField fullWidth size="small" label="CPF ou CNPJ" value={documento}
                onChange={e => setDocumento(e.target.value)}
                onKeyDown={e => e.key === 'Enter' && handleConsultar()}
                placeholder="000.000.000-00 ou 00.000.000/0000-00" />
            </Grid>
            <Grid item xs={12} sm={6} md={3}>
              <Button variant="contained" startIcon={<SearchIcon />} onClick={handleConsultar}
                disabled={loading} sx={{ height: 40 }}>
                {loading ? <CircularProgress size={20} /> : 'Consultar'}
              </Button>
            </Grid>
          </Grid>

          {resultado && (
            <Card sx={{ maxWidth: 600 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>Resultado da Consulta</Typography>
                <Divider sx={{ mb: 2 }} />
                <Grid container spacing={2}>
                  <Grid item xs={6}>
                    <Typography variant="subtitle2" color="text.secondary">Tipo</Typography>
                    <Typography>{resultado.tipo_consulta}</Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography variant="subtitle2" color="text.secondary">Documento</Typography>
                    <Typography>{resultado.documento}</Typography>
                  </Grid>
                  <Grid item xs={12}>
                    <Typography variant="subtitle2" color="text.secondary">Existe na Base CFO?</Typography>
                    <Chip
                      label={resultado.existe_base_cfo ? 'SIM' : 'NÃO'}
                      color={resultado.existe_base_cfo ? 'success' : 'warning'}
                      size="small"
                    />
                  </Grid>
                  {resultado.existe_base_cfo && (
                    <>
                      <Grid item xs={6}>
                        <Typography variant="subtitle2" color="text.secondary">Nome CFO</Typography>
                        <Typography>{resultado.nome_cfo || '-'}</Typography>
                      </Grid>
                      <Grid item xs={6}>
                        <Typography variant="subtitle2" color="text.secondary">CRO</Typography>
                        <Typography>{resultado.cro_cfo || '-'}</Typography>
                      </Grid>
                      <Grid item xs={6}>
                        <Typography variant="subtitle2" color="text.secondary">Inscrição</Typography>
                        <Typography>{resultado.inscricao_cfo || '-'}</Typography>
                      </Grid>
                      <Grid item xs={6}>
                        <Typography variant="subtitle2" color="text.secondary">Situação</Typography>
                        <Typography>{resultado.situacao_cfo || '-'}</Typography>
                      </Grid>
                    </>
                  )}
                </Grid>
              </CardContent>
            </Card>
          )}
        </>
      )}

      {tab === 1 && (
        <>
          {historico.length > 0 && !loading && (
            <Box sx={{ mb: 2, display: 'flex', gap: 2 }}>
              <Button
                variant="outlined"
                color="success"
                startIcon={<DownloadIcon />}
                disabled={exporting}
                onClick={async () => {
                  setExporting(true);
                  try {
                    await exportService.exportGenericExcel({
                      data: historico.map(h => ({ ...h })),
                      columns: [
                        { key: 'tipo_consulta', label: 'Tipo' },
                        { key: 'documento_consultado', label: 'Documento' },
                        { key: 'existe_base_cfo', label: 'Base CFO' },
                        { key: 'nome_cfo', label: 'Nome CFO' },
                        { key: 'data_hora', label: 'Data/Hora' },
                      ],
                      title: 'Visão RFB - Historico',
                      filename: 'consulta_rfb_historico',
                    });
                    setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
                  } catch (err) {
                    setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
                  } finally { setExporting(false); }
                }}
              >
                {exporting ? 'Exportando...' : 'Exportar Excel'}
              </Button>
            </Box>
          )}
          {loading ? <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box> : (
            <TableContainer sx={{ maxHeight: 'calc(100vh - 350px)' }}>
              <Table stickyHeader size="small">
                <TableHead><TableRow>
                  <TableCell sx={{ fontWeight: 'bold' }}>Tipo</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Documento</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Base CFO</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Nome CFO</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Data/Hora</TableCell>
                </TableRow></TableHead>
                <TableBody>
                  {historico.map((h, i) => (
                    <TableRow key={i} hover>
                      <TableCell>{h.tipo_consulta}</TableCell>
                      <TableCell>{h.documento_consultado}</TableCell>
                      <TableCell>
                        <Chip label={h.existe_base_cfo === 'True' ? 'Sim' : 'Não'}
                          color={h.existe_base_cfo === 'True' ? 'success' : 'default'} size="small" />
                      </TableCell>
                      <TableCell>{h.nome_cfo || '-'}</TableCell>
                      <TableCell>{h.data_hora || '-'}</TableCell>
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
