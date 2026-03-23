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
const CATEGORIAS = ['CD','TPD','THD','ASB','TSB','APD','EPAO'];

interface TipoFiscalizacao { codigo: string; nome: string; }

const DESCRICOES: Record<string, string> = {
  'por-categoria-ano': 'Estatísticas de fiscalizações por CRO, categoria e ano.',
  'sem-inscricao-tipo': 'Fiscalizações de pessoas sem inscrição por tipo (PF/PJ).',
  'por-fiscal': 'Estatísticas de fiscalização por fiscal.',
  'por-fiscal-sem-inscricao': 'Fiscal com pessoas sem inscrição.',
  'irregularidades': 'Tipos de irregularidades encontradas nas fiscalizações.',
  'denuncias': 'Estatísticas de denúncias recebidas.',
  'por-idade': 'Fiscalizados agrupados por idade e categoria.',
  'qtd-fiscais': 'Quantidade de fiscais por CRO.',
  'nomes-fiscais': 'Lista de fiscais com nome e CPF por CRO.',
  'termos-categoria-periodo': 'Fiscalizações por termos, categoria e período.',
  'sem-inscricao-periodo': 'Sem inscrições por período.',
  'fiscal-categoria-periodo': 'Fiscal por categoria e período.',
  'fiscal-sem-inscricao-periodo': 'Fiscal sem inscrição por período.',
  'irregularidades-periodo': 'Irregularidades por período.',
  'denuncias-periodo': 'Denúncias por período.',
  'idade-periodo': 'Fiscalizados por idade e período.',
  'fiscais-ativos': 'Lista de fiscais ativos com acesso ao sistema.',
  'fiscais-acesso-sistema': 'Status de acesso dos fiscais ao sistema.',
  'coordenadores': 'Coordenadores de fiscalização cadastrados.',
};

// Tipos que requerem CRO obrigatório
const REQUER_CRO = ['qtd-fiscais', 'nomes-fiscais', 'fiscais-ativos'];

// Tipos que requerem período (inicio/termino)
const REQUER_PERIODO = [
  'termos-categoria-periodo', 'sem-inscricao-periodo', 'fiscal-categoria-periodo',
  'fiscal-sem-inscricao-periodo', 'irregularidades-periodo', 'denuncias-periodo', 'idade-periodo',
];

// Tipos que aceitam filtro de categoria
const ACEITA_CATEGORIA = ['por-categoria-ano', 'termos-categoria-periodo', 'fiscal-categoria-periodo', 'irregularidades-periodo'];

