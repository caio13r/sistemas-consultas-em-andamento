import React, { useState } from 'react';
import {
  Container, Paper, Typography, TextField, Button, Box, Alert,
  CircularProgress, Chip, Card, CardContent, Divider,
} from '@mui/material';
import {
  Search as SearchIcon,
  AccessTime as AccessTimeIcon,
} from '@mui/icons-material';
import { Link } from 'react-router-dom';
import { checkRequestStatus, UserRequest } from '../services/userRequestService';
import BackgroundEffect from '../components/BackgroundEffect';

const statusMap: Record<string, { label: string; color: 'default' | 'warning' | 'info' | 'success' | 'error' }> = {
  pendente: { label: 'Pendente', color: 'warning' },
  em_analise: { label: 'Em Análise', color: 'info' },
  aprovado: { label: 'Aprovado', color: 'success' },
  rejeitado: { label: 'Rejeitado', color: 'error' },
  esclarecimento: { label: 'Aguardando Esclarecimento', color: 'default' },
};

const StatusSolicitacao: React.FC = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [requests, setRequests] = useState<UserRequest[] | null>(null);

  const handleSearch = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!email.trim()) return;

    setLoading(true);
    setError('');
    setRequests(null);

    try {
      const data = await checkRequestStatus(email);
      setRequests(data.requests);
    } catch {
      setError('Erro ao consultar status. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('pt-BR', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit',
    });
  };

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#FBF8F4', position: 'relative' }}>
      {/* Mesh grid background */}
      <Box
        sx={{
          position: 'fixed', inset: 0, pointerEvents: 'none', zIndex: 0,
          backgroundImage: `
            linear-gradient(rgba(122,30,38,0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(122,30,38,0.06) 1px, transparent 1px)
          `,
          backgroundSize: '48px 48px',
        }}
      />
      {/* Header */}
      <Box sx={{ bgcolor: '#7A1E26', color: 'white', py: 2, px: 3, position: 'relative', zIndex: 1 }}>
        <Container maxWidth="sm">
          <Typography variant="h6">Visão CFO</Typography>
          <Typography variant="body2" sx={{ opacity: 0.9 }}>Verificar Status da Solicitação</Typography>
        </Container>
      </Box>

      <Container maxWidth="sm" sx={{ py: 4, position: 'relative', zIndex: 1 }}>
        <Paper sx={{ p: 4 }}>
          <Box sx={{ textAlign: 'center', mb: 3 }}>
            <AccessTimeIcon sx={{ fontSize: 48, color: 'text.secondary', mb: 1 }} />
            <Typography variant="h5" gutterBottom>Status da Solicitação</Typography>
            <Typography variant="body2" color="text.secondary">
              Digite seu email para verificar o status das suas solicitações
            </Typography>
          </Box>

          <form onSubmit={handleSearch}>
            <TextField
              fullWidth label="Email" type="email" value={email}
              onChange={(e) => setEmail(e.target.value)} sx={{ mb: 2 }}
              required
            />
            <Button
              type="submit" variant="contained" fullWidth
              disabled={loading || !email.trim()}
              startIcon={loading ? <CircularProgress size={20} /> : <SearchIcon />}
            >
              {loading ? 'Buscando...' : 'Verificar Status'}
            </Button>
          </form>

          {error && <Alert severity="error" sx={{ mt: 2 }}>{error}</Alert>}

          {requests !== null && requests.length === 0 && (
            <Alert severity="info" sx={{ mt: 3 }}>
              Nenhuma solicitação encontrada para este email.
            </Alert>
          )}

          {requests && requests.length > 0 && (
            <Box sx={{ mt: 3 }}>
              <Typography variant="subtitle2" gutterBottom>
                {requests.length} solicitação(ões) encontrada(s)
              </Typography>

              {requests.map(req => {
                const statusInfo = statusMap[req.status] || { label: req.status, color: 'default' as const };
                return (
                  <Card key={req.id} variant="outlined" sx={{ mb: 2 }}>
                    <CardContent>
                      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                        <Typography variant="subtitle2">Solicitação #{req.id}</Typography>
                        <Chip label={statusInfo.label} color={statusInfo.color} size="small" />
                      </Box>
                      <Divider sx={{ my: 1 }} />
                      <Typography variant="body2" color="text.secondary">
                        Data: {formatDate(req.created_at)}
                      </Typography>
                      <Typography variant="body2" color="text.secondary">
                        Origem: {req.origem_tipo.toUpperCase()} - {req.organizacao}
                      </Typography>
                      <Typography variant="body2" color="text.secondary">
                        Serviços: {req.items.map(i => i.servico_nome || i.permission_name).join(', ')}
                      </Typography>

                      {req.status === 'rejeitado' && req.reject_reason && (
                        <Alert severity="error" sx={{ mt: 1 }} variant="outlined">
                          <Typography variant="body2"><strong>Motivo:</strong> {req.reject_reason}</Typography>
                        </Alert>
                      )}

                      {req.status === 'esclarecimento' && req.clarification_message && (
                        <Alert severity="warning" sx={{ mt: 1 }} variant="outlined">
                          <Typography variant="body2"><strong>Esclarecimento solicitado:</strong> {req.clarification_message}</Typography>
                        </Alert>
                      )}

                      {req.status === 'aprovado' && (
                        <Alert severity="success" sx={{ mt: 1 }} variant="outlined">
                          <Typography variant="body2">Seu acesso foi aprovado. Utilize as credenciais enviadas para fazer login.</Typography>
                        </Alert>
                      )}
                    </CardContent>
                  </Card>
                );
              })}
            </Box>
          )}
        </Paper>

        <Box sx={{ textAlign: 'center', mt: 3 }}>
          <Button component={Link} to="/login" size="small" color="inherit">
            Voltar ao Login
          </Button>
          <Typography variant="body2" component="span" sx={{ mx: 1 }}>|</Typography>
          <Button component={Link} to="/solicitar-usuario" size="small" color="inherit">
            Nova Solicitação
          </Button>
        </Box>
      </Container>
    </Box>
  );
};

export default StatusSolicitacao;
