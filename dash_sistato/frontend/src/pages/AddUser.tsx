import React, { useState } from 'react';
import {
  Paper,
  Typography,
  Box,
  TextField,
  Button,
  Grid,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  FormControlLabel,
  Switch,
  InputAdornment,
  SelectChangeEvent,
  Snackbar,
  Alert,
} from '@mui/material';
import { Save as SaveIcon } from '@mui/icons-material';
import { userService } from '../services/userService';
import { useNavigate } from 'react-router-dom';
import PageContainer from '../components/PageContainer';

// Lista de grupos de acesso
const gruposAcesso = [
  { id: 0, nome: 'Administrador', uf: 'ADM' },
  { id: 1, nome: 'CFO', uf: 'CFO' },
  { id: 2, nome: 'CRO-AC', uf: 'AC' },
  { id: 3, nome: 'CRO-AL', uf: 'AL' },
  { id: 4, nome: 'CRO-AM', uf: 'AM' },
  { id: 5, nome: 'CRO-AP', uf: 'AP' },
  { id: 6, nome: 'CRO-BA', uf: 'BA' },
  { id: 7, nome: 'CRO-CE', uf: 'CE' },
  { id: 8, nome: 'CRO-DF', uf: 'DF' },
  { id: 9, nome: 'CRO-ES', uf: 'ES' },
  { id: 10, nome: 'CRO-GO', uf: 'GO' },
  { id: 11, nome: 'CRO-MA', uf: 'MA' },
  { id: 12, nome: 'CRO-MT', uf: 'MT' },
  { id: 13, nome: 'CRO-MS', uf: 'MS' },
  { id: 14, nome: 'CRO-MG', uf: 'MG' },
  { id: 15, nome: 'CRO-PA', uf: 'PA' },
  { id: 16, nome: 'CRO-PB', uf: 'PB' },
  { id: 17, nome: 'CRO-PR', uf: 'PR' },
  { id: 18, nome: 'CRO-PE', uf: 'PE' },
  { id: 19, nome: 'CRO-PI', uf: 'PI' },
  { id: 20, nome: 'CRO-RJ', uf: 'RJ' },
  { id: 21, nome: 'CRO-RN', uf: 'RN' },
  { id: 22, nome: 'CRO-RO', uf: 'RO' },
  { id: 23, nome: 'CRO-RS', uf: 'RS' },
  { id: 24, nome: 'CRO-RR', uf: 'RR' },
  { id: 25, nome: 'CRO-SC', uf: 'SC' },
  { id: 26, nome: 'CRO-SE', uf: 'SE' },
  { id: 27, nome: 'CRO-SP', uf: 'SP' },
  { id: 28, nome: 'CRO-TO', uf: 'TO' },
];

// Lista de subgrupos (você pode adicionar os valores reais aqui)
const subgruposAcesso = [
  { id: 1, nome: 'Subgrupo 1' },
  { id: 2, nome: 'Subgrupo 2' },
  { id: 3, nome: 'Subgrupo 3' },
];

export default function AddUser() {
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    full_name: '',
    password: '',
    role_id: '',
    subrole_id: '',
    is_active: true,
    is_superuser: false,
  });

  const [snackbar, setSnackbar] = useState({
    open: false,
    message: '',
    severity: 'success' as 'success' | 'error',
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | { name?: string; value: unknown }> | SelectChangeEvent) => {
    const { name, value, checked } = e.target as HTMLInputElement;
    setFormData(prev => ({
      ...prev,
      [name as string]: name === 'is_active' || name === 'is_superuser' ? checked : value
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await userService.createUser({
        ...formData,
        role_id: parseInt(formData.role_id as string),
        subrole_id: parseInt(formData.subrole_id as string),
      });

      setSnackbar({
        open: true,
        message: 'Usuário criado com sucesso!',
        severity: 'success',
      });

      // Limpa o formulário
      setFormData({
        username: '',
        email: '',
        full_name: '',
        password: '',
        role_id: '',
        subrole_id: '',
        is_active: true,
        is_superuser: false,
      });

      // Redireciona para a lista de usuários após 2 segundos
      setTimeout(() => {
        navigate('/users');
      }, 2000);

    } catch (error) {
      setSnackbar({
        open: true,
        message: error instanceof Error ? error.message : 'Erro ao criar usuário',
        severity: 'error',
      });
    }
  };

  const handleCloseSnackbar = () => {
    setSnackbar(prev => ({ ...prev, open: false }));
  };

  return (
    <PageContainer>
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" component="h1" gutterBottom>
          Adicionar Novo Usuário
        </Typography>
        <Typography variant="body1" color="text.secondary">
          Preencha os dados abaixo para criar um novo usuário no sistema
        </Typography>
      </Box>

      <form onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              required
              label="Nome de Usuário"
              name="username"
              value={formData.username}
              onChange={handleChange}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              required
              label="Nome Completo"
              name="full_name"
              value={formData.full_name}
              onChange={handleChange}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              required
              label="E-mail"
              name="email"
              type="email"
              value={formData.email}
              onChange={handleChange}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <TextField
              fullWidth
              required
              label="Senha"
              name="password"
              type="password"
              value={formData.password}
              onChange={handleChange}
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth required>
              <InputLabel>Grupo de Acesso</InputLabel>
              <Select
                name="role_id"
                value={formData.role_id}
                onChange={handleChange}
                label="Grupo de Acesso"
              >
                <MenuItem value="1">Administrador</MenuItem>
                <MenuItem value="2">CFO</MenuItem>
                <MenuItem value="3">Usuário</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControl fullWidth required>
              <InputLabel>Subgrupo de Acesso</InputLabel>
              <Select
                name="subrole_id"
                value={formData.subrole_id}
                onChange={handleChange}
                label="Subgrupo de Acesso"
              >
                <MenuItem value="1">Acesso Total</MenuItem>
                <MenuItem value="2">Acesso Limitado</MenuItem>
                <MenuItem value="3">Somente Leitura</MenuItem>
              </Select>
            </FormControl>
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControlLabel
              control={
                <Switch
                  checked={formData.is_active}
                  onChange={handleChange}
                  name="is_active"
                  color="primary"
                />
              }
              label="Usuário Ativo"
            />
          </Grid>

          <Grid item xs={12} md={6}>
            <FormControlLabel
              control={
                <Switch
                  checked={formData.is_superuser}
                  onChange={handleChange}
                  name="is_superuser"
                  color="primary"
                />
              }
              label="Super Usuário"
            />
          </Grid>

          <Grid item xs={12}>
            <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
              <Button
                variant="contained"
                color="primary"
                type="submit"
                startIcon={<SaveIcon />}
                size="large"
              >
                Salvar Usuário
              </Button>
            </Box>
          </Grid>
        </Grid>
      </form>

      <Snackbar
        open={snackbar.open}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
      >
        <Alert
          onClose={handleCloseSnackbar}
          severity={snackbar.severity}
          sx={{ width: '100%' }}
        >
          {snackbar.message}
        </Alert>
      </Snackbar>
    </PageContainer>
  );
} 