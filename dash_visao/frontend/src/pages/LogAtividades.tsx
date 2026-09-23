import React, { useState, useEffect, useCallback } from 'react';
import {
  Typography,
  Box,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  CircularProgress,
  Chip,
  TablePagination,
  Collapse,
  Card,
  CardContent,
  Button,
  Tabs,
  Tab,
  Badge,
  IconButton,
  Switch,
  Alert,
  Tooltip,
  InputAdornment,
} from '@mui/material';
import {
  FilterList as FilterIcon,
  KeyboardArrowDown,
  KeyboardArrowUp,
  Settings as SettingsIcon,
  Send as SendIcon,
  CheckCircle as CheckCircleIcon,
  Error as ErrorIcon,
  Visibility,
  VisibilityOff,
  VpnKey as VpnKeyIcon,
  ContentCopy as ContentCopyIcon,
  Login as LoginIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

interface ActivityLogEntry {
  id: number;
  user_id: number | null;
  username: string | null;
  method: string;
  path: string;
  status_code: number | null;
  ip_address: string | null;
  user_agent: string | null;
  query_params: string | null;
  duration_ms: number | null;
  error_detail: string | null;
  created_at: string | null;
}

const METHOD_COLORS: Record<string, 'success' | 'info' | 'warning' | 'error' | 'default'> = {
  GET: 'info',
  POST: 'success',
  PUT: 'warning',
  PATCH: 'warning',
  DELETE: 'error',
};

const STATUS_LABELS: Record<number, string> = {
  400: 'Requisição incorreta',
  401: 'Usuário não logado ou sessão expirada',
  403: 'Acesso negado — sem permissão',
  404: 'Página ou recurso não encontrado',
  405: 'Operação não permitida nesta rota',
  409: 'Conflito — registro já existe',
  422: 'Dados enviados com formato inválido',
  429: 'Limite de requisições excedido',
  500: 'Falha interna do sistema',
  502: 'Servidor intermediário fora do ar',
  503: 'Sistema temporariamente indisponível',
  504: 'Servidor demorou demais para responder',
};

function formatDate(dateStr: string | null) {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('pt-BR');
}

function getStatusColor(code: number | null): 'success' | 'info' | 'warning' | 'error' | 'default' {
  if (!code) return 'default';
  if (code < 300) return 'success';
  if (code < 400) return 'info';
  if (code < 500) return 'warning';
  return 'error';
}

function parseErrorDetail(raw: string | null): string | null {
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw);
    if (parsed.detail) {
      if (typeof parsed.detail === 'string') return parsed.detail;
      return JSON.stringify(parsed.detail, null, 2);
    }
    return JSON.stringify(parsed, null, 2);
  } catch {
    return raw;
  }
}

function buildCopyText(log: ActivityLogEntry, detail: string | null): string {
  const lines = [
    `Data/Hora:  ${formatDate(log.created_at)}`,
    `Usuário:    ${log.username || '-'}`,
    `Método:     ${log.method}`,
    `Rota:       ${log.path}`,
    `Status:     ${log.status_code || '-'}${log.status_code && STATUS_LABELS[log.status_code] ? ` — ${STATUS_LABELS[log.status_code]}` : ''}`,
    `Duração:    ${log.duration_ms != null ? `${log.duration_ms}ms` : '-'}`,
    `IP:         ${log.ip_address || '-'}`,
  ];
  if (detail) {
    lines.push('', `Detalhe do erro:`, detail);
  }
  return lines.join('\n');
}

