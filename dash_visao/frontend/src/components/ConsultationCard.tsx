import { Card, CardContent, Typography, Button, Box } from '@mui/material';
import { ArrowForward as ArrowForwardIcon } from '@mui/icons-material';
import { styled } from '@mui/material/styles';

const StyledCard = styled(Card)(({ theme }) => ({
  height: '100%',
  display: 'flex',
  flexDirection: 'column',
  transition: 'transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out',
  '&:hover': {
    transform: 'translateY(-4px)',
    boxShadow: '0 4px 20px rgba(0,0,0,0.1)',
  },
}));

const StyledButton = styled(Button)(({ theme }) => ({
  marginTop: 'auto',
  color: '#800000',
  '&:hover': {
    backgroundColor: 'rgba(128, 0, 0, 0.04)',
  },
}));

interface ConsultationCardProps {
  title: string;
  description: string;
  onClick: () => void;
}

export default function ConsultationCard({ title, description, onClick }: ConsultationCardProps) {
  return (
    <StyledCard>
      <CardContent sx={{ flexGrow: 1, display: 'flex', flexDirection: 'column' }}>
        <Typography variant="h6" component="h2" gutterBottom sx={{ fontWeight: 'bold' }}>
          {title}
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          {description}
        </Typography>
        <Box sx={{ mt: 'auto' }}>
          <StyledButton
            endIcon={<ArrowForwardIcon />}
            onClick={onClick}
            fullWidth
          >
            Clique para acessar
          </StyledButton>
        </Box>
      </CardContent>
    </StyledCard>
  );
} 