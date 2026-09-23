import React, { useState } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Snackbar, Alert,
  Paper, Chip, Card, CardContent, CardActionArea, IconButton,
  TextField, alpha, Divider, Tooltip, TablePagination,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
  ArrowBack as ArrowBackIcon, AccountBalance as BankIcon,
  Receipt as ReceiptIcon, CreditCard as CardIcon,
  AttachMoney as MoneyIcon, TableChart as TableIcon,
  InfoOutlined as InfoIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';
import { formatColumnLabel } from '../utils/columnLabels';
import { getApiErrorMessage, ensureXlsxBlob, getBlobErrorMessage } from '../utils/apiError';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];
const CIELO_JOB_POLL_SEC = 3;
const MAX_CIELO_JOB_POLLS = 600;
const MAX_EXCEL_RETRIES = 600;

const isCieloTimeoutError = (e: any) =>
  e?.code === 'ECONNABORTED' || e?.message?.includes('timeout') || e?.response?.status === 504;

const isCieloRetryableExcelError = (e: any) =>
  isCieloTimeoutError(e) ||
  e?.code === 'ERR_NETWORK' ||
  e?.message === 'Network Error' ||
  [500, 502, 503].includes(e?.response?.status);

interface RelatorioConfig {
  id: string;
  nome: string;
  descricao: string;
  endpoint: string;
  icon: React.ReactNode;
  color: string;
  filtros: string[];
  /** Apenas exportação Excel, sem tabela na tela */
  apenasExcel?: boolean;
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
  {
    id: 'cielo_checkout', nome: 'Consolidação Cielo Checkout (SISCAF)',
    descricao: 'Pagamentos Cielo consolidados por regional.',
    endpoint: '/relatorios/cielo-checkout-consolidado/excel', icon: <CardIcon />, color: '#0066CC',
    filtros: ['periodo', 'cro'],
    apenasExcel: true,
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
  'Valor_Bruto', 'Valor_CRO', 'Tarifa_Cartao', 'Split_Federal',
  // Sintético
  'ValorBruto', 'ValorCartao', 'ValorLiquido', 'VALOR_TOTAL', 'VALOR_PRINCIPAL',
  'ATUALIZACAO_MONETARIA', 'MULTA', 'JUROS', 'ValorBruto_CFO', 'ValorLiquido_CFO',
  // Analítico
  'ValorCartao', 'ValorCRO', 'ValorBrutoCFO', 'ValorLiquidoCFO',
  'ValorTotal', 'ValorPrincipal', 'AtualizacaoMonetaria', 'Multa', 'Juros',
  // Renegociações
  'ValorTotalRenegociado', 'ValorTotalPago'];

const formatCell = (col: string, val: any) => {
  if (val == null) return '-';
  if (CURRENCY_COLS.includes(col)) return fmtBRL(val);
  if (typeof val === 'number' && !col.toLowerCase().includes('ano') && !col.toLowerCase().includes('dia')) return fmtNum(val);
  return String(val);
};

interface CieloSubTipo {
  id: string;
  nome: string;
  descricao: string;
}

const CIELO_SUB_TIPOS: CieloSubTipo[] = [
  {
    id: 'analitico',
    nome: 'Relatório de Crédito Cielo Checkout (Analítico)',
    descricao: '',
  },
  {
    id: 'sintetico',
    nome: 'Relatório de Créditos Cielo Checkout (Sintético)',
    descricao: 'Visão consolidada/resumida dos créditos Cielo Checkout por regional.',
  },
  {
    id: 'renegociacoes',
    nome: 'Relatório de Renegociações Ativas (Analítico)',
    descricao: '',
  },
];

export default function RelatorioFinanceiro() {
  const [selected, setSelected] = useState<RelatorioConfig | null>(null);
  const [cieloSubTipo, setCieloSubTipo] = useState<CieloSubTipo | null>(null);
  const [filters, setFilters] = useState({ inicio: '', termino: '', uf: '', etapa: '', cro: 'TODOS' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [totalRegistros, setTotalRegistros] = useState(0);
  const [pendentes, setPendentes] = useState<string[]>([]);
  const [resumoRegionais, setResumoRegionais] = useState<{ CRO: string; Registros: number; Status?: string }[]>([]);
  const [jobStatus, setJobStatus] = useState('');
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [excelStatus, setExcelStatus] = useState('');
  const [excelReady, setExcelReady] = useState<{ jobId: string; filename: string } | null>(null);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(100);
  const handleSelect = (rel: RelatorioConfig) => {
    setSelected(rel);
    setCieloSubTipo(rel.id === 'cielo_checkout' ? CIELO_SUB_TIPOS[0] : null);
    setFilters({ inicio: '', termino: '', uf: '', etapa: '', cro: 'TODOS' });
    setResultados([]); setSearched(false); setPage(0);
    setExcelStatus(''); setExcelReady(null);
  };

  const excelRetryRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);
  const excelGenRef = React.useRef(0);
  const excelRetryCountRef = React.useRef(0);
  const searchPollRef = React.useRef<ReturnType<typeof setTimeout> | null>(null);

  React.useEffect(() => () => {
    if (searchPollRef.current) clearTimeout(searchPollRef.current);
    if (excelRetryRef.current) clearTimeout(excelRetryRef.current);
  }, []);

  const handleBack = () => {
    if (searchPollRef.current) clearTimeout(searchPollRef.current);
    if (excelRetryRef.current) clearTimeout(excelRetryRef.current);
    searchGenRef.current += 1;
    excelGenRef.current += 1;
    excelRetryCountRef.current = 0;
    setSelected(null);
    setCieloSubTipo(null);
    setFilters({ inicio: '', termino: '', uf: '', etapa: '', cro: 'TODOS' });
    setResultados([]); setSearched(false);
    setPendentes([]); setJobStatus(''); setTotalRegistros(0); setResumoRegionais([]);
    setExcelStatus(''); setExcelReady(null); setExporting(false);
  };

  const searchGenRef = React.useRef(0);

  const pollCieloJsonJob = async (jobId: string, searchGen: number, pollCount = 0) => {
    if (searchGen !== searchGenRef.current) return;

    try {
      const statusRes = await api.get(`/relatorios/cielo-checkout/jobs/${jobId}`, { timeout: 30000 });
      const status = statusRes.data?.status;

      if (status === 'done') {
        if (searchGen !== searchGenRef.current) return;
        const data = Array.isArray(statusRes.data.resultados) ? statusRes.data.resultados : [];
        setResultados(data);
        setTotalRegistros(statusRes.data?.total ?? data.length);
        setPendentes(statusRes.data?.pendentes || []);
        setResumoRegionais(statusRes.data?.resumo_regionais || []);
        setJobStatus('');
        setLoading(false);
        if (data.length === 0 && (statusRes.data?.pendentes || []).length === 0) {
          setSnackbar({ open: true, message: 'Nenhum registro encontrado para o período informado.', severity: 'info' });
        }
        return;
      }

      if (status === 'error') {
        throw new Error(statusRes.data?.detail || 'Erro ao gerar relatório.');
      }

      const queueHint = status === 'pending' ? 'Aguardando na fila...' : 'Consultando regionais na Implanta...';
      setJobStatus(queueHint);

      if (pollCount >= MAX_CIELO_JOB_POLLS) {
        throw new Error('A consulta está demorando mais que o esperado. Tente novamente em alguns minutos.');
      }

      searchPollRef.current = setTimeout(
        () => pollCieloJsonJob(jobId, searchGen, pollCount + 1),
        CIELO_JOB_POLL_SEC * 1000,
      );
    } catch (e: any) {
      if (searchGen !== searchGenRef.current) return;
      setLoading(false);
      setJobStatus('');
      setSnackbar({
        open: true,
        message: getApiErrorMessage(e, 'Erro ao gerar relatório'),
        severity: 'error',
      });
    }
  };

  const _doSearch = async () => {
    if (!selected) return;
    const f = selected.filtros;
    if (f.includes('periodo') && (!filters.inicio || !filters.termino)) {
      setSnackbar({ open: true, message: 'Informe o período (início e término).', severity: 'warning' }); return;
    }
    if (f.includes('uf_obrigatorio') && !filters.uf) {
      setSnackbar({ open: true, message: 'Selecione uma UF.', severity: 'warning' }); return;
    }

    const isCielo = selected.id === 'cielo_checkout';
    const searchGen = ++searchGenRef.current;

    if (searchPollRef.current) clearTimeout(searchPollRef.current);
    setLoading(true);
    setSearched(true);
    setResultados([]);
    setPendentes([]);
    setPage(0);
    setJobStatus('');
    setTotalRegistros(0);
    setResumoRegionais([]);

    try {
      const params = new URLSearchParams();
      if (filters.inicio && f.includes('periodo')) params.append('inicio', filters.inicio);
      if (filters.termino && f.includes('periodo')) params.append('termino', filters.termino);
      if (filters.uf && (f.includes('estado') || f.includes('uf_obrigatorio'))) {
        params.append(f.includes('estado') ? 'estado' : 'uf', filters.uf);
      }
      if (filters.etapa && f.includes('etapa')) params.append('etapa', filters.etapa);

      if (isCielo) {
        params.append('cro', filters.cro || 'TODOS');
        params.append('tipo_relatorio', cieloSubTipo?.id || 'sintetico');

        const jobRes = await api.post(`/relatorios/cielo-checkout/jobs?${params}`, undefined, { timeout: 30000 });
        const jobId = jobRes.data?.job_id;
        if (!jobId) throw new Error('O servidor não retornou o identificador da consulta.');

        const queuePos = jobRes.data?.queue_position;
        setJobStatus(
          queuePos && queuePos > 1
            ? `Na fila (posição ${queuePos}). A consulta à Implanta só inicia após sua vez.`
            : 'Consulta enfileirada. Aguarde...',
        );
        pollCieloJsonJob(jobId, searchGen);
        return;
      }

      const endpoint = selected.endpoint;
      const res = await api.get(`${endpoint}?${params}`);
      if (searchGen !== searchGenRef.current) return;

      const data = Array.isArray(res.data) ? res.data : (res.data.resultados || []);
      const total = res.data?.total ?? data.length;
      setResultados(data);
      setTotalRegistros(total);
      setPendentes([]);
      setResumoRegionais([]);
      setLoading(false);
    } catch (e: any) {
      if (searchGen !== searchGenRef.current) return;
      setLoading(false);
      setJobStatus('');
      const status = e.response?.status;
      const fallback = status === 504
        ? 'O servidor demorou para responder (timeout). Tente novamente.'
        : 'Erro ao gerar relatório';
      setSnackbar({ open: true, message: getApiErrorMessage(e, fallback), severity: 'error' });
    }
  };

  const handleSearch = () => _doSearch();

  const handleClear = () => {
    if (searchPollRef.current) clearTimeout(searchPollRef.current);
    if (excelRetryRef.current) clearTimeout(excelRetryRef.current);
    searchGenRef.current += 1;
    excelGenRef.current += 1;
    excelRetryCountRef.current = 0;
    setFilters({ inicio: '', termino: '', uf: '', etapa: '', cro: 'TODOS' });
    setResultados([]); setPendentes([]); setSearched(false);
    setJobStatus(''); setTotalRegistros(0); setResumoRegionais([]);
    setExcelStatus(''); setExcelReady(null); setExporting(false);
  };

  const downloadCieloExcelJob = async (jobId: string, filename: string) => {
    if (!selected || selected.id !== 'cielo_checkout') return;
    const downloadRes = await api.get(`${selected.endpoint}/jobs/${jobId}/download`, {
      responseType: 'blob',
      timeout: 120000,
    });
    const xlsxBlob = await ensureXlsxBlob(downloadRes.data);
    const url = window.URL.createObjectURL(xlsxBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  };

  const handleGerarExcelCielo = async () => {
    if (!selected || selected.id !== 'cielo_checkout') return;
    if (!filters.inicio || !filters.termino) {
      setSnackbar({ open: true, message: 'Informe o período (data início e data fim).', severity: 'warning' });
      return;
    }

    const endpoint = selected.endpoint;
    const excelGen = ++excelGenRef.current;
    if (excelRetryRef.current) clearTimeout(excelRetryRef.current);
    excelRetryCountRef.current = 0;
    setExcelReady(null);
    setExcelStatus('Iniciando geração do Excel...');
    setExporting(true);

    try {
      const params = new URLSearchParams({
        inicio: filters.inicio,
        termino: filters.termino,
        cro: filters.cro || 'TODOS',
        tipo_relatorio: cieloSubTipo?.id || 'analitico',
      });

      const cro = filters.cro || 'TODOS';
      const tipoLabel = cieloSubTipo?.id || 'analitico';
      const croPrefix = cro.toUpperCase() === 'TODOS' ? 'TODOS-REGIONAIS' : `CRO-${cro.toUpperCase()}`;
      const inicioFmt = filters.inicio.split('-').reverse().join('-');
      const terminoFmt = filters.termino.split('-').reverse().join('-');

      const jobRes = await api.post(`${endpoint}/jobs?${params}`, undefined, { timeout: 30000 });
      const jobId = jobRes.data?.job_id;
      if (!jobId) throw new Error('O servidor não retornou o identificador da geração do Excel.');

      setSnackbar({
        open: true,
        message: 'Geração do Excel iniciada em segundo plano. O download começará automaticamente quando a planilha estiver pronta.',
        severity: 'info',
      });
      setExcelStatus('Gerando Excel em segundo plano. Aguarde...');

      const pollJob = async () => {
        if (excelGen !== excelGenRef.current) return;

        try {
          const statusRes = await api.get(`${endpoint}/jobs/${jobId}`, { timeout: 30000 });
          const status = statusRes.data?.status;

          if (status === 'done') {
            if (excelGen !== excelGenRef.current) return;
            const filename = statusRes.data?.filename || `${croPrefix}_${tipoLabel}_${inicioFmt}_a_${terminoFmt}.xlsx`;
            setExcelReady({ jobId, filename });
            setExcelStatus('Excel pronto. Clique em "Baixar Excel pronto" se o download não iniciar automaticamente.');

            excelRetryCountRef.current = 0;
            setExporting(false);
            setSnackbar({ open: true, message: 'Planilha Excel pronta para download!', severity: 'success' });
            try {
              await downloadCieloExcelJob(jobId, filename);
            } catch {
              // O botão explícito permanece disponível quando o navegador bloqueia o download automático.
            }
            return;
          }

          if (status === 'error') {
            throw new Error(statusRes.data?.detail || 'Erro ao gerar planilha Excel.');
          }

          excelRetryCountRef.current += 1;
          setExcelStatus(`Gerando Excel em segundo plano... verificação ${excelRetryCountRef.current}`);
          if (excelRetryCountRef.current >= MAX_EXCEL_RETRIES) {
            throw new Error('A planilha ainda está sendo gerada. Aguarde alguns instantes e tente baixar novamente.');
          }

          if (excelRetryRef.current) clearTimeout(excelRetryRef.current);
          excelRetryRef.current = setTimeout(pollJob, CIELO_JOB_POLL_SEC * 1000);
        } catch (e: any) {
          if (excelGen !== excelGenRef.current) return;
          if (isCieloRetryableExcelError(e) && excelRetryCountRef.current < MAX_EXCEL_RETRIES) {
            excelRetryCountRef.current += 1;
            setExcelStatus(`Aguardando o servidor... verificação ${excelRetryCountRef.current}`);
            if (excelRetryRef.current) clearTimeout(excelRetryRef.current);
            excelRetryRef.current = setTimeout(pollJob, CIELO_JOB_POLL_SEC * 1000);
            return;
          }

          let msg = getApiErrorMessage(e, 'Erro ao gerar planilha Excel');
          if (e.response?.data instanceof Blob) {
            msg = await getBlobErrorMessage(e.response.data, msg);
          }
          excelRetryCountRef.current = 0;
          setExcelStatus('');
          setExporting(false);
          setSnackbar({ open: true, message: msg, severity: 'error' });
        }
      };

      pollJob();
    } catch (e: any) {
      if (excelGen !== excelGenRef.current) return;
      let msg = getApiErrorMessage(e, 'Erro ao iniciar geração do Excel');
      if (e.response?.data instanceof Blob) {
        msg = await getBlobErrorMessage(e.response.data, msg);
      }
      excelRetryCountRef.current = 0;
      setExcelStatus('');
      setExporting(false);
      setSnackbar({ open: true, message: msg, severity: 'error' });
    }
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

  const isCieloView = selected?.id === 'cielo_checkout';
  const isCieloTodos = isCieloView && (filters.cro || 'TODOS').toUpperCase() === 'TODOS';
  const isExcelOnly = (selected?.apenasExcel ?? false) && !isCieloView;
  const isRenegociacoes = cieloSubTipo?.id === 'renegociacoes';
  const showLoading = !isExcelOnly && loading;

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  // Card selection
  if (!selected) {
    return (
      <PageContainer>
        <Box sx={{ mb: 3 }}>
          <Typography variant="h5" sx={{ fontWeight: 700 }}>Relatórios Financeiros</Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Selecione o relatório que deseja gerar.
          </Typography>
        </Box>

        <Grid container spacing={2}>
          {RELATORIOS.map((rel) => (
            <Grid item xs={12} sm={6} md={4} key={rel.id}>
              <Card
                variant="outlined"
                sx={{
                  height: '100%', display: 'flex', flexDirection: 'column',
                  borderLeft: `4px solid ${rel.color}`,
                  transition: 'box-shadow 0.15s, transform 0.15s',
                  '&:hover': { boxShadow: 4, transform: 'translateY(-2px)' },
                }}
              >
                <CardActionArea onClick={() => handleSelect(rel)} sx={{ flexGrow: 1 }}>
                  <CardContent sx={{ p: 2 }}>
                    <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1.5 }}>
                      <Box sx={{
                        width: 44, height: 44, borderRadius: 2, flexShrink: 0,
                        bgcolor: alpha(rel.color, 0.1),
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                      }}>
                        {React.cloneElement(rel.icon as React.ReactElement, { sx: { fontSize: 22, color: rel.color } })}
                      </Box>
                      <Box sx={{ flex: 1, minWidth: 0 }}>
                        <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.3, mb: 0.5 }}>
                          {rel.nome}
                        </Typography>
                        <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.5, display: 'block' }}>
                          {rel.descricao}
                        </Typography>
                        <Box sx={{ mt: 1.5, display: 'flex', gap: 0.75, flexWrap: 'wrap' }}>
                          <Chip
                            size="small"
                            icon={rel.apenasExcel ? <DownloadIcon /> : <TableIcon />}
                            label={rel.apenasExcel ? 'Exportação Excel' : 'Visualização em tela'}
                            sx={{
                              fontSize: '0.68rem', height: 20,
                              bgcolor: alpha(rel.color, 0.08),
                              color: rel.color,
                              '& .MuiChip-icon': { fontSize: 12, color: rel.color },
                            }}
                          />
                          {rel.id === 'cielo_checkout' && (
                            <Chip size="small" label="3 tipos" sx={{
                              fontSize: '0.68rem', height: 20,
                              bgcolor: alpha(rel.color, 0.08), color: rel.color,
                            }} />
                          )}
                        </Box>
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
      <Box sx={{ mb: 3 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
          <IconButton onClick={handleBack} size="small" sx={{
            color: selected.color,
            bgcolor: alpha(selected.color, 0.08),
            '&:hover': { bgcolor: alpha(selected.color, 0.16) },
          }}>
            <ArrowBackIcon fontSize="small" />
          </IconButton>
          <Box sx={{
            display: 'flex', alignItems: 'center', gap: 1.5, flex: 1,
            borderLeft: `4px solid ${selected.color}`, pl: 1.5, py: 0.5,
          }}>
            <Box sx={{
              width: 36, height: 36, borderRadius: 1.5, flexShrink: 0,
              bgcolor: alpha(selected.color, 0.1),
              display: 'flex', alignItems: 'center', justifyContent: 'center',
            }}>
              {React.cloneElement(selected.icon as React.ReactElement, { sx: { fontSize: 18, color: selected.color } })}
            </Box>
            <Box>
              <Typography variant="h6" sx={{ fontWeight: 700, lineHeight: 1.2 }}>{selected.nome}</Typography>
              <Typography variant="caption" color="text.secondary">{selected.descricao}</Typography>
            </Box>
          </Box>
        </Box>
      </Box>

      {/* Filters */}
      <Paper variant="outlined" sx={{
        mb: 3, overflow: 'hidden',
        borderLeft: `4px solid ${selected.color}`,
        bgcolor: alpha(selected.color, 0.015),
      }}>
        {/* Filtros header */}
        <Box sx={{ px: 2.5, pt: 2, pb: 1, display: 'flex', alignItems: 'center', gap: 1 }}>
          <Typography variant="caption" sx={{ fontWeight: 700, color: selected.color, textTransform: 'uppercase', letterSpacing: 0.8 }}>
            Filtros
          </Typography>
          {isExcelOnly && (
            <Tooltip title="Este relatório gera apenas uma planilha Excel para download, sem pré-visualização em tela." arrow>
              <InfoIcon sx={{ fontSize: 14, color: 'text.disabled', cursor: 'help' }} />
            </Tooltip>
          )}
        </Box>
        <Divider />

        <Box sx={{ p: 2.5 }}>
          <Grid container spacing={2} alignItems="flex-start">

            {/* Tipo de relatório — Cielo */}
            {selected.id === 'cielo_checkout' && (
              <Grid item xs={12} md={5}>
                <FormControl fullWidth size="small">
                  <InputLabel>Tipo de Relatório</InputLabel>
                  <Select
                    value={cieloSubTipo?.id ?? CIELO_SUB_TIPOS[0].id}
                    label="Tipo de Relatório"
                    onChange={e => setCieloSubTipo(CIELO_SUB_TIPOS.find(s => s.id === e.target.value) ?? CIELO_SUB_TIPOS[0])}
                  >
                    {CIELO_SUB_TIPOS.map(sub => (
                      <MenuItem key={sub.id} value={sub.id}>{sub.nome}</MenuItem>
                    ))}
                  </Select>
                </FormControl>
                {cieloSubTipo && (
                  <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 0.75, ml: 0.5, lineHeight: 1.4 }}>
                    {cieloSubTipo.descricao}
                  </Typography>
                )}
              </Grid>
            )}

            {/* CRO */}
            {selected.filtros.includes('cro') && (
              <Grid item xs={12} sm={5} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>CRO</InputLabel>
                  <Select value={filters.cro} label="CRO" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
                    <MenuItem value="TODOS">Todos os regionais</MenuItem>
                    {UF_LIST.map(u => <MenuItem key={u} value={u}>CRO-{u}</MenuItem>)}
                  </Select>
                </FormControl>
              </Grid>
            )}

            {/* Período */}
            {selected.filtros.includes('periodo') && (
              <>
                {selected.id === 'cielo_checkout' && (
                  <Grid item xs={12} sx={{ pb: 0 }}>
                    <Typography variant="caption" sx={{ fontWeight: 600, color: 'text.secondary' }}>
                      {cieloSubTipo?.id === 'renegociacoes' ? 'Data da renegociação' : cieloSubTipo?.id === 'analitico' ? 'Data da transação (pagamento)' : 'Data do crédito'}
                    </Typography>
                  </Grid>
                )}
                <Grid item xs={6} sm={3} md={2}>
                  <TextField fullWidth size="small" label="Data Início" type="date" value={filters.inicio}
                    onChange={e => setFilters(p => ({ ...p, inicio: e.target.value }))}
                    InputLabelProps={{ shrink: true }} />
                </Grid>
                <Grid item xs={6} sm={3} md={2}>
                  <TextField fullWidth size="small" label="Data Fim" type="date" value={filters.termino}
                    onChange={e => setFilters(p => ({ ...p, termino: e.target.value }))}
                    InputLabelProps={{ shrink: true }} />
                </Grid>
              </>
            )}

            {/* UF */}
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

            {/* Etapa */}
            {selected.filtros.includes('etapa') && (
              <Grid item xs={6} sm={3} md={2}>
                <TextField fullWidth size="small" label="Etapa" value={filters.etapa}
                  onChange={e => setFilters(p => ({ ...p, etapa: e.target.value }))} />
              </Grid>
            )}
          </Grid>

          {/* Actions */}
          <Box sx={{ mt: 2.5, pt: 2, borderTop: '1px solid', borderColor: 'divider', display: 'flex', gap: 1.5, alignItems: 'center' }}>
            {isExcelOnly ? (
              <Button
                variant="contained"
                size="medium"
                startIcon={exporting ? <CircularProgress size={16} sx={{ color: 'inherit' }} /> : <DownloadIcon />}
                onClick={() => handleGerarExcelCielo()}
                disabled={exporting}
                sx={{ bgcolor: selected.color, '&:hover': { bgcolor: selected.color, filter: 'brightness(0.88)' }, minWidth: 150 }}
              >
                {exporting ? 'Gerando...' : 'Gerar Excel'}
              </Button>
            ) : (
              <Button
                variant="contained"
                size="medium"
                startIcon={showLoading ? <CircularProgress size={16} sx={{ color: 'inherit' }} /> : <SearchIcon />}
                onClick={handleSearch}
                disabled={showLoading}
                sx={{ bgcolor: selected.color, '&:hover': { bgcolor: selected.color, filter: 'brightness(0.88)' }, minWidth: 120 }}
              >
                {showLoading ? 'Gerando...' : 'Gerar Relatório'}
              </Button>
            )}
            <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}
              sx={{ color: 'text.secondary', borderColor: 'divider' }}>
              Limpar
            </Button>
            {excelReady && (
              <Button
                variant="outlined"
                startIcon={<DownloadIcon />}
                onClick={async () => {
                  try {
                    await downloadCieloExcelJob(excelReady.jobId, excelReady.filename);
                    setSnackbar({ open: true, message: 'Download iniciado.', severity: 'success' });
                  } catch (e: any) {
                    let msg = getApiErrorMessage(e, 'Erro ao baixar planilha Excel');
                    if (e.response?.data instanceof Blob) {
                      msg = await getBlobErrorMessage(e.response.data, msg);
                    }
                    setSnackbar({ open: true, message: msg, severity: 'error' });
                  }
                }}
                sx={{ color: 'success.main', borderColor: 'success.main' }}
              >
                Baixar Excel pronto
              </Button>
            )}
          </Box>
          {isRenegociacoes && (
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1.5, lineHeight: 1.5 }}>
              {excelStatus || (exporting
                ? 'Geração do Excel enfileirada. A consulta à Implanta só ocorre após você clicar no botão.'
                : 'Clique em Gerar Excel para enfileirar a geração da planilha.')}
            </Typography>
          )}
        </Box>
      </Paper>

