import { Box, Card, CardContent, Typography, Button } from '@mui/material';
import { ArrowForward as ArrowForwardIcon } from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';

interface ConsultaCardProps {
  title: string;
  description: string;
  path: string;
}

export default function ConsultaCard({ title, description, path }: ConsultaCardProps) {
  const navigate = useNavigate();

  return (
    <Card
      sx={{
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        transition: 'transform 0.2s, box-shadow 0.2s',
        '&:hover': {
          transform: 'translateY(-4px)',
          boxShadow: 3,
        },
      }}
    >
      <CardContent sx={{ flexGrow: 1, p: 3 }}>
        <Typography variant="h6" component="h2" gutterBottom sx={{ fontWeight: 'bold' }}>
          {title}
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          {description}
        </Typography>
        <Button
          variant="contained"
          color="primary"
          endIcon={<ArrowForwardIcon />}
          onClick={() => navigate(path)}
          sx={{
            bgcolor: '#800000',
            '&:hover': {
              bgcolor: '#600000',
            },
          }}
        >
          Clique para acessar
        </Button>
      </CardContent>
    </Card>
  );
} 