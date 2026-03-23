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
  IconButton,
  Tooltip,
  TablePagination,
  Collapse,
  Card,
  CardContent,
  Button,
} from '@mui/material';
import {
  FilterList as FilterIcon,
  Visibility as ViewIcon,
  Add as AddIconMui,
  Edit as EditIconMui,
  Delete as DeleteIconMui,
  KeyboardArrowDown,
  KeyboardArrowUp,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';

interface ChangeLogEntry {
  id: number;
  user_id: number | null;
  username: string | null;
  action: string;
  resource: string;
  resource_id: string | null;
  description: string | null;
  request_body: string | null;
  ip_address: string | null;
  endpoint: string;
  created_at: string | null;
}

const ACTION_LABELS: Record<string, { label: string; color: 'success' | 'info' | 'error' }> = {
  CREATE: { label: 'Criação', color: 'success' },
  UPDATE: { label: 'Edição', color: 'info' },
  DELETE: { label: 'Exclusão', color: 'error' },
};

const RESOURCE_LABELS: Record<string, string> = {
  users: 'Usuários',
  roles: 'Perfis',
  permissions: 'Permissões',
  menus: 'Menus',
  documentos: 'Documentos',
  auditorias: 'Auditorias',
  temas: 'Temas',
  servicos: 'Serviços',
};

function ExpandableRow({ row }: { row: ChangeLogEntry }) {
  const [open, setOpen] = useState(false);

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR');
  };

  const actionInfo = ACTION_LABELS[row.action] || { label: row.action, color: 'info' as const };

  let parsedBody: string | null = null;
  if (row.request_body) {
    try {
      parsedBody = JSON.stringify(JSON.parse(row.request_body), null, 2);
    } catch {
      parsedBody = row.request_body;
    }
  }

  return (
    <>
      <TableRow hover>
        <TableCell sx={{ width: 40, p: 0.5 }}>
          {parsedBody && (
            <IconButton size="small" onClick={() => setOpen(!open)}>
              {open ? <KeyboardArrowUp /> : <KeyboardArrowDown />}
            </IconButton>
          )}
        </TableCell>
        <TableCell>{formatDate(row.created_at)}</TableCell>
        <TableCell>{row.username || '-'}</TableCell>
        <TableCell>
          <Chip
            label={actionInfo.label}
            color={actionInfo.color}
            size="small"
            variant="outlined"
          />
        </TableCell>
        <TableCell>{RESOURCE_LABELS[row.resource] || row.resource}</TableCell>
        <TableCell>{row.resource_id || '-'}</TableCell>
        <TableCell sx={{ maxWidth: 300, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
          <Tooltip title={row.description || ''} arrow>
            <span>{row.description || '-'}</span>
          </Tooltip>
        </TableCell>
        <TableCell>{row.ip_address || '-'}</TableCell>
      </TableRow>
      {parsedBody && (
        <TableRow>
          <TableCell colSpan={8} sx={{ py: 0, borderBottom: open ? undefined : 'none' }}>
            <Collapse in={open} timeout="auto" unmountOnExit>
              <Box sx={{ py: 1.5, px: 2 }}>
                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                  Dados enviados
                </Typography>
                <Box
                  component="pre"
                  sx={{
                    bgcolor: 'grey.50',
                    p: 1.5,
                    borderRadius: 1,
                    fontSize: '0.75rem',
                    overflow: 'auto',
                    maxHeight: 300,
                    m: 0,
                    border: '1px solid',
                    borderColor: 'divider',
                  }}
                >
                  {parsedBody}
                </Box>
              </Box>
            </Collapse>
          </TableCell>
        </TableRow>
      )}
    </>
  );
}

export default function LogAlteracoes() {
  const [logs, setLogs] = useState<ChangeLogEntry[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(25);
  const [showFilters, setShowFilters] = useState(false);

  // Filtros
  const [filterUsername, setFilterUsername] = useState('');
  const [filterAction, setFilterAction] = useState('');
  const [filterResource, setFilterResource] = useState('');
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
      if (filterAction) params.action = filterAction;
      if (filterResource) params.resource = filterResource;
      if (filterStartDate) params.start_date = filterStartDate;
      if (filterEndDate) params.end_date = filterEndDate;

      const response = await api.get('/activity-logs/changes', { params });
      setLogs(response.data.logs);
      setTotal(response.data.total);
    } catch (error) {
      console.error('Erro ao carregar log de alterações:', error);
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
    setFilterAction('');
    setFilterResource('');
    setFilterStartDate('');
    setFilterEndDate('');
    setPage(0);
    setTimeout(fetchLogs, 0);
  };

  return (
    <PageContainer>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Box>
          <Typography variant="h5" component="h1" fontWeight={600}>
            Log de Alterações
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Histórico de criações, edições e exclusões no sistema
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
              <FormControl size="small" sx={{ minWidth: 140 }}>
                <InputLabel>Ação</InputLabel>
                <Select
                  value={filterAction}
                  label="Ação"
                  onChange={(e) => setFilterAction(e.target.value)}
                >
                  <MenuItem value="">Todas</MenuItem>
                  <MenuItem value="CREATE">Criação</MenuItem>
                  <MenuItem value="UPDATE">Edição</MenuItem>
                  <MenuItem value="DELETE">Exclusão</MenuItem>
                </Select>
              </FormControl>
              <FormControl size="small" sx={{ minWidth: 150 }}>
                <InputLabel>Recurso</InputLabel>
                <Select
                  value={filterResource}
                  label="Recurso"
                  onChange={(e) => setFilterResource(e.target.value)}
                >
                  <MenuItem value="">Todos</MenuItem>
                  {Object.entries(RESOURCE_LABELS).map(([key, label]) => (
                    <MenuItem key={key} value={key}>{label}</MenuItem>
                  ))}
                </Select>
              </FormControl>
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
                  <TableCell sx={{ width: 40 }} />
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 160 }}>Data/Hora</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>Usuário</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 100 }}>Ação</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>Recurso</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 80 }}>ID</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 200 }}>Descrição</TableCell>
                  <TableCell sx={{ fontWeight: 'bold', minWidth: 120 }}>IP</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {logs.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={8} align="center" sx={{ py: 4 }}>
                      <Typography color="text.secondary">
                        Nenhuma alteração registrada
                      </Typography>
                    </TableCell>
                  </TableRow>
                ) : (
                  logs.map((log) => <ExpandableRow key={log.id} row={log} />)
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
