import React, { useState, useEffect } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
  Card, CardContent, CardActionArea, IconButton,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

interface TipoPrescricao { codigo: string; nome: string; }

const DESCRICOES: Record<string, string> = {
  'por-tipo': 'Quantidade de prescrições agrupadas por tipo em um período.',
  'por-profissional': 'Busca prescrições por nome do profissional (CD).',
  'por-periodo': 'Lista prescrições em um período com filtros de UF e tipo.',
  'por-paciente': 'Busca prescrições por nome ou CPF do paciente.',
  'ultimos-registros': 'Últimos 3000 registros de prescrições.',
  'por-uf': 'Prescrições agrupadas por UF.',
  'validacao': 'Validação de prescrição por ID.',
  'profissionais': 'Busca profissionais na base de prescrições.',
};

// Mapeamento tipo → endpoint
const ENDPOINTS: Record<string, string> = {
  'por-tipo': '/consulta-prescricao/por-tipo',
  'por-profissional': '/consulta-prescricao/por-profissional',
  'por-periodo': '/consulta-prescricao/por-periodo',
  'por-paciente': '/consulta-prescricao/por-paciente',
  'ultimos-registros': '/consulta-prescricao/ultimos-registros',
  'por-uf': '/consulta-prescricao/por-uf',
  'validacao': '/consulta-prescricao/validacao',
  'profissionais': '/consulta-prescricao/profissionais',
};

const REQUER_PERIODO = ['por-tipo', 'por-periodo'];
const SEM_FILTROS = ['ultimos-registros', 'por-uf'];

