import React from 'react';
import { Box, CircularProgress } from '@mui/material';
import { useAuth } from '../contexts/AuthContext';

/** Aguarda restauração da sessão (token no localStorage) antes de avaliar rotas protegidas. */
const AuthLoadingScreen: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isLoading } = useAuth();

  if (isLoading) {
    return (
      <Box
        sx={{
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          minHeight: '60vh',
          width: '100%',
        }}
      >
        <CircularProgress sx={{ color: '#7A1E26' }} />
      </Box>
    );
  }

  return <>{children}</>;
};

export default AuthLoadingScreen;
