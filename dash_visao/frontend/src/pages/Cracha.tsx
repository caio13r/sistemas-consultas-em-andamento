import React, { useState, useRef, useCallback } from 'react';
import {
  Typography, Box, TextField, Button, Divider, Snackbar, Alert, Grid,
  Card, CardContent, CircularProgress, Paper,
} from '@mui/material';
import {
  Search as SearchIcon, CameraAlt as CameraIcon, FileUpload as UploadIcon,
  Badge as BadgeIcon, Clear as ClearIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

export default function Cracha() {
  const [cpf, setCpf] = useState('');
  const [profissional, setProfissional] = useState<any>(null);
  const [foto, setFoto] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [gerando, setGerando] = useState(false);
  const [cameraActive, setCameraActive] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });
  const videoRef = useRef<HTMLVideoElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleBuscar = async () => {
    if (!cpf || cpf.length < 11) {
      setSnackbar({ open: true, message: 'Informe um CPF valido.', severity: 'warning' }); return;
    }
    setLoading(true);
    try {
      const res = await api.get(`/cracha/dados/${cpf}`);
      if (res.data.total > 0) {
        setProfissional(res.data.resultados[0]);
      } else {
        setProfissional(null);
        setSnackbar({ open: true, message: 'Profissional nao encontrado ou inativo.', severity: 'warning' });
      }
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
    } finally { setLoading(false); }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (ev) => {
      const base64 = ev.target?.result as string;
      cropAndSetFoto(base64);
    };
    reader.readAsDataURL(file);
  };

  const cropAndSetFoto = (base64: string) => {
    const img = new Image();
    img.onload = () => {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d')!;
      const cw = 300, ch = 400;
      canvas.width = cw; canvas.height = ch;
      const imgRatio = img.width / img.height;
      const cRatio = cw / ch;
      let sx, sy, sw, sh;
      if (imgRatio > cRatio) {
        sh = img.height; sw = sh * cRatio; sx = (img.width - sw) / 2; sy = 0;
      } else {
        sw = img.width; sh = sw / cRatio; sx = 0; sy = (img.height - sh) / 2;
      }
      ctx.drawImage(img, sx, sy, sw, sh, 0, 0, cw, ch);
      setFoto(canvas.toDataURL('image/jpeg'));
    };
    img.src = base64;
  };

  const handleAbrirCamera = async () => {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ video: true });
      streamRef.current = stream;
      setCameraActive(true);
      setTimeout(() => {
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.play();
        }
      }, 100);
    } catch {
      setSnackbar({ open: true, message: 'Erro ao acessar a camera.', severity: 'error' });
    }
  };

  const handleCapturar = () => {
    if (!videoRef.current) return;
    const canvas = document.createElement('canvas');
    canvas.width = videoRef.current.videoWidth;
    canvas.height = videoRef.current.videoHeight;
    canvas.getContext('2d')!.drawImage(videoRef.current, 0, 0);
    const dataURL = canvas.toDataURL('image/jpeg');
    cropAndSetFoto(dataURL);
    handleFecharCamera();
  };

  const handleFecharCamera = useCallback(() => {
    streamRef.current?.getTracks().forEach(t => t.stop());
    streamRef.current = null;
    setCameraActive(false);
  }, []);

  const handleGerar = async () => {
    if (!cpf) return;
    setGerando(true);
    try {
      const res = await api.post('/cracha/gerar', {
        cpf,
        foto_base64: foto || undefined,
      });
      setProfissional(res.data.profissional);
      if (res.data.foto) setFoto(res.data.foto);
      setSnackbar({ open: true, message: 'Cracha gerado com sucesso!', severity: 'success' });
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao gerar cracha', severity: 'error' });
    } finally { setGerando(false); }
  };

  const handleLimpar = () => {
    setCpf(''); setProfissional(null); setFoto(null); handleFecharCamera();
  };

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Cracha Profissional</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Upload de foto, busca por CPF e geracao de cracha.
      </Typography>
      <Divider sx={{ mb: 3 }} />

      {/* Busca por CPF */}
      <Grid container spacing={2} sx={{ mb: 3 }}>
        <Grid item xs={12} sm={6} md={4}>
          <TextField fullWidth size="small" label="CPF do Profissional" value={cpf}
            onChange={e => setCpf(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && handleBuscar()}
            placeholder="000.000.000-00" />
        </Grid>
        <Grid item xs={12} sm={6} md={4}>
          <Box sx={{ display: 'flex', gap: 1 }}>
            <Button variant="contained" startIcon={<SearchIcon />} onClick={handleBuscar} disabled={loading}>
              {loading ? 'Buscando...' : 'Buscar'}
            </Button>
            <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleLimpar}>Limpar</Button>
          </Box>
        </Grid>
      </Grid>

      {/* Dados do profissional */}
      {profissional && (
        <Grid container spacing={3}>
          <Grid item xs={12} md={8}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>Dados do Profissional</Typography>
                <Divider sx={{ mb: 2 }} />
                <Grid container spacing={1}>
                  {Object.entries(profissional).map(([k, v]) => (
                    <Grid item xs={6} sm={4} key={k}>
                      <Typography variant="caption" color="text.secondary">{k}</Typography>
                      <Typography variant="body2" sx={{ fontWeight: 'bold' }}>{v != null ? String(v) : '-'}</Typography>
                    </Grid>
                  ))}
                </Grid>
              </CardContent>
            </Card>
          </Grid>

          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>Foto</Typography>
                <Divider sx={{ mb: 2 }} />

                {/* Preview da foto */}
                <Box sx={{ display: 'flex', justifyContent: 'center', mb: 2 }}>
                  {foto ? (
                    <img src={foto.startsWith('data:') ? foto : `data:image/jpeg;base64,${foto}`}
                      alt="Foto" style={{ width: 150, height: 200, objectFit: 'cover', borderRadius: 8 }} />
                  ) : (
                    <Paper sx={{ width: 150, height: 200, display: 'flex', alignItems: 'center', justifyContent: 'center', bgcolor: '#f5f5f5' }}>
                      <Typography variant="body2" color="text.secondary">Sem foto</Typography>
                    </Paper>
                  )}
                </Box>

                {/* Camera */}
                {cameraActive && (
                  <Box sx={{ mb: 2, textAlign: 'center' }}>
                    <video ref={videoRef} style={{ width: '100%', maxWidth: 300, borderRadius: 8 }} />
                    <Box sx={{ mt: 1, display: 'flex', gap: 1, justifyContent: 'center' }}>
                      <Button size="small" variant="contained" color="success" onClick={handleCapturar}>Capturar</Button>
                      <Button size="small" variant="outlined" color="error" onClick={handleFecharCamera}>Cancelar</Button>
                    </Box>
                  </Box>
                )}

                {/* Botoes de foto */}
                {!cameraActive && (
                  <Box sx={{ display: 'flex', gap: 1, justifyContent: 'center', flexWrap: 'wrap' }}>
                    <Button size="small" variant="outlined" startIcon={<CameraIcon />} onClick={handleAbrirCamera}>
                      Tirar Foto
                    </Button>
                    <Button size="small" variant="outlined" startIcon={<UploadIcon />}
                      onClick={() => fileInputRef.current?.click()}>
                      Escolher Arquivo
                    </Button>
                    <input ref={fileInputRef} type="file" accept="image/*" hidden onChange={handleFileUpload} />
                  </Box>
                )}

                {/* Gerar cracha */}
                <Box sx={{ mt: 3, textAlign: 'center' }}>
                  <Button variant="contained" color="primary" size="large" startIcon={<BadgeIcon />}
                    onClick={handleGerar} disabled={gerando} fullWidth>
                    {gerando ? <CircularProgress size={24} /> : 'Gerar Cracha'}
                  </Button>
                </Box>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
