import React, { useState, useEffect } from 'react';
import {
  Typography,
  Box,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  CircularProgress,
  Chip,
  TablePagination,
  Collapse,
  Card,
  CardContent,
  Button,
} from '@mui/material';
import { FilterList as FilterIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

interface ActivityLogEntry {
  id: number;
  user_id: number | null;
  username: string | null;
  method: string;
  path: string;
  status_code: number | null;
  ip_address: string | null;
  user_agent: string | null;
  query_params: string | null;
  duration_ms: number | null;
  created_at: string | null;
}

const METHOD_COLORS: Record<string, 'success' | 'info' | 'warning' | 'error' | 'default'> = {
  GET: 'info',
  POST: 'success',
  PUT: 'warning',
  PATCH: 'warning',
  DELETE: 'error',
};

export default function LogAtividades() {
  const [logs, setLogs] = useState<ActivityLogEntry[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(25);
  const [showFilters, setShowFilters] = useState(false);

  // Filtros
  const [filterUsername, setFilterUsername] = useState('');
  const [filterMethod, setFilterMethod] = useState('');
  const [filterPath, setFilterPath] = useState('');
  const [filterStartDate, setFilterStartDate] = useState('');
  const [filterEndDate, setFilterEndDate] = useState('');

  const fetchLogs = async () => {
    try {
      setLoading(true);
      const params: Record<string, string | number> = {
        page: page + 1,
        per_page: rowsPerPage,
      };
      if (filterUsername) params.username = filterUsername;
      if (filterMethod) params.method = filterMethod;
      if (filterPath) params.path = filterPath;
      if (filterStartDate) params.start_date = filterStartDate;
      if (filterEndDate) params.end_date = filterEndDate;

      const response = await api.get('/activity-logs', { params });
      setLogs(response.data.logs);
      setTotal(response.data.total);
    } catch (error) {
      console.error('Erro ao carregar log de atividades:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchLogs();
  }, [page, rowsPerPage]);

  const handleFilter = () => {
    setPage(0);
    fetchLogs();
  };

  const handleClearFilters = () => {
    setFilterUsername('');
    setFilterMethod('');
    setFilterPath('');
    setFilterStartDate('');
    setFilterEndDate('');
    setPage(0);
    setTimeout(fetchLogs, 0);
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('pt-BR');
  };

  const getStatusColor = (code: number | null) => {
    if (!code) return 'default';
    if (code < 300) return 'success';
    if (code < 400) return 'info';
    if (code < 500) return 'warning';
    return 'error';
  };

  return (
    <PageContainer>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Box>
          <Typography variant="h5" component="h1" fontWeight={600}>
            Log de Atividades
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Registro de todas as requisições ao sistema
          </Typography>
        </Box>
        <Button
          variant="outlined"
          startIcon={<FilterIcon />}
          onClick={() => setShowFilters(!showFilters)}
          size="small"
        >
          Filtros
        </Button>
      </Box>

      <Collapse in={showFilters}>
        <Card variant="outlined" sx={{ mb: 2 }}>
          <CardContent sx={{ py: 1.5, '&:last-child': { pb: 1.5 } }}>
            <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap', alignItems: 'center' }}>
              <TextField
                label="Usuário"
                size="small"
                value={filterUsername}
                onChange={(e) => setFilterUsername(e.target.value)}
                sx={{ minWidth: 150 }}
              />
              <FormControl size="small" sx={{ minWidth: 120 }}>
                <InputLabel>Método</InputLabel>
                <Select
                  value={filterMethod}
                  label="Método"
                  onChange={(e) => setFilterMethod(e.target.value)}
                >
                  <MenuItem value="">Todos</MenuItem>
                  <MenuItem value="GET">GET</MenuItem>
                  <MenuItem value="POST">POST</MenuItem>
                  <MenuItem value="PUT">PUT</MenuItem>
                  <MenuItem value="PATCH">PATCH</MenuItem>
                  <MenuItem value="DELETE">DELETE</MenuItem>
                </Select>
              </FormControl>
              <TextField
                label="Rota"
                size="small"
                value={filterPath}
                onChange={(e) => setFilterPath(e.target.value)}
                placeholder="Ex: /users"
                sx={{ minWidth: 150 }}
              />
              <TextField
                label="Data início"
                type="date"
                size="small"
                value={filterStartDate}
                onChange={(e) => setFilterStartDate(e.target.value)}
                InputLabelProps={{ shrink: true }}
                sx={{ minWidth: 150 }}
              />
              <TextField
                label="Data fim"
                type="date"
                size="small"
                value={filterEndDate}
                onChange={(e) => setFilterEndDate(e.target.value)}
                InputLabelProps={{ shrink: true }}
                sx={{ minWidth: 150 }}
              />
              <Button variant="contained" size="small" onClick={handleFilter}>
                Buscar
              </Button>
              <Button variant="text" size="small" onClick={handleClearFilters}>
                Limpar
              </Button>
            </Box>
          </CardContent>
        </Card>
      </Collapse>

      {loading ? (
        <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
          <CircularProgress />
        </Box>
      ) : (
        <>
          <TableContainer sx={{ maxHeight: 'calc(100vh - 320px)' }}>
            <Table stickyHeader size="small">
              <TableHead>
                <TableRow>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 160 }}>Data/Hora</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>Usuário</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }}>Método</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 250 }}>Rota</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }}>Status</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 100 }}>Duração</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>IP</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {logs.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} align="center" sx={{ py: 4 }}>
                      <Typography color="text.secondary">
                        Nenhuma atividade registrada
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : (
                  logs.map((log) => (
                    <TableRow key={log.id} hover>
                      <TableCell>{formatDate(log.created_at)}</TableCell>
                      <TableCell>{log.username || '-'}</TableCell>
                      <TableCell>
                        <Chip
                          label={log.method}
                          color={METHOD_COLORS[log.method] || 'default'}
                          size="small"
                          variant="outlined"
                          sx={{ fontWeight: 600, fontFamily: 'monospace' }}
                        />
                      </TableCell>
                      <TableCell sx={{ fontFamily: 'monospace', fontSize: '0.8rem' }}>
                        {log.path}
                      </TableCell>
                      <TableCell>
                        <Chip
                          label={log.status_code || '-'}
                          color={getStatusColor(log.status_code) as any}
                          size="small"
                          variant="filled"
                          sx={{ fontWeight: 600, minWidth: 48 }}
                        />
                      </TableCell>
                      <TableCell>
                        {log.duration_ms != null ? `${log.duration_ms}ms` : '-'}
                      </TableCell>
                      <TableCell>{log.ip_address || '-'}</TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </TableContainer>
          <TablePagination
            component="div"
            count={total}
            page={page}
            onPageChange={(_, newPage) => setPage(newPage)}
            rowsPerPage={rowsPerPage}
            onRowsPerPageChange={(e) => {
              setRowsPerPage(parseInt(e.target.value, 10));
              setPage(0);
            }}
            rowsPerPageOptions={[10, 25, 50, 100]}
            labelRowsPerPage="Linhas por página:"
            labelDisplayedRows={({ from, to, count }) => `${from}-${to} de ${count}`}
          />
        </>
      )}
    </PageContainer>
  );
}
