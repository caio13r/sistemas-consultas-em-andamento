import React, { useEffect, useState } from 'react';
import { Box, Paper, Typography, TextField, Button, Grid, FormControl, InputLabel, Select, MenuItem, Snackbar, Alert, SelectChangeEvent } from '@mui/material';
import { userService } from '../services/userService';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate, useParams } from 'react-router-dom';
import PageContainer from '../components/PageContainer';

export default function EditProfile() {
  const { user, fetchCurrentUser } = useAuth();
  const { id } = useParams<{ id?: string }>();
  const navigate = useNavigate();
  const [formData, setFormData] = useState({
    username: '',
    email: '',
    full_name: '',
    password: '',
    role_id: '',
    subrole_id: '',
  });
  const [snackbar, setSnackbar] = useState({ open: false, message: '', severity: 'success' as 'success' | 'error' });

  const isSelfEdit = !id;

  useEffect(() => {
    async function fetchUserData() {
      try {
        const dataToSet = id ? await userService.getUserById(Number(id)) : user;
        
        if (dataToSet) {
          setFormData({
            username: dataToSet.username || '',
            email: dataToSet.email || '',
            full_name: dataToSet.full_name || '',
            password: '',
            role_id: dataToSet.role_id?.toString() || '',
            subrole_id: dataToSet.subrole_id?.toString() || '',
          });
        } else if (isSelfEdit) {
          await fetchCurrentUser();
        }
      } catch (error) {
        console.error("Failed to fetch user data", error);
        setSnackbar({ open: true, message: 'Falha ao carregar dados do usuário.', severity: 'error' });
      }
    }
    fetchUserData();
  }, [id, user, fetchCurrentUser, isSelfEdit]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSelectChange = (e: SelectChangeEvent<string>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      const payload = {
        username: formData.username,
        email: formData.email,
        full_name: formData.full_name,
        ...(formData.password && { password: formData.password }),
        ...(!isSelfEdit && { 
          role_id: parseInt(formData.role_id),
          subrole_id: parseInt(formData.subrole_id),
        }),
      };

      if (isSelfEdit) {
        await userService.updateMe(payload);
      } else {
        await userService.updateUser(Number(id), payload);
      }
      
      setSnackbar({ open: true, message: 'Perfil atualizado com sucesso!', severity: 'success' });
      
      if(isSelfEdit) {
        await fetchCurrentUser();
      }

      setTimeout(() => navigate('/'), 2000);
    } catch (error: any) {
      setSnackbar({ open: true, message: error.message || 'Erro ao atualizar perfil', severity: 'error' });
    }
  };

  const handleCloseSnackbar = () => setSnackbar(prev => ({ ...prev, open: false }));

  return (
    <PageContainer>
      <Typography variant="h5" gutterBottom>{isSelfEdit ? 'Editar Meu Perfil' : 'Editar Usuário'}</Typography>
      <form onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          <Grid item xs={12}>
            <TextField fullWidth required label="Nome de Usuário" name="username" value={formData.username} onChange={handleInputChange} />
          </Grid>
          <Grid item xs={12}>
            <TextField fullWidth required label="Nome Completo" name="full_name" value={formData.full_name} onChange={handleInputChange} />
          </Grid>
          <Grid item xs={12}>
            <TextField fullWidth required label="E-mail" name="email" type="email" value={formData.email} onChange={handleInputChange} />
          </Grid>
          <Grid item xs={12}>
            <TextField fullWidth label="Nova Senha" name="password" type="password" value={formData.password} onChange={handleInputChange} helperText="Deixe em branco para não alterar" />
          </Grid>
          
          {!isSelfEdit && (
            <>
              <Grid item xs={12} sm={6}>
                <FormControl fullWidth required>
                  <InputLabel>Grupo de Acesso</InputLabel>
                  <Select name="role_id" value={formData.role_id} onChange={handleSelectChange} label="Grupo de Acesso">
                    <MenuItem value="1">Administrador</MenuItem>
                    <MenuItem value="2">CFO</MenuItem>
                    <MenuItem value="3">Usuário</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} sm={6}>
                <FormControl fullWidth required>
                  <InputLabel>Subgrupo de Acesso</InputLabel>
                  <Select name="subrole_id" value={formData.subrole_id} onChange={handleSelectChange} label="Subgrupo de Acesso">
                    <MenuItem value="1">Acesso Total</MenuItem>
                    <MenuItem value="2">Acesso Limitado</MenuItem>
                    <MenuItem value="3">Somente Leitura</MenuItem>
                  </Select>
                </FormControl>
              </Grid>
            </>
          )}

          <Grid item xs={12}>
            <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
              <Button variant="contained" color="primary" type="submit" size="large">Salvar Alterações</Button>
            </Box>
          </Grid>
        </Grid>
      </form>
      <Snackbar open={snackbar.open} autoHideDuration={6000} onClose={handleCloseSnackbar} anchorOrigin={{ vertical: 'top', horizontal: 'right' }}>
        <Alert onClose={handleCloseSnackbar} severity={snackbar.severity} sx={{ width: '100%' }}>
          {snackbar.message}
        </Alert>
      </Snackbar>
    </PageContainer>
  );
} 