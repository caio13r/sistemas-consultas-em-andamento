import React, { useState, useEffect, useCallback } from 'react';
import {
  Typography, Box, TextField, Button, Alert, CircularProgress,
  ToggleButtonGroup, ToggleButton, RadioGroup, Radio, FormControlLabel,
  Switch, Chip, Divider, InputAdornment, Tabs, Tab,
  Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  Pagination, IconButton, Tooltip,
} from '@mui/material';
import {
  Save as SaveIcon,
  Dns as DnsIcon,
  Schedule as ScheduleIcon,
  Info as InfoIcon,
  Storage as StorageIcon,
  Speed as SpeedIcon,
  ContentCopy as CopyIcon,
  CheckCircle as CheckIcon,
  Cancel as CancelIcon,
  AppRegistration as RegisterIcon,
  Output as OutputIcon,
  Code as CodeIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

// --- Types ---

interface BackupConfigData {
  id: number;
  mode: string;
  host: string;
  port: number;
  poll_interval: number;
  weekdays: string;
  weekdays_labels: string[];
  start_time: string;
  end_time: string;
  enabled: boolean;
  effective_url: string;
  updated_at: string | null;
}

interface ProgressData {
  worker_active: boolean;
  poll_interval: number;
  poll_origin: string;
  in_window: boolean;
  phase: string;
  phase_detail: string;
  last_cycle: { started_at: string | null; result: string | null; job_id: string | null } | null;
  db_engine: string;
  dump_tools: Record<string, { available: boolean; path: string | null }>;
  python_drivers: Record<string, boolean>;
  system_key: string;
  effective_url: string;
  queries_per_window: number;
  weekdays: string;
  weekdays_labels: string[];
  start_time: string;
  end_time: string;
}

interface HistoryItem {
  id: number;
  started_at: string | null;
  result: string;
  job_id: string | null;
  poll_status: number | null;
  upload_status: number | null;
  file_size: number | null;
  duration_ms: number | null;
  message: string | null;
}

interface ReferenceData {
  registration: { field: string; value: string; note: string }[];
  config_json: Record<string, any>;
  egress: Record<string, string>;
  agent_reference_json: Record<string, any>;
  curl_poll: string;
  curl_upload: string;
  db_engine: string;
  motor_label: string;
  dump_tool: string;
  system_key: string;
  effective_url: string;
}

// --- Constants ---

const WEEKDAYS = [
  { value: 0, label: 'Dom' },
  { value: 1, label: 'Seg' },
  { value: 2, label: 'Ter' },
  { value: 3, label: 'Qua' },
  { value: 4, label: 'Qui' },
  { value: 5, label: 'Sex' },
  { value: 6, label: 'Sab' },
];

const sectionSx = {
  p: 3,
  borderRadius: 2,
  border: '1px solid rgba(122,30,38,0.08)',
  backgroundColor: 'rgba(122,30,38,0.015)',
};

const codeBlockSx = {
  bgcolor: '#1a1a2e',
  color: '#e0e0e0',
  p: 2,
  borderRadius: 1,
  fontSize: '0.75rem',
  fontFamily: "'JetBrains Mono', 'Fira Code', monospace",
  overflow: 'auto',
  lineHeight: 1.6,
  whiteSpace: 'pre-wrap' as const,
  position: 'relative' as const,
};

const labelSx = {
  fontWeight: 700, mb: 0.5, display: 'block', color: 'text.secondary', fontSize: '0.7rem',
  textTransform: 'uppercase' as const, letterSpacing: '0.03em',
};

// --- Helpers ---

const formatSize = (bytes: number | null) => {
  if (!bytes) return '-';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1048576).toFixed(1)} MB`;
};

const formatDate = (iso: string | null) => {
  if (!iso) return '-';
  return new Date(iso).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text);
};

// --- Component ---

const BackupConfig: React.FC = () => {
  const [activeTab, setActiveTab] = useState(0);
  const [config, setConfig] = useState<BackupConfigData | null>(null);
  const [progress, setProgress] = useState<ProgressData | null>(null);
  const [history, setHistory] = useState<HistoryItem[]>([]);
  const [historyPage, setHistoryPage] = useState(1);
  const [historyTotal, setHistoryTotal] = useState(0);
  const [historyTotalPages, setHistoryTotalPages] = useState(1);
  const [reference, setReference] = useState<ReferenceData | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');

  // Form state
  const [mode, setMode] = useState('desenvolvimento');
  const [host, setHost] = useState('192.168.100.112');
  const [port, setPort] = useState(9000);
  const [pollInterval, setPollInterval] = useState(60);
  const [weekdays, setWeekdays] = useState<number[]>([0]);
  const [startTime, setStartTime] = useState('16:00');
  const [endTime, setEndTime] = useState('16:20');
  const [enabled, setEnabled] = useState(true);

  const fetchAll = useCallback(async () => {
    try {
      const [cfgRes, progressRes, histRes, refRes] = await Promise.all([
        api.get('/backup-config'),
        api.get('/backup-config/progress'),
        api.get('/backup-config/history', { params: { page: historyPage, per_page: 10 } }),
        api.get('/backup-config/reference'),
      ]);
      const cfg: BackupConfigData = cfgRes.data;
      setConfig(cfg);
      setProgress(progressRes.data);
      setHistory(histRes.data.items);
      setHistoryTotal(histRes.data.total);
      setHistoryTotalPages(histRes.data.total_pages);
      setReference(refRes.data);
      setMode(cfg.mode);
      setHost(cfg.host);
      setPort(cfg.port);
      setPollInterval(cfg.poll_interval);
      setWeekdays(cfg.weekdays.split(',').map(Number));
      setStartTime(cfg.start_time);
      setEndTime(cfg.end_time);
      setEnabled(cfg.enabled);
    } catch {
      setError('Erro ao carregar configuracao de backup');
    } finally {
      setLoading(false);
    }
  }, [historyPage]);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const fetchHistory = useCallback(async (page: number) => {
    try {
      const res = await api.get('/backup-config/history', { params: { page, per_page: 10 } });
      setHistory(res.data.items);
      setHistoryTotal(res.data.total);
      setHistoryTotalPages(res.data.total_pages);
    } catch { /* silencioso */ }
  }, []);

  const handleHistoryPage = (_: any, page: number) => {
    setHistoryPage(page);
    fetchHistory(page);
  };

  const handleSave = async () => {
    setSaving(true);
    setError('');
    setSuccess('');
    try {
      await api.put('/backup-config', {
        mode,
        host: host.trim(),
        port,
        poll_interval: pollInterval,
        weekdays: [...weekdays].sort((a, b) => a - b).join(','),
        start_time: startTime,
        end_time: endTime,
        enabled,
      });
      setSuccess('Configuracao salva com sucesso');
      await fetchAll();
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao salvar configuracao');
    } finally {
      setSaving(false);
    }
  };

  const effectiveUrl = mode === 'producao' ? `https://${host}` : `http://${host}:${port}`;

  const estimateQueries = () => {
    try {
      const [sh, sm] = startTime.split(':').map(Number);
      const [eh, em] = endTime.split(':').map(Number);
      const windowSec = (eh * 60 + em - sh * 60 - sm) * 60;
      if (windowSec <= 0) return 0;
      return Math.max(1, Math.floor(windowSec / Math.max(pollInterval, 1)));
    } catch { return 0; }
  };

  const activeWeekdayLabels = [...weekdays]
    .sort((a, b) => a - b)
    .map(d => WEEKDAYS.find(w => w.value === d)?.label)
    .filter(Boolean)
    .join(', ');

  if (loading) {
    return (
      <PageContainer>
        <Box display="flex" justifyContent="center" alignItems="center" minHeight="300px">
          <CircularProgress />
        </Box>
      </PageContainer>
    );
  }

  // ====== RENDER ======
  return (
    <PageContainer>
      {/* Header */}
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" sx={{ fontWeight: 700, color: '#7A1E26', mb: 0.5 }}>
          Backup (Monitor)
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Configuracao do agente de backup e cadastro no Monitor central.
        </Typography>
      </Box>

      {success && <Alert severity="success" sx={{ mb: 2 }} onClose={() => setSuccess('')}>{success}</Alert>}
      {error && <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError('')}>{error}</Alert>}

      {/* Tabs */}
      <Tabs
        value={activeTab}
        onChange={(_, v) => setActiveTab(v)}
        sx={{ mb: 3, borderBottom: 1, borderColor: 'divider' }}
      >
        <Tab icon={<SpeedIcon />} iconPosition="start" label="Progresso" />
        <Tab icon={<DnsIcon />} iconPosition="start" label="Configuracao" />
        <Tab icon={<RegisterIcon />} iconPosition="start" label="Cadastro no Monitor" />
        <Tab icon={<OutputIcon />} iconPosition="start" label="Contrato de saida" />
      </Tabs>

      {/* ======================== TAB 0: PROGRESSO ======================== */}
      {activeTab === 0 && (
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
          {/* Status bar */}
          <Box sx={{ ...sectionSx, borderLeft: '4px solid', borderLeftColor: progress?.in_window ? 'success.main' : progress?.worker_active ? 'warning.main' : 'error.main' }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
              <StorageIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Progresso do backup (Monitor)
              </Typography>
            </Box>

            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap', mb: 2 }}>
              <Chip
                label={progress?.worker_active ? 'Worker ativo' : 'Worker inativo'}
                size="small"
                color={progress?.worker_active ? 'success' : 'error'}
                sx={{ fontWeight: 600 }}
              />
              <Typography variant="body2" color="text.secondary">
                poll {progress?.poll_interval}s ({progress?.poll_origin})
              </Typography>
              <Chip
                label={progress?.in_window ? 'dentro da janela' : 'fora da janela'}
                size="small"
                variant="outlined"
                sx={{ fontWeight: 600 }}
              />
            </Box>

            {/* Fase atual */}
            <Box sx={{ mb: 2 }}>
              <Typography variant="caption" sx={labelSx}>Fase atual</Typography>
              <Typography variant="body1" sx={{ fontWeight: 700 }}>{progress?.phase}</Typography>
              <Typography variant="body2" color="text.secondary">{progress?.phase_detail}</Typography>
            </Box>

            {/* Job e ultimo ciclo */}
            <Box sx={{ display: 'flex', gap: 4, flexWrap: 'wrap', mb: 2 }}>
              <Box>
                <Typography variant="caption" sx={labelSx}>Job no Monitor</Typography>
                <Typography variant="body2" sx={{ fontWeight: 600 }}>
                  {progress?.last_cycle?.job_id || '\u2014'}
                </Typography>
              </Box>
              <Box>
                <Typography variant="caption" sx={labelSx}>Ultimo ciclo</Typography>
                <Typography variant="body2">
                  {progress?.last_cycle?.started_at
                    ? `${formatDate(progress.last_cycle.started_at)} \u00B7 Resultado: ${progress.last_cycle.result || '-'}`
                    : '\u2014'}
                </Typography>
              </Box>
            </Box>

            {/* Motor e ferramentas */}
            <Divider sx={{ my: 1.5 }} />
            <Box sx={{ display: 'flex', gap: 4, flexWrap: 'wrap', alignItems: 'center' }}>
              <Box>
                <Typography variant="caption" sx={labelSx}>Motor DATABASE_URL</Typography>
                <Typography variant="body2" sx={{ fontWeight: 600 }}>{progress?.db_engine}</Typography>
              </Box>
              {progress?.dump_tools && Object.entries(progress.dump_tools).map(([tool, info]) => (
                <Box key={tool}>
                  <Typography variant="caption" sx={labelSx}>{tool}</Typography>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                    {info.available
                      ? <><CheckIcon sx={{ fontSize: 14, color: 'success.main' }} /> <Typography variant="body2" sx={{ fontFamily: 'monospace', fontSize: '0.75rem' }}>{info.path}</Typography></>
                      : <><CancelIcon sx={{ fontSize: 14, color: 'error.main' }} /> <Typography variant="body2" color="text.secondary">nao encontrado</Typography></>
                    }
                  </Box>
                </Box>
              ))}
              {progress?.python_drivers && (
                <Box>
                  <Typography variant="caption" sx={labelSx}>Drivers Python (app)</Typography>
                  <Typography variant="body2">
                    {Object.entries(progress.python_drivers).map(([drv, ok]) => (
                      <span key={drv}>{drv} {ok ? '\u2713' : '\u2717'}&nbsp;&nbsp;</span>
                    ))}
                  </Typography>
                </Box>
              )}
            </Box>
          </Box>

          {/* Historico recente */}
          <Box sx={sectionSx}>
            <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 2 }}>
              Historico recente
            </Typography>
            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow>
                    <TableCell>Inicio (UTC)</TableCell>
                    <TableCell>Resultado</TableCell>
                    <TableCell>Job</TableCell>
                    <TableCell>Poll</TableCell>
                    <TableCell>Upload</TableCell>
                    <TableCell>Tamanho</TableCell>
                    <TableCell>ms</TableCell>
                    <TableCell>Mensagem</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {history.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={8} align="center">
                        <Typography variant="body2" color="text.secondary" sx={{ py: 3 }}>
                          Nenhum registro de backup ainda.
                        </Typography>
                      </TableCell>
                    </TableRow>
                  ) : (
                    history.map(h => (
                      <TableRow key={h.id}>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>{formatDate(h.started_at)}</TableCell>
                        <TableCell>
                          <Chip
                            label={h.result === 'sem_job' ? 'Sem job pendente' : h.result}
                            size="small"
                            color={h.result === 'upload_ok' ? 'success' : h.result === 'error' ? 'error' : 'default'}
                            sx={{ fontWeight: 600, fontSize: '0.7rem' }}
                          />
                        </TableCell>
                        <TableCell>{h.job_id || '\u2014'}</TableCell>
                        <TableCell>{h.poll_status ?? '\u2014'}</TableCell>
                        <TableCell>{h.upload_status ?? '\u2014'}</TableCell>
                        <TableCell>{formatSize(h.file_size)}</TableCell>
                        <TableCell>{h.duration_ms ?? '\u2014'}</TableCell>
                        <TableCell sx={{ maxWidth: 300, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                          {h.message || '\u2014'}
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </TableContainer>
            {historyTotalPages > 1 && (
              <Box sx={{ display: 'flex', justifyContent: 'center', mt: 2 }}>
                <Pagination
                  count={historyTotalPages}
                  page={historyPage}
                  onChange={handleHistoryPage}
                  size="small"
                  color="primary"
                />
              </Box>
            )}
          </Box>
        </Box>
      )}

      {/* ======================== TAB 1: CONFIGURACAO ======================== */}
      {activeTab === 1 && (
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
          {/* Habilitado */}
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
            <Switch checked={enabled} onChange={(_, v) => setEnabled(v)} color="primary" />
            <Typography variant="body1" sx={{ fontWeight: 600 }}>
              Backup {enabled ? 'habilitado' : 'desabilitado'}
            </Typography>
          </Box>

          {/* Destino */}
          <Box sx={sectionSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
              <DnsIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>Destino do Monitor</Typography>
            </Box>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
              Defina o IP ou hostname onde a API do Monitor corre (modo desenvolvimento) ou a URL base HTTPS em producao.
              Clique em Salvar base para aplicar; esta definicao fica guardada na aplicacao e sobrepoe MONITOR_API_URL no env.docker quando preenchida e gravada.
            </Typography>

            <Typography variant="caption" sx={labelSx}>Modo de configuracao</Typography>
            <RadioGroup row value={mode} onChange={(e) => setMode(e.target.value)} sx={{ mb: 2 }}>
              <FormControlLabel value="desenvolvimento" control={<Radio size="small" />} label="Desenvolvimento: host/IP + porta" />
              <FormControlLabel value="producao" control={<Radio size="small" />} label="Producao: URL HTTPS" />
            </RadioGroup>

            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
              <TextField label="Host / IP do Monitor" value={host} onChange={(e) => setHost(e.target.value)} size="small" sx={{ flex: 2, minWidth: 200 }} />
              {mode === 'desenvolvimento' && (
                <TextField label="Porta" type="number" value={port} onChange={(e) => setPort(Number(e.target.value))} size="small" sx={{ flex: 1, minWidth: 100 }} inputProps={{ min: 1, max: 65535 }} />
              )}
            </Box>
          </Box>

          {/* Agendamento */}
          <Box sx={sectionSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
              <ScheduleIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>Agendamento</Typography>
            </Box>

            <TextField
              label="Intervalo entre consultas ao Monitor"
              type="number"
              value={pollInterval}
              onChange={(e) => setPollInterval(Math.max(15, Number(e.target.value)))}
              size="small"
              sx={{ mb: 2, width: 300 }}
              inputProps={{ min: 15 }}
              InputProps={{ endAdornment: <InputAdornment position="end">s</InputAdornment> }}
              helperText="Entre cada ciclo (verificar job, dump, envio). Aumentar reduz pedidos HTTP a rede; o minimo e 15s."
            />

            <Typography variant="caption" sx={{ ...labelSx, mb: 1 }}>Dias da semana para consultar o Monitor</Typography>
            <ToggleButtonGroup
              value={weekdays}
              onChange={(_, newVal) => { if (newVal.length > 0) setWeekdays(newVal); }}
              sx={{ mb: 2, flexWrap: 'wrap' }}
            >
              {WEEKDAYS.map(d => (
                <ToggleButton
                  key={d.value}
                  value={d.value}
                  sx={{
                    px: 2, py: 0.8,
                    '&.Mui-selected': {
                      backgroundColor: 'rgba(122,30,38,0.1)',
                      color: '#7A1E26',
                      fontWeight: 700,
                      '&:hover': { backgroundColor: 'rgba(122,30,38,0.18)' },
                    },
                  }}
                >
                  {d.label}
                </ToggleButton>
              ))}
            </ToggleButtonGroup>

            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
              <TextField label="Horario inicial" type="time" value={startTime} onChange={(e) => setStartTime(e.target.value)} size="small" sx={{ width: 160 }} InputLabelProps={{ shrink: true }} />
              <TextField label="Horario final" type="time" value={endTime} onChange={(e) => setEndTime(e.target.value)} size="small" sx={{ width: 160 }} InputLabelProps={{ shrink: true }} />
            </Box>
          </Box>

          {/* Resumo */}
          <Box sx={{ ...sectionSx, backgroundColor: 'rgba(122,30,38,0.03)' }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
              <InfoIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>Resumo</Typography>
            </Box>

            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap' }}>
              <Typography variant="body2" color="text.secondary">Janela ativa em</Typography>
              <Typography variant="body2" sx={{ fontWeight: 600 }}>
                {activeWeekdayLabels} ({startTime}-{endTime})
              </Typography>
              <Typography variant="body2" color="text.secondary">
                com ~{estimateQueries()} consulta(s) por janela no intervalo atual.
              </Typography>
            </Box>

            <Divider sx={{ my: 1.5 }} />

            <Box sx={{ display: 'flex', gap: 3, flexWrap: 'wrap' }}>
              <Box>
                <Typography variant="caption" color="text.secondary">Base efetiva</Typography>
                <Typography variant="body2" sx={{ fontWeight: 600, fontFamily: 'monospace' }}>{effectiveUrl}</Typography>
              </Box>
              <Box>
                <Typography variant="caption" color="text.secondary">Origem do intervalo</Typography>
                <Typography variant="body2" sx={{ fontWeight: 600 }}>interface</Typography>
              </Box>
              <Box>
                <Typography variant="caption" color="text.secondary">Origem da janela</Typography>
                <Typography variant="body2" sx={{ fontWeight: 600 }}>interface</Typography>
              </Box>
              <Box>
                <Typography variant="caption" color="text.secondary">Status</Typography>
                <Chip
                  label={progress?.in_window ? 'Dentro da janela' : 'Fora da janela agora'}
                  size="small"
                  color={progress?.in_window ? 'success' : 'default'}
                  sx={{ fontWeight: 600 }}
                />
              </Box>
            </Box>

            {config?.updated_at && (
              <>
                <Divider sx={{ my: 1.5 }} />
                <Typography variant="caption" color="text.secondary">
                  Atualizado em: {new Date(config.updated_at).toLocaleString('pt-BR')}
                </Typography>
              </>
            )}
          </Box>

          {/* Botao Salvar */}
          <Box sx={{ display: 'flex', justifyContent: 'flex-end' }}>
            <Button
              variant="contained"
              startIcon={saving ? <CircularProgress size={18} color="inherit" /> : <SaveIcon />}
              onClick={handleSave}
              disabled={saving}
              sx={{ px: 4 }}
            >
              {saving ? 'Salvando...' : 'Salvar base'}
            </Button>
          </Box>
        </Box>
      )}

      {/* ======================== TAB 2: CADASTRO NO MONITOR ======================== */}
      {activeTab === 2 && reference && (
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
          {/* Tabela de cadastro */}
          <Box sx={sectionSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
              <RegisterIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Cadastro no Monitor — Novo alvo de backup
              </Typography>
            </Box>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
              Espelho do formulario do sistema central (como na API de Ingestao).
              Preencha no Monitor o alvo de backup com os valores abaixo.
              A Chave do sistema do sistema monitorado deve ser a mesma da aba API de Ingestao: <strong>{reference.system_key}</strong>.
            </Typography>

            <TableContainer>
              <Table size="small">
                <TableHead>
                  <TableRow>
                    <TableCell sx={{ fontWeight: 700, width: '22%' }}>Campo no Monitor</TableCell>
                    <TableCell sx={{ fontWeight: 700, width: '30%' }}>Valor recomendado</TableCell>
                    <TableCell sx={{ fontWeight: 700 }}>Observacao tecnica</TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {reference.registration.map((row, i) => (
                    <TableRow key={i}>
                      <TableCell sx={{ fontWeight: 600, verticalAlign: 'top' }}>{row.field}</TableCell>
                      <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.8rem', verticalAlign: 'top' }}>{row.value}</TableCell>
                      <TableCell sx={{ color: 'text.secondary', fontSize: '0.8rem', verticalAlign: 'top' }}>{row.note}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          </Box>

          {/* Config JSON sugerido */}
          <Box sx={sectionSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
              <CodeIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Config JSON (opcional)
              </Typography>
            </Box>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
              Cole no campo Config JSON do alvo no Monitor. Contem apenas dicas; sem senhas.
            </Typography>
            <Box sx={codeBlockSx}>
              <Tooltip title="Copiar">
                <IconButton
                  size="small"
                  onClick={() => copyToClipboard(JSON.stringify(reference.config_json, null, 2))}
                  sx={{ position: 'absolute', top: 8, right: 8, color: 'rgba(255,255,255,0.5)', '&:hover': { color: '#fff' } }}
                >
                  <CopyIcon fontSize="small" />
                </IconButton>
              </Tooltip>
              {JSON.stringify(reference.config_json, null, 2)}
            </Box>
          </Box>

          {/* Notas */}
          <Box sx={sectionSx}>
            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1 }}>
              Relacao entre alvo de backup e agente
            </Typography>
            <Box component="ol" sx={{ pl: 2.5, '& li': { mb: 0.5, fontSize: '0.85rem', color: 'text.secondary' } }}>
              <li>A chave do sistema no alvo deve ser a mesma usada em systemKey na ingestao e na query ?systemKey= do poll.</li>
              <li>O header x-backup-agent-key deve coincidir com BACKUP_AGENT_SHARED_KEY no Monitor ou com a chave por alvo preenchida acima.</li>
              <li>Credenciais de banco (DATABASE_URL) nao sao enviadas ao Monitor; apenas o ficheiro dump no POST multipart.</li>
            </Box>
          </Box>
        </Box>
      )}

      {/* ======================== TAB 3: CONTRATO DE SAIDA ======================== */}
      {activeTab === 3 && reference && (
        <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
          {/* Contrato de saida (egress) */}
          <Box sx={sectionSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
              <OutputIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Contrato de saida (egress)
              </Typography>
            </Box>

            <Box sx={{ display: 'flex', gap: 4, flexWrap: 'wrap', mb: 2 }}>
              {[
                { label: 'Header do agente', value: reference.egress.poll_header },
                { label: 'Variavel local', value: reference.egress.poll_header_env_var },
                { label: 'Chave do sistema', value: reference.egress.system_key },
              ].map(item => (
                <Box key={item.label}>
                  <Typography variant="caption" sx={labelSx}>{item.label}</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 600, fontFamily: 'monospace' }}>{item.value}</Typography>
                </Box>
              ))}
            </Box>

            <Box sx={{ mb: 2 }}>
              <Typography variant="caption" sx={labelSx}>Poll (proximo job)</Typography>
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                <Typography variant="body2" sx={{ fontFamily: 'monospace', fontSize: '0.8rem', wordBreak: 'break-all' }}>
                  {reference.egress.poll_url}
                </Typography>
                <Tooltip title="Copiar">
                  <IconButton size="small" onClick={() => copyToClipboard(reference.egress.poll_url)}>
                    <CopyIcon fontSize="small" />
                  </IconButton>
                </Tooltip>
              </Box>
            </Box>

            <Box>
              <Typography variant="caption" sx={labelSx}>Upload</Typography>
              <Typography variant="body2" color="text.secondary">{reference.egress.upload_method}</Typography>
            </Box>

            <Divider sx={{ my: 2 }} />

            <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
              No servidor do Monitor (referencia): Deve coincidir com a chave configurada no Monitor (BACKUP_AGENT_SHARED_KEY ou chave por alvo).
              O Monitor nao recebe credenciais do banco do sistema monitorado.
            </Typography>

            <Box component="ul" sx={{ pl: 2, '& li': { mb: 0.5, fontSize: '0.82rem', color: 'text.secondary' } }}>
              <li>Instale pg_dump / mysqldump no host onde o agente roda.</li>
              <li>Credenciais do banco ficam so no sistema monitorado (env ou ficheiro restrito).</li>
              <li>No Monitor defina a Frequencia do alvo; no servidor monitorado agende o script com cron ou systemd timer (ex.: a cada 1-5 min).</li>
            </Box>
          </Box>

          {/* JSON de referencia do agente */}
          <Box sx={sectionSx}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 1 }}>
              <CodeIcon sx={{ color: '#7A1E26', fontSize: 20 }} />
              <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
                Cadastro / JSON de referencia (agente de backup)
              </Typography>
            </Box>
            <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
              Util ao documentar o sistema no Monitor
            </Typography>
            <Box sx={codeBlockSx}>
              <Tooltip title="Copiar">
                <IconButton
                  size="small"
                  onClick={() => copyToClipboard(JSON.stringify(reference.agent_reference_json, null, 2))}
                  sx={{ position: 'absolute', top: 8, right: 8, color: 'rgba(255,255,255,0.5)', '&:hover': { color: '#fff' } }}
                >
                  <CopyIcon fontSize="small" />
                </IconButton>
              </Tooltip>
              {JSON.stringify(reference.agent_reference_json, null, 2)}
            </Box>
          </Box>

          {/* Exemplos curl */}
          <Box sx={sectionSx}>
            <Typography variant="subtitle2" sx={{ fontWeight: 700, mb: 1.5 }}>Exemplos</Typography>

            <Typography variant="caption" sx={labelSx}>Obter proximo job</Typography>
            <Box sx={{ ...codeBlockSx, mb: 2 }}>
              <Tooltip title="Copiar">
                <IconButton
                  size="small"
                  onClick={() => copyToClipboard(reference.curl_poll)}
                  sx={{ position: 'absolute', top: 8, right: 8, color: 'rgba(255,255,255,0.5)', '&:hover': { color: '#fff' } }}
                >
                  <CopyIcon fontSize="small" />
                </IconButton>
              </Tooltip>
              {reference.curl_poll}
            </Box>

            <Typography variant="caption" sx={labelSx}>Enviar artifact</Typography>
            <Box sx={codeBlockSx}>
              <Tooltip title="Copiar">
                <IconButton
                  size="small"
                  onClick={() => copyToClipboard(reference.curl_upload)}
                  sx={{ position: 'absolute', top: 8, right: 8, color: 'rgba(255,255,255,0.5)', '&:hover': { color: '#fff' } }}
                >
                  <CopyIcon fontSize="small" />
                </IconButton>
              </Tooltip>
              {reference.curl_upload}
            </Box>
          </Box>
        </Box>
      )}
    </PageContainer>
  );
};

export default BackupConfig;
