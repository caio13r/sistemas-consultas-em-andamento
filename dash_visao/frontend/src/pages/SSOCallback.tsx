import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { CircularProgress, Box, Typography } from '@mui/material';
import api from '../services/api';
import { authService } from '../services/authService';
import { useAuth } from '../contexts/AuthContext';

export default function SSOCallback() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { fetchCurrentUser } = useAuth();
  const [error, setError] = useState('');

  useEffect(() => {
    const code = searchParams.get('code');
    if (!code) {
      setError('Código de autorização não encontrado.');
      return;
    }

    const redirectUri = `${window.location.origin}/sso/callback`;

    api.post('/sso/callback', { code, redirect_uri: redirectUri })
      .then(async (res) => {
        const { access_token } = res.data;
        localStorage.setItem('token', access_token);
        (authService as any).token = access_token;
        localStorage.removeItem('acceptedTerms');
        await fetchCurrentUser();
        navigate('/termos-de-uso');
      })
      .catch((err) => {
        const msg = err.response?.data?.detail || 'Erro na autenticação SSO.';
        setError(msg);
        setTimeout(() => navigate('/login'), 3000);
      });
  }, []);

  if (error) {
    return (
      <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', minHeight: '100vh', gap: 2 }}>
        <Typography color="error" variant="h6">{error}</Typography>
        <Typography variant="body2" color="text.secondary">Redirecionando para o login...</Typography>
      </Box>
    );
  }

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', minHeight: '100vh', gap: 2 }}>
      <CircularProgress sx={{ color: '#7A1E26' }} />
      <Typography variant="body2" color="text.secondary">Autenticando via SSO...</Typography>
    </Box>
  );
}
