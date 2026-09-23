import React, { useState, useEffect, useMemo } from 'react';
import {
  Typography,
  Box,
  Card,
  CardContent,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  Alert,
  CircularProgress,
  List,
  ListItemButton,
  ListItemText,
  Chip,
  IconButton,
  Accordion,
  AccordionSummary,
  AccordionDetails
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  ExpandMore as ExpandMoreIcon,
  Shield as ShieldIcon,
  Person as PersonIcon
} from '@mui/icons-material';
import PageContainer from '../components/PageContainer';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

interface Role {
  id: number;
  name: string;
  description: string;
}

interface Permission {
  id: number;
  name: string;
  description: string;
  action: string;
  resource: string;
}

interface RolePermission {
  id: number;
  name: string;
  description: string;
  action: string;
  resource: string;
  granted: boolean;
}

const ACTION_LABELS: Record<string, string> = {
  view: 'Visualizar',
  create: 'Criar',
  edit: 'Editar',
  delete: 'Excluir',
  export: 'Exportar',
  approve: 'Aprovar',
  manage: 'Gerenciar'
};

const RESOURCE_LABELS: Record<string, string> = {
  users: 'Usuários',
  roles: 'Perfis',
  permissions: 'Permissões',
  menus: 'Menus',
  auditorias: 'Auditorias',
  consulta_integrada: 'Visão integrada',
  consulta_auditoria: 'Consulta Auditoria',
  consulta_prescricao: 'Consulta Prescrição',
  relatorio_adimplencia: 'Relatório Adimplência',
  relatorio_auditoria: 'Relatório Auditoria',
  relatorio_financeiro: 'Relatório Financeiro'
};

function groupPermissionsByResource(permissions: Permission[] | RolePermission[]) {
  const groups: Record<string, (Permission | RolePermission)[]> = {};
  permissions.forEach((p) => {
    const key = p.resource;
    if (!groups[key]) groups[key] = [];
    groups[key].push(p);
  });
  return groups;
}