export default function ConsultaFiscalizacao() {
  const [tipos, setTipos] = useState<TipoFiscalizacao[]>([]);
  const [selectedTipo, setSelectedTipo] = useState<string | null>(null);
  const [filters, setFilters] = useState({ cro: '', categoria: '', ano: '', pessoa: '', inicio: '', termino: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [nomeConsulta, setNomeConsulta] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingTipos, setLoadingTipos] = useState(true);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    api.get('/consulta-fiscalizacao/tipos')
      .then(res => setTipos(res.data))
      .catch(() => {})
      .finally(() => setLoadingTipos(false));
  }, []);

  const handleCardClick = (codigo: string) => {
    setSelectedTipo(codigo);
    setFilters({ cro: '', categoria: '', ano: '', pessoa: '', inicio: '', termino: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ cro: '', categoria: '', ano: '', pessoa: '', inicio: '', termino: '' });
    setResultados([]); setTotal(0); setSearched(false); setNomeConsulta('');
  };

  const handleSearch = async () => {
    if (!selectedTipo) return;
    if (REQUER_CRO.includes(selectedTipo) && !filters.cro) {
      setSnackbar({ open: true, message: 'Selecione um CRO/UF.', severity: 'warning' }); return;
    }
    if (REQUER_PERIODO.includes(selectedTipo) && (!filters.inicio || !filters.termino)) {
      setSnackbar({ open: true, message: 'Informe o período (início e término).', severity: 'warning' }); return;
    }
    setLoading(true); setSearched(true);
    try {
      // Coordenadores usa endpoint separado
      if (selectedTipo === 'coordenadores') {
        const res = await api.get('/consulta-fiscalizacao/coordenadores');
        setResultados(res.data.resultados); setTotal(res.data.total); setNomeConsulta(res.data.nome);
      } else {
        const params = new URLSearchParams();
        params.append('tipo', selectedTipo);
        if (filters.cro) params.append('cro', filters.cro);
        if (filters.categoria) params.append('categoria', filters.categoria);
        if (filters.ano) params.append('ano', filters.ano);
        if (filters.pessoa) params.append('pessoa', filters.pessoa);
        if (filters.inicio) params.append('inicio', filters.inicio);
        if (filters.termino) params.append('termino', filters.termino);
        const res = await api.get(`/consulta-fiscalizacao/buscar?${params}`);
        setResultados(res.data.resultados); setTotal(res.data.total); setNomeConsulta(res.data.nome);
      }
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ cro: '', categoria: '', ano: '', pessoa: '', inicio: '', termino: '' });
    setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];
  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || selectedTipo || '';

  // Incluir coordenadores na lista de cards
  const allTipos = [...tipos, ...(tipos.find(t => t.codigo === 'coordenadores') ? [] : [{ codigo: 'coordenadores', nome: 'Coordenadores de Fiscalização' }])];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Fiscalização</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Estatísticas de fiscalizações dos CROs.
      </Typography>
      <Divider sx={{ mb: 3 }} />

      {!selectedTipo && (
        <>
          {loadingTipos ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : (
            <Grid container spacing={3}>
              {allTipos.map((tipo) => (
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

          <Grid container spacing={2} sx={{ mb: 2 }}>
            <Grid item xs={6} sm={4} md={3}>
              <FormControl fullWidth size="small">
                <InputLabel>CRO</InputLabel>
                <Select value={filters.cro} label="CRO" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
                  <MenuItem value="">Todos</MenuItem>
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
            {ACEITA_CATEGORIA.includes(selectedTipo) && (
              <Grid item xs={6} sm={4} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>Categoria</InputLabel>
                  <Select value={filters.categoria} label="Categoria" onChange={e => setFilters(p => ({ ...p, categoria: e.target.value }))}>
                    <MenuItem value="">Todas</MenuItem>
                    {CATEGORIAS.map(c => <MenuItem key={c} value={c}>{c}</MenuItem>)}
                  </Select>
                </FormControl>
              </Grid>
            )}
            {selectedTipo === 'sem-inscricao-tipo' && (
              <Grid item xs={6} sm={4} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>Tipo Pessoa</InputLabel>
                  <Select value={filters.pessoa} label="Tipo Pessoa" onChange={e => setFilters(p => ({ ...p, pessoa: e.target.value }))}>
                    <MenuItem value="">Todos</MenuItem>
                    <MenuItem value="PF SEM INSCRIÇÃO">PF Sem Inscrição</MenuItem>
                    <MenuItem value="PJ SEM INSCRIÇÃO">PJ Sem Inscrição</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
            )}
            {['por-categoria-ano', 'sem-inscricao-tipo'].includes(selectedTipo) && (
              <Grid item xs={6} sm={4} md={3}>
                <TextField fullWidth size="small" label="Ano" value={filters.ano}
                  onChange={e => setFilters(p => ({ ...p, ano: e.target.value }))}
                  onKeyDown={e => e.key === 'Enter' && handleSearch()} placeholder="Ex: 2024" />
              </Grid>
            )}
            {REQUER_PERIODO.includes(selectedTipo) && (
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
                    const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: k }));
                    await exportService.exportGenericExcel({
                      data: resultados.map(r => ({ ...r })),
                      columns: cols,
                      title: `Consulta Fiscalização - ${nomeConsulta || selectedNome}`,
                      filename: `fiscalizacao_${selectedTipo}`,
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
                <strong>{nomeConsulta}</strong> - {total} resultado(s)
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
