import React from 'react';
import {
  Box,
  Grid,
  Paper,
  Typography,
  Card,
  CardContent,
  CardHeader,
  IconButton,
  Tooltip,
} from '@mui/material';
import {
  Refresh as RefreshIcon,
  Download as DownloadIcon,
} from '@mui/icons-material';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip as RechartsTooltip,
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from 'recharts';

// Cores para os gráficos
const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8'];

interface DashboardProps {
  userType: 'cfo' | 'cro';
  isAdmin?: boolean;
  isSupervisor?: boolean;
}

export default function Dashboard({ userType, isAdmin, isSupervisor }: DashboardProps) {
  // Dados de exemplo - substituir por dados reais da API
  const statusData = [
    { name: 'Pendentes', value: 30 },
    { name: 'Em Análise', value: 20 },
    { name: 'Aprovados', value: 15 },
    { name: 'Reprovados', value: 5 },
  ];

  const timelineData = [
    { name: 'Jan', pendentes: 4, emAnalise: 3, aprovados: 2, reprovados: 1 },
    { name: 'Fev', pendentes: 3, emAnalise: 2, aprovados: 4, reprovados: 1 },
    { name: 'Mar', pendentes: 5, emAnalise: 4, aprovados: 3, reprovados: 2 },
    { name: 'Abr', pendentes: 2, emAnalise: 3, aprovados: 5, reprovados: 1 },
  ];

  const handleRefresh = () => {
    // Implementar refresh dos dados
    console.log('Atualizando dados...');
  };

  const handleExport = () => {
    // Implementar exportação dos dados
    console.log('Exportando dados...');
  };

  return (
    <Box sx={{ flexGrow: 1, p: 3 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
        <Typography variant="h4" component="h1">
          Dashboard
        </Typography>
        <Box>
          <Tooltip title="Atualizar">
            <IconButton onClick={handleRefresh}>
              <RefreshIcon />
            </IconButton>
          </Tooltip>
          <Tooltip title="Exportar">
            <IconButton onClick={handleExport}>
              <DownloadIcon />
            </IconButton>
          </Tooltip>
        </Box>
      </Box>

      <Grid container spacing={3}>
        {/* KPIs */}
        <Grid item xs={12} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Total de Auditorias
              </Typography>
              <Typography variant="h4">70</Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Pendentes
              </Typography>
              <Typography variant="h4" color="warning.main">30</Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Em Análise
              </Typography>
              <Typography variant="h4" color="info.main">20</Typography>
            </CardContent>
          </Card>
        </Grid>
        <Grid item xs={12} md={3}>
          <Card>
            <CardContent>
              <Typography color="textSecondary" gutterBottom>
                Concluídas
              </Typography>
              <Typography variant="h4" color="success.main">20</Typography>
            </CardContent>
          </Card>
        </Grid>

        {/* Gráfico de Status */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              Status das Auditorias
            </Typography>
            <Box sx={{ height: 300 }}>
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={statusData}
                    cx="50%"
                    cy="50%"
                    labelLine={false}
                    outerRadius={80}
                    fill="#8884d8"
                    dataKey="value"
                    label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                  >
                    {statusData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                    ))}
                  </Pie>
                  <RechartsTooltip />
                </PieChart>
              </ResponsiveContainer>
            </Box>
          </Paper>
        </Grid>

        {/* Gráfico de Timeline */}
        <Grid item xs={12} md={6}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              Evolução Mensal
            </Typography>
            <Box sx={{ height: 300 }}>
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={timelineData}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="name" />
                  <YAxis />
                  <RechartsTooltip />
                  <Legend />
                  <Bar dataKey="pendentes" fill="#FFBB28" />
                  <Bar dataKey="emAnalise" fill="#0088FE" />
                  <Bar dataKey="aprovados" fill="#00C49F" />
                  <Bar dataKey="reprovados" fill="#FF8042" />
                </BarChart>
              </ResponsiveContainer>
            </Box>
          </Paper>
        </Grid>

        {/* Últimas Atividades */}
        <Grid item xs={12}>
          <Paper sx={{ p: 2 }}>
            <Typography variant="h6" gutterBottom>
              Últimas Atividades
            </Typography>
            {/* Implementar lista de últimas atividades */}
          </Paper>
        </Grid>
      </Grid>
    </Box>
  );
} 