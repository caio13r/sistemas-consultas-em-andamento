import React, { useState, useEffect } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Divider, Snackbar, Alert,
  Card, CardContent, CardActionArea, IconButton,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon,
  ArrowBack as ArrowBackIcon, FileDownload as DownloadIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

interface TipoAuditoria { codigo: string; nome: string; }

// Descrições para cada tipo de auditoria
const DESCRICOES: Record<string, string> = {
  cpf_cnpj_invalido: 'Profissionais e empresas com CPF ou CNPJ inválido no cadastro.',
  pre_cadastrado_com_inscricao: 'Registros pré-cadastrados que possuem inscrição ativa.',
  provisorios_vencidos: 'Inscrições provisórias com prazo de validade expirado.',
  inscricao_mais_de_um_cro: 'Profissionais com inscrição principal em mais de um CRO.',
  inscricoes_isentas: 'Inscrições com situação de isenção de anuidade.',
  profissionais_idade_inferior_20: 'Profissionais cadastrados com idade inferior a 20 anos.',
  profissionais_idade_superior_80: 'Profissionais cadastrados com idade superior a 80 anos.',
  cpf_duplicado: 'Pessoas físicas com CPF duplicado no sistema.',
  cpf_duplicado_uma_categoria: 'Profissionais com CPF duplicado dentro de uma mesma categoria.',
  filial_sem_matriz: 'Empresas filiais sem vínculo com matriz cadastrada.',
  rt_mais_de_uma_empresa: 'Responsáveis técnicos vinculados a mais de uma empresa.',
  empresa_ativa_sem_rt: 'Empresas com situação ativa sem responsável técnico.',
  caducados_registro_outro_estado: 'Inscrições caducadas com registro ativo em outro estado.',
  identidades_digitais_emitidas: 'Consolidado de identidades digitais emitidas por CRO.',
};

export default function ConsultaAuditorias() {
  const [tipos, setTipos] = useState<TipoAuditoria[]>([]);
  const [selectedTipo, setSelectedTipo] = useState<string | null>(null);
  const [filters, setFilters] = useState({ cro: '' });
  const [resultados, setResultados] = useState<any[]>([]);
  const [total, setTotal] = useState(0);
  const [nomeAuditoria, setNomeAuditoria] = useState('');
  const [loading, setLoading] = useState(false);
  const [loadingTipos, setLoadingTipos] = useState(true);
  const [searched, setSearched] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'error' as any });
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    api.get('/consulta-auditorias/tipos')
      .then(res => setTipos(res.data))
      .catch(() => {})
      .finally(() => setLoadingTipos(false));
  }, []);

  const handleCardClick = (codigo: string) => {
    setSelectedTipo(codigo);
    setFilters({ cro: '' });
    setResultados([]);
    setTotal(0);
    setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ cro: '' });
    setResultados([]);
    setTotal(0);
    setSearched(false);
    setNomeAuditoria('');
  };

  const handleSearch = async () => {
    if (!selectedTipo) return;
    setLoading(true); setSearched(true);
    try {
      const params = new URLSearchParams();
      params.append('tipo', selectedTipo);
      if (filters.cro) params.append('cro', filters.cro);
      const res = await api.get(`/consulta-auditorias/buscar?${params}`);
      setResultados(res.data.resultados);
      setTotal(res.data.total);
      setNomeAuditoria(res.data.nome);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao buscar', severity: 'error' });
      setResultados([]); setTotal(0);
    } finally { setLoading(false); }
  };

  const handleClear = () => {
    setFilters({ cro: '' });
    setResultados([]);
    setTotal(0);
    setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];

  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || '';

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>Consulta Auditoria</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Selecione o tipo de auditoria para consultar os dados dos CROs.
      </Typography>
      <Divider sx={{ mb: 3 }} />

      {/* Cards de seleção de tipo de auditoria */}
      {!selectedTipo && (
        <>
          {loadingTipos ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
              <CircularProgress />
            </Box>
          ) : (
            <Grid container spacing={3}>
              {tipos.map((tipo) => (
                <Grid item xs={12} sm={6} md={4} key={tipo.codigo}>
                  <Card
                    sx={{
                      height: '100%',
                      display: 'flex',
                      flexDirection: 'column',
                      '&:hover': {
                        boxShadow: 6,
                        transform: 'translateY(-4px)',
                        transition: 'all 0.3s ease-in-out',
                      },
                    }}
                  >
                    <CardActionArea
                      onClick={() => handleCardClick(tipo.codigo)}
                      sx={{ flexGrow: 1 }}
                    >
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

      {/* Área de filtros e resultados */}
      {selectedTipo && (
        <>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 2 }}>
            <IconButton onClick={handleBack} size="small">
              <ArrowBackIcon />
            </IconButton>
            <Typography variant="h6">{selectedNome}</Typography>
          </Box>
          <Divider sx={{ mb: 2 }} />

          <Grid container spacing={2} sx={{ mb: 2 }}>
            <Grid item xs={6} sm={4} md={3}>
              <FormControl fullWidth size="small">
                <InputLabel>CRO</InputLabel>
                <Select
                  value={filters.cro}
                  label="CRO"
                  onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}
                >
                  <MenuItem value="">Todos</MenuItem>
                  {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                </Select>
              </FormControl>
            </Grid>
          </Grid>

          <Box sx={{ mb: 3, display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>
              Buscar
            </Button>
            <Button variant="outlined" startIcon={<ClearIcon />} onClick={handleClear}>
              Limpar
            </Button>
            {searched && resultados.length > 0 && (
              <Button
                variant="outlined"
                color="success"
                startIcon={<DownloadIcon />}
                disabled={exporting}
                onClick={async () => {
                  setExporting(true);
                  try {
                    const cols = Object.keys(resultados[0]).map(k => ({ key: k, label: k }));
                    await exportService.exportGenericExcel({
                      data: resultados.map(r => ({ ...r })),
                      columns: cols,
                      title: `Auditoria - ${nomeAuditoria || selectedNome}`,
                      filename: `auditoria_${selectedTipo}`,
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

          {loading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
              <CircularProgress />
            </Box>
          ) : searched && (
            <>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                <strong>{nomeAuditoria}</strong> - {total} resultado(s)
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
