import React, { useState, useEffect } from 'react';
import {
  Container, Paper, Stepper, Step, StepLabel, Typography, Button, Box,
  TextField, RadioGroup, FormControlLabel, Radio, FormControl, FormLabel,
  Card, CardContent, Checkbox, Alert, CircularProgress, Chip, MenuItem, Select,
  InputLabel, FormHelperText
} from '@mui/material';
import {
  CheckCircle as CheckCircleIcon,
  Send as SendIcon,
  ArrowBack as ArrowBackIcon,
  ArrowForward as ArrowForwardIcon,
} from '@mui/icons-material';
import { Link } from 'react-router-dom';
import {
  getPublicServicos,
  createUserRequest,
  ServicoPublic,
  UserRequestItemCreate,
} from '../services/userRequestService';

const steps = ['Origem e Dados', 'Serviços e Permissões', 'Justificativa e Confirmação'];

const CRO_LIST = [
  'CRO-AC', 'CRO-AL', 'CRO-AM', 'CRO-AP', 'CRO-BA', 'CRO-CE', 'CRO-DF',
  'CRO-ES', 'CRO-GO', 'CRO-MA', 'CRO-MG', 'CRO-MS', 'CRO-MT', 'CRO-PA',
  'CRO-PB', 'CRO-PE', 'CRO-PI', 'CRO-PR', 'CRO-RJ', 'CRO-RN', 'CRO-RO',
  'CRO-RR', 'CRO-RS', 'CRO-SC', 'CRO-SE', 'CRO-SP', 'CRO-TO',
];

const DEPARTAMENTOS_CFO = [
  'Presidência', 'Diretoria', 'TI', 'Fiscalização', 'Financeiro',
  'Jurídico', 'Comunicação', 'Cadastro', 'Administrativo', 'Outro',
];

