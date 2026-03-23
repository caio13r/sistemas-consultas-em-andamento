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

interface TipoEstatistica { codigo: string; nome: string; }

const DESCRICOES: Record<string, string> = {
  'populacao': 'Dados somados de população por região, UF e categorias profissionais.',
  'inscricao-categoria-ano': 'Consolidado de inscrições por CRO, categoria e ano de registro.',
  'categoria-faixa-etaria': 'Consolidado por CRO, categoria e faixa etária (nascimento).',
  'especialidade-sexo': 'Especialidades por CRO e sexo (consolidado).',
  'especialidade-faixa-etaria': 'Especialidades por CRO e faixa etária.',
  'profissionais-por-idade': 'Contagem de profissionais ativos por idade e categoria.',
  'especialidade-sexo-detalhado': 'Registro de especialidades por CRO, sexo (detalhado).',
  'ativos-localidade-br': 'Total de ativos por localidade (visão nacional).',
  'ativos-localidade-uf': 'Total de ativos por localidade (filtrando por UF).',
  'dda-ativos': 'Endereços residenciais de ativos com DDA por localidade.',
  'enderecos-residenciais': 'Endereços residenciais de profissionais ativos.',
  'enderecos-comerciais': 'Endereços comerciais atualizados por localidade.',
  'especialidade-sexo-ano': 'Registro de especialidades por CRO, sexo e ano.',
  'especialidade-tecnica-sexo': 'Especialidades técnicas por CRO e sexo.',
  'especialidade-tecnica-sexo-ano': 'Especialidades técnicas por CRO, sexo e ano.',
  'habilitacao-sexo': 'Habilitações por CRO e sexo.',
  'habilitacao-sexo-ano': 'Habilitações por CRO, sexo e ano.',
  'ativos-por-ano': 'Total de inscritos ativos por ano e categoria (últimos 18 anos).',
  'sexo-especialidade-municipio': 'Sexo x Especialidade x Município (base WSCFO).',
  'geral': 'Resumo nacional de profissionais ativos por CRO.',
};

// Tipos que exigem CRO obrigatório
const REQUER_CRO = [
  'ativos-localidade-uf', 'dda-ativos', 'enderecos-residenciais', 'enderecos-comerciais',
  'especialidade-sexo-ano', 'especialidade-tecnica-sexo', 'especialidade-tecnica-sexo-ano',
  'habilitacao-sexo', 'habilitacao-sexo-ano',
];

// Tipos que aceitam filtro de ano
const ACEITA_ANO = [
  'especialidade-sexo-ano', 'especialidade-tecnica-sexo-ano', 'habilitacao-sexo-ano',
];

export default function ConsultaEstatistica() {
  const [tipos, setTipos] = useState<TipoEstatistica[]>([]);
  const [selectedTipo, setSelectedTipo] = useState<string | null>(null);
  const [filters, setFilters] = useState({ cro: '', ano: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [nomeConsulta, setNomeConsulta] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingTipos, setLoadingTipos] = useState(true);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    api.get('/consulta-estatistica/tipos')
      .then(res => setTipos(res.data))
      .catch(() => {})
      .finally(() => setLoadingTipos(false));
  }, []);

  const handleCardClick = (codigo: string) => {
    setSelectedTipo(codigo);
    setFilters({ cro: '', ano: '' });
    setResultados([]);
    setTotal(0);
    setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ cro: '', ano: '' });
    setResultados([]);
    setTotal(0);
    setSearched(false);
    setNomeConsulta('');
  };

  const handleSearch = async () => {
    if (!selectedTipo) return;
    if (REQUER_CRO.includes(selectedTipo) && !filters.cro) {
      setSnackbar({ open: true, message: 'Selecione um CRO/UF para esta consulta.', severity: 'warning' });
      return;
    }
    if (ACEITA_ANO.includes(selectedTipo) && !filters.ano) {
      setSnackbar({ open: true, message: 'Informe o ano para esta consulta.', severity: 'warning' });
      return;
    }
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      params.append('tipo', selectedTipo);
      if (filters.cro) params.append('cro', filters.cro);
      if (filters.ano) params.append('ano', filters.ano);
      const res = await api.get(`/consulta-estatistica/buscar?${params}`);
      setResultados(res.data.resultados);
      setTotal(res.data.total);
      setNomeConsulta(res.data.nome);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ cro: '', ano: '' });
    setResultados([]);
    setTotal(0);
    setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];
  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || '';

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Estatística</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Dados populacionais e estatísticas de profissionais por CRO, categoria e região.
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

          <Grid container spacing={2} sx={{ mb: 2 }}>
            <Grid item xs={6} sm={4} md={3}>
              <FormControl fullWidth size="small">
                <InputLabel>CRO / UF</InputLabel>
                <Select value={filters.cro} label="CRO / UF" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
                  <MenuItem value="">Todos</MenuItem>
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
            {ACEITA_ANO.includes(selectedTipo) && (
              <Grid item xs={6} sm={4} md={3}>
                <TextField fullWidth size="small" label="Ano" value={filters.ano}
                  onChange={e => setFilters(p => ({ ...p, ano: e.target.value }))}
                  onKeyDown={e => e.key === 'Enter' && handleSearch()}
                  placeholder="Ex: 2024" />
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
                    const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: k }));
                    await exportService.exportGenericExcel({
                      data: resultados.map(r => ({ ...r })),
                      columns: cols,
                      title: `Consulta Estatística - ${nomeConsulta || selectedNome}`,
                      filename: `estatistica_${selectedTipo}`,
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
