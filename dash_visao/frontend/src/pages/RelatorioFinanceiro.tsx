import React, { useState } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Snackbar, Alert,
  Paper, Chip, Card, CardContent, CardActionArea, IconButton,
  TextField, alpha,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
  ArrowBack as ArrowBackIcon, AccountBalance as BankIcon,
  Receipt as ReceiptIcon, CreditCard as CardIcon,
  AttachMoney as MoneyIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';
import { formatColumnLabel } from '../utils/columnLabels';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

interface RelatorioConfig {
  id: string;
  nome: string;
  descricao: string;
  endpoint: string;
  icon: React.ReactNode;
  color: string;
  filtros: string[];
}

const RELATORIOS: RelatorioConfig[] = [
  {
    id: 'arrecadacao_bb', nome: 'Arrecadação Banco do Brasil',
    descricao: 'Arrecadação por convênio e CRO com totais de tarifas e valores.',
    endpoint: '/relatorios/arrecadacao-bb', icon: <BankIcon />, color: '#1565C0',
    filtros: ['periodo'],
  },
  {
    id: 'tarifas_bb', nome: 'Tarifas Banco do Brasil',
    descricao: 'Tarifas bancárias (registro, liquidação, baixa) por convênio e CRO.',
    endpoint: '/relatorios/tarifas-bb', icon: <ReceiptIcon />, color: '#AD1457',
    filtros: ['periodo'],
  },
  {
    id: 'pagamentos_diversos', nome: 'Pagamentos Diversos',
    descricao: 'Pagamentos por forma de pagamento (PIX, boleto, cartão) e CRO.',
    endpoint: '/relatorios/pagamentos-diversos', icon: <MoneyIcon />, color: '#2E7D32',
    filtros: ['periodo'],
  },
  {
    id: 'arrecadacao_selfpay', nome: 'Arrecadação SelfPay / BkBank',
    descricao: 'Arrecadação com split federal, tarifa de cartão e valor líquido por CRO.',
    endpoint: '/relatorios/arrecadacao-selfpay', icon: <CardIcon />, color: '#E65100',
    filtros: ['periodo'],
  },
];

