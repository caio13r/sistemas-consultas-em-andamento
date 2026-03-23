import React, { useState, useEffect, useCallback } from 'react';
import {
  Container, Paper, Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, Chip, IconButton, Tooltip,
  Dialog, DialogTitle, DialogContent, DialogActions, TextField,
  CircularProgress, Alert,
} from '@mui/material';
import {
  CheckCircle as ApproveIcon,
  Cancel as RejectIcon,
  Refresh as RefreshIcon,
  Download as DownloadIcon,
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

interface DocumentoPendente {
  id: number;
  titulo: string;
  descricao: string | null;
  categoria: string;
  filename: string;
  file_type: string;
  file_size: number;
  status: string;
  uploader_name: string | null;
  created_at: string;
  cro: string | null;
}

const categoriaLabels: Record<string, string> = {
  oficio: 'Ofício',
  portaria: 'Portaria',
  ata: 'Ata',
  relatorio: 'Relatório',
  outro: 'Outro',
};

function formatBytes(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

const DocumentoValidacao: React.FC = () => {
  const navigate = useNavigate();
  const [documentos, setDocumentos] = useState<DocumentoPendente[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [actionLoading, setActionLoading] = useState(false);

  // Reject dialog
  const [rejectOpen, setRejectOpen] = useState(false);
  const [rejectDocId, setRejectDocId] = useState<number | null>(null);
  const [rejectNotes, setRejectNotes] = useState('');

  const loadPendentes = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get('/documentos/pendentes');
      setDocumentos(res.data);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao carregar documentos pendentes');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadPendentes();
  }, [loadPendentes]);

  const handleAprovar = async (id: number) => {
    setActionLoading(true);
    setError('');
    try {
      await api.put(`/documentos/${id}/validar`, { status: 'aprovado' });
      setSuccess('Documento aprovado com sucesso');
      setDocumentos(prev => prev.filter(d => d.id !== id));
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao aprovar documento');
    } finally {
      setActionLoading(false);
    }
  };

  const handleRejeitar = async () => {
    if (!rejectDocId) return;
    setActionLoading(true);
    setError('');
    try {
      await api.put(`/documentos/${rejectDocId}/validar`, {
        status: 'rejeitado',
        notes: rejectNotes || undefined,
      });
      setSuccess('Documento rejeitado');
      setDocumentos(prev => prev.filter(d => d.id !== rejectDocId));
      setRejectOpen(false);
      setRejectNotes('');
      setRejectDocId(null);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao rejeitar documento');
    } finally {
      setActionLoading(false);
    }
  };

  const handleDownload = async (id: number, filename: string) => {
    try {
      const res = await api.get(`/documentos/${id}/download`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([res.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch {
      setError('Erro ao fazer download');
    }
  };

  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3, gap: 2 }}>
        <Button startIcon={<ArrowBackIcon />} onClick={() => navigate('/documentos')} variant="outlined" size="small">
          Voltar
        </Button>
        <Typography variant="h5" sx={{ fontWeight: 700, color: '#1C2024', flexGrow: 1 }}>
          Validação de Documentos
        </Typography>
        <Chip label={`${documentos.length} pendente(s)`} color="warning" />
        <Tooltip title="Atualizar">
          <IconButton onClick={loadPendentes}><RefreshIcon /></IconButton>
        </Tooltip>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError('')}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }} onClose={() => setSuccess('')}>{success}</Alert>}

      <TableContainer component={Paper}>
        {loading ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}><CircularProgress /></Box>
        ) : documentos.length === 0 ? (
          <Box sx={{ p: 4, textAlign: 'center' }}>
            <Typography color="text.secondary">Nenhum documento pendente de validação</Typography>
          </Box>
        ) : (
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell>Título</TableCell>
                <TableCell>Categoria</TableCell>
                <TableCell>Enviado por</TableCell>
                <TableCell>Tamanho</TableCell>
                <TableCell>CRO</TableCell>
                <TableCell>Data</TableCell>
                <TableCell align="center">Ações</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {documentos.map(doc => (
                <TableRow key={doc.id}>
                  <TableCell>
                    <Typography variant="body2" sx={{ fontWeight: 600 }}>{doc.titulo}</Typography>
                    <Typography variant="caption" color="text.secondary">{doc.filename}</Typography>
                    {doc.descricao && (
                      <Typography variant="caption" display="block" color="text.secondary" sx={{ mt: 0.5 }}>
                        {doc.descricao}
                      </Typography>
                    )}
                  </TableCell>
                  <TableCell>{categoriaLabels[doc.categoria] || doc.categoria}</TableCell>
                  <TableCell>{doc.uploader_name || '-'}</TableCell>
                  <TableCell>{formatBytes(doc.file_size)}</TableCell>
                  <TableCell>{doc.cro || '-'}</TableCell>
                  <TableCell>{doc.created_at ? new Date(doc.created_at).toLocaleDateString('pt-BR') : '-'}</TableCell>
                  <TableCell align="center">
                    <Box sx={{ display: 'flex', gap: 0.5, justifyContent: 'center' }}>
                      <Tooltip title="Download">
                        <IconButton size="small" onClick={() => handleDownload(doc.id, doc.filename)}>
                          <DownloadIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="Aprovar">
                        <IconButton
                          size="small" color="success"
                          onClick={() => handleAprovar(doc.id)}
                          disabled={actionLoading}
                        >
                          <ApproveIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="Rejeitar">
                        <IconButton
                          size="small" color="error"
                          onClick={() => { setRejectDocId(doc.id); setRejectOpen(true); }}
                          disabled={actionLoading}
                        >
                          <RejectIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </Box>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </TableContainer>

      {/* Reject Dialog */}
      <Dialog open={rejectOpen} onClose={() => setRejectOpen(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Rejeitar Documento</DialogTitle>
        <DialogContent>
          <TextField
            fullWidth multiline rows={3}
            label="Motivo da rejeição (opcional)"
            value={rejectNotes}
            onChange={e => setRejectNotes(e.target.value)}
            sx={{ mt: 1 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => { setRejectOpen(false); setRejectNotes(''); }}>Cancelar</Button>
          <Button variant="contained" color="error" onClick={handleRejeitar} disabled={actionLoading}>
            Rejeitar
          </Button>
        </DialogActions>
      </Dialog>
    </Container>
  );
};

export default DocumentoValidacao;
