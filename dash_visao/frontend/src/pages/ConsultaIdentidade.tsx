import React, { useState, useEffect } from 'react';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Snackbar, Alert,
  Grid, FormControl, InputLabel, Select, MenuItem, Divider,
  Card, CardContent, CardActionArea, IconButton, Chip,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, FileDownload as DownloadIcon,
  ArrowBack as ArrowBackIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';
import { formatColumnLabel } from '../utils/columnLabels';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

interface TipoIdentidade { codigo: string; nome: string; grupo: string; }

const DESCRICOES: Record<string, string> = {
  'busca-identidade': 'Busca por nome, CPF ou AR na base de identidade.',
  'descartadas': 'Identidades descartadas por período e UF.',
  'postagem-estatistica': 'Estatísticas totais de postagem por UF.',
  'postagem-detalhada': 'Detalhamento de postagens por período e UF.',
  'perdidas': 'Identidades reportadas como perdidas.',
  'retornadas': 'Identidades retornadas (devolvidas).',
  'emitidas-consolidado': 'Total consolidado de identidades digitais únicas emitidas.',
  'emitidas-por-cro': 'Emissões consolidadas por CRO.',
  'emitidas-por-periodo': 'Emissões por CRO, ano e mês.',
  'cobranca': 'Dados de cobrança CFO ID por profissional.',
  'estatisticas-producao': 'Produção, postagem e entrega (visão geral).',
  'evolucao-emissao': 'Evolução mensal da emissão de identidades por CRO.',
  'carteirinhas-despachadas': 'Buscar carteirinhas despachadas por CRO, CPF ou inscrição.',
  'carteirinhas-por-periodo': 'Carteirinhas despachadas em um período.',
  'carteirinhas-estatisticas': 'Estatísticas de carteirinhas por CRO.',
};

// Tipos que requerem busca (tipo_busca + valor)
const REQUER_BUSCA = ['busca-identidade'];
// Tipos que aceitam período
const ACEITA_PERIODO = [
  'descartadas', 'postagem-estatistica', 'postagem-detalhada', 'perdidas', 'retornadas',
  'carteirinhas-por-periodo',
];
// Tipos que requerem período obrigatório
const REQUER_PERIODO = ['carteirinhas-por-periodo'];

