import React, { useEffect, useState } from 'react';
import {
  Typography, Button, Dialog, DialogTitle, DialogContent, DialogActions, TextField, Box, IconButton, List, ListItem, ListItemText, ListItemSecondaryAction, Divider, Tooltip
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon, People as PeopleIcon } from '@mui/icons-material';
import api from '../services/api';
import PageContainer from '../components/PageContainer';

interface Role {
  id: number;
  name: string;
  description: string;
}

interface User {
  id: number;
  username: string;
  email: string;
  full_name: string;
}

export default function Roles() {
  const [roles, setRoles] = useState<Role[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [openDialog, setOpenDialog] = useState(false);
  const [editingRole, setEditingRole] = useState<Role | null>(null);
  const [roleForm, setRoleForm] = useState({ name: '', description: '' });
  const [openUsersDialog, setOpenUsersDialog] = useState(false);
  const [users, setUsers] = useState<User[]>([]);
  const [selectedRoleId, setSelectedRoleId] = useState<number | null>(null);
  const [userToAdd, setUserToAdd] = useState('');

  useEffect(() => { fetchRoles(); }, []);

  const fetchRoles = async () => {
    try {
      setLoading(true);
      const response = await api.get('/roles/');
      setRoles(response.data);
    } catch (err) {
      setError('Erro ao carregar roles');
    } finally {
      setLoading(false);
    }
  };

  const handleOpenDialog = (role?: Role) => {
    setEditingRole(role || null);
    setRoleForm(role ? { name: role.name, description: role.description } : { name: '', description: '' });
    setOpenDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenDialog(false);
    setEditingRole(null);
    setRoleForm({ name: '', description: '' });
  };

  const handleSaveRole = async () => {
    try {
      if (editingRole) {
        const response = await api.put(`/roles/${editingRole.id}/`, roleForm);
        setRoles(prev => prev.map(r => r.id === editingRole.id ? response.data : r));
      } else {
        const response = await api.post('/roles/', roleForm);
        setRoles(prev => [...prev, response.data]);
      }
      handleCloseDialog();
    } catch (err) {
      setError('Erro ao salvar role');
    }
  };

  const handleDeleteRole = async (roleId: number) => {
    if (!window.confirm('Tem certeza que deseja excluir este role?')) return;
    try {
      await api.delete(`/roles/${roleId}/`);
      setRoles(prev => prev.filter(r => r.id !== roleId));
    } catch (err) {
      setError('Erro ao deletar role');
    }
  };

  // Usuários do role
  const handleOpenUsersDialog = async (roleId: number) => {
    setSelectedRoleId(roleId);
    setOpenUsersDialog(true);
    try {
      const response = await api.get(`/roles/${roleId}/users/`);
      setUsers(response.data);
    } catch (err) {
      setError('Erro ao carregar usuários do role');
    }
  };

  const handleCloseUsersDialog = () => {
    setOpenUsersDialog(false);
    setUsers([]);
    setUserToAdd('');
    setSelectedRoleId(null);
  };

  const handleAddUserToRole = async () => {
    if (!selectedRoleId || !userToAdd) return;
    try {
      await api.post(`/roles/${selectedRoleId}/users/`, { username: userToAdd });
      const response = await api.get(`/roles/${selectedRoleId}/users/`);
      setUsers(response.data);
      setUserToAdd('');
    } catch (err) {
      setError('Erro ao adicionar usuário ao role');
    }
  };

  const handleRemoveUserFromRole = async (userId: number) => {
    if (!selectedRoleId) return;
    try {
      await api.delete(`/roles/${selectedRoleId}/users/${userId}/`);
      setUsers(prev => prev.filter(u => u.id !== userId));
    } catch (err) {
      setError('Erro ao remover usuário do role');
    }
  };

  return (
    <PageContainer>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
        <Typography variant="h5">Perfis (Roles)</Typography>
        <Button variant="contained" color="primary" startIcon={<AddIcon />} onClick={() => handleOpenDialog()}>Novo Perfil</Button>
      </Box>
      {error && <Typography color="error">{error}</Typography>}
      <Divider sx={{ mb: 2 }} />
      <List>
        {roles.map(role => (
          <ListItem key={role.id} divider>
            <ListItemText primary={role.name} secondary={role.description} />
            <ListItemSecondaryAction>
              <Tooltip title="Usuários deste perfil"><IconButton onClick={() => handleOpenUsersDialog(role.id)}><PeopleIcon /></IconButton></Tooltip>
              <Tooltip title="Editar"><IconButton onClick={() => handleOpenDialog(role)}><EditIcon /></IconButton></Tooltip>
              <Tooltip title="Excluir"><IconButton onClick={() => handleDeleteRole(role.id)}><DeleteIcon /></IconButton></Tooltip>
            </ListItemSecondaryAction>
          </ListItem>
        ))}
      </List>
      {/* Dialog de criar/editar role */}
      <Dialog open={openDialog} onClose={handleCloseDialog} maxWidth="xs" fullWidth>
        <DialogTitle>{editingRole ? 'Editar Perfil' : 'Novo Perfil'}</DialogTitle>
        <DialogContent>
          <TextField label="Nome" fullWidth margin="normal" value={roleForm.name} onChange={e => setRoleForm(f => ({ ...f, name: e.target.value }))} />
          <TextField label="Descrição" fullWidth margin="normal" value={roleForm.description} onChange={e => setRoleForm(f => ({ ...f, description: e.target.value }))} />
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseDialog}>Cancelar</Button>
          <Button onClick={handleSaveRole} variant="contained" color="primary">Salvar</Button>
        </DialogActions>
      </Dialog>
      {/* Dialog de usuários do role */}
      <Dialog open={openUsersDialog} onClose={handleCloseUsersDialog} maxWidth="sm" fullWidth>
        <DialogTitle>Usuários do Perfil</DialogTitle>
        <DialogContent>
          <Box display="flex" mb={2}>
            <TextField label="Usuário (username)" value={userToAdd} onChange={e => setUserToAdd(e.target.value)} fullWidth />
            <Button onClick={handleAddUserToRole} variant="contained" color="primary" sx={{ ml: 2 }}>Adicionar</Button>
          </Box>
          <List>
            {users.map(user => (
              <ListItem key={user.id} divider>
                <ListItemText primary={user.full_name || user.username} secondary={user.email} />
                <ListItemSecondaryAction>
                  <Tooltip title="Remover"><IconButton onClick={() => handleRemoveUserFromRole(user.id)}><DeleteIcon /></IconButton></Tooltip>
                </ListItemSecondaryAction>
              </ListItem>
            ))}
          </List>
        </DialogContent>
        <DialogActions>
          <Button onClick={handleCloseUsersDialog}>Fechar</Button>
        </DialogActions>
      </Dialog>
    </PageContainer>
  );
} 