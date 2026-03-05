import React, { useState, useEffect, useCallback } from 'react';
import {
  Container, Paper, Typography, Box, Card, CardContent, Chip, Button,
  Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  TextField, Dialog, DialogTitle, DialogContent, DialogActions,
  CircularProgress, Alert, FormControl, InputLabel, Select, MenuItem,
  Checkbox, FormControlLabel, IconButton, Tooltip, Divider,
} from '@mui/material';
import {
  Refresh as RefreshIcon,
  Visibility as VisibilityIcon,
  CheckCircle as CheckCircleIcon,
  Cancel as CancelIcon,
  HelpOutline as HelpIcon,
  Edit as EditIcon,
  PlayArrow as PlayArrowIcon,
} from '@mui/icons-material';
import {
  getAdminStats, getAdminRequests, analyzeRequest, approveRequest,
  rejectRequest, clarifyRequest, modifyRequest,
  UserRequest, UserRequestStats,
} from '../services/userRequestService';
import api from '../services/api';

const statusMap: Record<string, { label: string; color: 'default' | 'warning' | 'info' | 'success' | 'error' }> = {
  pendente: { label: 'Pendente', color: 'warning' },
  em_analise: { label: 'Em Análise', color: 'info' },
  aprovado: { label: 'Aprovado', color: 'success' },
  rejeitado: { label: 'Rejeitado', color: 'error' },
  esclarecimento: { label: 'Esclarecimento', color: 'default' },
};

interface RoleOption {
  id: number;
  name: string;
  description: string;
}

