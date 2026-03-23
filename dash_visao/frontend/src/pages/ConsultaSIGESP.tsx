import React from 'react';
import { Typography, Box, Divider, Alert } from '@mui/material';
import { AccountBalance as AccountBalanceIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';

export default function ConsultaSIGESP() {
  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta SIGESP</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Integração com sistema governamental SIGESP para consulta de dados financeiros e funcionais.</Typography>
      <Divider sx={{ mb: 3 }} />
      <Box sx={{ textAlign: 'center', py: 8 }}>
        <AccountBalanceIcon sx={{ fontSize: 80, color: 'text.disabled', mb: 2 }} />
        <Alert severity="info" sx={{ maxWidth: 500, mx: 'auto' }}>
          Este módulo está em desenvolvimento. A integração com o SIGESP depende de liberação de acesso à API governamental.
        </Alert>
      </Box>
    </PageContainer>
  );
}