function ExpandableErrorRow({ log }: { log: ActivityLogEntry }) {
  const [open, setOpen] = useState(false);
  const [copied, setCopied] = useState(false);
  const detail = parseErrorDetail(log.error_detail);
  const hasDetail = !!detail;
  const statusLabel = detail || (log.status_code ? STATUS_LABELS[log.status_code] : null);

  const handleCopy = () => {
    navigator.clipboard.writeText(buildCopyText(log, detail)).then(() => {
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    });
  };

  return (
    <>
      <TableRow
        hover
        sx={{
          bgcolor: log.status_code && log.status_code >= 500
            ? 'error.main'
            : log.status_code && log.status_code >= 400
              ? 'warning.main'
              : undefined,
          '& td': {
            color: log.status_code && log.status_code >= 500
              ? 'error.contrastText'
              : undefined,
          },
          ...( (log.status_code && log.status_code >= 400) ? {
            bgcolor: log.status_code >= 500
              ? 'rgba(211,47,47,0.06)'
              : 'rgba(237,108,2,0.05)',
          } : {}),
        }}
      >
        <TableCell sx={{ width: 40, p: 0.5 }}>
          {hasDetail && (
            <IconButton size="small" onClick={() => setOpen(!open)}>
              {open ? <KeyboardArrowUp /> : <KeyboardArrowDown />}
            </IconButton>
          )}
        </TableCell>
        <TableCell>{formatDate(log.created_at)}</TableCell>
        <TableCell>{log.username || '-'}</TableCell>
        <TableCell>
          <Chip
            label={log.method}
            color={METHOD_COLORS[log.method] || 'default'}
            size="small"
            variant="outlined"
            sx={{ fontWeight: 600, fontFamily: 'monospace' }}
          />
        </TableCell>
        <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>
          {log.path}
        </TableCell>
        <TableCell>
          <Chip
            label={log.status_code || '-'}
            color={getStatusColor(log.status_code) as any}
            size="small"
            variant="filled"
            sx={{ fontWeight: 600, minWidth: 48 }}
          />
          {statusLabel && (
            <Typography variant="caption" color="text.secondary" sx={{ ml: 0.5, fontSize: '0.7rem' }}>
              {statusLabel}
            </Typography>
          )}
        </TableCell>
        <TableCell>
          {log.duration_ms != null ? `${log.duration_ms}ms` : '-'}
        </TableCell>
        <TableCell>{log.ip_address || '-'}</TableCell>
      </TableRow>
      {hasDetail && (
        <TableRow>
          <TableCell colSpan={8} sx={{ py: 0, borderBottom: open ? undefined : 'none' }}>
            <Collapse in={open} timeout="auto" unmountOnExit>
              <Box sx={{ py: 1.5, px: 2 }}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                  <Typography variant="subtitle2" color="error.main">
                    Detalhes do Erro
                  </Typography>
                  <Tooltip title={copied ? 'Copiado!' : 'Copiar dados do erro'}>
                    <Button
                      size="small"
                      variant="outlined"
                      color={copied ? 'success' : 'error'}
                      startIcon={copied ? <CheckCircleIcon sx={{ fontSize: 16 }} /> : <ContentCopyIcon sx={{ fontSize: 16 }} />}
                      onClick={handleCopy}
                      sx={{ fontSize: '0.7rem', py: 0.25, px: 1, minWidth: 0 }}
                    >
                      {copied ? 'Copiado' : 'Copiar'}
                    </Button>
                  </Tooltip>
                </Box>
                <Box
                  component="pre"
                  sx={{
                    bgcolor: 'rgba(211,47,47,0.04)',
                    p: 1.5,
                    borderRadius: 1,
                    fontSize: '0.75rem',
                    overflow: 'auto',
                    maxHeight: 300,
                    m: 0,
                    border: '1px solid',
                    borderColor: 'error.light',
                    color: 'error.dark',
                    whiteSpace: 'pre-wrap',
                    wordBreak: 'break-word',
                    userSelect: 'all',
                  }}
                >
{`Usuário:    ${log.username || '-'}
Data/Hora:  ${formatDate(log.created_at)}
Método:     ${log.method}
Rota:       ${log.path}
Status:     ${log.status_code || '-'}${log.status_code && STATUS_LABELS[log.status_code] ? ` — ${STATUS_LABELS[log.status_code]}` : ''}
Duração:    ${log.duration_ms != null ? `${log.duration_ms}ms` : '-'}
IP:         ${log.ip_address || '-'}

Detalhe:
${detail}`}
                </Box>
              </Box>
            </Collapse>
          </TableCell>
        </TableRow>
      )}
    </>
  );
}

// =============================================
// Monitoramento Central - Configuração
// =============================================
interface MonitorConfigData {
  id: number;
  mode: string;
  host: string;
  port: number;
  enabled: boolean;
  system_key: string;
  environment: string;
  ingest_key_configured: boolean;
  effective_url: string;
  updated_at: string | null;
}

interface MonitorReference {
  system_name: string;
  system_key: string;
  environment: string;
  effective_url: string;
  monitor_systems_url: string;
  frontend_url: string;
  ingest_key_configured: boolean;
  registration: { field: string; value: string; note: string }[];
  config_json: Record<string, unknown>;
}