export default function ConsultaIdentidade() {
  const [tipos, setTipos] = useState<TipoIdentidade[]>([]);
  const [selectedTipo, setSelectedTipo] = useState<string | null>(null);
  const [filters, setFilters] = useState({
    tipo_busca: 'nome', valor: '', cro: '', inicio: '', termino: '', ano: '', mes: '', nome: '',
  });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [nomeConsulta, setNomeConsulta] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingTipos, setLoadingTipos] = useState(true);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    api.get('/consulta-identidade/tipos')
      .then(res => setTipos(res.data))
      .catch(() => {})
      .finally(() => setLoadingTipos(false));
  }, []);

  const handleCardClick = (codigo: string) => {
    setSelectedTipo(codigo);
    setFilters({ tipo_busca: 'nome', valor: '', cro: '', inicio: '', termino: '', ano: '', mes: '', nome: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ tipo_busca: 'nome', valor: '', cro: '', inicio: '', termino: '', ano: '', mes: '', nome: '' });
    setResultados([]); setTotal(0); setSearched(false); setNomeConsulta('');
  };

  const handleSearch = async () => {
    if (!selectedTipo) return;
    if (REQUER_BUSCA.includes(selectedTipo) && !filters.valor) {
      setSnackbar({ open: true, message: 'Informe o valor de busca.', severity: 'warning' }); return;
    }
    if (REQUER_PERIODO.includes(selectedTipo) && (!filters.inicio || !filters.termino)) {
      setSnackbar({ open: true, message: 'Informe o período (início e término).', severity: 'warning' }); return;
    }
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      params.append('tipo', selectedTipo);
      if (filters.cro) params.append('cro', filters.cro);
      if (filters.tipo_busca) params.append('tipo_busca', filters.tipo_busca);
      if (filters.valor) params.append('valor', filters.valor);
      if (filters.inicio) params.append('inicio', filters.inicio);
      if (filters.termino) params.append('termino', filters.termino);
      if (filters.ano) params.append('ano', filters.ano);
      if (filters.mes) params.append('mes', filters.mes);
      if (filters.nome) params.append('nome', filters.nome);
      const res = await api.get(`/consulta-identidade/buscar?${params}`);
      setResultados(res.data.resultados); setTotal(res.data.total); setNomeConsulta(res.data.nome);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ tipo_busca: 'nome', valor: '', cro: '', inicio: '', termino: '', ano: '', mes: '', nome: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];
  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || '';

  // Agrupar tipos
  const grupos = tipos.reduce<Record<string, TipoIdentidade[]>>((acc, t) => {
    const g = t.grupo || 'Outros';
    (acc[g] = acc[g] || []).push(t);
    return acc;
  }, {});

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Identidade</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Verificação de identidade profissional, carteirinhas e CFO ID.
      </Typography>
      <Divider sx={{ mb: 3 }} />

      {!selectedTipo && (
        <>
          {loadingTipos ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : (
            Object.entries(grupos).map(([grupo, items]) => (
              <Box key={grupo} sx={{ mb: 4 }}>
                <Chip label={grupo} color="primary" sx={{ mb: 2 }} />
                <Grid container spacing={3}>
                  {items.map((tipo) => (
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
              </Box>
            ))
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

          <Grid container spacing={2} sx={{ mb: 2 }}>
            {REQUER_BUSCA.includes(selectedTipo) && (
              <>
                <Grid item xs={6} sm={4} md={3}>
                  <FormControl fullWidth size="small">
                    <InputLabel>Tipo de Busca</InputLabel>
                    <Select value={filters.tipo_busca} label="Tipo de Busca" onChange={e => setFilters(p => ({ ...p, tipo_busca: e.target.value }))}>
                      <MenuItem value="nome">Nome</MenuItem>
                      <MenuItem value="cpf">CPF</MenuItem>
                      <MenuItem value="ar">AR</MenuItem>
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} sm={6} md={4}>
                  <TextField fullWidth size="small" label="Valor de busca" value={filters.valor}
                    onChange={e => setFilters(p => ({ ...p, valor: e.target.value }))}
                    onKeyDown={e => e.key === 'Enter' && handleSearch()} />
                </Grid>
              </>
            )}
            {!REQUER_BUSCA.includes(selectedTipo) && (
              <Grid item xs={6} sm={4} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>CRO / UF</InputLabel>
                  <Select value={filters.cro} label="CRO / UF" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
                    <MenuItem value="">Todos</MenuItem>
                    {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                  </Select>
                </FormControl>
              </Grid>
            )}
            {ACEITA_PERIODO.includes(selectedTipo) && (
              <>
                <Grid item xs={6} sm={4} md={3}>
                  <TextField fullWidth size="small" label="Data Início" type="date" value={filters.inicio}
                    onChange={e => setFilters(p => ({ ...p, inicio: e.target.value }))}
                    InputLabelProps={{ shrink: true }} />
                </Grid>
                <Grid item xs={6} sm={4} md={3}>
                  <TextField fullWidth size="small" label="Data Término" type="date" value={filters.termino}
                    onChange={e => setFilters(p => ({ ...p, termino: e.target.value }))}
                    InputLabelProps={{ shrink: true }} />
                </Grid>
              </>
            )}
            {['emitidas-por-periodo', 'evolucao-emissao'].includes(selectedTipo) && (
              <>
                <Grid item xs={6} sm={4} md={2}>
                  <TextField fullWidth size="small" label="Ano" value={filters.ano}
                    onChange={e => setFilters(p => ({ ...p, ano: e.target.value }))} placeholder="Ex: 2024" />
                </Grid>
                <Grid item xs={6} sm={4} md={2}>
                  <TextField fullWidth size="small" label="Mês" value={filters.mes}
                    onChange={e => setFilters(p => ({ ...p, mes: e.target.value }))} placeholder="1-12" />
                </Grid>
              </>
            )}
            {selectedTipo === 'cobranca' && (
              <Grid item xs={12} sm={6} md={4}>
                <TextField fullWidth size="small" label="Nome do Profissional" value={filters.nome}
                  onChange={e => setFilters(p => ({ ...p, nome: e.target.value }))}
                  onKeyDown={e => e.key === 'Enter' && handleSearch()} />
              </Grid>
            )}
            {selectedTipo === 'carteirinhas-despachadas' && (
              <Grid item xs={12} sm={6} md={4}>
                <TextField fullWidth size="small" label="CPF ou Inscrição" value={filters.valor}
                  onChange={e => setFilters(p => ({ ...p, valor: e.target.value }))}
                  onKeyDown={e => e.key === 'Enter' && handleSearch()} />
              </Grid>
            )}
          </Grid>

          <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
            <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
            {searched && resultados.length > 0 && (
              <Button
                variant="outlined" color="success" startIcon={<DownloadIcon />} disabled={exporting}
                onClick={async () => {
                  setExporting(true);
                  try {
                    const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: formatColumnLabel(k) }));
                    await exportService.exportGenericExcel({
                      data: resultados.map(r => ({ ...r })),
                      columns: cols,
                      title: `Consulta Identidade - ${nomeConsulta || selectedNome}`,
                      filename: `identidade_${selectedTipo}`,
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
              {resultados.length === 0 ? (
                <Alert severity="info" sx={{ mb: 2 }}>Identidade não cadastrada.</Alert>
              ) : (
                <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                  <strong>{nomeConsulta}</strong> - {total} resultado(s)
                </Typography>
              )}
              {resultados.length > 0 && (
                <TableContainer sx={{ maxHeight: 'calc(100vh - 400px)' }}>
                  <Table stickyHeader size="small">
                    <TableHead>
                      <TableRow>
                        {columns.map(col => (
                          <TableCell key={col} sx={{ fontWeight: 'bold', whiteSpace: 'nowrap' }}>{formatColumnLabel(col)}</TableCell>
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
