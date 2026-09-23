import React, { useState, useEffect } from 'react';
import {
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
  SelectChangeEvent,
  Snackbar,
  Alert,
  Chip,
  CircularProgress,
} from '@mui/material';
import { Save as SaveIcon } from '@mui/icons-material';
import { userService, RoleOption } from '../services/userService';
import { useNavigate } from 'react-router-dom';
import PageContainer from '../components/PageContainer';
import { getApiErrorMessage } from '../utils/apiError';

export default function AddUser() {
  const navigate = useNavigate();
  const [roles, setRoles] = useState<RoleOption[]>([]);
  const [rolesLoading, setRolesLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    full_name: '',
    password: '',
    role_ids: [] as number[],
    is_active: true,
    is_superuser: false,
  });

  const [snackbar, setSnackbar] = useState({
    open: false,
    message: '',
    severity: 'success' as 'success' | 'error',
  });

  useEffect(() => {
    userService
      .getRoles()
      .then(setRoles)
      .catch((err) => {
        setSnackbar({
          open: true,
          message: getApiErrorMessage(err, 'Erro ao carregar perfis'),
          severity: 'error',
        });
      })
      .finally(() => setRolesLoading(false));
  }, []);

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | { name?: string; value: unknown }> | SelectChangeEvent<number[]>
  ) => {
    const { name, value, checked } = e.target as HTMLInputElement;
    setFormData((prev) => ({
      ...prev,
      [name as string]:
        name === 'is_active' || name === 'is_superuser' ? checked : value,
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (formData.role_ids.length === 0) {
      setSnackbar({
        open: true,
        message: 'Selecione ao menos um perfil de acesso',
        severity: 'error',
      });
      return;
    }

    setSaving(true);
    try {
      await userService.createUser({
        username: formData.username.trim(),
        email: formData.email.trim(),
        full_name: formData.full_name.trim(),
        password: formData.password,
        is_active: formData.is_active,
        is_superuser: formData.is_superuser,
        role_ids: formData.role_ids,
      });

      setSnackbar({
        open: true,
        message: 'Usuário criado com sucesso!',
        severity: 'success',
      });

      setTimeout(() => navigate('/users'), 1500);
    } catch (error) {
      setSnackbar({
        open: true,
        message: getApiErrorMessage(error, 'Erro ao criar usuário'),
        severity: 'error',
      });
    } finally {
      setSaving(false);
    }
  };

  const handleCloseSnackbar = () => {
    setSnackbar((prev) => ({ ...prev, open: false }));
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

      {rolesLoading ? (
        <Box display="flex" justifyContent="center" py={6}>
          <CircularProgress />
        </Box>
      ) : (
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
                helperText="Mínimo recomendado: 8 caracteres"
              />
            </Grid>

            <Grid item xs={12}>
              <FormControl fullWidth required>
                <InputLabel>Perfis de Acesso</InputLabel>
                <Select
                  multiple
                  name="role_ids"
                  value={formData.role_ids}
                  onChange={handleChange}
                  label="Perfis de Acesso"
                  renderValue={(selected) => (
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                      {(selected as number[]).map((id) => {
                        const role = roles.find((r) => r.id === id);
                        return (
                          <Chip key={id} label={role?.name || id} size="small" />
                        );
                      })}
                    </Box>
                  )}
                >
                  {roles.map((role) => (
                    <MenuItem key={role.id} value={role.id}>
                      {role.name}
                      {role.description ? ` — ${role.description}` : ''}
                    </MenuItem>
                  ))}
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
                <Button variant="outlined" onClick={() => navigate('/users')} disabled={saving}>
                  Cancelar
                </Button>
                <Button
                  variant="contained"
                  color="primary"
                  type="submit"
                  startIcon={<SaveIcon />}
                  size="large"
                  disabled={saving}
                >
                  {saving ? 'Salvando...' : 'Salvar Usuário'}
                </Button>
              </Box>
            </Grid>
          </Grid>
        </form>
      )}

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