function MonitorConfigCard() {
  const [config, setConfig] = useState<MonitorConfigData | null>(null);
  const [reference, setReference] = useState<MonitorReference | null>(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);
  const [editHost, setEditHost] = useState('');
  const [editPort, setEditPort] = useState('');
  const [editEnabled, setEditEnabled] = useState(true);
  const [editIngestKey, setEditIngestKey] = useState('');
  const [showIngestKey, setShowIngestKey] = useState(false);
  const [expanded, setExpanded] = useState(false);
  const [copiedJson, setCopiedJson] = useState(false);

  const fetchConfig = useCallback(async () => {
    try {
      setLoading(true);
      const [cfgResp, refResp] = await Promise.all([
        api.get('/monitor-config'),
        api.get('/monitor-config/reference').catch(() => null),
      ]);
      const data = cfgResp.data;
      setConfig(data);
      setEditHost(data.host);
      setEditPort(String(data.port));
      setEditEnabled(data.enabled);
      setEditIngestKey('');
      if (refResp) setReference(refResp.data);
    } catch {
      // Config ainda não existe, será criada na primeira chamada
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchConfig();
  }, [fetchConfig]);

  const handleSave = async () => {
    setSaving(true);
    setTestResult(null);
    try {
      const payload: Record<string, any> = {
        host: editHost.trim(),
        port: parseInt(editPort, 10),
        enabled: editEnabled,
      };
      if (editIngestKey) {
        payload.ingest_key = editIngestKey;
      }
      const resp = await api.put('/monitor-config', payload);
      setConfig(resp.data);
      setEditIngestKey('');
      const refResp = await api.get('/monitor-config/reference').catch(() => null);
      if (refResp) setReference(refResp.data);
    } catch (err: any) {
      setTestResult({ success: false, message: err?.response?.data?.detail || 'Erro ao salvar' });
    } finally {
      setSaving(false);
    }
  };

  const handleTest = async () => {
    setTesting(true);
    setTestResult(null);
    try {
      const resp = await api.post('/monitor-config/test');
      setTestResult(resp.data);
    } catch (err: any) {
      setTestResult({ success: false, message: err?.response?.data?.detail || 'Erro ao testar' });
    } finally {
      setTesting(false);
    }
  };

  const copyJson = async () => {
    if (!reference) return;
    try {
      await navigator.clipboard.writeText(JSON.stringify(reference.config_json, null, 2));
      setCopiedJson(true);
      window.setTimeout(() => setCopiedJson(false), 2000);
    } catch {
      // ignore
    }
  };

  const hasChanges = config && (
    editHost !== config.host ||
    editPort !== String(config.port) ||
    editEnabled !== config.enabled ||
    editIngestKey !== ''
  );

  if (loading) return null;

  return (
    <Card
      variant="outlined"
      sx={{
        mb: 2,
        borderLeft: '4px solid',
        borderLeftColor: config?.enabled ? 'info.main' : 'text.disabled',
        bgcolor: config?.enabled ? 'rgba(2,136,209,0.03)' : 'rgba(0,0,0,0.02)',
        borderColor: config?.enabled ? 'rgba(2,136,209,0.2)' : 'divider',
      }}
    >
      <CardContent sx={{ py: 1.5, '&:last-child': { pb: 1.5 } }}>
        {/* Header */}
        <Box
          sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', cursor: 'pointer' }}
          onClick={() => setExpanded(!expanded)}
        >
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <SettingsIcon sx={{ color: config?.enabled ? 'info.main' : 'text.disabled', fontSize: 20 }} />
            <Typography variant="subtitle2" fontWeight={600} color={config?.enabled ? 'info.main' : 'text.disabled'}>
              Destino Atual do Monitoramento Central
            </Typography>
            <Chip
              label={config?.enabled ? 'Ativo' : 'Inativo'}
              size="small"
              color={config?.enabled ? 'info' : 'default'}
              variant="outlined"
              sx={{ fontWeight: 600, fontSize: '0.7rem', height: 22 }}
            />
          </Box>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            {config && (
              <Typography variant="caption" color="text.secondary" sx={{ fontFamily: 'monospace' }}>
                {config.effective_url}
              </Typography>
            )}
            <IconButton size="small">
              {expanded ? <KeyboardArrowUp /> : <KeyboardArrowDown />}
            </IconButton>
          </Box>
        </Box>

        <Collapse in={expanded} timeout="auto">
          <Box sx={{ mt: 2 }}>
            {/* Subtitulo */}
            <Typography variant="caption" color="text.secondary" sx={{ mb: 1.5, display: 'block' }}>
              Configuracao temporaria editavel pela interface
            </Typography>

            {/* Modo + Toggle */}
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center', mb: 2 }}>
              <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
                <Typography variant="body2" fontWeight={500}>Habilitado:</Typography>
                <Switch
                  size="small"
                  checked={editEnabled}
                  onChange={(e) => setEditEnabled(e.target.checked)}
                  color="info"
                />
              </Box>
              <Chip
                label="Modo de configuracao: Desenvolvimento"
                size="small"
                variant="outlined"
                color="info"
                sx={{ fontSize: '0.75rem' }}
              />
            </Box>

            {/* Campos de configuracao */}
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'flex-end', mb: 2 }}>
              <TextField
                label="Host/IP de desenvolvimento"
                size="small"
                value={editHost}
                onChange={(e) => setEditHost(e.target.value)}
                sx={{ minWidth: 220 }}
                placeholder="192.168.100.112"
              />
              <TextField
                label="Porta"
                size="small"
                value={editPort}
                onChange={(e) => setEditPort(e.target.value)}
                sx={{ width: 100 }}
                placeholder="9000"
                type="number"
              />
              <TextField
                label="x-ingest-key"
                size="small"
                type={showIngestKey ? 'text' : 'password'}
                value={editIngestKey}
                onChange={(e) => setEditIngestKey(e.target.value)}
                sx={{ minWidth: 240 }}
                placeholder={config?.ingest_key_configured ? '(chave configurada)' : 'Chave de autenticacao'}
                InputProps={{
                  startAdornment: (
                    <InputAdornment position="start">
                      <VpnKeyIcon sx={{ fontSize: 18, color: config?.ingest_key_configured ? 'success.main' : 'text.disabled' }} />
                    </InputAdornment>
                  ),
                  endAdornment: (
                    <InputAdornment position="end">
                      <IconButton size="small" onClick={() => setShowIngestKey(!showIngestKey)} edge="end">
                        {showIngestKey ? <VisibilityOff fontSize="small" /> : <Visibility fontSize="small" />}
                      </IconButton>
                    </InputAdornment>
                  ),
                }}
              />
              <Button
                variant="contained"
                size="small"
                onClick={handleSave}
                disabled={saving || !hasChanges}
                color="info"
              >
                {saving ? 'Salvando...' : 'Salvar'}
              </Button>
              <Tooltip title="Envia um evento de teste para verificar a conexao">
                <Button
                  variant="outlined"
                  size="small"
                  onClick={handleTest}
                  disabled={testing || !config?.enabled}
                  startIcon={testing ? <CircularProgress size={14} /> : <SendIcon />}
                  color="info"
                >
                  Testar Conexao
                </Button>
              </Tooltip>
            </Box>

            {/* Destino efetivo */}
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
              <Typography variant="body2" color="text.secondary">
                <strong>Destino efetivo:</strong>{' '}
                <Typography component="span" variant="body2" sx={{ fontFamily: 'monospace', color: 'info.main' }}>
                  http://{editHost || '...'}:{editPort || '...'}/ingest/push-http
                </Typography>
              </Typography>
            </Box>

            {/* Info de origem */}
            <Box sx={{ display: 'flex', gap: 3, mt: 1, flexWrap: 'wrap', alignItems: 'center' }}>
              <Typography variant="caption" color="text.secondary">
                Origem da configuracao: <strong>interface</strong>
              </Typography>
              <Typography variant="caption" color={config?.ingest_key_configured ? 'success.main' : 'warning.main'}>
                x-ingest-key: <strong>{config?.ingest_key_configured ? 'configurada' : 'nao configurada'}</strong>
              </Typography>
              {config?.updated_at && (
                <Typography variant="caption" color="text.secondary">
                  Atualizado em: <strong>{new Date(config.updated_at).toLocaleString('pt-BR')}</strong>
                </Typography>
              )}
            </Box>

            <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 1, fontStyle: 'italic' }}>
              Em modo desenvolvimento, o sistema monta automaticamente http://host:porta/ingest/push-http
            </Typography>

            {/* Resultado do teste */}
            {testResult && (
              <Alert
                severity={testResult.success ? 'success' : 'error'}
                icon={testResult.success ? <CheckCircleIcon /> : <ErrorIcon />}
                sx={{ mt: 1.5 }}
                onClose={() => setTestResult(null)}
              >
                {testResult.message}
              </Alert>
            )}

            {/* Cadastro no Monitor */}
            {reference && (
              <Box
                sx={{
                  mt: 2.5,
                  pt: 2,
                  borderTop: '1px solid',
                  borderColor: 'divider',
                }}
              >
                <Typography variant="subtitle2" fontWeight={700} sx={{ mb: 0.5 }}>
                  Cadastro no Monitor de Sistemas
                </Typography>
                <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mb: 1.5 }}>
                  Use estes valores em{' '}
                  <Typography
                    component="a"
                    href={reference.monitor_systems_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    variant="caption"
                    sx={{ color: 'info.main', fontWeight: 700 }}
                  >
                    {reference.monitor_systems_url}
                  </Typography>
                  {' '}para cadastrar o Visão. A chave do sistema deve ser{' '}
                  <strong>{reference.system_key}</strong>.
                </Typography>

                <TableContainer sx={{ mb: 2, border: '1px solid', borderColor: 'divider', borderRadius: 1 }}>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell sx={{ fontWeight: 700, width: '28%' }}>Campo no Monitor</TableCell>
                        <TableCell sx={{ fontWeight: 700, width: '32%' }}>Valor</TableCell>
                        <TableCell sx={{ fontWeight: 700 }}>Observação</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {reference.registration.map((row) => (
                        <TableRow key={row.field}>
                          <TableCell sx={{ fontWeight: 600, verticalAlign: 'top' }}>{row.field}</TableCell>
                          <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.8rem', verticalAlign: 'top' }}>
                            {row.value}
                          </TableCell>
                          <TableCell sx={{ color: 'text.secondary', fontSize: '0.8rem', verticalAlign: 'top' }}>
                            {row.note}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>

                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1 }}>
                  <Typography variant="subtitle2" fontWeight={700}>
                    Config JSON
                  </Typography>
                  <Button
                    size="small"
                    variant="outlined"
                    color="info"
                    startIcon={copiedJson ? <CheckCircleIcon sx={{ fontSize: 16 }} /> : <ContentCopyIcon sx={{ fontSize: 16 }} />}
                    onClick={copyJson}
                  >
                    {copiedJson ? 'Copiado' : 'Copiar JSON'}
                  </Button>
                </Box>
                <Box
                  component="pre"
                  sx={{
                    m: 0,
                    p: 1.5,
                    borderRadius: 1,
                    bgcolor: '#0A0506',
                    color: '#E8E8E8',
                    fontSize: '0.75rem',
                    fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
                    overflow: 'auto',
                    maxHeight: 280,
                  }}
                >
                  {JSON.stringify(reference.config_json, null, 2)}
                </Box>
              </Box>
            )}
          </Box>
        </Collapse>
      </CardContent>
    </Card>
  );
}