const SolicitarUsuario: React.FC = () => {
  const [activeStep, setActiveStep] = useState(0);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState(false);
  const [createdId, setCreatedId] = useState<number | null>(null);
  const [error, setError] = useState('');

  // Step 1
  const [origemTipo, setOrigemTipo] = useState('');
  const [nomeCompleto, setNomeCompleto] = useState('');
  const [email, setEmail] = useState('');
  const [telefone, setTelefone] = useState('');
  const [organizacao, setOrganizacao] = useState('');
  const [departamento, setDepartamento] = useState('');

  // Step 2
  const [servicos, setServicos] = useState<ServicoPublic[]>([]);
  const [selectedServicos, setSelectedServicos] = useState<Set<number>>(new Set());
  const [loadingServicos, setLoadingServicos] = useState(false);
  const [outro, setOutro] = useState('');
  const [sugestaoDesenvolvimento, setSugestaoDesenvolvimento] = useState('');

  // Step 3
  const [justificativa, setJustificativa] = useState('');

  useEffect(() => {
    if (origemTipo) {
      loadServicos();
    }
  }, [origemTipo]);

  const loadServicos = async () => {
    setLoadingServicos(true);
    try {
      const data = await getPublicServicos(origemTipo);
      setServicos(data);
    } catch {
      setError('Erro ao carregar serviços');
    } finally {
      setLoadingServicos(false);
    }
  };

  const toggleServico = (id: number) => {
    setSelectedServicos(prev => {
      const next = new Set(prev);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const canAdvance = (): boolean => {
    if (activeStep === 0) {
      return !!(origemTipo && nomeCompleto && email && organizacao);
    }
    if (activeStep === 1) {
      return selectedServicos.size > 0;
    }
    if (activeStep === 2) {
      return !!justificativa.trim();
    }
    return false;
  };

  const handleSubmit = async () => {
    setLoading(true);
    setError('');
    try {
      const items: UserRequestItemCreate[] = servicos
        .filter(s => selectedServicos.has(s.id))
        .map(s => ({
          servico_id: s.id,
          permission_name: s.permissao_nome,
        }));

      const result = await createUserRequest({
        nome_completo: nomeCompleto,
        email,
        telefone: telefone || undefined,
        origem_tipo: origemTipo,
        organizacao,
        departamento: departamento || undefined,
        justificativa,
        outro: outro || undefined,
        sugestao_desenvolvimento: sugestaoDesenvolvimento || undefined,
        items,
      });

      setCreatedId(result.id);
      setSuccess(true);
    } catch (err: any) {
      setError(err?.response?.data?.detail || 'Erro ao enviar solicitação');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <Box sx={{ minHeight: '100vh', bgcolor: '#f5f5f5', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <Container maxWidth="sm">
          <Paper sx={{ p: 4, textAlign: 'center' }}>
            <CheckCircleIcon sx={{ fontSize: 80, color: 'success.main', mb: 2 }} />
            <Typography variant="h4" gutterBottom>Solicitação Enviada!</Typography>
            <Typography variant="body1" color="text.secondary" sx={{ mb: 2 }}>
              Sua solicitação foi registrada com sucesso.
            </Typography>
            <Chip label={`Solicitação #${createdId}`} color="primary" sx={{ mb: 3, fontSize: '1.1rem', py: 2, px: 1 }} />
            <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
              Você receberá uma atualização por email ({email}) quando sua solicitação for analisada.
            </Typography>
            <Box sx={{ display: 'flex', gap: 2, justifyContent: 'center' }}>
              <Button component={Link} to="/status-solicitacao" variant="outlined">
                Verificar Status
              </Button>
              <Button component={Link} to="/login" variant="contained">
                Voltar ao Login
              </Button>
            </Box>
          </Paper>
        </Container>
      </Box>
    );
  }

  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#f5f5f5' }}>
      {/* Header */}
      <Box sx={{ bgcolor: '#8d0f12', color: 'white', py: 2, px: 3 }}>
        <Container maxWidth="md">
          <Typography variant="h6">Sistema de Consultas - CFO</Typography>
          <Typography variant="body2" sx={{ opacity: 0.9 }}>Solicitação de Novo Usuário</Typography>
        </Container>
      </Box>

      <Container maxWidth="md" sx={{ py: 4 }}>
        <Paper sx={{ p: 4 }}>
          <Stepper activeStep={activeStep} sx={{ mb: 4 }}>
            {steps.map(label => (
              <Step key={label}>
                <StepLabel>{label}</StepLabel>
              </Step>
            ))}
          </Stepper>

          {error && <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>}

          {/* Step 1: Origem e Dados */}
          {activeStep === 0 && (
            <Box>
              <Typography variant="h6" gutterBottom>Informações Pessoais</Typography>

              <FormControl component="fieldset" sx={{ mb: 3 }}>
                <FormLabel>Origem *</FormLabel>
                <RadioGroup
                  row
                  value={origemTipo}
                  onChange={(e) => {
                    setOrigemTipo(e.target.value);
                    setOrganizacao(e.target.value === 'cfo' ? 'CFO' : '');
                    setDepartamento('');
                    setSelectedServicos(new Set());
                  }}
                >
                  <FormControlLabel value="cfo" control={<Radio />} label="CFO - Conselho Federal" />
                  <FormControlLabel value="cro" control={<Radio />} label="CRO - Conselho Regional" />
                </RadioGroup>
              </FormControl>

              <TextField
                fullWidth label="Nome Completo *" value={nomeCompleto}
                onChange={(e) => setNomeCompleto(e.target.value)} sx={{ mb: 2 }}
              />
              <TextField
                fullWidth label="Email *" type="email" value={email}
                onChange={(e) => setEmail(e.target.value)} sx={{ mb: 2 }}
              />
              <TextField
                fullWidth label="Telefone" value={telefone}
                onChange={(e) => setTelefone(e.target.value)} sx={{ mb: 2 }}
              />

              {origemTipo === 'cro' && (
                <FormControl fullWidth sx={{ mb: 2 }}>
                  <InputLabel>Organização *</InputLabel>
                  <Select
                    value={organizacao}
                    label="Organização *"
                    onChange={(e) => setOrganizacao(e.target.value)}
                  >
                    {CRO_LIST.map(cro => (
                      <MenuItem key={cro} value={cro}>{cro}</MenuItem>
                    ))}
                  </Select>
                </FormControl>
              )}

              {origemTipo === 'cfo' && (
                <FormControl fullWidth sx={{ mb: 2 }}>
                  <InputLabel>Departamento</InputLabel>
                  <Select
                    value={departamento}
                    label="Departamento"
                    onChange={(e) => setDepartamento(e.target.value)}
                  >
                    {DEPARTAMENTOS_CFO.map(dep => (
                      <MenuItem key={dep} value={dep}>{dep}</MenuItem>
                    ))}
                  </Select>
                </FormControl>
              )}
            </Box>
          )}

          {/* Step 2: Serviços e Permissões */}
          {activeStep === 1 && (
            <Box>
              <Typography variant="h6" gutterBottom>Selecione os Serviços Desejados</Typography>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
                Marque os serviços que você precisa acessar.
                {origemTipo === 'cro' && ' Como CRO, apenas serviços disponíveis para regionais são exibidos.'}
              </Typography>

              {loadingServicos ? (
                <Box display="flex" justifyContent="center" p={4}>
                  <CircularProgress />
                </Box>
              ) : (
                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr' }, gap: 2, mb: 3 }}>
                  {servicos.map(servico => (
                    <Card
                      key={servico.id}
                      variant="outlined"
                      sx={{
                        cursor: 'pointer',
                        border: selectedServicos.has(servico.id) ? '2px solid #1976d2' : '1px solid #e0e0e0',
                        bgcolor: selectedServicos.has(servico.id) ? 'rgba(25,118,210,0.04)' : 'white',
                        '&:hover': { borderColor: '#1976d2' },
                      }}
                      onClick={() => toggleServico(servico.id)}
                    >
                      <CardContent sx={{ display: 'flex', alignItems: 'flex-start', gap: 1, py: 2, '&:last-child': { pb: 2 } }}>
                        <Checkbox
                          checked={selectedServicos.has(servico.id)}
                          sx={{ p: 0, mr: 1 }}
                        />
                        <Box>
                          <Typography variant="subtitle2">{servico.nome}</Typography>
                          {servico.descricao && (
                            <Typography variant="body2" color="text.secondary">{servico.descricao}</Typography>
                          )}
                        </Box>
                      </CardContent>
                    </Card>
                  ))}
                </Box>
              )}

              <TextField
                fullWidth multiline rows={2} label="Outro serviço não listado"
                value={outro} onChange={(e) => setOutro(e.target.value)} sx={{ mb: 2 }}
                helperText="Descreva se precisa de acesso a algum serviço não listado acima"
              />
              <TextField
                fullWidth multiline rows={2} label="Sugestão de Desenvolvimento"
                value={sugestaoDesenvolvimento} onChange={(e) => setSugestaoDesenvolvimento(e.target.value)}
                helperText="Alguma sugestão de funcionalidade ou melhoria?"
              />
            </Box>
          )}

          {/* Step 3: Justificativa e Confirmação */}
          {activeStep === 2 && (
            <Box>
              <Typography variant="h6" gutterBottom>Justificativa e Resumo</Typography>

              <TextField
                fullWidth multiline rows={4} label="Justificativa *"
                value={justificativa} onChange={(e) => setJustificativa(e.target.value)} sx={{ mb: 3 }}
                helperText="Explique por que você precisa de acesso ao sistema"
              />

              <Paper variant="outlined" sx={{ p: 3, bgcolor: '#fafafa' }}>
                <Typography variant="subtitle1" gutterBottom sx={{ fontWeight: 'bold' }}>Resumo da Solicitação</Typography>
                <Box sx={{ display: 'grid', gridTemplateColumns: '140px 1fr', gap: 1 }}>
                  <Typography variant="body2" color="text.secondary">Nome:</Typography>
                  <Typography variant="body2">{nomeCompleto}</Typography>
                  <Typography variant="body2" color="text.secondary">Email:</Typography>
                  <Typography variant="body2">{email}</Typography>
                  <Typography variant="body2" color="text.secondary">Origem:</Typography>
                  <Typography variant="body2">{origemTipo.toUpperCase()} - {organizacao}</Typography>
                  {departamento && (
                    <>
                      <Typography variant="body2" color="text.secondary">Departamento:</Typography>
                      <Typography variant="body2">{departamento}</Typography>
                    </>
                  )}
                  <Typography variant="body2" color="text.secondary">Serviços:</Typography>
                  <Box>
                    {servicos.filter(s => selectedServicos.has(s.id)).map(s => (
                      <Chip key={s.id} label={s.nome} size="small" sx={{ mr: 0.5, mb: 0.5 }} />
                    ))}
                  </Box>
                </Box>
              </Paper>
            </Box>
          )}

          {/* Navigation */}
          <Box sx={{ display: 'flex', justifyContent: 'space-between', mt: 4 }}>
            <Button
              disabled={activeStep === 0}
              onClick={() => setActiveStep(prev => prev - 1)}
              startIcon={<ArrowBackIcon />}
            >
              Voltar
            </Button>

            {activeStep < steps.length - 1 ? (
              <Button
                variant="contained"
                disabled={!canAdvance()}
                onClick={() => setActiveStep(prev => prev + 1)}
                endIcon={<ArrowForwardIcon />}
              >
                Próximo
              </Button>
            ) : (
              <Button
                variant="contained"
                color="success"
                disabled={!canAdvance() || loading}
                onClick={handleSubmit}
                endIcon={loading ? <CircularProgress size={20} /> : <SendIcon />}
              >
                {loading ? 'Enviando...' : 'Enviar Solicitação'}
              </Button>
            )}
          </Box>
        </Paper>

        <Box sx={{ textAlign: 'center', mt: 3 }}>
          <Button component={Link} to="/login" size="small" color="inherit">
            Voltar ao Login
          </Button>
          <Typography variant="body2" component="span" sx={{ mx: 1 }}>|</Typography>
          <Button component={Link} to="/status-solicitacao" size="small" color="inherit">
            Verificar Status
          </Button>
        </Box>
      </Container>
    </Box>
  );
};

export default SolicitarUsuario;