export default function ConsultaPrescricao() {
  const [tipos, setTipos] = useState<TipoPrescricao[]>([]);
  const [selectedTipo, setSelectedTipo] = useState<string | null>(null);
  const [filters, setFilters] = useState({
    data_inicial: '', data_final: '', cd_nome: '', uf: '', tipo: '',
    paciente_nome: '', paciente_cpf: '', id_prescricao: '', termo: '',
  });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [loadingTipos, setLoadingTipos] = useState(true);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    api.get('/consulta-prescricao/tipos')
      .then(res => setTipos(res.data))
      .catch(() => {})
      .finally(() => setLoadingTipos(false));
  }, []);

  const handleCardClick = (codigo: string) => {
    setSelectedTipo(codigo);
    setFilters({ data_inicial: '', data_final: '', cd_nome: '', uf: '', tipo: '', paciente_nome: '', paciente_cpf: '', id_prescricao: '', termo: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ data_inicial: '', data_final: '', cd_nome: '', uf: '', tipo: '', paciente_nome: '', paciente_cpf: '', id_prescricao: '', termo: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const handleSearch = async () => {
    if (!selectedTipo) return;
    if (REQUER_PERIODO.includes(selectedTipo) && (!filters.data_inicial || !filters.data_final)) {
      setSnackbar({ open: true, message: 'Informe data inicial e final.', severity: 'warning' }); return;
    }
    if (selectedTipo === 'por-profissional' && !filters.cd_nome) {
      setSnackbar({ open: true, message: 'Informe o nome do profissional.', severity: 'warning' }); return;
    }
    if (selectedTipo === 'por-paciente' && !filters.paciente_nome && !filters.paciente_cpf) {
      setSnackbar({ open: true, message: 'Informe nome ou CPF do paciente.', severity: 'warning' }); return;
    }
    if (selectedTipo === 'validacao' && !filters.id_prescricao) {
      setSnackbar({ open: true, message: 'Informe o ID da prescrição.', severity: 'warning' }); return;
    }
    if (selectedTipo === 'profissionais' && !filters.termo) {
      setSnackbar({ open: true, message: 'Informe o nome ou CPF do profissional.', severity: 'warning' }); return;
    }

    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      if (filters.data_inicial) params.append('data_inicial', filters.data_inicial);
      if (filters.data_final) params.append('data_final', filters.data_final);
      if (filters.cd_nome) params.append('cd_nome', filters.cd_nome);
      if (filters.uf) params.append('uf', filters.uf);
      if (filters.tipo) params.append('tipo', filters.tipo);
      if (filters.paciente_nome) params.append('paciente_nome', filters.paciente_nome);
      if (filters.paciente_cpf) params.append('paciente_cpf', filters.paciente_cpf);
      if (filters.id_prescricao) params.append('id_prescricao', filters.id_prescricao);
      if (filters.termo) params.append('termo', filters.termo);

      const endpoint = ENDPOINTS[selectedTipo];
      const res = await api.get(`${endpoint}?${params}`);
      setResultados(res.data.resultados); setTotal(res.data.total);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ data_inicial: '', data_final: '', cd_nome: '', uf: '', tipo: '', paciente_nome: '', paciente_cpf: '', id_prescricao: '', termo: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];
  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || '';

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Prescrição</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Consulta de prescrições odontológicas (base db_prescricao).
      </Typography>
      <Divider sx={{ mb: 3 }} />

      {!selectedTipo && (
        <>
          {loadingTipos ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : (
            <Grid container spacing={3}>
              {tipos.map((tipo) => (
                <Grid item xs={12} sm={6} md={4} key={tipo.codigo}>
                  <Card sx={{
                    height: '100%', display: 'flex', flexDirection: 'column',
                    '&:hover': { boxShadow: 6, transform: 'translateY(-4px)', transition: 'all 0.3s ease-in-out' },
                  }}>
                    <CardActionArea onClick={() => handleCardClick(tipo.codigo)} sx={{ flexGrow: 1 }}>
                      <CardContent>
                        <Typography gutterBottom variant="h6" component="h2" sx={{ fontWeight: 'bold' }}>
                          {tipo.nome}
                        </Typography>
                        <Typography variant="body2" color="text.secondary">
                          {DESCRICOES[tipo.codigo] || 'Clique para consultar.'}
                        </Typography>
                      </CardContent>
                    </CardActionArea>
                  </Card>
                </Grid>
              ))}
            </Grid>
          )}
        </>
      )}

      {selectedTipo && (
        <>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
            <IconButton onClick={handleBack} size="small"><ArrowBackIcon /></IconButton>
            <Typography variant="h6">{selectedNome}</Typography>
          </Box>
          <Divider sx={{ mb: 2 }} />

          {!SEM_FILTROS.includes(selectedTipo) && (
            <Grid container spacing={2} sx={{ mb: 2 }}>
              {REQUER_PERIODO.includes(selectedTipo) && (
                <>
                  <Grid item xs={6} sm={4} md={3}>
                    <TextField fullWidth size="small" label="Data Inicial" type="date" InputLabelProps={{ shrink: true }}
                      value={filters.data_inicial} onChange={e => setFilters(p => ({ ...p, data_inicial: e.target.value }))} />
                  </Grid>
                  <Grid item xs={6} sm={4} md={3}>
                    <TextField fullWidth size="small" label="Data Final" type="date" InputLabelProps={{ shrink: true }}
                      value={filters.data_final} onChange={e => setFilters(p => ({ ...p, data_final: e.target.value }))} />
                  </Grid>
                </>
              )}
              {selectedTipo === 'por-periodo' && (
                <>
                  <Grid item xs={6} sm={4} md={3}>
                    <FormControl fullWidth size="small"><InputLabel>UF</InputLabel>
                      <Select value={filters.uf} label="UF" onChange={e => setFilters(p => ({ ...p, uf: e.target.value }))}>
                        <MenuItem value="">Todos</MenuItem>
                        {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                      </Select>
                    </FormControl>
                  </Grid>
                  <Grid item xs={6} sm={4} md={3}>
                    <TextField fullWidth size="small" label="Tipo" value={filters.tipo}
                      onChange={e => setFilters(p => ({ ...p, tipo: e.target.value }))} />
                  </Grid>
                </>
              )}
              {selectedTipo === 'por-profissional' && (
                <Grid item xs={12} sm={6} md={4}>
                  <TextField fullWidth size="small" label="Nome do Profissional (CD)" value={filters.cd_nome}
                    onChange={e => setFilters(p => ({ ...p, cd_nome: e.target.value }))}
                    onKeyDown={e => e.key === 'Enter' && handleSearch()} />
                </Grid>
              )}
              {selectedTipo === 'por-paciente' && (
                <>
                  <Grid item xs={6} sm={4} md={4}>
                    <TextField fullWidth size="small" label="Nome do Paciente" value={filters.paciente_nome}
                      onChange={e => setFilters(p => ({ ...p, paciente_nome: e.target.value }))}
                      onKeyDown={e => e.key === 'Enter' && handleSearch()} />
                  </Grid>
                  <Grid item xs={6} sm={4} md={3}>
                    <TextField fullWidth size="small" label="CPF do Paciente" value={filters.paciente_cpf}
                      onChange={e => setFilters(p => ({ ...p, paciente_cpf: e.target.value }))}
                      onKeyDown={e => e.key === 'Enter' && handleSearch()} />
                  </Grid>
                </>
              )}
              {selectedTipo === 'validacao' && (
                <Grid item xs={12} sm={6} md={4}>
                  <TextField fullWidth size="small" label="ID da Prescrição" value={filters.id_prescricao}
                    onChange={e => setFilters(p => ({ ...p, id_prescricao: e.target.value }))}
                    onKeyDown={e => e.key === 'Enter' && handleSearch()} />
                </Grid>
              )}
              {selectedTipo === 'profissionais' && (
                <Grid item xs={12} sm={6} md={4}>
                  <TextField fullWidth size="small" label="Nome ou CPF do Profissional" value={filters.termo}
                    onChange={e => setFilters(p => ({ ...p, termo: e.target.value }))}
                    onKeyDown={e => e.key === 'Enter' && handleSearch()} />
                </Grid>
              )}
            </Grid>
          )}

          <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
            <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
            {searched && resultados.length > 0 && (
              <Button
                variant="outlined" color="success" startIcon={<DownloadIcon />} disabled={exporting}
                onClick={async () => {
                  setExporting(true);
                  try {
                    const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: k }));
                    await exportService.exportGenericExcel({
                      data: resultados.map(r => ({ ...r })),
                      columns: cols,
                      title: `Consulta Prescrição - ${selectedNome}`,
                      filename: `prescricao_${selectedTipo}`,
                    });
                    setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
                  } catch {
                    setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
                  } finally { setExporting(false); }
                }}
              >
                {exporting ? 'Exportando...' : 'Exportar Excel'}
              </Button>
            )}
          </Box>
          <Divider sx={{ mb: 2 }} />

          {loading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : searched && (
            <>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                <strong>{selectedNome}</strong> - {total} resultado(s)
              </Typography>
              {resultados.length > 0 && (
                <TableContainer sx={{ maxHeight: 'calc(100vh - 400px)' }}>
                  <Table stickyHeader size="small">
                    <TableHead>
                      <TableRow>
                        {columns.map(col => (
                          <TableCell key={col} sx={{ fontWeight: 'bold', whiteSpace: 'nowrap' }}>{col}</TableCell>
                        ))}
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {resultados.map((r, i) => (
                        <TableRow key={i} hover>
                          {columns.map(col => (
                            <TableCell key={col}>{r[col] != null ? String(r[col]) : '-'}</TableCell>
                          ))}
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </TableContainer>
              )}
            </>
          )}
        </>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
