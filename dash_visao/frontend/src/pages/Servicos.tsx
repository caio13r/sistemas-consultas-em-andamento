import React, { useState, useEffect } from 'react';
import {
  Typography,
  Box,
  Grid,
  Card,
  CardContent,
  CardActionArea,
  CircularProgress,
  Chip,
} from '@mui/material';
import {
  Search as SearchIcon,
  Assignment as AssignmentIcon,
  Gavel as GavelIcon,
  Badge as BadgeIcon,
  BarChart as BarChartIcon,
  MedicalServices as MedicalIcon,
  AccountBalance as AccountBalanceIcon,
  Public as PublicIcon,
  TableChart as TableChartIcon,
  Assessment as AssessmentIcon,
  HowToVote as HowToVoteIcon,
  Policy as PolicyIcon,
  List as ListIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

interface Servico {
  id: number;
  nome: string;
  slug: string;
  descricao: string | null;
  icone: string | null;
  ativo: boolean;
  ordem: number;
}

const iconMap: Record<string, React.ReactElement> = {
  Search: <SearchIcon sx={{ fontSize: 40 }} />,
  Assignment: <AssignmentIcon sx={{ fontSize: 40 }} />,
  Gavel: <GavelIcon sx={{ fontSize: 40 }} />,
  Badge: <BadgeIcon sx={{ fontSize: 40 }} />,
  BarChart: <BarChartIcon sx={{ fontSize: 40 }} />,
  MedicalServices: <MedicalIcon sx={{ fontSize: 40 }} />,
  AccountBalance: <AccountBalanceIcon sx={{ fontSize: 40 }} />,
  Public: <PublicIcon sx={{ fontSize: 40 }} />,
  TableChart: <TableChartIcon sx={{ fontSize: 40 }} />,
  Assessment: <AssessmentIcon sx={{ fontSize: 40 }} />,
  HowToVote: <HowToVoteIcon sx={{ fontSize: 40 }} />,
  Policy: <PolicyIcon sx={{ fontSize: 40 }} />,
};

export default function Servicos() {
  const [servicos, setServicos] = useState<Servico[]>([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    fetchServicos();
  }, []);

  const fetchServicos = async () => {
    try {
      const response = await api.get<Servico[]>('/servicos/');
      setServicos(response.data);
    } catch (error) {
      console.error('Erro ao carregar serviços:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleClick = (slug: string) => {
    navigate(`/${slug}`);
  };

  if (loading) {
    return (
      <PageContainer>
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <Box sx={{ mb: 4 }}>
        <Typography variant="h5" component="h1" gutterBottom>
          Serviços Disponíveis
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Selecione um serviço para acessar. Apenas serviços com permissão são exibidos.
        </Typography>
      </Box>

      <Grid container spacing={3}>
        {servicos.map((servico) => (
          <Grid item xs={12} sm={6} md={4} lg={3} key={servico.id}>
            <Card
              sx={{
                height: '100%',
                transition: 'transform 0.2s, box-shadow 0.2s',
                '&:hover': {
                  transform: 'translateY(-4px)',
                  boxShadow: 4,
                },
              }}
            >
              <CardActionArea
                onClick={() => handleClick(servico.slug)}
                sx={{ height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'flex-start', p: 0 }}
              >
                <CardContent sx={{ width: '100%' }}>
                  <Box sx={{ display: 'flex', alignItems: 'center', mb: 2 }}>
                    <Box sx={{ color: 'primary.main', mr: 2 }}>
                      {iconMap[servico.icone || ''] || <ListIcon sx={{ fontSize: 40 }} />}
                    </Box>
                    <Typography variant="h6" component="div" sx={{ fontSize: '1rem' }}>
                      {servico.nome}
                    </Typography>
                  </Box>
                  <Typography variant="body2" color="text.secondary">
                    {servico.descricao || 'Sem descrição'}
                  </Typography>
                </CardContent>
              </CardActionArea>
            </Card>
          </Grid>
        ))}

        {servicos.length === 0 && (
          <Grid item xs={12}>
            <Box sx={{ textAlign: 'center', py: 4 }}>
              <Typography color="text.secondary">
                Nenhum serviço disponível para seu perfil.
              </Typography>
            </Box>
          </Grid>
        )}
      </Grid>
    </PageContainer>
  );
}