      {/* Loading */}
      {showLoading && (
        <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 2, py: 10 }}>
          <CircularProgress size={36} sx={{ color: selected.color }} />
          <Typography variant="body2" color="text.secondary" sx={{ textAlign: 'center', maxWidth: 480 }}>
            {jobStatus || (isRenegociacoes
              ? 'Consultando renegociações na Implanta. Isso pode levar alguns minutos.'
              : 'Gerando relatório na Implanta. Isso pode levar alguns minutos.')}
          </Typography>
        </Box>
      )}

      {/* Aviso de regionais sem dados */}
      {!isExcelOnly && !showLoading && searched && pendentes.length > 0 && (
        <Paper variant="outlined" sx={{
          mb: 1.5, p: 1.5, display: 'flex', alignItems: 'flex-start', gap: 1.5,
          bgcolor: '#FFF3E0', borderColor: '#FFB74D', borderLeft: '4px solid #F57C00',
        }}>
          <InfoIcon sx={{ color: '#F57C00', mt: 0.25, fontSize: 18 }} />
          <Box sx={{ flex: 1 }}>
            <Typography variant="body2" sx={{ fontWeight: 600, color: '#E65100' }}>
              Alguns regionais não retornaram dados
            </Typography>
            <Typography variant="caption" sx={{ display: 'block', color: 'text.secondary', mt: 0.5 }}>
              Clique em Gerar Relatório novamente se precisar tentar de novo.
            </Typography>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5, mt: 1 }}>
              {pendentes.map((uf) => (
                <Chip key={uf} label={uf} size="small" sx={{ height: 22, fontSize: '0.7rem', bgcolor: '#FFE0B2', color: '#E65100' }} />
              ))}
            </Box>
          </Box>
        </Paper>
      )}

      {/* Results */}
      {!isExcelOnly && !showLoading && searched && (
        <Paper variant="outlined" sx={{ overflow: 'hidden', borderTop: `3px solid ${selected.color}` }}>
          {/* Results toolbar */}
          <Box sx={{
            px: 2, py: 1.25, display: 'flex', justifyContent: 'space-between', alignItems: 'center',
            bgcolor: alpha(selected.color, 0.04), borderBottom: '1px solid', borderColor: 'divider',
          }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
              <Typography variant="body2" sx={{ fontWeight: 600 }}>{selected.nome}</Typography>
              <Chip
                label={`${totalRegistros.toLocaleString('pt-BR')} registro${totalRegistros !== 1 ? 's' : ''}${pendentes.length > 0 ? ' · parcial' : ''}`}
                size="small" variant="filled"
                sx={{ bgcolor: alpha(selected.color, 0.12), color: selected.color, fontWeight: 700, fontSize: '0.72rem' }}
              />
            </Box>
            {resultados.length > 0 && (
              <Box sx={{ display: 'flex', gap: 1 }}>
                <Button
                  variant="outlined" size="small"
                  startIcon={exporting ? <CircularProgress size={14} /> : <DownloadIcon />}
                  disabled={exporting}
                  onClick={isCieloView ? () => handleGerarExcelCielo() : handleExport}
                  sx={isCieloView ? { color: selected.color, borderColor: selected.color } : { color: 'success.main', borderColor: 'success.main' }}>
                  Excel
                </Button>
                {!isCieloView && (
                  <Button variant="outlined" color="error" size="small" startIcon={<DownloadIcon />}
                    disabled={exporting} onClick={handleExportPdf}>
                    PDF
                  </Button>
                )}
              </Box>
            )}
          </Box>

          {resultados.length > 0 ? (
            <>
              <TableContainer sx={{ maxHeight: 'calc(100vh - 380px)' }}>
                <Table stickyHeader size="small">
                  <TableHead>
                    <TableRow>
                      {columns.map(col => (
                        <TableCell key={col} align={CURRENCY_COLS.includes(col) ? 'right' : undefined}
                          sx={{ whiteSpace: 'nowrap', bgcolor: alpha(selected.color, 0.07), fontWeight: 700, fontSize: '0.78rem' }}>
                          {formatColumnLabel(col)}
                        </TableCell>
                      ))}
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {(() => {
                      const parseNum = (v: any): number => {
                        if (v == null) return 0;
                        if (typeof v === 'number') return v;
                        const s = String(v).replace(/R\$\s?/g, '').replace(/\./g, '').replace(',', '.');
                        return parseFloat(s) || 0;
                      };
                      const currCols = columns.filter(c => CURRENCY_COLS.includes(c));
                      const sumRows = (rows: any[]) => {
                        const s: Record<string, number> = {};
                        currCols.forEach(c => { s[c] = 0; });
                        rows.forEach(r => currCols.forEach(c => { s[c] += parseNum(r[c]); }));
                        return s;
                      };
                      const isTodos = isCieloTodos;

                      const totalRowSx = { bgcolor: alpha(selected.color, 0.10), position: 'sticky' as const, bottom: 0, zIndex: 1 };
                      const subtotalRowSx = { bgcolor: '#FFF8E1' };
                      const totalCellSx = { fontWeight: 700, fontSize: '0.82rem', borderTop: '2px solid', borderColor: 'divider' };
                      const subtotalCellSx = { fontWeight: 700, fontSize: '0.82rem', borderTop: '1px solid', borderColor: 'divider', color: '#7B3F00' };

                      const renderTotalRow = (label: string, sums: Record<string, number>, isSub: boolean, key: string) => (
                        <TableRow key={key} sx={isSub ? subtotalRowSx : totalRowSx}>
                          {columns.map((col, ci) => (
                            <TableCell key={col} align={CURRENCY_COLS.includes(col) ? 'right' : undefined}
                              sx={isSub ? subtotalCellSx : totalCellSx}>
                              {ci === 0 ? label : CURRENCY_COLS.includes(col) ? fmtBRL(sums[col] ?? 0) : ''}
                            </TableCell>
                          ))}
                        </TableRow>
                      );

                      // Totais gerais calculados sobre TODOS os dados (antes de paginar)
                      const grandSums = sumRows(resultados);
                      // Cielo "todos os regionais": agrupa o conjunto completo (amostra balanceada do backend)
                      const displayData = isTodos ? resultados : resultados.slice(page * rowsPerPage, page * rowsPerPage + rowsPerPage);

                      if (!isCieloView) {
                        const dataRows = displayData.map((r, i) => (
                          <TableRow key={i} hover sx={{ bgcolor: i % 2 === 1 ? alpha(selected.color, 0.018) : undefined }}>
                            {columns.map(col => (
                              <TableCell key={col} align={CURRENCY_COLS.includes(col) ? 'right' : undefined}
                                sx={CURRENCY_COLS.includes(col) ? { fontWeight: 600, fontVariantNumeric: 'tabular-nums' } : { fontSize: '0.82rem' }}>
                                {formatCell(col, r[col])}
                              </TableCell>
                            ))}
                          </TableRow>
                        ));
                        dataRows.push(renderTotalRow('TOTAL GERAL', grandSums, false, 'grand-total'));
                        return dataRows;
                      }

                      // Cielo: agrupa por CRO com subtotais
                      const groups: Record<string, any[]> = {};
                      displayData.forEach(r => {
                        const cro = r['CRO'] || '';
                        if (!groups[cro]) groups[cro] = [];
                        groups[cro].push(r);
                      });

                      const groupOrder = isTodos
                        ? (resumoRegionais.length > 0
                          ? [...resumoRegionais].sort((a, b) => a.CRO.localeCompare(b.CRO)).map(r => r.CRO)
                          : UF_LIST)
                        : Object.keys(groups).sort();

                      const rows: React.ReactNode[] = [];

                      groupOrder.forEach(cro => {
                        const croRows = groups[cro] || [];
                        if (isTodos && croRows.length === 0) {
                          rows.push(
                            <TableRow key={`${cro}-empty`} sx={{ bgcolor: '#FAFAFA' }}>
                              <TableCell colSpan={columns.length} sx={{ fontSize: '0.82rem', color: 'text.secondary', fontStyle: 'italic' }}>
                                CRO-{cro} — sem registros no período (ou ainda carregando)
                              </TableCell>
                            </TableRow>
                          );
                          return;
                        }
                        croRows.forEach((r, i) => {
                          rows.push(
                            <TableRow key={`${cro}-${i}`} hover sx={{ bgcolor: i % 2 === 1 ? alpha(selected.color, 0.018) : undefined }}>
                              {columns.map(col => (
                                <TableCell key={col} align={CURRENCY_COLS.includes(col) ? 'right' : undefined}
                                  sx={CURRENCY_COLS.includes(col) ? { fontWeight: 600, fontVariantNumeric: 'tabular-nums' } : { fontSize: '0.82rem' }}>
                                  {formatCell(col, r[col])}
                                </TableCell>
                              ))}
                            </TableRow>
                          );
                        });
                        if (isTodos && croRows.length > 0) {
                          const sub = sumRows(croRows);
                          rows.push(renderTotalRow(`SUBTOTAL CRO-${cro}`, sub, true, `sub-${cro}`));
                        }
                      });

                      rows.push(renderTotalRow('TOTAL GERAL', grandSums, false, 'grand-total'));
                      return rows;
                    })()}
                  </TableBody>
                </Table>
              </TableContainer>
              {!isCieloTodos && resultados.length > rowsPerPage && (
                <TablePagination
                  component="div"
                  count={resultados.length}
                  page={page}
                  onPageChange={(_, p) => setPage(p)}
                  rowsPerPage={rowsPerPage}
                  onRowsPerPageChange={e => { setRowsPerPage(parseInt(e.target.value, 10)); setPage(0); }}
                  rowsPerPageOptions={[50, 100, 200, 500]}
                  labelRowsPerPage="Linhas por página:"
                  labelDisplayedRows={({ from, to, count }) => `${from}–${to} de ${count}`}
                />
              )}
              {isCieloTodos && totalRegistros > resultados.length && (
                <Box sx={{ px: 2, py: 1, bgcolor: alpha(selected.color, 0.04), borderTop: '1px solid', borderColor: 'divider' }}>
                  <Typography variant="caption" color="text.secondary">
                    Exibindo amostra de {resultados.length.toLocaleString('pt-BR')} registros de {totalRegistros.toLocaleString('pt-BR')} no total
                    ({resumoRegionais.filter(r => r.Registros > 0).length} regionais com dados).
                    Use o botão Excel para exportar todos os registros.
                  </Typography>
                </Box>
              )}
            </>
          ) : (
            <Box sx={{ p: 6, textAlign: 'center' }}>
              <Typography color="text.secondary">Nenhum resultado encontrado para o período informado.</Typography>
            </Box>
          )}
        </Paper>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity} variant="filled" elevation={6}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
