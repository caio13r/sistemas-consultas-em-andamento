import React, { useState } from 'react';
import {
  Typography, Box, TextField, Button, Divider, Snackbar, Alert, Grid,
  FormControl, InputLabel, Select, MenuItem, Card, CardContent, Table,
  TableBody, TableCell, TableContainer, TableHead, TableRow, CircularProgress,
  Tabs, Tab,
} from '@mui/material';
import { Send as SendIcon, Assessment as ReportIcon } from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import api from '../services/api';
import { formatColumnLabel } from '../utils/columnLabels';

const LGPD_OPTIONS = [
  { value: '5', label: 'Totalmente adequado' },
  { value: '4', label: 'Razoavelmente adequado' },
  { value: '3', label: 'Pouco adequado' },
  { value: '2', label: 'Em processo de contratação de empresa' },
  { value: '1', label: 'Não iniciamos a adequação' },
];

const SIM_NAO = [
  { value: '1', label: 'Sim' },
  { value: '0', label: 'Não' },
];

const PORTAL_OPTIONS = [
  { value: '1', label: 'Próprio' },
  { value: '0', label: 'Solução de Terceiros' },
];

const initialForm = {
  f_autlai: '', f_portlai: '', f_cargolai: '', f_vinculolai: '',
  f_aptoautlai: '', f_aptolai: '', f_sitelai: '', f_portallai: '',
  f_sitesolu: '', f_anolai: '', f_lgpd: '', f_autlgpd: '',
  f_arealgpd: '', f_explgpd: '',
};

