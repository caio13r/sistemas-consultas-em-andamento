import React from 'react';
import { Container, Paper, Typography, Box, Button } from '@mui/material';
import { Construction as ConstructionIcon, ArrowBack as ArrowBackIcon } from '@mui/icons-material';
import { useNavigate, useLocation } from 'react-router-dom';

const ComingSoon: React.FC = () => {
  const navigate = useNavigate();
  const location = useLocation();

  // Extrair nome amigável da rota
  const pageName = location.pathname
    .replace(/^\//, '')
    .replace(/-/g, ' ')
    .replace(/\b\w/g, c => c.toUpperCase());

  return (
    <Container maxWidth="sm" sx={{ py: 8 }}>
      <Paper sx={{ p: 6, textAlign: 'center' }}>
        <ConstructionIcon sx={{ fontSize: 80, color: '#8d0f12', mb: 2, opacity: 0.7 }} />
        <Typography variant="h4" gutterBottom sx={{ fontWeight: 'bold', color: '#333' }}>
          Em Construção
        </Typography>
        <Typography variant="h6" color="text.secondary" gutterBottom>
          {pageName || 'Esta página'}
        </Typography>
        <Typography variant="body1" color="text.secondary" sx={{ mb: 4 }}>
          Esta funcionalidade está sendo desenvolvida e estará disponível em breve.
        </Typography>
        <Button
          variant="outlined"
          startIcon={<ArrowBackIcon />}
          onClick={() => navigate(-1)}
          sx={{ mr: 2 }}
        >
          Voltar
        </Button>
        <Button
          variant="contained"
          onClick={() => navigate('/')}
        >
          Ir para Dashboard
        </Button>
      </Paper>
    </Container>
  );
};

export default ComingSoon;
