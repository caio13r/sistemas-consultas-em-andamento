import React, { useState, useEffect } from 'react';
import {
  Typography, Box, Button, Table, TableBody, TableCell,
  TableContainer, TableHead, TableRow, CircularProgress, Grid,
  FormControl, InputLabel, Select, MenuItem, Snackbar, Alert,
  Card, CardContent, CardActionArea, IconButton, Chip, Paper,
} from '@mui/material';
import {
  Search as SearchIcon, Clear as ClearIcon,
  ArrowBack as ArrowBackIcon, FileDownload as DownloadIcon,
  FindInPage as FindIcon,
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { exportService } from '../services/exportService';

const UF_LIST = ['AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO'];

interface TipoAuditoria { codigo: string; nome: string; }

const DESCRICOES: Record<string, string> = {
  cpf_cnpj_invalido: 'Profissionais e empresas com CPF ou CNPJ invalido no cadastro.',
  pre_cadastrado_com_inscricao: 'Registros pre-cadastrados que possuem inscricao ativa.',
  provisorios_vencidos: 'Inscricoes provisorias com prazo de validade expirado.',
  inscricao_mais_de_um_cro: 'Profissionais com inscricao principal em mais de um CRO.',
  inscricoes_isentas: 'Inscricoes com situacao de isencao de anuidade.',
  profissionais_idade_inferior_20: 'Profissionais cadastrados com idade inferior a 20 anos.',
  profissionais_idade_superior_80: 'Profissionais cadastrados com idade superior a 80 anos.',
  cpf_duplicado: 'Pessoas fisicas com CPF duplicado no sistema.',
  cpf_duplicado_uma_categoria: 'Profissionais com CPF duplicado dentro de uma mesma categoria.',
  filial_sem_matriz: 'Empresas filiais sem vinculo com matriz cadastrada.',
  rt_mais_de_uma_empresa: 'Responsaveis tecnicos vinculados a mais de uma empresa.',
  empresa_ativa_sem_rt: 'Empresas com situacao ativa sem responsavel tecnico.',
  caducados_registro_outro_estado: 'Inscricoes caducadas com registro ativo em outro estado.',
  identidades_digitais_emitidas: 'Consolidado de identidades digitais emitidas por CRO.',
  secundaria_sem_origem_ativa: 'Inscricoes secundarias ativas sem origem ativa vinculada.',
  profissionais_sem_data_colacao: 'Profissionais CDS sem data de colacao de grau registrada.',
  sem_email_correspondencia: 'Profissionais e empresas em atividade sem e-mail de correspondencia.',
  sem_data_inscricao: 'Profissionais e empresas sem data de inscricao registrada.',
  usuarios_implanta: 'Usuarios ativos e inativos do Sistema Implanta.',
  profissionais_nome_social: 'Profissionais que possuem nome social cadastrado.',
  pessoas_com_dda: 'Pessoas com Debito Direto Autorizado (DDA).',
  idade_remissao: 'Profissionais com idade para remissao de anuidade.',
  multiplos_registros: 'Profissionais e empresas com multiplos registros.',
  parcelas_vencidas: 'Parcelas de parcelamentos vencidas e nao pagas.',
  sem_data_registro_federal: 'Profissionais e empresas sem data de registro federal.',
  sem_celular_valido: 'Profissionais e empresas em atividade sem celular valido.',
  tipo_temporario: 'Profissionais em atividade com tipo de inscricao temporario.',
  detalhe_militar_isento: 'Profissionais em atividade com detalhe militar isento.',
  pf_cpf_duplicado: 'Pessoas fisicas com CPF repetido no cadastro.',
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
    setResultados([]); setTotal(0); setSearched(false);
  };

  const handleBack = () => {
    setSelectedTipo(null);
    setFilters({ cro: '' });
    setResultados([]); setTotal(0); setSearched(false); setNomeAuditoria('');
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
    setFilters({ cro: '' }); setResultados([]); setTotal(0); setSearched(false);
  };

  const columns = resultados.length > 0 ? Object.keys(resultados[0]) : [];
  const selectedNome = tipos.find(t => t.codigo === selectedTipo)?.nome || '';

  return (
    <PageContainer>
      {/* Page Header */}
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 1 }}>
          {selectedTipo && (
            <IconButton onClick={handleBack} size="small" sx={{ color: 'primary.main' }}>
              <ArrowBackIcon />
            </IconButton>
          )}
          <Box>
            <Typography variant="h5">
              {selectedTipo ? selectedNome : 'Consulta Auditoria'}
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
              {selectedTipo
                ? DESCRICOES[selectedTipo] || ''
                : `Selecione o tipo de auditoria para consultar os dados dos CROs.`}
            </Typography>
          </Box>
        </Box>
        {!selectedTipo && (
          <Chip label={`${tipos.length} tipos disponíveis`} size="small" variant="outlined" sx={{ mt: 1 }} />
        )}
      </Box>

      {/* Cards Grid */}
      {!selectedTipo && (
        <>
          {loadingTipos ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
              <CircularProgress size={40} />
            </Box>
          ) : (
            <Grid container spacing={2.5}>
              {tipos.map((tipo) => (
                <Grid item xs={12} sm={6} md={4} lg={3} key={tipo.codigo}>
                  <Card sx={{ height: '100%', display: 'flex', flexDirection: 'column', position: 'relative', overflow: 'visible' }}>
                    <Box sx={{ position: 'absolute', top: 0, left: 0, right: 0, height: 3, bgcolor: 'primary.main', borderRadius: '12px 12px 0 0' }} />
                    <CardActionArea onClick={() => handleCardClick(tipo.codigo)} sx={{ flexGrow: 1, p: 0.5 }}>
                      <CardContent sx={{ pt: 2.5 }}>
                        <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 1.5 }}>
                          <Box sx={{
                            width: 36, height: 36, borderRadius: 2,
                            bgcolor: 'rgba(141,15,18,0.06)', display: 'flex',
                            alignItems: 'center', justifyContent: 'center', flexShrink: 0,
                          }}>
                            <FindIcon sx={{ fontSize: 18, color: 'primary.main' }} />
                          </Box>
                          <Box>
                            <Typography variant="subtitle2" sx={{ fontWeight: 700, lineHeight: 1.3, mb: 0.5 }}>
                              {tipo.nome}
                            </Typography>
                            <Typography variant="caption" color="text.secondary" sx={{ lineHeight: 1.4, display: 'block' }}>
                              {DESCRICOES[tipo.codigo] || 'Clique para consultar.'}
                            </Typography>
                          </Box>
                        </Box>
                      </CardContent>
                    </CardActionArea>
                  </Card>
                </Grid>
              ))}
            </Grid>
          )}
        </>
      )}

      {/* Filters & Results */}
      {selectedTipo && (
        <>
          <Paper variant="outlined" sx={{ p: 2.5, mb: 3, bgcolor: 'rgba(141,15,18,0.015)' }}>
            <Grid container spacing={2} alignItems="center">
              <Grid item xs={12} sm={4} md={3}>
                <FormControl fullWidth size="small">
                  <InputLabel>CRO</InputLabel>
                  <Select value={filters.cro} label="CRO" onChange={e => setFilters(p => ({ ...p, cro: e.target.value }))}>
                    <MenuItem value="">Todos</MenuItem>
                    {UF_LIST.map(u => <MenuItem key={u} value={u}>{u}</MenuItem>)}
                  </Select>
                </FormControl>
              </Grid>
              <Grid item>
                <Box sx={{ display: 'flex', gap: 1.5 }}>
                  <Button variant="contained" startIcon={<SearchIcon />} onClick={handleSearch} disabled={loading}>
                    Buscar
                  </Button>
                  <Button variant="outlined" color="secondary" startIcon={<ClearIcon />} onClick={handleClear}>
                    Limpar
                  </Button>
                </Box>
              </Grid>
            </Grid>
          </Paper>

          {loading ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 8 }}>
              <CircularProgress size={40} />
            </Box>
          ) : searched && (
            <>
              {/* Results Header */}
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                  <Typography variant="subtitle2" color="text.secondary">
                    {nomeAuditoria}
                  </Typography>
                  <Chip
                    label={`${total.toLocaleString('pt-BR')} resultado${total !== 1 ? 's' : ''}`}
                    size="small"
                    color={total > 0 ? 'primary' : 'default'}
                    variant="outlined"
                  />
                </Box>
                {resultados.length > 0 && (
                  <Button
                    variant="outlined" color="success" size="small" startIcon={<DownloadIcon />}
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
                      } catch {
                        setSnackbar({ open: true, message: 'Erro ao exportar Excel', severity: 'error' });
                      } finally { setExporting(false); }
                    }}
                  >
                    {exporting ? 'Exportando...' : 'Excel'}
                  </Button>
                )}
              </Box>

              {/* Table */}
              {resultados.length > 0 && (
                <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
                  <TableContainer sx={{ maxHeight: 'calc(100vh - 380px)' }}>
                    <Table stickyHeader size="small">
                      <TableHead>
                        <TableRow>
                          {columns.map(col => (
                            <TableCell key={col} sx={{ whiteSpace: 'nowrap' }}>{col}</TableCell>
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
                </Paper>
              )}

              {resultados.length === 0 && (
                <Paper variant="outlined" sx={{ p: 6, textAlign: 'center' }}>
                  <Typography color="text.secondary">Nenhum resultado encontrado.</Typography>
                </Paper>
              )}
            </>
          )}
        </>
      )}

      <Snackbar open={snackbar.open} autoHideDuration={4000} onClose={() => setSnackbar(p => ({ ...p, open: false }))} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert severity={snackbar.severity} variant="filled" elevation={6}>{snackbar.message}</Alert>
      </Snackbar>
    </PageContainer>
  );
}
