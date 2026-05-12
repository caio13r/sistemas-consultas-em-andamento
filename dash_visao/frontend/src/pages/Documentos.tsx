import React, { useState, useEffect, useCallback } from 'react';
import {
  Container, Paper, Typography, Box, Card, CardContent, CardActionArea,
  Table, TableBody, TableCell, TableContainer, TableHead, TableRow,
  TextField, Chip, IconButton, Tooltip, FormControl, InputLabel, Select,
  MenuItem, CircularProgress, Alert, Button, Grid,
} from '@mui/material';
import {
  CloudUpload as UploadIcon,
  FactCheck as ValidacaoIcon,
  FolderOpen as RepositorioIcon,
  Download as DownloadIcon,
  Refresh as RefreshIcon,
  Search as SearchIcon,
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

interface DocumentoItem {
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

const statusConfig: Record<string, { label: string; color: 'warning' | 'success' | 'error' }> = {
  pendente: { label: 'Pendente', color: 'warning' },
  aprovado: { label: 'Aprovado', color: 'success' },
  rejeitado: { label: 'Rejeitado', color: 'error' },
};

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

const Documentos: React.FC = () => {
  const navigate = useNavigate();
  const { hasPermission } = useAuth();
  const [view, setView] = useState<'cards' | 'repositorio'>('cards');
  const [documentos, setDocumentos] = useState<DocumentoItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [categoriaFilter, setCategoriaFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [croFilter, setCroFilter] = useState('');
  const [search, setSearch] = useState('');

  const loadDocumentos = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params: Record<string, string> = {};
      if (categoriaFilter) params.categoria = categoriaFilter;
      if (statusFilter) params.status = statusFilter;
      if (croFilter) params.cro = croFilter;
      if (search) params.search = search;
      const res = await api.get('/documentos/', { params });
      setDocumentos(res.data);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao carregar documentos');
    } finally {
      setLoading(false);
    }
  }, [categoriaFilter, statusFilter, croFilter, search]);

  useEffect(() => {
    if (view === 'repositorio') {
      loadDocumentos();
    }
  }, [view, loadDocumentos]);

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
      setError('Erro ao fazer download do arquivo');
    }
  };

  if (view === 'cards') {
    return (
      <Container maxWidth="lg" sx={{ py: 4 }}>
        <Typography variant="h5" gutterBottom sx={{ fontWeight: 700, color: '#1C2024', mb: 3 }}>
          Gestão de Documentos
        </Typography>
        <Grid container spacing={3}>
          <Grid item xs={12} md={4}>
            <Card>
              <CardActionArea onClick={() => navigate('/documentos/upload')} sx={{ p: 3, textAlign: 'center' }}>
                <UploadIcon sx={{ fontSize: 56, color: '#7A1E26', mb: 1 }} />
                <CardContent>
                  <Typography variant="h6" gutterBottom>Upload</Typography>
                  <Typography variant="body2" color="text.secondary">
                    Envie novos documentos para o sistema
                  </Typography>
                </CardContent>
              </CardActionArea>
            </Card>
          </Grid>
          {hasPermission('manage_documentos') && (
            <Grid item xs={12} md={4}>
              <Card>
                <CardActionArea onClick={() => navigate('/documentos/validacao')} sx={{ p: 3, textAlign: 'center' }}>
                  <ValidacaoIcon sx={{ fontSize: 56, color: '#ED6C02', mb: 1 }} />
                  <CardContent>
                    <Typography variant="h6" gutterBottom>Validação</Typography>
                    <Typography variant="body2" color="text.secondary">
                      Aprove ou rejeite documentos pendentes
                    </Typography>
                  </CardContent>
                </CardActionArea>
              </Card>
            </Grid>
          )}
          <Grid item xs={12} md={hasPermission('manage_documentos') ? 4 : 8}>
            <Card>
              <CardActionArea onClick={() => setView('repositorio')} sx={{ p: 3, textAlign: 'center' }}>
                <RepositorioIcon sx={{ fontSize: 56, color: '#2E7D32', mb: 1 }} />
                <CardContent>
                  <Typography variant="h6" gutterBottom>Repositório</Typography>
                  <Typography variant="body2" color="text.secondary">
                    Consulte documentos por tipo e categoria
                  </Typography>
                </CardContent>
              </CardActionArea>
            </Card>
          </Grid>
        </Grid>
      </Container>
    );
  }

  // Repositório view
  return (
    <Container maxWidth="lg" sx={{ py: 4 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3, gap: 2 }}>
        <Button startIcon={<ArrowBackIcon />} onClick={() => setView('cards')} variant="outlined" size="small">
          Voltar
        </Button>
        <Typography variant="h5" sx={{ fontWeight: 700, color: '#1C2024', flexGrow: 1 }}>
          Repositório de Documentos
        </Typography>
        <Tooltip title="Atualizar">
          <IconButton onClick={loadDocumentos}><RefreshIcon /></IconButton>
        </Tooltip>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

      {/* Filtros */}
      <Paper sx={{ p: 2, mb: 3 }}>
        <Grid container spacing={2} alignItems="center">
          <Grid item xs={12} sm={3}>
            <TextField
              fullWidth size="small" label="Buscar por título"
              value={search} onChange={e => setSearch(e.target.value)}
              InputProps={{ endAdornment: <SearchIcon color="action" /> }}
            />
          </Grid>
          <Grid item xs={6} sm={2}>
            <FormControl fullWidth size="small">
              <InputLabel>Categoria</InputLabel>
              <Select value={categoriaFilter} label="Categoria" onChange={e => setCategoriaFilter(e.target.value)}>
                <MenuItem value="">Todas</MenuItem>
                {Object.entries(categoriaLabels).map(([k, v]) => (
                  <MenuItem key={k} value={k}>{v}</MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={6} sm={2}>
            <FormControl fullWidth size="small">
              <InputLabel>Status</InputLabel>
              <Select value={statusFilter} label="Status" onChange={e => setStatusFilter(e.target.value)}>
                <MenuItem value="">Todos</MenuItem>
                <MenuItem value="pendente">Pendente</MenuItem>
                <MenuItem value="aprovado">Aprovado</MenuItem>
                <MenuItem value="rejeitado">Rejeitado</MenuItem>
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={6} sm={2}>
            <TextField
              fullWidth size="small" label="CRO (UF)"
              value={croFilter} onChange={e => setCroFilter(e.target.value.toUpperCase())}
              inputProps={{ maxLength: 2 }}
            />
          </Grid>
          <Grid item xs={6} sm={3}>
            <Button variant="contained" fullWidth onClick={loadDocumentos} startIcon={<SearchIcon />}>
              Filtrar
            </Button>
          </Grid>
        </Grid>
      </Paper>

      {/* Tabela */}
      <TableContainer component={Paper}>
        {loading ? (
          <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}><CircularProgress /></Box>
        ) : documentos.length === 0 ? (
          <Box sx={{ p: 4, textAlign: 'center' }}>
            <Typography color="text.secondary">Nenhum documento encontrado</Typography>
          </Box>
        ) : (
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell>Título</TableCell>
                <TableCell>Categoria</TableCell>
                <TableCell>Status</TableCell>
                <TableCell>Enviado por</TableCell>
                <TableCell>Tamanho</TableCell>
                <TableCell>CRO</TableCell>
                <TableCell>Data</TableCell>
                <TableCell align="center">Ações</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {documentos.map(doc => {
                const st = statusConfig[doc.status] || { label: doc.status, color: 'default' as const };
                return (
                  <TableRow key={doc.id}>
                    <TableCell>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>{doc.titulo}</Typography>
                      <Typography variant="caption" color="text.secondary">{doc.filename}</Typography>
                    </TableCell>
                    <TableCell>{categoriaLabels[doc.categoria] || doc.categoria}</TableCell>
                    <TableCell>
                      <Chip label={st.label} color={st.color} size="small" />
                    </TableCell>
                    <TableCell>{doc.uploader_name || '-'}</TableCell>
                    <TableCell>{formatBytes(doc.file_size)}</TableCell>
                    <TableCell>{doc.cro || '-'}</TableCell>
                    <TableCell>{doc.created_at ? new Date(doc.created_at).toLocaleDateString('pt-BR') : '-'}</TableCell>
                    <TableCell align="center">
                      <Tooltip title="Download">
                        <IconButton size="small" onClick={() => handleDownload(doc.id, doc.filename)}>
                          <DownloadIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </TableContainer>
    </Container>
  );
};

export default Documentos;
