import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Typography, Box, Paper, Grid, Card, CardContent, CardActionArea,
  CircularProgress, Divider,
} from '@mui/material';
import {
  People as PeopleIcon,
  Business as BusinessIcon,
  Search as SearchIcon,
  Assessment as ReportIcon,
  TrendingUp as TrendingIcon,
  PersonSearch as PersonSearchIcon,
  Gavel as GavelIcon,
  BarChart as ChartIcon,
  Description as DescriptionIcon,
  HowToVote as VoteIcon,
  Storage as StorageIcon,
  Badge as BadgeIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

interface DashboardStats {
  total_users: number;
  active_users: number;
  active_today: number;
  recent_activity_7d: number;
}

interface DB3Stats {
  profissionais_ativos: number | null;
  empresas_ativas: number | null;
  profissionais_por_categoria: { categoria: string; total: number }[];
  profissionais_por_uf: { uf: string; total: number }[];
}

const quickLinks = [
  { path: '/consulta-integrada', title: 'Consulta Integrada', icon: <PersonSearchIcon />, perm: 'view_consulta_integrada', color: '#1976d2' },
  { path: '/consulta-identidade', title: 'Identidade Profissional', icon: <BadgeIcon />, perm: 'view_consulta_identidade', color: '#388e3c' },
  { path: '/consulta-rfb', title: 'Consulta RFB', icon: <SearchIcon />, perm: 'view_consulta_rfb', color: '#f57c00' },
  { path: '/consulta-fiscalizacao', title: 'Fiscalizacao', icon: <GavelIcon />, perm: 'view_consulta_fiscalizacao', color: '#d32f2f' },
  { path: '/consulta-auditorias', title: 'Auditorias', icon: <DescriptionIcon />, perm: 'view_consulta_auditoria', color: '#7b1fa2' },
  { path: '/consulta-estatistica', title: 'Estatisticas', icon: <ChartIcon />, perm: 'view_consulta_estatistica', color: '#0288d1' },
  { path: '/relatorios-diversos', title: 'Relatorios', icon: <ReportIcon />, perm: 'view_relatorios_diversos', color: '#455a64' },
  { path: '/eleicoes-regionais', title: 'Eleicoes', icon: <VoteIcon />, perm: 'view_eleicoes_regionais', color: '#c62828' },
  { path: '/tabelas-centralizadas', title: 'Tabelas Centralizadas', icon: <StorageIcon />, perm: 'view_tabelas_centralizadas', color: '#00695c' },
];

export default function Home() {
  const navigate = useNavigate();
  const { user, hasPermission } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [db3Stats, setDb3Stats] = useState<DB3Stats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const [localRes, db3Res] = await Promise.allSettled([
          api.get('/dashboard/stats'),
          api.get('/dashboard/stats/db3'),
        ]);
        if (localRes.status === 'fulfilled') setStats(localRes.value.data);
        if (db3Res.status === 'fulfilled') setDb3Stats(db3Res.value.data);
      } catch (err) {
        console.error('Erro ao carregar dashboard:', err);
      } finally {
        setLoading(false);
      }
    };
    fetchStats();
  }, []);

  const visibleLinks = quickLinks.filter(l => hasPermission(l.perm));

  return (
    <PageContainer>
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" gutterBottom>
          Bem-vindo, {user?.full_name || user?.username || 'Usuario'}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Sistema de Consultas - Conselho Federal de Odontologia
        </Typography>
      </Box>

      {/* Indicadores principais */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={6} sm={3}>
          <Paper sx={{ p: 2.5, textAlign: 'center', borderTop: '4px solid #1976d2' }}>
            <PeopleIcon sx={{ fontSize: 36, color: '#1976d2', mb: 1 }} />
            <Typography variant="h4" sx={{ fontWeight: 'bold' }}>
              {loading ? <CircularProgress size={24} /> : (db3Stats?.profissionais_ativos?.toLocaleString('pt-BR') ?? '-')}
            </Typography>
            <Typography variant="body2" color="text.secondary">Profissionais Ativos</Typography>
          </Paper>
        </Grid>
        <Grid item xs={6} sm={3}>
          <Paper sx={{ p: 2.5, textAlign: 'center', borderTop: '4px solid #388e3c' }}>
            <BusinessIcon sx={{ fontSize: 36, color: '#388e3c', mb: 1 }} />
            <Typography variant="h4" sx={{ fontWeight: 'bold' }}>
              {loading ? <CircularProgress size={24} /> : (db3Stats?.empresas_ativas?.toLocaleString('pt-BR') ?? '-')}
            </Typography>
            <Typography variant="body2" color="text.secondary">Empresas Ativas</Typography>
          </Paper>
        </Grid>
        <Grid item xs={6} sm={3}>
          <Paper sx={{ p: 2.5, textAlign: 'center', borderTop: '4px solid #f57c00' }}>
            <TrendingIcon sx={{ fontSize: 36, color: '#f57c00', mb: 1 }} />
            <Typography variant="h4" sx={{ fontWeight: 'bold' }}>
              {loading ? <CircularProgress size={24} /> : (stats?.recent_activity_7d?.toLocaleString('pt-BR') ?? '-')}
            </Typography>
            <Typography variant="body2" color="text.secondary">Consultas (7 dias)</Typography>
          </Paper>
        </Grid>
        <Grid item xs={6} sm={3}>
          <Paper sx={{ p: 2.5, textAlign: 'center', borderTop: '4px solid #7b1fa2' }}>
            <PeopleIcon sx={{ fontSize: 36, color: '#7b1fa2', mb: 1 }} />
            <Typography variant="h4" sx={{ fontWeight: 'bold' }}>
              {loading ? <CircularProgress size={24} /> : (stats?.active_today ?? '-')}
            </Typography>
            <Typography variant="body2" color="text.secondary">Usuarios Ativos Hoje</Typography>
          </Paper>
        </Grid>
      </Grid>

      {/* Distribuicao por Categoria e UF */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom sx={{ color: '#1976d2' }}>
              Profissionais por Categoria
            </Typography>
            <Divider sx={{ mb: 2 }} />
            {loading ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 3 }}><CircularProgress /></Box>
            ) : (
              (db3Stats?.profissionais_por_categoria || []).map((item, idx) => (
                <Box key={idx} sx={{ display: 'flex', justifyContent: 'space-between', py: 1, borderBottom: '1px solid #f0f0f0' }}>
                  <Typography variant="body2">{item.categoria || 'N/A'}</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {item.total?.toLocaleString('pt-BR')}
                  </Typography>
                </Box>
              ))
            )}
          </Paper>
        </Grid>
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom sx={{ color: '#388e3c' }}>
              Top 10 UFs por Profissionais
            </Typography>
            <Divider sx={{ mb: 2 }} />
            {loading ? (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 3 }}><CircularProgress /></Box>
            ) : (
              (db3Stats?.profissionais_por_uf || []).map((item, idx) => (
                <Box key={idx} sx={{ display: 'flex', justifyContent: 'space-between', py: 1, borderBottom: '1px solid #f0f0f0' }}>
                  <Typography variant="body2">{item.uf || 'N/A'}</Typography>
                  <Typography variant="body2" sx={{ fontWeight: 'bold' }}>
                    {item.total?.toLocaleString('pt-BR')}
                  </Typography>
                </Box>
              ))
            )}
          </Paper>
        </Grid>
      </Grid>

      {/* Acesso rapido */}
      <Typography variant="h6" gutterBottom>Acesso Rapido</Typography>
      <Divider sx={{ mb: 2 }} />
      <Grid container spacing={2}>
        {visibleLinks.map((link) => (
          <Grid item xs={6} sm={4} md={3} key={link.path}>
            <Card
              sx={{
                height: '100%',
                '&:hover': { boxShadow: 6, transform: 'translateY(-2px)', transition: 'all 0.2s' },
              }}
            >
              <CardActionArea onClick={() => navigate(link.path)} sx={{ p: 2, textAlign: 'center' }}>
                <Box sx={{ color: link.color, mb: 1 }}>{React.cloneElement(link.icon, { sx: { fontSize: 36 } })}</Box>
                <Typography variant="body2" sx={{ fontWeight: 500 }}>{link.title}</Typography>
              </CardActionArea>
            </Card>
          </Grid>
        ))}
      </Grid>
    </PageContainer>
  );
}
