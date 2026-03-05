import React, { useState, useEffect } from 'react';
import {
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  IconButton,
  Typography,
  Box,
  Snackbar,
  Alert,
  CircularProgress,
  Button,
} from '@mui/material';
import {
  Edit as EditIcon,
  Delete as DeleteIcon,
  Visibility as ViewIcon,
  Add as AddIcon,
} from '@mui/icons-material';
import { userService, User } from '../services/userService';
import { useNavigate } from 'react-router-dom';
import PageContainer from '../components/PageContainer';

// Mapeamento de roles para nomes legíveis
const roleNames: { [key: number]: string } = {
  1: 'Administrador',
  2: 'CFO',
  3: 'Usuário',
};

export default function UserList() {
  const navigate = useNavigate();
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [snackbar, setSnackbar] = useState({
    open: false,
    message: '',
    severity: 'success' as 'success' | 'error',
  });

  const loadUsers = async () => {
    try {
      setLoading(true);
      const data = await userService.getUsers();
      console.log('Dados recebidos:', data); // Debug
      setUsers(data as User[]);
    } catch (error) {
      console.error('Erro ao carregar usuários:', error); // Debug
      setSnackbar({
        open: true,
        message: error instanceof Error ? error.message : 'Erro ao carregar usuários',
        severity: 'error',
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadUsers();
  }, []);

  const handleDelete = async (id: number) => {
    if (window.confirm('Tem certeza que deseja excluir este usuário?')) {
      try {
        await userService.deleteUser(id);
        setSnackbar({
          open: true,
          message: 'Usuário excluído com sucesso!',
          severity: 'success',
        });
        loadUsers(); // Recarrega a lista
      } catch (error) {
        setSnackbar({
          open: true,
          message: error instanceof Error ? error.message : 'Erro ao excluir usuário',
          severity: 'error',
        });
      }
    }
  };

  const handleEdit = (id: number) => {
    navigate(`/edit-profile/${id}`);
  };

  const handleView = (id: number) => {
    navigate(`/view-user/${id}`);
  };

  const handleAddUser = () => {
    navigate('/add-user');
  };

  const handleCloseSnackbar = () => {
    setSnackbar(prev => ({ ...prev, open: false }));
  };

  if (loading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100%', mt: 8 }}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <PageContainer>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5" component="h1">
          Lista de Usuários
        </Typography>
        <Button
          variant="contained"
          color="primary"
          startIcon={<AddIcon />}
          onClick={handleAddUser}
        >
          Adicionar Usuário
        </Button>
      </Box>
      <TableContainer sx={{ maxHeight: 'calc(100vh - 250px)', minHeight: '400px' }}>
        <Table stickyHeader>
          <TableHead>
            <TableRow>
              <TableCell sx={{ fontWeight: 'bold' }}>Nome de Usuário</TableCell>
              <TableCell sx={{ fontWeight: 'bold' }}>E-mail</TableCell>
              <TableCell sx={{ fontWeight: 'bold' }}>Grupo</TableCell>
              <TableCell sx={{ fontWeight: 'bold' }}>Status</TableCell>
              <TableCell align="center" sx={{ fontWeight: 'bold' }}>Ações</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {users.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} align="center" sx={{ py: 3 }}>
                  <Typography variant="body1" color="text.secondary">
                    {snackbar.severity === 'error' ? 
                      'Erro ao carregar usuários. Por favor, verifique se o backend está rodando.' : 
                      'Nenhum usuário encontrado'}
                  </Typography>
                </TableCell>
              </TableRow>
            ) : (
              users.map((user) => (
                <TableRow key={user.id} hover>
                  <TableCell>{user.username}</TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>{roleNames[user.role_id] || 'Desconhecido'}</TableCell>
                  <TableCell>
                    <Box
                      sx={{
                        display: 'inline-block',
                        px: 1,
                        py: 0.5,
                        borderRadius: 1,
                        bgcolor: user.is_active ? 'success.light' : 'error.light',
                        color: user.is_active ? 'success.dark' : 'error.dark',
                        fontSize: '0.875rem',
                      }}
                    >
                      {user.is_active ? 'Ativo' : 'Inativo'}
                    </Box>
                  </TableCell>
                  <TableCell align="center">
                    <IconButton 
                      color="primary" 
                      size="small" 
                      title="Visualizar"
                      onClick={() => handleView(user.id!)}
                    >
                      <ViewIcon />
                    </IconButton>
                    <IconButton 
                      color="primary" 
                      size="small" 
                      title="Editar"
                      onClick={() => handleEdit(user.id!)}
                    >
                      <EditIcon />
                    </IconButton>
                    <IconButton 
                      color="error" 
                      size="small" 
                      title="Excluir"
                      onClick={() => handleDelete(user.id!)}
                    >
                      <DeleteIcon />
                    </IconButton>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </TableContainer>
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