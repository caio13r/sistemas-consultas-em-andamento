import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Typography, Box, TextField, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Snackbar, Alert,
  Grid, FormControl, InputLabel, Select, MenuItem, Chip, IconButton,
  Tooltip, Divider, Tabs, Tab, Paper,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon, Visibility as ViewIcon,
  FileDownload as DownloadIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

interface Profissional {
  nome: string | null;
  cpf: string | null;
  cro: string | null;
  categoria: string | null;
  inscricao: string | null;
  data_inscricao: string | null;
  tipo_inscricao: string | null;
  situacao: string | null;
  detalhe: string | null;
  situacao_financeira: string | null;
  id_registro: string | number | null;
}

interface Empresa {
  razao_social: string | null;
  nome_fantasia: string | null;
  cnpj: string | null;
  cro: string | null;
  categoria: string | null;
  inscricao: string | null;
  situacao: string | null;
  detalhe: string | null;
  situacao_financeira: string | null;
  logradouro: string | null;
  municipio: string | null;
  uf: string | null;
  telefone: string | null;
  email: string | null;
  id_registro: string | number | null;
}

const UF_LIST = [
  'AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT',
  'PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO',
];

const CATEGORIAS = [
  { value: 'CD', label: 'CD - Cirurgião-Dentista' },
  { value: 'TPD', label: 'TPD - Técnico em Prótese Dentária' },
  { value: 'THD', label: 'THD - Técnico em Higiene Dental' },
  { value: 'ASB', label: 'ASB - Auxiliar de Saúde Bucal' },
  { value: 'TSB', label: 'TSB - Técnico em Saúde Bucal' },
  { value: 'APD', label: 'APD - Auxiliar de Prótese Dentária' },
  { value: 'EPAO', label: 'EPAO - Especialista Patologia Oral' },
  { value: 'LB', label: 'LB - Laboratório' },
  { value: 'ECIPO', label: 'ECIPO' },
];

const situacaoColor: Record<string, 'success' | 'error' | 'warning' | 'default'> = {
  'Ativo': 'success', 'Inativo': 'error', 'Suspenso': 'warning', 'Cancelado': 'error',
};

