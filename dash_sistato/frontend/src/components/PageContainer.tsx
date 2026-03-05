import React from 'react';
import { Paper } from '@mui/material';

const PageContainer: React.FC<React.PropsWithChildren> = ({ children }) => (
  <Paper
    sx={{
      width: '100%',
      maxWidth: '1600px',
      p: 3,
      mt: 0,
      boxSizing: 'border-box',
      minHeight: 'calc(100vh - 80px)', // ajusta para header
    }}
    elevation={1}
  >
    {children}
  </Paper>
);

export default PageContainer; 