const AdminSolicitacoes: React.FC = () => {
  const [stats, setStats] = useState<UserRequestStats | null>(null);
  const [requests, setRequests] = useState<UserRequest[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');
  const [origemFilter, setOrigemFilter] = useState('');
  const [search, setSearch] = useState('');
  const [error, setError] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  // Detail dialog
  const [detailOpen, setDetailOpen] = useState(false);
  const [selectedReq, setSelectedReq] = useState<UserRequest | null>(null);
  const [adminNotes, setAdminNotes] = useState('');
  const [itemApprovals, setItemApprovals] = useState<Record<number, boolean>>({});

  // Approve dialog
  const [approveOpen, setApproveOpen] = useState(false);
  const [approveUsername, setApproveUsername] = useState('');
  const [approvePassword, setApprovePassword] = useState('');
  const [approveRoleIds, setApproveRoleIds] = useState<number[]>([]);
  const [roles, setRoles] = useState<RoleOption[]>([]);

  // Reject dialog
  const [rejectOpen, setRejectOpen] = useState(false);
  const [rejectReason, setRejectReason] = useState('');

  // Clarify dialog
  const [clarifyOpen, setClarifyOpen] = useState(false);
  const [clarifyMessage, setClarifyMessage] = useState('');

  const [actionLoading, setActionLoading] = useState(false);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const [statsData, reqData] = await Promise.all([
        getAdminStats(),
        getAdminRequests({
          status_filter: statusFilter || undefined,
          origem_tipo: origemFilter || undefined,
          search: search || undefined,
        }),
      ]);
      setStats(statsData);
      setRequests(reqData.requests);
      setTotal(reqData.total);
    } catch {
      setError('Erro ao carregar dados');
    } finally {
      setLoading(false);
    }
  }, [statusFilter, origemFilter, search]);

  useEffect(() => { loadData(); }, [loadData]);

  useEffect(() => {
    // Load roles for approve dialog
    api.get('/users/roles').then(res => setRoles(res.data)).catch(() => {});
  }, []);

  const openDetail = (req: UserRequest) => {
    setSelectedReq(req);
    setAdminNotes(req.admin_notes || '');
    const approvals: Record<number, boolean> = {};
    req.items.forEach(item => { approvals[item.id] = item.approved; });
    setItemApprovals(approvals);
    setDetailOpen(true);
  };

  const handleAnalyze = async () => {
    if (!selectedReq) return;
    setActionLoading(true);
    try {
      await analyzeRequest(selectedReq.id);
      setSuccessMsg('Solicitação movida para análise');
      setDetailOpen(false);
      loadData();
    } catch (err: any) {
      setError(err?.response?.data?.detail || 'Erro ao analisar');
    } finally {
      setActionLoading(false);
    }
  };

  const handleModify = async () => {
    if (!selectedReq) return;
    setActionLoading(true);
    try {
      await modifyRequest(selectedReq.id, {
        items: Object.entries(itemApprovals).map(([id, approved]) => ({
          item_id: parseInt(id),
          approved,
        })),
        admin_notes: adminNotes || undefined,
      });
      setSuccessMsg('Itens modificados com sucesso');
      setDetailOpen(false);
      loadData();
    } catch (err: any) {
      setError(err?.response?.data?.detail || 'Erro ao modificar');
    } finally {
      setActionLoading(false);
    }
  };

  const openApproveDialog = () => {
    if (!selectedReq) return;
    const suggestedUsername = selectedReq.email.split('@')[0].replace(/[^a-zA-Z0-9._-]/g, '');
    setApproveUsername(suggestedUsername);
    setApprovePassword('');
    setApproveRoleIds([]);
    setApproveOpen(true);
  };

  const handleApprove = async () => {
    if (!selectedReq) return;
    setActionLoading(true);
    try {
      await approveRequest(selectedReq.id, {
        username: approveUsername,
        password: approvePassword,
        role_ids: approveRoleIds,
        admin_notes: adminNotes || undefined,
      });
      setSuccessMsg('Solicitação aprovada e usuário criado');
      setApproveOpen(false);
      setDetailOpen(false);
      loadData();
    } catch (err: any) {
      setError(err?.response?.data?.detail || 'Erro ao aprovar');
    } finally {
      setActionLoading(false);
    }
  };

  const openRejectDialog = () => {
    setRejectReason('');
    setRejectOpen(true);
  };

  const handleReject = async () => {
    if (!selectedReq) return;
    setActionLoading(true);
    try {
      await rejectRequest(selectedReq.id, {
        reject_reason: rejectReason,
        admin_notes: adminNotes || undefined,
      });
      setSuccessMsg('Solicitação rejeitada');
      setRejectOpen(false);
      setDetailOpen(false);
      loadData();
    } catch (err: any) {
      setError(err?.response?.data?.detail || 'Erro ao rejeitar');
    } finally {
      setActionLoading(false);
    }
  };

  const openClarifyDialog = () => {
    setClarifyMessage('');
    setClarifyOpen(true);
  };

  const handleClarify = async () => {
    if (!selectedReq) return;
    setActionLoading(true);
    try {
      await clarifyRequest(selectedReq.id, {
        clarification_message: clarifyMessage,
        admin_notes: adminNotes || undefined,
      });
      setSuccessMsg('Pedido de esclarecimento enviado');
      setClarifyOpen(false);
      setDetailOpen(false);
      loadData();
    } catch (err: any) {
      setError(err?.response?.data?.detail || 'Erro ao pedir esclarecimento');
    } finally {
      setActionLoading(false);
    }
  };

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  };

  return (
    <Container maxWidth="lg" sx={{ py: 3 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5">Solicitações de Usuário</Typography>
        <Button startIcon={<RefreshIcon />} onClick={loadData} variant="outlined" size="small">
          Atualizar
        </Button>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError('')}>{error}</Alert>}
      {successMsg && <Alert severity="success" sx={{ mb: 2 }} onClose={() => setSuccessMsg('')}>{successMsg}</Alert>}

      {/* Stats cards */}
      {stats && (
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: 'repeat(2, 1fr)', md: 'repeat(5, 1fr)' }, gap: 2, mb: 3 }}>
          {([
            { key: 'pendente', label: 'Pendentes', color: '#ed6c02' },
            { key: 'em_analise', label: 'Em Análise', color: '#0288d1' },
            { key: 'aprovado', label: 'Aprovadas', color: '#2e7d32' },
            { key: 'rejeitado', label: 'Rejeitadas', color: '#d32f2f' },
            { key: 'esclarecimento', label: 'Esclarecimento', color: '#757575' },
          ] as const).map(({ key, label, color }) => (
            <Card key={key} variant="outlined" sx={{ cursor: 'pointer', borderLeft: `4px solid ${color}` }}
              onClick={() => setStatusFilter(statusFilter === key ? '' : key)}
            >
              <CardContent sx={{ py: 2, '&:last-child': { pb: 2 } }}>
                <Typography variant="h4" sx={{ color, fontWeight: 'bold' }}>
                  {stats[key]}
                </Typography>
                <Typography variant="body2" color="text.secondary">{label}</Typography>
              </CardContent>
            </Card>
          ))}
        </Box>
      )}

      {/* Filters */}
      <Paper sx={{ p: 2, mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
        <TextField
          size="small" label="Buscar" value={search}
          onChange={(e) => setSearch(e.target.value)}
          sx={{ minWidth: 200 }}
        />
        <FormControl size="small" sx={{ minWidth: 140 }}>
          <InputLabel>Status</InputLabel>
          <Select value={statusFilter} label="Status" onChange={(e) => setStatusFilter(e.target.value)}>
            <MenuItem value="">Todos</MenuItem>
            <MenuItem value="pendente">Pendente</MenuItem>
            <MenuItem value="em_analise">Em Análise</MenuItem>
            <MenuItem value="aprovado">Aprovado</MenuItem>
            <MenuItem value="rejeitado">Rejeitado</MenuItem>
            <MenuItem value="esclarecimento">Esclarecimento</MenuItem>
          </Select>
        </FormControl>
        <FormControl size="small" sx={{ minWidth: 120 }}>
          <InputLabel>Origem</InputLabel>
          <Select value={origemFilter} label="Origem" onChange={(e) => setOrigemFilter(e.target.value)}>
            <MenuItem value="">Todos</MenuItem>
            <MenuItem value="cfo">CFO</MenuItem>
            <MenuItem value="cro">CRO</MenuItem>
          </Select>
        </FormControl>
      </Paper>

      {/* Table */}
      <TableContainer component={Paper}>
        {loading ? (
          <Box display="flex" justifyContent="center" p={4}><CircularProgress /></Box>
        ) : (
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell>#</TableCell>
                <TableCell>Nome</TableCell>
                <TableCell>Email</TableCell>
                <TableCell>Origem</TableCell>
                <TableCell>Data</TableCell>
                <TableCell>Status</TableCell>
                <TableCell align="right">Ações</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {requests.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} align="center">Nenhuma solicitação encontrada</TableCell>
                </TableRow>
              ) : (
                requests.map(req => {
                  const si = statusMap[req.status] || { label: req.status, color: 'default' as const };
                  return (
                    <TableRow key={req.id} hover>
                      <TableCell>{req.id}</TableCell>
                      <TableCell>{req.nome_completo}</TableCell>
                      <TableCell>{req.email}</TableCell>
                      <TableCell>
                        <Chip label={req.origem_tipo.toUpperCase()} size="small" variant="outlined" />
                        <Typography variant="caption" display="block">{req.organizacao}</Typography>
                      </TableCell>
                      <TableCell>{formatDate(req.created_at)}</TableCell>
                      <TableCell><Chip label={si.label} color={si.color} size="small" /></TableCell>
                      <TableCell align="right">
                        <Tooltip title="Ver detalhes">
                          <IconButton size="small" onClick={() => openDetail(req)}>
                            <VisibilityIcon fontSize="small" />
                          </IconButton>
                        </Tooltip>
                      </TableCell>
                    </TableRow>
                  );
                })
              )}
            </TableBody>
          </Table>
        )}
      </TableContainer>

      {total > 0 && (
        <Typography variant="body2" color="text.secondary" sx={{ mt: 1, textAlign: 'right' }}>
          Total: {total} solicitação(ões)
        </Typography>
      )}

      {/* Detail Dialog */}
      <Dialog open={detailOpen} onClose={() => setDetailOpen(false)} maxWidth="md" fullWidth>
        {selectedReq && (
          <>
            <DialogTitle>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <span>Solicitação #{selectedReq.id}</span>
                <Chip
                  label={(statusMap[selectedReq.status] || { label: selectedReq.status }).label}
                  color={(statusMap[selectedReq.status] || { color: 'default' as const }).color}
                  size="small"
                />
              </Box>
            </DialogTitle>
            <DialogContent>
              {/* Solicitante info */}
              <Typography variant="subtitle2" sx={{ mt: 1, mb: 1 }}>Dados do Solicitante</Typography>
              <Box sx={{ display: 'grid', gridTemplateColumns: '140px 1fr', gap: 0.5, mb: 2 }}>
                <Typography variant="body2" color="text.secondary">Nome:</Typography>
                <Typography variant="body2">{selectedReq.nome_completo}</Typography>
                <Typography variant="body2" color="text.secondary">Email:</Typography>
                <Typography variant="body2">{selectedReq.email}</Typography>
                <Typography variant="body2" color="text.secondary">Telefone:</Typography>
                <Typography variant="body2">{selectedReq.telefone || '-'}</Typography>
                <Typography variant="body2" color="text.secondary">Origem:</Typography>
                <Typography variant="body2">{selectedReq.origem_tipo.toUpperCase()} - {selectedReq.organizacao}</Typography>
                {selectedReq.departamento && (
                  <>
                    <Typography variant="body2" color="text.secondary">Departamento:</Typography>
                    <Typography variant="body2">{selectedReq.departamento}</Typography>
                  </>
                )}
                <Typography variant="body2" color="text.secondary">Justificativa:</Typography>
                <Typography variant="body2">{selectedReq.justificativa}</Typography>
                {selectedReq.outro && (
                  <>
                    <Typography variant="body2" color="text.secondary">Outro:</Typography>
                    <Typography variant="body2">{selectedReq.outro}</Typography>
                  </>
                )}
                {selectedReq.sugestao_desenvolvimento && (
                  <>
                    <Typography variant="body2" color="text.secondary">Sugestão:</Typography>
                    <Typography variant="body2">{selectedReq.sugestao_desenvolvimento}</Typography>
                  </>
                )}
              </Box>

              <Divider sx={{ my: 2 }} />

              {/* Items with checkboxes */}
              <Typography variant="subtitle2" sx={{ mb: 1 }}>Serviços Solicitados</Typography>
              {selectedReq.items.map(item => (
                <FormControlLabel
                  key={item.id}
                  control={
                    <Checkbox
                      checked={itemApprovals[item.id] ?? item.approved}
                      onChange={(e) => setItemApprovals(prev => ({ ...prev, [item.id]: e.target.checked }))}
                      disabled={selectedReq.status === 'aprovado' || selectedReq.status === 'rejeitado'}
                    />
                  }
                  label={item.servico_nome || item.permission_name || 'Item'}
                />
              ))}

              <Divider sx={{ my: 2 }} />

              {/* Admin notes */}
              <TextField
                fullWidth multiline rows={2} label="Notas do Administrador" value={adminNotes}
                onChange={(e) => setAdminNotes(e.target.value)} sx={{ mt: 1 }}
                disabled={selectedReq.status === 'aprovado' || selectedReq.status === 'rejeitado'}
              />
            </DialogContent>
            <DialogActions sx={{ p: 2, flexWrap: 'wrap', gap: 1 }}>
              {selectedReq.status !== 'aprovado' && selectedReq.status !== 'rejeitado' && (
                <>
                  {(selectedReq.status === 'pendente' || selectedReq.status === 'esclarecimento') && (
                    <Button onClick={handleAnalyze} startIcon={<PlayArrowIcon />} disabled={actionLoading} variant="outlined">
                      Analisar
                    </Button>
                  )}
                  <Button onClick={handleModify} startIcon={<EditIcon />} disabled={actionLoading} variant="outlined" color="info">
                    Salvar Itens
                  </Button>
                  <Button onClick={openApproveDialog} startIcon={<CheckCircleIcon />} disabled={actionLoading} variant="contained" color="success">
                    Aprovar
                  </Button>
                  <Button onClick={openRejectDialog} startIcon={<CancelIcon />} disabled={actionLoading} variant="outlined" color="error">
                    Rejeitar
                  </Button>
                  <Button onClick={openClarifyDialog} startIcon={<HelpIcon />} disabled={actionLoading} variant="outlined">
                    Pedir Esclarecimento
                  </Button>
                </>
              )}
              <Button onClick={() => setDetailOpen(false)}>Fechar</Button>
            </DialogActions>
          </>
        )}
      </Dialog>

      {/* Approve Dialog */}
      <Dialog open={approveOpen} onClose={() => setApproveOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Aprovar Solicitação</DialogTitle>
        <DialogContent>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Defina as credenciais e perfis do novo usuário.
          </Typography>
          <TextField
            fullWidth label="Username *" value={approveUsername}
            onChange={(e) => setApproveUsername(e.target.value)} sx={{ mb: 2, mt: 1 }}
          />
          <TextField
            fullWidth label="Senha *" type="password" value={approvePassword}
            onChange={(e) => setApprovePassword(e.target.value)} sx={{ mb: 2 }}
          />
          <FormControl fullWidth sx={{ mb: 2 }}>
            <InputLabel>Perfis (Roles)</InputLabel>
            <Select
              multiple value={approveRoleIds}
              onChange={(e) => setApproveRoleIds(e.target.value as number[])}
              label="Perfis (Roles)"
              renderValue={(selected) => (
                <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                  {(selected as number[]).map(id => {
                    const role = roles.find(r => r.id === id);
                    return <Chip key={id} label={role?.name || id} size="small" />;
                  })}
                </Box>
              )}
            >
              {roles.map(role => (
                <MenuItem key={role.id} value={role.id}>{role.name} - {role.description}</MenuItem>
              ))}
            </Select>
          </FormControl>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setApproveOpen(false)}>Cancelar</Button>
          <Button
            variant="contained" color="success" onClick={handleApprove}
            disabled={!approveUsername || !approvePassword || actionLoading}
          >
            {actionLoading ? <CircularProgress size={20} /> : 'Confirmar Aprovação'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Reject Dialog */}
      <Dialog open={rejectOpen} onClose={() => setRejectOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Rejeitar Solicitação</DialogTitle>
        <DialogContent>
          <TextField
            fullWidth multiline rows={3} label="Motivo da Rejeição *" value={rejectReason}
            onChange={(e) => setRejectReason(e.target.value)} sx={{ mt: 1 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setRejectOpen(false)}>Cancelar</Button>
          <Button
            variant="contained" color="error" onClick={handleReject}
            disabled={!rejectReason.trim() || actionLoading}
          >
            {actionLoading ? <CircularProgress size={20} /> : 'Confirmar Rejeição'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Clarify Dialog */}
      <Dialog open={clarifyOpen} onClose={() => setClarifyOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Pedir Esclarecimento</DialogTitle>
        <DialogContent>
          <TextField
            fullWidth multiline rows={3} label="Mensagem de Esclarecimento *" value={clarifyMessage}
            onChange={(e) => setClarifyMessage(e.target.value)} sx={{ mt: 1 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setClarifyOpen(false)}>Cancelar</Button>
          <Button
            variant="contained" onClick={handleClarify}
            disabled={!clarifyMessage.trim() || actionLoading}
          >
            {actionLoading ? <CircularProgress size={20} /> : 'Enviar'}
          </Button>
        </DialogActions>
      </Dialog>
    </Container>
  );
};

export default AdminSolicitacoes;