export default function ConsultaIntegrada() {
  const navigate = useNavigate();
  const [tab, setTab] = useState(0);
  const [filters, setFilters] = useState({
    nome: '', inscricao: '', cpf: '', email: '', telefone: '', cro: '', categoria: '', cnpj: '',
  });
  const [resultadosPF, setResultadosPF] = useState<Profissional[]>([]);
  const [resultadosPJ, setResultadosPJ] = useState<Empresa[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  const handleSearch = async () => {
    const hasFilter = Object.values(filters).some(v => v.trim() !== '');
    if (!hasFilter) {
      setSnackbar({ open: true, message: 'Preencha pelo menos um campo de busca.', severity: 'info' });
      return;
    }
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      Object.entries(filters).forEach(([k, v]) => { if (v.trim()) params.append(k, v.trim()); });

      if (tab === 0) {
        const res = await api.get(`/consulta-integrada/profissionais?${params}`);
        setResultadosPF(res.data.resultados); setTotal(res.data.total); setResultadosPJ([]);
      } else {
        const res = await api.get(`/consulta-integrada/empresas?${params}`);
        setResultadosPJ(res.data.resultados); setTotal(res.data.total); setResultadosPF([]);
      }
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultadosPF([]); setResultadosPJ([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ nome: '', inscricao: '', cpf: '', email: '', telefone: '', cro: '', categoria: '', cnpj: '' });
    setResultadosPF([]); setResultadosPJ([]); setTotal(0); setSearched(false);
  };

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Visão integrada — Visão Nacional</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Quanto mais preciso os dados de busca, melhor e mais rápido será o resultado. Máximo 2000 resultados.
      </Typography>

      <Tabs value={tab} onChange={(_, v) => { setTab(v); handleClear(); }} sx={{ mb: 2 }}>
        <Tab label="Pessoa Física (Profissional)" />
        <Tab label="Pessoa Jurídica (Empresa)" />
      </Tabs>

      <Divider sx={{ mb: 2 }} />

      <Grid container spacing={2} sx={{ mb: 2 }}>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>CRO (Estado)</InputLabel>
            <Select value={filters.cro} label="CRO (Estado)" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <FormControl fullWidth size="small"><InputLabel>Categoria</InputLabel>
            <Select value={filters.categoria} label="Categoria" onChange={e => setFilters(p => ({ ...p, categoria: e.target.value }))}>
              <MenuItem value="">Todos</MenuItem>
              {CATEGORIAS.map(c => <MenuItem key={c.value} value={c.value}>{c.label}</MenuItem>)}
            </Select>
          </FormControl>
        </Grid>
        <Grid item xs={12} sm={6} md={6}>
          <TextField fullWidth size="small" label={tab === 0 ? "Nome do profissional" : "Razão social / Nome fantasia"} value={filters.nome} onChange={e => setFilters(p => ({ ...p, nome: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Inscrição" value={filters.inscricao} onChange={e => setFilters(p => ({ ...p, inscricao: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label={tab === 0 ? "CPF" : "CNPJ"} value={tab === 0 ? filters.cpf : filters.cnpj} onChange={e => setFilters(p => ({ ...p, [tab === 0 ? 'cpf' : 'cnpj']: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="E-mail" value={filters.email} onChange={e => setFilters(p => ({ ...p, email: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
        <Grid item xs={6} sm={4} md={3}>
          <TextField fullWidth size="small" label="Telefone" value={filters.telefone} onChange={e => setFilters(p => ({ ...p, telefone: e.target.value }))} onKeyDown={e => e.key === 'Enter' && handleSearch()} />
        </Grid>
      </Grid>

      <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
        <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>Buscar</Button>
        <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>Limpar</Button>
        {searched && (resultadosPF.length > 0 || resultadosPJ.length > 0) && (
          <Button
            variant="outlined"
            color="success"
            startIcon={<DownloadIcon />}
            disabled={exporting}
            onClick={async () => {
              setExporting(true);
              try {
                const data = tab === 0
                  ? resultadosPF.map(p => ({ ...p }))
                  : resultadosPJ.map(e => ({ ...e }));
                const columns = tab === 0
                  ? [
                      { key: 'nome', label: 'Nome' }, { key: 'cpf', label: 'CPF' },
                      { key: 'cro', label: 'CRO' }, { key: 'categoria', label: 'Categoria' },
                      { key: 'inscricao', label: 'Inscrição' }, { key: 'data_inscricao', label: 'Data da Inscrição' },
                      { key: 'tipo_inscricao', label: 'Tipo' },
                      { key: 'situacao', label: 'Situação' }, { key: 'situacao_financeira', label: 'Sit. Financeira' },
                    ]
                  : [
                      { key: 'razao_social', label: 'Razão Social' }, { key: 'cnpj', label: 'CNPJ' },
                      { key: 'cro', label: 'CRO' }, { key: 'inscricao', label: 'Inscrição' },
                      { key: 'situacao', label: 'Situação' }, { key: 'municipio', label: 'Município' },
                      { key: 'uf', label: 'UF' }, { key: 'telefone', label: 'Telefone' },
                    ];
                await exportService.exportGenericExcel({
                  data,
                  columns,
                  title: tab === 0 ? 'Visão integrada - Profissionais' : 'Visão integrada - Empresas',
                  filename: tab === 0 ? 'consulta_integrada_pf' : 'consulta_integrada_pj',
                });
                setSnackbar({ open: true, message: 'Excel exportado com sucesso!', severity: 'success' });
              } catch (err) {
                setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
              } finally { setExporting(false); }
            }}
          >
            {exporting ? 'Exportando...' : 'Exportar Excel'}
          </Button>
        )}
      </Box>

      <Divider sx={{ mb: 2 }} />

      {loading ? <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
      : searched && (
        <>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {total > 0 ? `${total} resultado(s)${total > 2000 ? ' (exibindo 2000)' : ''}` : 'Nenhum resultado'}
          </Typography>

          {/* Tabela PF */}
          {tab === 0 && resultadosPF.length > 0 && (
            <TableContainer sx={{ maxHeight: 'calc(100vh - 450px)' }}>
              <Table stickyHeader size="small">
                <TableHead><TableRow>
                  <TableCell sx={{ fontWeight: 'bold' }}>Nome</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CRO</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Categoria</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Inscrição</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Data da Inscrição</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CPF</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Tipo</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Situação</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Sit. Financeira</TableCell>
                  <TableCell align="center" sx={{ fontWeight: 'bold' }}>Ações</TableCell>
                </TableRow></TableHead>
                <TableBody>
                  {resultadosPF.map((p, i) => (
                    <TableRow key={i} hover>
                      <TableCell>{p.nome || '-'}</TableCell>
                      <TableCell>{p.cro || '-'}</TableCell>
                      <TableCell>{p.categoria || '-'}</TableCell>
                      <TableCell>{p.inscricao || '-'}</TableCell>
                      <TableCell>{p.data_inscricao || '-'}</TableCell>
                      <TableCell>{p.cpf || '-'}</TableCell>
                      <TableCell>{p.tipo_inscricao || '-'}</TableCell>
                      <TableCell><Chip label={p.situacao || '-'} color={situacaoColor[p.situacao || ''] || 'default'} size="small" variant="outlined" /></TableCell>
                      <TableCell>{p.situacao_financeira || '-'}</TableCell>
                      <TableCell align="center">
                        {p.id_registro && (
                          <Tooltip title="Ver detalhes">
                            <IconButton size="small" color="primary" onClick={() => navigate(`/consulta-integrada/profissional/${p.id_registro}`)}>
                              <ViewIcon />
                            </IconButton>
                          </Tooltip>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}

          {/* Tabela PJ */}
          {tab === 1 && resultadosPJ.length > 0 && (
            <TableContainer sx={{ maxHeight: 'calc(100vh - 450px)' }}>
              <Table stickyHeader size="small">
                <TableHead><TableRow>
                  <TableCell sx={{ fontWeight: 'bold' }}>Razão Social</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CNPJ</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>CRO</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Inscrição</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Situação</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Município</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>UF</TableCell>
                  <TableCell sx={{ fontWeight: 'bold' }}>Telefone</TableCell>
                </TableRow></TableHead>
                <TableBody>
                  {resultadosPJ.map((e, i) => (
                    <TableRow key={i} hover>
                      <TableCell>{e.razao_social || '-'}</TableCell>
                      <TableCell>{e.cnpj || '-'}</TableCell>
                      <TableCell>{e.cro || '-'}</TableCell>
                      <TableCell>{e.inscricao || '-'}</TableCell>
                      <TableCell><Chip label={e.situacao || '-'} color={situacaoColor[e.situacao || ''] || 'default'} size="small" variant="outlined" /></TableCell>
                      <TableCell>{e.municipio || '-'}</TableCell>
                      <TableCell>{e.uf || '-'}</TableCell>
                      <TableCell>{e.telefone || '-'}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TableContainer>
          )}
        </>
      )}


      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