// =============================================
// Logins do Dia
// =============================================
interface DailyLoginEntry {
  username: string;
  first_login: string;
  last_login: string;
  login_count: number;
  ip_address: string | null;
}

function DailyLoginsPanel() {
  const [logins, setLogins] = useState<DailyLoginEntry[]>([]);
  const [totalUsers, setTotalUsers] = useState(0);
  const [loading, setLoading] = useState(true);
  const [selectedDate, setSelectedDate] = useState(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
  });

  const fetchLogins = useCallback(async () => {
    try {
      setLoading(true);
      const resp = await api.get('/activity-logs/daily-logins', {
        params: { target_date: selectedDate },
      });
      setLogins(resp.data.logins);
      setTotalUsers(resp.data.total_users);
    } catch (error) {
      console.error('Erro ao carregar logins do dia:', error);
    } finally {
      setLoading(false);
    }
  }, [selectedDate]);

  useEffect(() => {
    fetchLogins();
  }, [fetchLogins]);

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mb: 2, flexWrap: 'wrap' }}>
        <TextField
          label="Data"
          type="date"
          size="small"
          value={selectedDate}
          onChange={(e) => setSelectedDate(e.target.value)}
          InputLabelProps={{ shrink: true }}
          sx={{ minWidth: 170 }}
        />
        <Chip
          icon={<LoginIcon sx={{ fontSize: 16 }} />}
          label={`${totalUsers} usuário${totalUsers !== 1 ? 's' : ''} logado${totalUsers !== 1 ? 's' : ''}`}
          color="primary"
          variant="outlined"
          sx={{ fontWeight: 600 }}
        />
      </Box>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      ) : logins.length === 0 ? (
        <Box sx={{ textAlign: 'center', py: 6 }}>
          <Typography color="text.secondary">Nenhum login registrado nesta data</Typography>
        </Box>
      ) : (
        <TableContainer>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell sx={{ fontWeight: 'bold' }}>#</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 180 }}>Usuário</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 160 }}>Primeiro Login</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 160 }}>Último Login</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }} align="center">Logins</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>IP</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {logins.map((entry, idx) => (
                <TableRow key={entry.username} hover>
                  <TableCell>{idx + 1}</TableCell>
                  <TableCell sx={{ fontWeight: 600 }}>{entry.username}</TableCell>
                  <TableCell>{formatDate(entry.first_login)}</TableCell>
                  <TableCell>{formatDate(entry.last_login)}</TableCell>
                  <TableCell align="center">
                    <Chip
                      label={entry.login_count}
                      size="small"
                      color={entry.login_count > 1 ? 'info' : 'default'}
                      variant="filled"
                      sx={{ fontWeight: 600, minWidth: 32 }}
                    />
                  </TableCell>
                  <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>
                    {entry.ip_address || '-'}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
}

