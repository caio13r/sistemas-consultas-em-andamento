import React from 'react';
import { Box, Paper } from '@mui/material';

const PageContainer: React.FC<React.PropsWithChildren> = ({ children }) => (
  <Box sx={{ width: '100%', maxWidth: '1600px', mx: 'auto' }}>
    <Paper
      sx={{
        width: '100%',
        p: { xs: 2, sm: 3, md: 4 },
        mt: 0,
        boxSizing: 'border-box',
        minHeight: 'calc(100vh - 80px)',
        border: '1px solid rgba(0,0,0,0.06)',
      }}
      elevation={0}
    >
      {children}
    </Paper>
  </Box>
);

export default PageContainer;