const fmtBRL = (v: any) => {
  if (v == null) return '-';
  const n = Number(v);
  return isNaN(n) ? String(v) : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const fmtNum = (v: any) => {
  if (v == null) return '-';
  const n = Number(v);
  return isNaN(n) ? String(v) : n.toLocaleString('pt-BR');
};

// Colunas que devem ser formatadas como moeda
const CURRENCY_COLS = ['Total_Tarifa_Liquidacao', 'Total_Arrecadado', 'Total_Pago',
  'Valor_Bruto', 'Valor_CRO', 'Tarifa_Cartao', 'Split_Federal'];

const formatCell = (col: string, val: any) => {
  if (val == null) return '-';
  if (CURRENCY_COLS.includes(col)) return fmtBRL(val);
  if (typeof val === 'number' && !col.toLowerCase().includes('ano') && !col.toLowerCase().includes('dia')) return fmtNum(val);
  return String(val);
};

export default function RelatorioFinanceiro() {
  const [selected, setSelected] = useState<RelatorioConfig | null>(null);
  const [filters, setFilters] = useState({ inicio: '', termino: '', uf: '', etapa: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });

  const handleSelect = (rel: RelatorioConfig) => {
    setSelected(rel);
    setFilters({ inicio: '', termino: '', uf: '', etapa: '' });
    setResultados([]); setSearched(false);
  };

  const handleBack = () => {
    setSelected(null);
    setFilters({ inicio: '', termino: '', uf: '', etapa: '' });
    setResultados([]); setSearched(false);
  };

  const handleSearch = async () => {
    if (!selected) return;
    const f = selected.filtros;
    if (f.includes('periodo') && (!filters.inicio || !filters.termino)) {
      setSnackbar({ open: true, message: 'Informe o período (início e término).', severity: 'warning' }); return;
    }
    if (f.includes('uf_obrigatorio') && !filters.uf) {
      setSnackbar({ open: true, message: 'Selecione uma UF.', severity: 'warning' }); return;
    }

    setLoading(true); setSearched(true); setResultados([]);
    try {
      const params = new URLSearchParams();
      if (filters.inicio && f.includes('periodo')) params.append('inicio', filters.inicio);
      if (filters.termino && f.includes('periodo')) params.append('termino', filters.termino);
      if (filters.uf && (f.includes('estado') || f.includes('uf_obrigatorio'))) {
        params.append(f.includes('estado') ? 'estado' : 'uf', filters.uf);
      }
      if (filters.etapa && f.includes('etapa')) params.append('etapa', filters.etapa);

      const res = await api.get(`${selected.endpoint}?${params}`);
      const data = Array.isArray(res.data) ? res.data : (res.data.resultados || []);
      setResultados(data);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao gerar relatório', severity: 'error' });
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ inicio: '', termino: '', uf: '', etapa: '' });
    setResultados([]); setSearched(false);
  };

  const handleExport = async () => {
    if (!selected || resultados.length === 0) return;
    setExporting(true);
    try {
      const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: formatColumnLabel(k) }));
      await exportService.exportGenericExcel({
        data: resultados.map(r => ({ ...r })),
        columns: cols,
        title: selected.nome,
        filename: `financeiro_${selected.id}`,
      });
      setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
    } catch {
      setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
    } finally { setExporting(false); }
  };

  const handleExportPdf = async () => {
    if (!selected || resultados.length === 0) return;
    setExporting(true);
    try {
      await exportService.exportGenericPdf({
        data: resultados.map(r => ({ ...r })),
        title: selected.nome,
        filename: `financeiro_${selected.id}`,
      });
      setSnackbar({ open: true, message: 'PDF exportado com sucesso!', severity: 'success' });
    } catch {
      setSnackbar({ open: true, message: 'Erro ao exportar PDF', severity: 'error' });
    } finally { setExporting(false); }
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  // Card selection
  if (!selected) {
    return (
      <PageContainer>
        <Box sx={{ mb: 4 }}>
          <Typography variant="h5">Relatórios Financeiros</Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Selecione o relatório financeiro que deseja gerar.
          </Typography>
        </Box>

        <Grid container spacing={2.5}>
          {RELATORIOS.map((rel) => (
            <Grid item xs={12} sm={6} md={4} key={rel.id}>
              <Card sx={{ height: '100%', display: 'flex', flexDirection: 'column', position: 'relative', overflow: 'visible' }}>
                <Box sx={{ position: 'absolute', top: 0, left: 0, right: 0, height: 3, bgcolor: rel.color, borderRadius: '12px 12px 0 0' }} />
                <CardActionArea onClick={() => handleSelect(rel)} sx={{ flexGrow: 1, p: 0.5 }}>
                  <CardContent sx={{ pt: 2.5 }}>
                    <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1.5 }}>
                      <Box sx={{
                        width: 40, height: 40, borderRadius: 2,
                        bgcolor: alpha(rel.color, 0.08), display: 'flex',
                        alignItems: 'center', justifyContent: 'center', flexShrink: 0,
                      }}>
                        {React.cloneElement(rel.icon as React.ReactElement, { sx: { fontSize: 20, color: rel.color } })}
                      </Box>
                      <Box>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.3, mb: 0.5 }}>
                          {rel.nome}
                        </Typography>
                        <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.4, display: 'block' }}>
                          {rel.descricao}
                        </Typography>
                      </Box>
                    </Box>
                  </CardContent>
                </CardActionArea>
              </Card>
            </Grid>
          ))}
        </Grid>

        <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
          <Alert severity={snackbar.severity} variant="filled" elevation={6}>{snackbar.message}</Alert>
        </Snackbar>
      </PageContainer>
    );
  }

  // Selected report
  return (
    <PageContainer>
      {/* Header */}
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 1 }}>
          <IconButton onClick={handleBack} size="small" sx={{ color: 'primary.main' }}>
            <ArrowBackIcon />
          </IconButton>
          <Box>
            <Typography variant="h5">{selected.nome}</Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
              {selected.descricao}
            </Typography>
          </Box>
        </Box>
      </Box>

      {/* Filters */}
      <Paper variant="outlined" sx={{ p: 2.5, mb: 3, bgcolor: 'rgba(141,15,18,0.015)' }}>
        <Grid container spacing={2} alignItems="center">
          {selected.filtros.includes('periodo') && (
            <>
              <Grid item xs={6} sm={3} md={2}>
                <TextField fullWidth size="small" label="Data Início" type="date" value={filters.inicio}
                  onChange={e => setFilters(p => ({ ...p, inicio: e.target.value }))}
                  InputLabelProps={{ shrink: true }} />
              </Grid>
              <Grid item xs={6} sm={3} md={2}>
                <TextField fullWidth size="small" label="Data Término" type="date" value={filters.termino}
                  onChange={e => setFilters(p => ({ ...p, termino: e.target.value }))}
                  InputLabelProps={{ shrink: true }} />
              </Grid>
            </>
          )}
          {(selected.filtros.includes('estado') || selected.filtros.includes('uf_obrigatorio')) && (
            <Grid item xs={6} sm={3} md={2}>
              <FormControl fullWidth size="small">
                <InputLabel>UF</InputLabel>
                <Select value={filters.uf} label="UF" onChange={e => setFilters(p => ({ ...p, uf: e.target.value }))}>
                  {!selected.filtros.includes('uf_obrigatorio') && <MenuItem value="">Todos</MenuItem>}
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
          )}
          {selected.filtros.includes('etapa') && (
            <Grid item xs={6} sm={3} md={2}>
              <TextField fullWidth size="small" label="Etapa" value={filters.etapa}
                onChange={e => setFilters(p => ({ ...p, etapa: e.target.value }))} />
            </Grid>
          )}
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
          <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <Typography variant="subtitle2" color="text.secondary">{selected.nome}</Typography>
              <Chip
                label={`${resultados.length} registro${resultados.length !== 1 ? 's' : ''}`}
                size="small" color="primary" variant="outlined"
              />
            </Box>
            {resultados.length > 0 && (
              <Box sx={{ display: 'flex', gap: 1 }}>
                <Button variant="outlined" color="success" size="small" startIcon={<DownloadIcon />}
                  disabled={exporting} onClick={handleExport}>
                  {exporting ? 'Exportando...' : 'Excel'}
                </Button>
                <Button variant="outlined" color="error" size="small" startIcon={<DownloadIcon />}
                  disabled={exporting} onClick={handleExportPdf}>
                  PDF
                </Button>
              </Box>
            )}
          </Box>

          {resultados.length > 0 ? (
            <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
              <TableContainer sx={{ maxHeight: 'calc(100vh - 380px)' }}>
                <Table stickyHeader size="small">
                  <TableHead>
                    <TableRow>
                      {columns.map(col => (
                        <TableCell key={col} align={CURRENCY_COLS.includes(col) ? 'right' : undefined}
                          sx={{ whiteSpace: 'nowrap' }}>
                          {formatColumnLabel(col)}
                        </TableCell>
                      ))}
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {resultados.map((r, i) => (
                      <TableRow key={i} hover>
                        {columns.map(col => (
                          <TableCell key={col} align={CURRENCY_COLS.includes(col) ? 'right' : undefined}
                            sx={CURRENCY_COLS.includes(col) ? { fontWeight: 600, fontVariantNumeric: 'tabular-nums' } : undefined}>
                            {formatCell(col, r[col])}
                          </TableCell>
                        ))}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>
            </Paper>
          ) : (
            <Paper variant="outlined" sx={{ p: 6, textAlign: 'center' }}>
              <Typography color="text.secondary">Nenhum resultado encontrado para o período informado.</Typography>
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