// =============================================
// Uso de Funcionalidades
// =============================================
interface FeatureUserEntry {
  username: string;
  acessos: number;
  ultimo_acesso: string | null;
}

interface FeatureUsageEntry {
  nome: string;
  total_acessos: number;
  usuarios_unicos: number;
  primeiro_acesso: string | null;
  ultimo_acesso: string | null;
  usuarios: FeatureUserEntry[];
}

function FeatureUsagePanel() {
  const [data, setData] = useState<FeatureUsageEntry[]>([]);
  const [loading, setLoading] = useState(false);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [searched, setSearched] = useState(false);

  const fetchUsage = useCallback(async () => {
    setLoading(true);
    try {
      const params: Record<string, string> = {};
      if (startDate) params.start_date = startDate;
      if (endDate) params.end_date = endDate;
      const resp = await api.get('/activity-logs/feature-usage', { params });
      setData(resp.data.funcionalidades);
      setSearched(true);
    } catch (error) {
      console.error('Erro ao carregar uso de funcionalidades:', error);
    } finally {
      setLoading(false);
    }
  }, [startDate, endDate]);

  useEffect(() => {
    fetchUsage();
  }, []);

  const [expanded, setExpanded] = useState<string | null>(null);
  const maxAcessos = data.length > 0 ? Math.max(...data.map(d => d.total_acessos)) : 1;

  const toggleExpand = (nome: string) => {
    setExpanded(expanded === nome ? null : nome);
  };

  return (
    <Box>
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mb: 3, flexWrap: 'wrap' }}>
        <TextField
          label="Data início"
          type="date"
          size="small"
          value={startDate}
          onChange={(e) => setStartDate(e.target.value)}
          InputLabelProps={{ shrink: true }}
          sx={{ minWidth: 160 }}
        />
        <TextField
          label="Data fim"
          type="date"
          size="small"
          value={endDate}
          onChange={(e) => setEndDate(e.target.value)}
          InputLabelProps={{ shrink: true }}
          sx={{ minWidth: 160 }}
        />
        <Button variant="contained" size="small" onClick={fetchUsage} disabled={loading}>
          Filtrar
        </Button>
      </Box>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      ) : searched && data.length === 0 ? (
        <Box sx={{ textAlign: 'center', py: 6 }}>
          <Typography color="text.secondary">Nenhum dado de uso encontrado no período</Typography>
        </Box>
      ) : (
        <TableContainer>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell sx={{ width: 40 }} />
                <TableCell sx={{ fontWeight: 'bold', width: 40 }}>#</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 200 }}>Funcionalidade</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 300 }}>Acessos</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }} align="center">Total</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }} align="center">Usuários</TableCell>
                <TableCell sx={{ fontWeight: 'bold', minWidth: 150 }}>Último Acesso</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {data.map((item, idx) => {
                const pct = (item.total_acessos / maxAcessos) * 100;
                const isOpen = expanded === item.nome;
                return (
                  <React.Fragment key={item.nome}>
                    <TableRow hover sx={{ cursor: 'pointer' }} onClick={() => toggleExpand(item.nome)}>
                      <TableCell sx={{ p: 0.5 }}>
                        <IconButton size="small">
                          {isOpen ? <KeyboardArrowUp /> : <KeyboardArrowDown />}
                        </IconButton>
                      </TableCell>
                      <TableCell>{idx + 1}</TableCell>
                      <TableCell sx={{ fontWeight: 600 }}>{item.nome}</TableCell>
                      <TableCell>
                        <Box sx={{ flexGrow: 1, bgcolor: 'grey.100', borderRadius: 1, height: 20, overflow: 'hidden' }}>
                          <Box
                            sx={{
                              width: `${pct}%`,
                              height: '100%',
                              bgcolor: idx === 0 ? 'primary.main' : idx < 3 ? 'primary.light' : 'grey.400',
                              borderRadius: 1,
                              transition: 'width 0.5s ease',
                            }}
                          />
                        </Box>
                      </TableCell>
                      <TableCell align="center">
                        <Chip
                          label={item.total_acessos.toLocaleString('pt-BR')}
                          size="small"
                          color={idx === 0 ? 'primary' : 'default'}
                          variant={idx < 3 ? 'filled' : 'outlined'}
                          sx={{ fontWeight: 600 }}
                        />
                      </TableCell>
                      <TableCell align="center">
                        <Chip
                          label={item.usuarios_unicos}
                          size="small"
                          variant="outlined"
                          sx={{ fontWeight: 600 }}
                        />
                      </TableCell>
                      <TableCell sx={{ fontSize: '0.8rem' }}>
                        {item.ultimo_acesso ? new Date(item.ultimo_acesso).toLocaleString('pt-BR') : '-'}
                      </TableCell>
                    </TableRow>
                    <TableRow>
                      <TableCell colSpan={7} sx={{ py: 0, borderBottom: isOpen ? undefined : 'none' }}>
                        <Collapse in={isOpen} timeout="auto" unmountOnExit>
                          <Box sx={{ py: 1.5, px: 4 }}>
                            <Typography variant="subtitle2" sx={{ mb: 1 }}>
                              Usuários — {item.nome}
                            </Typography>
                            <Table size="small">
                              <TableHead>
                                <TableRow>
                                  <TableCell sx={{ fontWeight: 'bold' }}>Usuário</TableCell>
                                  <TableCell sx={{ fontWeight: 'bold' }} align="center">Acessos</TableCell>
                                  <TableCell sx={{ fontWeight: 'bold' }}>Último Acesso</TableCell>
                                </TableRow>
                              </TableHead>
                              <TableBody>
                                {(item.usuarios || []).map((u) => (
                                  <TableRow key={u.username} hover>
                                    <TableCell sx={{ fontWeight: 500 }}>{u.username}</TableCell>
                                    <TableCell align="center">
                                      <Chip label={u.acessos.toLocaleString('pt-BR')} size="small" variant="outlined" sx={{ fontWeight: 600 }} />
                                    </TableCell>
                                    <TableCell sx={{ fontSize: '0.8rem' }}>
                                      {u.ultimo_acesso ? new Date(u.ultimo_acesso).toLocaleString('pt-BR') : '-'}
                                    </TableCell>
                                  </TableRow>
                                ))}
                              </TableBody>
                            </Table>
                          </Box>
                        </Collapse>
                      </TableCell>
                    </TableRow>
                  </React.Fragment>
                );
              })}
            </TableBody>
          </Table>
        </TableContainer>
      )}
    </Box>
  );
}

