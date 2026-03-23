import React, { useState, useRef, useCallback } from 'react';
import {
  Container, Paper, Typography, Box, TextField, Button, FormControl,
  InputLabel, Select, MenuItem, Alert, LinearProgress, Grid, Chip,
} from '@mui/material';
import {
  CloudUpload as UploadIcon,
  InsertDriveFile as FileIcon,
  Delete as DeleteIcon,
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';

const categorias = [
  { value: 'oficio', label: 'Ofício' },
  { value: 'portaria', label: 'Portaria' },
  { value: 'ata', label: 'Ata' },
  { value: 'relatorio', label: 'Relatório' },
  { value: 'outro', label: 'Outro' },
];

const ALLOWED_EXTENSIONS = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.png', '.jpg', '.jpeg'];
const MAX_SIZE = 10 * 1024 * 1024; // 10MB

function formatBytes(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

const UFS = [
  'AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT',
  'PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO',
];

const DocumentoUpload: React.FC = () => {
  const navigate = useNavigate();
  const fileInputRef = useRef<HTMLInputElement>(null);

  const [file, setFile] = useState<File | null>(null);
  const [titulo, setTitulo] = useState('');
  const [descricao, setDescricao] = useState('');
  const [categoria, setCategoria] = useState('');
  const [cro, setCro] = useState('');
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [dragOver, setDragOver] = useState(false);

  const validateFile = (f: File): string | null => {
    const ext = '.' + f.name.split('.').pop()?.toLowerCase();
    if (!ALLOWED_EXTENSIONS.includes(ext)) {
      return `Tipo de arquivo não permitido. Permitidos: ${ALLOWED_EXTENSIONS.join(', ')}`;
    }
    if (f.size > MAX_SIZE) {
      return 'Arquivo excede o tamanho máximo de 10MB';
    }
    return null;
  };

  const handleFileSelect = (f: File) => {
    setError('');
    setSuccess('');
    const validationError = validateFile(f);
    if (validationError) {
      setError(validationError);
      return;
    }
    setFile(f);
  };

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(false);
    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile) handleFileSelect(droppedFile);
  }, []);

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setDragOver(true);
  };

  const handleDragLeave = () => setDragOver(false);

  const handleUpload = async () => {
    if (!file || !titulo.trim() || !categoria) {
      setError('Preencha todos os campos obrigatórios');
      return;
    }

    setUploading(true);
    setProgress(0);
    setError('');
    setSuccess('');

    try {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('titulo', titulo.trim());
      if (descricao.trim()) formData.append('descricao', descricao.trim());
      formData.append('categoria', categoria);
      if (cro) formData.append('cro', cro);

      await api.post('/documentos/upload', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (e) => {
          if (e.total) setProgress(Math.round((e.loaded * 100) / e.total));
        },
      });

      setSuccess('Documento enviado com sucesso! Aguardando validação.');
      setFile(null);
      setTitulo('');
      setDescricao('');
      setCategoria('');
      setCro('');
      setProgress(0);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao enviar documento');
    } finally {
      setUploading(false);
    }
  };

  return (
    <Container maxWidth="md" sx={{ py: 4 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', mb: 3, gap: 2 }}>
        <Button startIcon={<ArrowBackIcon />} onClick={() => navigate('/documentos')} variant="outlined" size="small">
          Voltar
        </Button>
        <Typography variant="h5" sx={{ fontWeight: 700, color: '#1C2024' }}>
          Upload de Documento
        </Typography>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

      <Paper sx={{ p: 4 }}>
        {/* Drag & Drop Zone */}
        <Box
          onDrop={handleDrop}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          onClick={() => fileInputRef.current?.click()}
          sx={{
            border: '2px dashed',
            borderColor: dragOver ? '#8D0F12' : '#ccc',
            borderRadius: 2,
            p: 4,
            textAlign: 'center',
            cursor: 'pointer',
            backgroundColor: dragOver ? 'rgba(141,15,18,0.04)' : '#fafafa',
            transition: 'all 0.2s',
            mb: 3,
            '&:hover': { borderColor: '#8D0F12', backgroundColor: 'rgba(141,15,18,0.02)' },
          }}
        >
          <input
            ref={fileInputRef}
            type="file"
            hidden
            accept={ALLOWED_EXTENSIONS.join(',')}
            onChange={e => {
              const f = e.target.files?.[0];
              if (f) handleFileSelect(f);
            }}
          />
          <UploadIcon sx={{ fontSize: 48, color: dragOver ? '#8D0F12' : '#999', mb: 1 }} />
          <Typography variant="body1" sx={{ fontWeight: 500 }}>
            Arraste um arquivo aqui ou clique para selecionar
          </Typography>
          <Typography variant="caption" color="text.secondary">
            PDF, DOC, DOCX, XLS, XLSX, PNG, JPG - Máximo 10MB
          </Typography>
        </Box>

        {/* File Preview */}
        {file && (
          <Paper variant="outlined" sx={{ p: 2, mb: 3, display: 'flex', alignItems: 'center', gap: 2 }}>
            <FileIcon sx={{ color: '#8D0F12', fontSize: 36 }} />
            <Box sx={{ flexGrow: 1 }}>
              <Typography variant="body2" sx={{ fontWeight: 600 }}>{file.name}</Typography>
              <Typography variant="caption" color="text.secondary">
                {formatBytes(file.size)} - {file.type || 'Tipo desconhecido'}
              </Typography>
            </Box>
            <Chip
              label="Remover"
              icon={<DeleteIcon />}
              onDelete={() => setFile(null)}
              onClick={() => setFile(null)}
              size="small"
              color="error"
              variant="outlined"
            />
          </Paper>
        )}

        {/* Form Fields */}
        <Grid container spacing={2}>
          <Grid item xs={12}>
            <TextField
              fullWidth required label="Título do Documento"
              value={titulo} onChange={e => setTitulo(e.target.value)}
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              fullWidth multiline rows={3} label="Descrição (opcional)"
              value={descricao} onChange={e => setDescricao(e.target.value)}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <FormControl fullWidth required>
              <InputLabel>Categoria</InputLabel>
              <Select value={categoria} label="Categoria" onChange={e => setCategoria(e.target.value)}>
                {categorias.map(c => (
                  <MenuItem key={c.value} value={c.value}>{c.label}</MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6}>
            <FormControl fullWidth>
              <InputLabel>CRO (opcional)</InputLabel>
              <Select value={cro} label="CRO (opcional)" onChange={e => setCro(e.target.value)}>
                <MenuItem value="">Nenhum</MenuItem>
                {UFS.map(uf => (
                  <MenuItem key={uf} value={uf}>CRO-{uf}</MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
        </Grid>

        {/* Progress Bar */}
        {uploading && (
          <Box sx={{ mt: 3 }}>
            <LinearProgress variant="determinate" value={progress} sx={{ height: 8, borderRadius: 4 }} />
            <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block', textAlign: 'center' }}>
              {progress}%
            </Typography>
          </Box>
        )}

        {/* Submit */}
        <Box sx={{ mt: 3, textAlign: 'right' }}>
          <Button
            variant="contained" size="large"
            startIcon={<UploadIcon />}
            onClick={handleUpload}
            disabled={uploading || !file || !titulo.trim() || !categoria}
          >
            {uploading ? 'Enviando...' : 'Enviar Documento'}
          </Button>
        </Box>
      </Paper>
    </Container>
  );
};

export default DocumentoUpload;