export default function Permissions() {
  const { user } = useAuth();
  const [roles, setRoles] = useState<Role[]>([]);
  const [permissions, setPermissions] = useState<Permission[]>([]);
  const [rolePermissions, setRolePermissions] = useState<RolePermission[]>([]);
  const [selectedRoleId, setSelectedRoleId] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadingPermissions, setLoadingPermissions] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [openRoleDialog, setOpenRoleDialog] = useState(false);
  const [openPermissionDialog, setOpenPermissionDialog] = useState(false);
  const [openEditRoleDialog, setOpenEditRoleDialog] = useState(false);
  const [openDeleteRoleDialog, setOpenDeleteRoleDialog] = useState(false);
  const [editingRole, setEditingRole] = useState<Role | null>(null);
  const [deletingRole, setDeletingRole] = useState<Role | null>(null);

  const [roleForm, setRoleForm] = useState({ name: '', description: '' });
  const [permissionForm, setPermissionForm] = useState({
    name: '',
    description: '',
    action: '',
    resource: ''
  });

  const hasPermission = (name: string) =>
    user?.permissions?.includes(name) || user?.is_superuser;

  const selectedRole = roles.find((r) => r.id === selectedRoleId);

  // Permissões de gerenciamento de usuários são exclusivas do perfil Administrador
  const adminOnlyPermissions = ['view_users', 'create_users', 'edit_users', 'delete_users'];

  const filteredPermissions = useMemo(() => {
    if (!selectedRole || selectedRole.name === 'Administrador') return permissions;
    return permissions.filter((p) => !adminOnlyPermissions.includes(p.name));
  }, [permissions, selectedRole]);

  const permissionGroups = useMemo(
    () => groupPermissionsByResource(filteredPermissions),
    [filteredPermissions]
  );

  const getRolePermissionGranted = (permissionId: number) => {
    const rp = rolePermissions.find((rp) => rp.id === permissionId);
    return rp?.granted ?? false;
  };

  useEffect(() => {
    fetchRolesAndPermissions();
  }, []);

  useEffect(() => {
    if (selectedRoleId) {
      fetchRolePermissions(selectedRoleId);
    } else {
      setRolePermissions([]);
    }
  }, [selectedRoleId]);

  const fetchRolesAndPermissions = async () => {
    try {
      setLoading(true);
      const [rolesRes, permissionsRes] = await Promise.all([
        api.get<Role[]>('/roles/'),
        api.get<Permission[]>('/permissions/')
      ]);
      setRoles(rolesRes.data);
      setPermissions(permissionsRes.data);
      if (rolesRes.data.length > 0 && !selectedRoleId) {
        setSelectedRoleId(rolesRes.data[0].id);
      }
    } catch (err) {
      setError('Erro ao carregar dados');
    } finally {
      setLoading(false);
    }
  };

  const fetchRolePermissions = async (roleId: number) => {
    try {
      setLoadingPermissions(true);
      const res = await api.get<RolePermission[]>(`/roles/${roleId}/permissions/`);
      setRolePermissions(res.data);
    } catch (err) {
      setRolePermissions([]);
    } finally {
      setLoadingPermissions(false);
    }
  };

  const handleRolePermissionChange = async (
    roleId: number,
    permissionId: number,
    granted: boolean
  ) => {
    if (!hasPermission('manage_permissions')) return;
    try {
      if (granted) {
        await api.post(`/permissions/${permissionId}/roles/`, {
          role_id: roleId,
          permission_id: permissionId,
          granted: true
        });
      } else {
        await api.delete(`/permissions/${permissionId}/roles/${roleId}/`);
      }
      await fetchRolePermissions(roleId);
    } catch (err) {
      setError('Erro ao atualizar permissão');
    }
  };

  const handleCreateRole = async () => {
    try {
      const response = await api.post<Role>('/roles/', roleForm);
      setRoles((prev) => [...prev, response.data]);
      setOpenRoleDialog(false);
      setRoleForm({ name: '', description: '' });
      setSelectedRoleId(response.data.id);
    } catch (err) {
      setError('Erro ao criar perfil');
    }
  };

  const handleCreatePermission = async () => {
    try {
      const response = await api.post<Permission>('/permissions/', permissionForm);
      setPermissions((prev) => [...prev, response.data]);
      setOpenPermissionDialog(false);
      setPermissionForm({ name: '', description: '', action: '', resource: '' });
    } catch (err) {
      setError('Erro ao criar permissão');
    }
  };

  const handleEditRole = (role: Role) => {
    setEditingRole(role);
    setRoleForm({ name: role.name, description: role.description || '' });
    setOpenEditRoleDialog(true);
  };

  const handleDeleteRole = (role: Role) => {
    setDeletingRole(role);
    setOpenDeleteRoleDialog(true);
  };

  const handleUpdateRole = async () => {
    if (!editingRole) return;
    try {
      const response = await api.put<Role>(`/roles/${editingRole.id}/`, roleForm);
      setRoles((prev) =>
        prev.map((r) => (r.id === editingRole.id ? response.data : r))
      );
      setOpenEditRoleDialog(false);
      setEditingRole(null);
      setRoleForm({ name: '', description: '' });
    } catch (err) {
      setError('Erro ao atualizar perfil');
    }
  };

  const handleConfirmDeleteRole = async () => {
    if (!deletingRole) return;
    try {
      await api.delete(`/roles/${deletingRole.id}/`);
      setRoles((prev) => prev.filter((r) => r.id !== deletingRole.id));
      if (selectedRoleId === deletingRole.id) {
        const remaining = roles.filter((r) => r.id !== deletingRole.id);
        setSelectedRoleId(remaining[0]?.id ?? null);
      }
      setOpenDeleteRoleDialog(false);
      setDeletingRole(null);
    } catch (err: any) {
      setError(err.response?.data?.detail || 'Erro ao excluir perfil');
    }
  };

  if (loading) {
    return (
      <PageContainer>
        <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
          <CircularProgress />
        </Box>
      </PageContainer>
    );
  }

  return (
    <PageContainer>
      <Box sx={{ display: 'flex', flexDirection: 'column', height: '100%' }}>
        {/* Header */}
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            mb: 3,
            flexWrap: 'wrap',
            gap: 2
          }}
        >
          <Box>
            <Typography variant="h5" component="h1" fontWeight={600}>
              Permissões
            </Typography>
            <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
              Selecione um perfil e configure suas permissões
            </Typography>
          </Box>
          <Box sx={{ display: 'flex', gap: 1 }}>
            {hasPermission('manage_roles') && (
              <Button
                variant="contained"
                startIcon={<PersonIcon />}
                onClick={() => setOpenRoleDialog(true)}
              >
                Novo perfil
              </Button>
            )}
            {hasPermission('manage_permissions') && (
              <Button
                variant="outlined"
                startIcon={<AddIcon />}
                onClick={() => setOpenPermissionDialog(true)}
              >
                Nova permissão
              </Button>
            )}
          </Box>
        </Box>

        {error && (
          <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>
            {error}
          </Alert>
        )}

        {/* Layout principal: lista de perfis + painel de permissões */}
        <Box
          sx={{
            display: 'flex',
            gap: 3,
            flex: 1,
            minHeight: 0,
            flexDirection: { xs: 'column', md: 'row' }
          }}
        >
          {/* Lista de perfis */}
          <Card sx={{ minWidth: { md: 280 }, maxWidth: { md: 320 } }}>
            <CardContent sx={{ py: 1, '&:last-child': { pb: 1 } }}>
              <Typography variant="subtitle2" color="text.secondary" sx={{ px: 1, mb: 1 }}>
                Perfis
              </Typography>
              <List dense disablePadding>
                {roles.map((role) => (
                  <ListItemButton
                    key={role.id}
                    selected={selectedRoleId === role.id}
                    onClick={() => setSelectedRoleId(role.id)}
                    sx={{
                      borderRadius: 1,
                      mb: 0.5,
                      '&.Mui-selected': {
                        backgroundColor: 'action.selected',
                        '&:hover': { backgroundColor: 'action.hover' }
                      }
                    }}
                  >
                    <ListItemText
                      primary={role.name}
                      secondary={role.description ? role.description.slice(0, 40) + (role.description.length > 40 ? '...' : '') : undefined}
                      primaryTypographyProps={{ fontWeight: selectedRoleId === role.id ? 600 : 400 }}
                    />
                    {hasPermission('manage_roles') && (
                      <Box onClick={(e) => e.stopPropagation()} sx={{ display: 'flex' }}>
                        <IconButton size="small" onClick={() => handleEditRole(role)}>
                          <EditIcon fontSize="small" />
                        </IconButton>
                        <IconButton size="small" color="error" onClick={() => handleDeleteRole(role)}>
                          <DeleteIcon fontSize="small" />
                        </IconButton>
                      </Box>
                    )}
                  </ListItemButton>
                ))}
              </List>
            </CardContent>
          </Card>

          {/* Painel de permissões do perfil selecionado */}
          <Card sx={{ flex: 1, minWidth: 0 }}>
            <CardContent>
              {!selectedRole ? (
                <Box
                  sx={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    py: 8,
                    color: 'text.secondary'
                  }}
                >
                  <ShieldIcon sx={{ fontSize: 48, mb: 1, opacity: 0.5 }} />
                  <Typography>Selecione um perfil para configurar permissões</Typography>
                  <Typography variant="body2" sx={{ mt: 0.5 }}>
                    Ou crie um novo perfil
                  </Typography>
                </Box>
              ) : (
                <>
                  <Box
                    sx={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      mb: 2,
                      flexWrap: 'wrap',
                      gap: 1
                    }}
                  >
                    <Box>
                      <Typography variant="h6" fontWeight={600}>
                        {selectedRole.name}
                      </Typography>
                      {selectedRole.description && (
                        <Typography variant="body2" color="text.secondary">
                          {selectedRole.description}
                        </Typography>
                      )}
                    </Box>
                    <Chip
                      label={`${rolePermissions.filter((rp) => rp.granted).length} permissões`}
                      size="small"
                      variant="outlined"
                    />
                  </Box>

                  {loadingPermissions ? (
                    <Box display="flex" justifyContent="center" py={4}>
                      <CircularProgress size={32} />
                    </Box>
                  ) : (
                    <Box>
                      {Object.entries(permissionGroups)
                        .sort(([a], [b]) => a.localeCompare(b))
                        .map(([resource, perms]) => (
                          <Accordion
                            key={resource}
                            defaultExpanded
                            disableGutters
                            sx={{
                              boxShadow: 'none',
                              '&:before': { display: 'none' },
                              borderBottom: 1,
                              borderColor: 'divider'
                            }}
                          >
                            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                              <Typography variant="subtitle2" fontWeight={500}>
                                {RESOURCE_LABELS[resource] || resource}
                              </Typography>
                            </AccordionSummary>
                            <AccordionDetails sx={{ pt: 0 }}>
                              <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                                {perms.map((perm) => {
                                  const granted = getRolePermissionGranted(perm.id);
                                  return (
                                    <Chip
                                      key={perm.id}
                                      label={ACTION_LABELS[perm.action] || perm.action}
                                      onClick={() =>
                                        hasPermission('manage_permissions') &&
                                        handleRolePermissionChange(
                                          selectedRole.id,
                                          perm.id,
                                          !granted
                                        )
                                      }
                                      color={granted ? 'primary' : 'default'}
                                      variant={granted ? 'filled' : 'outlined'}
                                      sx={{
                                        cursor: hasPermission('manage_permissions')
                                          ? 'pointer'
                                          : 'default'
                                      }}
                                    />
                                  );
                                })}
                              </Box>
                            </AccordionDetails>
                          </Accordion>
                        ))}
                    </Box>
                  )}
                </>
              )}
            </CardContent>
          </Card>
        </Box>
      </Box>

      {/* Diálogos */}
      <Dialog open={openRoleDialog} onClose={() => setOpenRoleDialog(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Novo perfil</DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            margin="dense"
            label="Nome"
            fullWidth
            value={roleForm.name}
            onChange={(e) => setRoleForm({ ...roleForm, name: e.target.value })}
            sx={{ mb: 2 }}
          />
          <TextField
            margin="dense"
            label="Descrição"
            fullWidth
            multiline
            rows={2}
            value={roleForm.description}
            onChange={(e) => setRoleForm({ ...roleForm, description: e.target.value })}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenRoleDialog(false)}>Cancelar</Button>
          <Button onClick={handleCreateRole} variant="contained">
            Criar
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={openEditRoleDialog}
        onClose={() => setOpenEditRoleDialog(false)}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>Editar perfil</DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            margin="dense"
            label="Nome"
            fullWidth
            value={roleForm.name}
            onChange={(e) => setRoleForm({ ...roleForm, name: e.target.value })}
            sx={{ mb: 2 }}
          />
          <TextField
            margin="dense"
            label="Descrição"
            fullWidth
            multiline
            rows={2}
            value={roleForm.description}
            onChange={(e) => setRoleForm({ ...roleForm, description: e.target.value })}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenEditRoleDialog(false)}>Cancelar</Button>
          <Button onClick={handleUpdateRole} variant="contained">
            Atualizar
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={openDeleteRoleDialog} onClose={() => setOpenDeleteRoleDialog(false)}>
        <DialogTitle>Excluir perfil</DialogTitle>
        <DialogContent>
          <Typography>
            Excluir o perfil &quot;{deletingRole?.name}&quot;? Esta ação não pode ser desfeita.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenDeleteRoleDialog(false)}>Cancelar</Button>
          <Button onClick={handleConfirmDeleteRole} color="error" variant="contained">
            Excluir
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={openPermissionDialog}
        onClose={() => setOpenPermissionDialog(false)}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>Nova permissão</DialogTitle>
        <DialogContent>
          <TextField
            autoFocus
            margin="dense"
            label="Nome"
            fullWidth
            value={permissionForm.name}
            onChange={(e) => setPermissionForm({ ...permissionForm, name: e.target.value })}
            sx={{ mb: 2 }}
          />
          <TextField
            margin="dense"
            label="Descrição"
            fullWidth
            multiline
            rows={2}
            value={permissionForm.description}
            onChange={(e) =>
              setPermissionForm({ ...permissionForm, description: e.target.value })
            }
            sx={{ mb: 2 }}
          />
          <FormControl fullWidth sx={{ mb: 2 }}>
            <InputLabel>Ação</InputLabel>
            <Select
              value={permissionForm.action}
              label="Ação"
              onChange={(e) => setPermissionForm({ ...permissionForm, action: e.target.value })}
            >
              {Object.entries(ACTION_LABELS).map(([value, label]) => (
                <MenuItem key={value} value={value}>
                  {label}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
          <TextField
            margin="dense"
            label="Recurso"
            fullWidth
            value={permissionForm.resource}
            onChange={(e) =>
              setPermissionForm({ ...permissionForm, resource: e.target.value })
            }
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenPermissionDialog(false)}>Cancelar</Button>
          <Button onClick={handleCreatePermission} variant="contained">
            Criar
          </Button>
        </DialogActions>
      </Dialog>
    </PageContainer>
  );
}