export default function LogAtividades() {
  const [logs, setLogs] = useState<ActivityLogEntry[]>([]);
  const [total, setTotal] = useState(0);
  const [totalErrors, setTotalErrors] = useState(0);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(25);
  const [showFilters, setShowFilters] = useState(false);
  const [tab, setTab] = useState(0); // 0 = Logins do Dia, 1 = Uso Funcionalidades, 2 = Todas, 3 = Falhas (5xx), 4 = Erros (4xx)

  // Filtros
  const [filterUsername, setFilterUsername] = useState('');
  const [filterMethod, setFilterMethod] = useState('');
  const [filterPath, setFilterPath] = useState('');
  const [filterStartDate, setFilterStartDate] = useState('');
  const [filterEndDate, setFilterEndDate] = useState('');

  const fetchLogs = async () => {
    try {
      setLoading(true);
      const params: Record<string, string | number> = {
        page: page + 1,
        per_page: rowsPerPage,
      };
      if (filterUsername) params.username = filterUsername;
      if (filterMethod) params.method = filterMethod;
      if (filterPath) params.path = filterPath;
      if (filterStartDate) params.start_date = filterStartDate;
      if (filterEndDate) params.end_date = filterEndDate;

      if (tab === 3) params.status_filter = 'server_errors';
      else if (tab === 4) params.status_filter = 'client_errors';

      const response = await api.get('/activity-logs', { params });
      setLogs(response.data.logs);
      setTotal(response.data.total);
      setTotalErrors(response.data.total_errors ?? 0);
    } catch (error) {
      console.error('Erro ao carregar log de atividades:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (tab >= 2) fetchLogs();
  }, [page, rowsPerPage, tab]);

  const handleFilter = () => {
    setPage(0);
    fetchLogs();
  };

  const handleClearFilters = () => {
    setFilterUsername('');
    setFilterMethod('');
    setFilterPath('');
    setFilterStartDate('');
    setFilterEndDate('');
    setPage(0);
    setTimeout(fetchLogs, 0);
  };

  const handleTabChange = (_: React.SyntheticEvent, newValue: number) => {
    setTab(newValue);
    setPage(0);
  };

  const isErrorTab = tab === 3 || tab === 4;
  const colCount = isErrorTab ? 8 : 7;

  return (
    <PageContainer>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Box>
          <Typography variant="h5" component="h1" fontWeight={600}>
            Log de Atividades
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Registro de todas as requisições ao sistema
          </Typography>
        </Box>
        {tab >= 2 && (
          <Button
            variant="outlined"
            startIcon={<FilterIcon />}
            onClick={() => setShowFilters(!showFilters)}
            size="small"
          >
            Filtros
          </Button>
        )}
      </Box>

      <Tabs
        value={tab}
        onChange={handleTabChange}
        sx={{ mb: 2, borderBottom: 1, borderColor: 'divider' }}
      >
        <Tab label="Logins do Dia" />
        <Tab label="Uso de Funcionalidades" />
        <Tab label="Todas as Atividades" />
        <Tab
          label={
            <Badge badgeContent={totalErrors > 0 ? '!' : undefined} color="error" variant="dot">
              <span>Falhas do Sistema</span>
            </Badge>
          }
        />
        <Tab
          label={
            <Badge badgeContent={totalErrors > 0 ? '!' : undefined} color="warning" variant="dot">
              <span>Erros de Acesso</span>
            </Badge>
          }
        />
      </Tabs>

      {tab === 0 ? (
        <DailyLoginsPanel />
      ) : tab === 1 ? (
        <FeatureUsagePanel />
      ) : (
      <>
      {/* Monitoramento Central */}
      <MonitorConfigCard />

      <Collapse in={showFilters}>
        <Card variant="outlined" sx={{ mb: 2 }}>
          <CardContent sx={{ py: 1.5, '&:last-child': { pb: 1.5 } }}>
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
              <TextField
                label="Usuário"
                size="small"
                value={filterUsername}
                onChange={(e) => setFilterUsername(e.target.value)}
                sx={{ minWidth: 150 }}
              />
              <FormControl size="small" sx={{ minWidth: 120 }}>
                <InputLabel>Método</InputLabel>
                <Select
                  value={filterMethod}
                  label="Método"
                  onChange={(e) => setFilterMethod(e.target.value)}
                >
                  <MenuItem value="">Todos</MenuItem>
                  <MenuItem value="GET">GET</MenuItem>
                  <MenuItem value="POST">POST</MenuItem>
                  <MenuItem value="PUT">PUT</MenuItem>
                  <MenuItem value="PATCH">PATCH</MenuItem>
                  <MenuItem value="DELETE">DELETE</MenuItem>
                </Select>
              </FormControl>
              <TextField
                label="Rota"
                size="small"
                value={filterPath}
                onChange={(e) => setFilterPath(e.target.value)}
                placeholder="Ex: /users"
                sx={{ minWidth: 150 }}
              />
              <TextField
                label="Data início"
                type="date"
                size="small"
                value={filterStartDate}
                onChange={(e) => setFilterStartDate(e.target.value)}
                InputLabelProps={{ shrink: true }}
                sx={{ minWidth: 150 }}
              />
              <TextField
                label="Data fim"
                type="date"
                size="small"
                value={filterEndDate}
                onChange={(e) => setFilterEndDate(e.target.value)}
                InputLabelProps={{ shrink: true }}
                sx={{ minWidth: 150 }}
              />
              <Button variant="contained" size="small" onClick={handleFilter}>
                Buscar
              </Button>
              <Button variant="text" size="small" onClick={handleClearFilters}>
                Limpar
              </Button>
            </Box>
          </CardContent>
        </Card>
      </Collapse>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      ) : (
        <>
          <TableContainer sx={{ maxHeight: 'calc(100vh - 380px)' }}>
            <Table stickyHeader size="small">
              <TableHead>
                <TableRow>
                  {isErrorTab && <TableCell sx={{ width: 40 }} />}
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 160 }}>Data/Hora</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>Usuário</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }}>Método</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 250 }}>Rota</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }}>Status</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 100 }}>Duração</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>IP</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {logs.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={colCount} align="center" sx={{ py: 4 }}>
                      <Typography color="text.secondary">
                        {isErrorTab
                          ? 'Nenhum erro ou falha registrado'
                          : 'Nenhuma atividade registrada'}
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : isErrorTab ? (
                  logs.map((log) => <ExpandableErrorRow key={log.id} log={log} />)
                ) : (
                  logs.map((log) => (
                    <TableRow key={log.id} hover>
                      <TableCell>{formatDate(log.created_at)}</TableCell>
                      <TableCell>{log.username || '-'}</TableCell>
                      <TableCell>
                        <Chip
                          label={log.method}
                          color={METHOD_COLORS[log.method] || 'default'}
                          size="small"
                          variant="outlined"
                          sx={{ fontWeight: 600, fontFamily: 'monospace' }}
                        />
                      </TableCell>
                      <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>
                        {log.path}
                      </TableCell>
                      <TableCell>
                        <Chip
                          label={log.status_code || '-'}
                          color={getStatusColor(log.status_code) as any}
                          size="small"
                          variant="filled"
                          sx={{ fontWeight: 600, minWidth: 48 }}
                        />
                      </TableCell>
                      <TableCell>
                        {log.duration_ms != null ? `${log.duration_ms}ms` : '-'}
                      </TableCell>
                      <TableCell>{log.ip_address || '-'}</TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={total}
            page={page}
            onPageChange={(_, newPage) => setPage(newPage)}
            rowsPerPage={rowsPerPage}
            onRowsPerPageChange={(e) => {
              setRowsPerPage(parseInt(e.target.value, 10));
              setPage(0);
            }}
            rowsPerPageOptions={[10, 25, 50, 100]}
            labelRowsPerPage="Linhas por página:"
            labelDisplayedRows={({ from, to, count }) => `${from}-${to} de ${count}`}
          />
        </>
      )}
      </>
      )}
    </PageContainer>
  );
}
