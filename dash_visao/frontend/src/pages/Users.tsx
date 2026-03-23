import { useState, useEffect } from 'react';
import {
  Box,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Typography,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  IconButton,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  SelectChangeEvent,
} from '@mui/material';
import { Edit as EditIcon, Delete as DeleteIcon } from '@mui/icons-material';
import api from '../services/api';

interface User {
  id: number;
  username: string;
  email: string;
  role: {
    id: number;
    name: string;
  };
  subrole: {
    id: number;
    name: string;
  };
  status: 'active' | 'inactive';
  created_at: string;
  last_activity: string;
}

interface Role {
  id: number;
  name: string;
}

interface SubRole {
  id: number;
  name: string;
}

interface FormData {
  id: number;
  username: string;
  email: string;
  password: string;
  role: string;
  subrole: string;
  status: 'active' | 'inactive';
}

export default function Users() {
  const [users, setUsers] = useState<User[]>([]);
  const [roles, setRoles] = useState<Role[]>([]);
  const [subRoles, setSubRoles] = useState<SubRole[]>([]);
  const [open, setOpen] = useState(false);
  const [editMode, setEditMode] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [formData, setFormData] = useState<FormData>({
    id: 0,
    username: '',
    email: '',
    password: '',
    role: '',
    subrole: '',
    status: 'active',
  });

  useEffect(() => {
    fetchUsers();
    fetchRoles();
    fetchSubRoles();
  }, []);

  const fetchUsers = async () => {
    try {
      const response = await api.get('/users/');
      setUsers(response.data);
    } catch (error) {
      setError('Erro ao carregar usuários');
    }
  };

  const fetchRoles = async () => {
    try {
      const response = await api.get('/users/roles/');
      setRoles(response.data);
    } catch (error) {
      setError('Erro ao carregar grupos de acesso');
    }
  };

  const fetchSubRoles = async () => {
    try {
      const response = await api.get('/users/subroles/');
      setSubRoles(response.data);
    } catch (error) {
      setError('Erro ao carregar subgrupos de acesso');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editMode) {
        await api.patch(`/users/${formData.id}`, formData);
        setSuccess('Usuário atualizado com sucesso!');
      } else {
        await api.post('/users/', formData);
        setSuccess('Usuário criado com sucesso!');
      }
      setOpen(false);
      fetchUsers();
      resetForm();
    } catch (error) {
      setError(editMode ? 'Erro ao atualizar usuário' : 'Erro ao criar usuário');
    }
  };

  const handleEdit = (user: User) => {
    setFormData({
      id: user.id,
      username: user.username,
      email: user.email,
      password: '',
      role: user.role.id.toString(),
      subrole: user.subrole.id.toString(),
      status: user.status,
    });
    setEditMode(true);
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setEditMode(false);
    resetForm();
  };

  const resetForm = () => {
    setFormData({
      id: 0,
      username: '',
      email: '',
      password: '',
      role: '',
      subrole: '',
      status: 'active',
    });
  };

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement> | SelectChangeEvent<string>
  ) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleStatusChange = async (userId: number, newStatus: 'active' | 'inactive') => {
    try {
      await api.patch(`/users/${userId}`, { status: newStatus });
      setSuccess(`Usuário ${newStatus === 'active' ? 'ativado' : 'desativado'} com sucesso!`);
      fetchUsers();
    } catch (error) {
      setError('Erro ao alterar status do usuário');
    }
  };

  return (
    <Box sx={{ flexGrow: 1 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
        <Typography variant="h4">Usuários</Typography>
        <Button variant="contained" onClick={() => {
          setEditMode(false);
          setOpen(true);
        }}>
          Novo Usuário
        </Button>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}
      {success && <Alert severity="success" sx={{ mb: 2 }}>{success}</Alert>}

      <TableContainer component={Paper}>
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>ID</TableCell>
              <TableCell>Nome</TableCell>
              <TableCell>Email</TableCell>
              <TableCell>Grupo</TableCell>
              <TableCell>Subgrupo</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Criação</TableCell>
              <TableCell>Última Atividade</TableCell>
              <TableCell>Ações</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {users.map((user) => (
              <TableRow key={user.id}>
                <TableCell>{user.id}</TableCell>
                <TableCell>{user.username}</TableCell>
                <TableCell>{user.email}</TableCell>
                <TableCell>{user.role?.name || ''}</TableCell>
                <TableCell>{user.subrole?.name || ''}</TableCell>
                <TableCell>
                  <Button
                    variant={user.status === 'active' ? 'contained' : 'outlined'}
                    color={user.status === 'active' ? 'success' : 'error'}
                    size="small"
                    onClick={() => handleStatusChange(user.id, user.status === 'active' ? 'inactive' : 'active')}
                  >
                    {user.status === 'active' ? 'Ativo' : 'Inativo'}
                  </Button>
                </TableCell>
                <TableCell>{new Date(user.created_at).toLocaleDateString()}</TableCell>
                <TableCell>{new Date(user.last_activity).toLocaleDateString()}</TableCell>
                <TableCell>
                  <IconButton size="small" onClick={() => handleEdit(user)}>
                    <EditIcon />
                  </IconButton>
                  <IconButton size="small" color="error">
                    <DeleteIcon />
                  </IconButton>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>

      <Dialog open={open} onClose={handleClose}>
        <DialogTitle>{editMode ? 'Editar Usuário' : 'Novo Usuário'}</DialogTitle>
        <form onSubmit={handleSubmit}>
          <DialogContent>
            <TextField
              margin="normal"
              required
              fullWidth
              label="Nome"
              name="username"
              value={formData.username}
              onChange={handleChange}
            />
            <TextField
              margin="normal"
              required
              fullWidth
              label="Email"
              name="email"
              type="email"
              value={formData.email}
              onChange={handleChange}
            />
            <TextField
              margin="normal"
              required={!editMode}
              fullWidth
              label="Senha"
              name="password"
              type="password"
              value={formData.password}
              onChange={handleChange}
              helperText={editMode ? "Deixe em branco para manter a senha atual" : ""}
            />
            <FormControl fullWidth margin="normal">
              <InputLabel>Grupo de Acesso</InputLabel>
              <Select
                name="role"
                value={formData.role}
                onChange={handleChange}
                label="Grupo de Acesso"
              >
                {roles.map((role) => (
                  <MenuItem key={role.id} value={role.id}>
                    {role.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
            <FormControl fullWidth margin="normal">
              <InputLabel>Subgrupo de Acesso</InputLabel>
              <Select
                name="subrole"
                value={formData.subrole}
                onChange={handleChange}
                label="Subgrupo de Acesso"
              >
                {subRoles.map((subRole) => (
                  <MenuItem key={subRole.id} value={subRole.id}>
                    {subRole.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Cancelar</Button>
            <Button type="submit" variant="contained">
              {editMode ? 'Salvar' : 'Criar'}
            </Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
} 