export default function FormularioLAI() {
  const [tab, setTab] = useState(0);
  const [form, setForm] = useState(initialForm);
  const [submitting, setSubmitting] = useState(false);
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'info' as any });
  // Relatorio
  const [relatorio, setRelatorio] = useState<any[]>([]);
  const [loadingRelatorio, setLoadingRelatorio] = useState(false);

  const setField = (field: string, value: string) => setForm(p => ({ ...p, [field]: value }));

  const handleSubmit = async () => {
    const required = ['f_autlai', 'f_portlai', 'f_cargolai', 'f_vinculolai', 'f_aptoautlai', 'f_aptolai', 'f_sitelai', 'f_portallai', 'f_anolai', 'f_lgpd', 'f_autlgpd'];
    const missing = required.filter(k => !form[k as keyof typeof form]);
    if (missing.length > 0) {
      setSnackbar({ open: true, message: 'Preencha todos os campos obrigatórios.', severity: 'warning' }); return;
    }
    setSubmitting(true);
    try {
      await api.post('/lai/enviar', form);
      setSnackbar({ open: true, message: 'Formulário LAI enviado com sucesso!', severity: 'success' });
      setForm(initialForm);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao enviar', severity: 'error' });
    } finally { setSubmitting(false); }
  };

  const handleLoadRelatorio = async () => {
    setLoadingRelatorio(true);
    try {
      const res = await api.get('/lai/relatorio');
      setRelatorio(res.data.resultados || []);
    } catch (e: any) {
      setSnackbar({ open: true, message: e.response?.data?.detail || 'Erro ao carregar relatório', severity: 'error' });
    } finally { setLoadingRelatorio(false); }
  };

  const renderSelect = (field: string, label: string, options: { value: string; label: string }[]) => (
    <FormControl fullWidth size="small">
      <InputLabel>{label}</InputLabel>
      <Select value={form[field as keyof typeof form] || ''} label={label} onChange={e => setField(field, e.target.value as string)}>
        {options.map(o => <MenuItem key={o.value} value={o.value}>{o.label}</MenuItem>)}
      </Select>
    </FormControl>
  );

  const columns = relatorio.length > 0 ? Object.keys(relatorio[0]) : [];

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>LAI - Lei de Acesso à Informação</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Formulário LAI/LGPD e relatório de respostas.
      </Typography>

      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 2 }}>
        <Tab label="Formulário LAI" />
        <Tab label="Relatório" />
      </Tabs>
      <Divider sx={{ mb: 3 }} />

      {tab === 0 && (
        <Card>
          <CardContent>
            <Alert severity="warning" sx={{ mb: 3 }}>
              Por favor, só preencher a solicitação abaixo as INSTITUIÇÕES DE ENSINO que possuem vínculo explícito com a instituição de ensino para a qual solicita acesso.
            </Alert>

            <Grid container spacing={2}>
              <Grid item xs={12} sm={7}>
                <TextField fullWidth size="small" label="Nome da autoridade LAI no CRO" value={form.f_autlai}
                  onChange={e => setField('f_autlai', e.target.value)} required />
              </Grid>
              <Grid item xs={12} sm={5}>
                <TextField fullWidth size="small" label="Portaria (número/ano)" value={form.f_portlai}
                  onChange={e => setField('f_portlai', e.target.value)} required placeholder="00/2024" />
              </Grid>
              <Grid item xs={12} sm={6}>
                <TextField fullWidth size="small" label="Cargo da autoridade LAI" value={form.f_cargolai}
                  onChange={e => setField('f_cargolai', e.target.value)} required />
              </Grid>
              <Grid item xs={12} sm={6}>
                <TextField fullWidth size="small" label="Vínculo empregatício" value={form.f_vinculolai}
                  onChange={e => setField('f_vinculolai', e.target.value)} required />
              </Grid>
              <Grid item xs={12} sm={4}>
                {renderSelect('f_aptoautlai', 'Autoridade LAI se sente apta?', SIM_NAO)}
              </Grid>
              <Grid item xs={12} sm={4}>
                {renderSelect('f_aptolai', 'Foi capacitada?', SIM_NAO)}
              </Grid>
              <Grid item xs={12} sm={4}>
                {renderSelect('f_sitelai', 'Publicada no site do CRO?', SIM_NAO)}
              </Grid>
              <Grid item xs={12} sm={6}>
                {renderSelect('f_portallai', 'Portal da Transparência', PORTAL_OPTIONS)}
              </Grid>
              <Grid item xs={12} sm={6}>
                <TextField fullWidth size="small" label="Solução de terceiros (qual?)" value={form.f_sitesolu}
                  onChange={e => setField('f_sitesolu', e.target.value)}
                  disabled={form.f_portallai !== '0'} />
              </Grid>
              <Grid item xs={12} sm={4}>
                <TextField fullWidth size="small" label="Dados a partir de qual ano?" value={form.f_anolai}
                  onChange={e => setField('f_anolai', e.target.value)} required placeholder="2020" inputProps={{ maxLength: 4 }} />
              </Grid>
              <Grid item xs={12} sm={4}>
                {renderSelect('f_lgpd', 'Grau de adequação LGPD', LGPD_OPTIONS)}
              </Grid>
              <Grid item xs={12} sm={4}>
                {renderSelect('f_autlgpd', 'Responsável LGPD definido?', SIM_NAO)}
              </Grid>
              <Grid item xs={12} sm={6}>
                <TextField fullWidth size="small" label="Área do responsável LGPD" value={form.f_arealgpd}
                  onChange={e => setField('f_arealgpd', e.target.value)}
                  disabled={form.f_autlgpd !== '1'} />
              </Grid>
              <Grid item xs={12} sm={6}>
                {renderSelect('f_explgpd', 'Responsável tem experiência?', [{ value: 'Sim', label: 'Sim' }, { value: 'Não', label: 'Não' }])}
              </Grid>
            </Grid>

            <Box sx={{ mt: 3, textAlign: 'center' }}>
              <Button variant="contained" size="large" startIcon={<SendIcon />} onClick={handleSubmit} disabled={submitting}>
                {submitting ? 'Enviando...' : 'Enviar Formulário'}
              </Button>
            </Box>
          </CardContent>
        </Card>
      )}

      {tab === 1 && (
        <>
          <Button variant="contained" startIcon={<ReportIcon />} onClick={handleLoadRelatorio} disabled={loadingRelatorio} sx={{ mb: 3 }}>
            {loadingRelatorio ? 'Carregando...' : 'Carregar Relatório'}
          </Button>

          {loadingRelatorio ? (
            <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}><CircularProgress /></Box>
          ) : relatorio.length > 0 && (
            <>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>{relatorio.length} registro(s)</Typography>
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
                    {relatorio.map((r, i) => (
                      <TableRow key={i} hover>
                        {columns.map(col => (
                          <TableCell key={col}>{r[col] != null ? String(r[col]) : '-'}</TableCell>
                        ))}
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </TableContainer>
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
