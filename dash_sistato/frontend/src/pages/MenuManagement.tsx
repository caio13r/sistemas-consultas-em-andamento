import React, { useState, useEffect, useCallback } from 'react';
import {
  Typography,
  Card,
  CardContent,
  Button,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Alert,
  CircularProgress,
  Box,
  IconButton,
  Tooltip,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  Divider,
  Switch,
  FormControlLabel,
  Chip,
  Tab,
  Tabs,
  Paper,
  Collapse,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
} from '@mui/material';
import {
  Add as AddIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  DragIndicator as DragIcon,
  ExpandMore,
  ExpandLess,
  Save as SaveIcon,
  Reorder as ReorderIcon,
  Cancel as CancelIcon,
  Home as HomeIcon,
  Search as SearchIcon,
  Assignment as AssignmentIcon,
  Gavel as GavelIcon,
  Badge as BadgeIcon,
  BarChart as BarChartIcon,
  MedicalServices as MedicalServicesIcon,
  AccountBalance as AccountBalanceIcon,
  Public as PublicIcon,
  TableChart as TableChartIcon,
  Assessment as AssessmentIcon,
  HowToVote as HowToVoteIcon,
  Policy as PolicyIcon,
  Folder as FolderIcon,
  Settings as SettingsIcon,
  AdminPanelSettings as AdminIcon,
  People as PeopleIcon,
  PersonAdd as PersonAddIcon,
  Security as SecurityIcon,
  ViewList as ViewListIcon,
  SupervisorAccount as SupervisorAccountIcon,
  HowToReg as HowToRegIcon,
  List as ListIcon,
} from '@mui/icons-material';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
  useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import PageContainer from '../components/PageContainer';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

interface SubMenuData {
  id: number;
  menu_id?: number;
  name: string;
  url?: string;
  icon?: string;
  description?: string;
  order: number;
  disable: boolean;
  permission_name?: string;
}

interface MenuData {
  id: number;
  name: string;
  url?: string;
  icon?: string;
  description?: string;
  order: number;
  disable: boolean;
  permission_name?: string;
  is_section: boolean;
  submenus: SubMenuData[];
}

const ICON_OPTIONS = [
  'Home', 'Search', 'Assignment', 'Gavel', 'Badge', 'BarChart',
  'MedicalServices', 'AccountBalance', 'Public', 'TableChart',
  'Assessment', 'HowToVote', 'Policy', 'Folder', 'Settings',
  'AdminPanelSettings', 'People', 'PersonAdd', 'Security',
  'ViewList', 'SupervisorAccount', 'HowToReg', 'Add',
];

const getIconComponent = (iconName?: string | null): React.ReactElement => {
  const iconMap: { [key: string]: React.ReactElement } = {
    'Home': <HomeIcon />,
    'Search': <SearchIcon />,
    'Assignment': <AssignmentIcon />,
    'Gavel': <GavelIcon />,
    'Badge': <BadgeIcon />,
    'BarChart': <BarChartIcon />,
    'MedicalServices': <MedicalServicesIcon />,
    'AccountBalance': <AccountBalanceIcon />,
    'Public': <PublicIcon />,
    'TableChart': <TableChartIcon />,
    'Assessment': <AssessmentIcon />,
    'HowToVote': <HowToVoteIcon />,
    'Policy': <PolicyIcon />,
    'Folder': <FolderIcon />,
    'Settings': <SettingsIcon />,
    'AdminPanelSettings': <AdminIcon />,
    'People': <PeopleIcon />,
    'PersonAdd': <PersonAddIcon />,
    'Security': <SecurityIcon />,
    'ViewList': <ViewListIcon />,
    'SupervisorAccount': <SupervisorAccountIcon />,
    'HowToReg': <HowToRegIcon />,
    'Add': <AddIcon />,
  };
  return iconMap[iconName || ''] || <ListIcon />;
};

// Componente de item arrastável para menus
function SortableMenuItem({ menu, onEdit, onDelete, onToggle, reorderMode }: {
  menu: MenuData;
  onEdit: (menu: MenuData) => void;
  onDelete: (id: number) => void;
  onToggle: (id: number, disable: boolean) => void;
  reorderMode: boolean;
}) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: menu.id, disabled: !reorderMode });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
    zIndex: isDragging ? 1000 : 'auto',
  };

  return (
    <ListItem
      ref={setNodeRef}
      style={style as React.CSSProperties}
      sx={{
        bgcolor: menu.disable ? 'action.disabledBackground' : (menu.is_section ? 'primary.50' : 'background.paper'),
        mb: 0.5,
        borderRadius: 1,
        border: '1px solid',
        borderColor: isDragging ? 'primary.main' : 'divider',
        '&:hover': { bgcolor: menu.disable ? 'action.disabledBackground' : 'action.hover' },
      }}
      secondaryAction={
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
          {menu.is_section && (
            <Chip label="Seção" size="small" color="info" variant="outlined" />
          )}
          {menu.permission_name && (
            <Chip label={menu.permission_name} size="small" variant="outlined" />
          )}
          <Switch
            size="small"
            checked={!menu.disable}
            onChange={() => onToggle(menu.id, !menu.disable)}
          />
          <Tooltip title="Editar">
            <IconButton size="small" onClick={() => onEdit(menu)}>
              <EditIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Excluir">
            <IconButton size="small" color="error" onClick={() => onDelete(menu.id)}>
              <DeleteIcon fontSize="small" />
            </IconButton>
          </Tooltip>
        </Box>
      }
    >
      {reorderMode && (
        <ListItemIcon sx={{ minWidth: 32, cursor: 'grab' }} {...attributes} {...listeners}>
          <DragIcon />
        </ListItemIcon>
      )}
      <ListItemIcon sx={{ minWidth: 36 }}>
        {getIconComponent(menu.icon)}
      </ListItemIcon>
      <ListItemText
        primary={
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <Typography variant="body1" sx={{ fontWeight: menu.is_section ? 600 : 400, color: menu.disable ? 'text.disabled' : 'text.primary' }}>
              {menu.name}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              #{menu.order}
            </Typography>
          </Box>
        }
        secondary={menu.url || (menu.is_section ? 'Separador de seção' : 'Sem URL')}
      />
    </ListItem>
  );
}

// Componente de item arrastável para submenus
function SortableSubMenuItem({ submenu, onEdit, onDelete, onToggle, reorderMode }: {
  submenu: SubMenuData;
  onEdit: (sub: SubMenuData) => void;
  onDelete: (menuId: number, subId: number) => void;
  onToggle: (subId: number, disable: boolean) => void;
  reorderMode: boolean;
}) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: submenu.id, disabled: !reorderMode });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  return (
    <ListItem
      ref={setNodeRef}
      style={style as React.CSSProperties}
      sx={{
        bgcolor: submenu.disable ? 'action.disabledBackground' : 'background.paper',
        mb: 0.5,
        borderRadius: 1,
        border: '1px solid',
        borderColor: isDragging ? 'primary.main' : 'divider',
        pl: reorderMode ? 2 : 4,
      }}
      secondaryAction={
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
          {submenu.permission_name && (
            <Chip label={submenu.permission_name} size="small" variant="outlined" />
          )}
          <Switch
            size="small"
            checked={!submenu.disable}
            onChange={() => onToggle(submenu.id, !submenu.disable)}
          />
          <Tooltip title="Editar">
            <IconButton size="small" onClick={() => onEdit(submenu)}>
              <EditIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Tooltip title="Excluir">
            <IconButton size="small" color="error" onClick={() => onDelete(submenu.menu_id!, submenu.id)}>
              <DeleteIcon fontSize="small" />
            </IconButton>
          </Tooltip>
        </Box>
      }
    >
      {reorderMode && (
        <ListItemIcon sx={{ minWidth: 32, cursor: 'grab' }} {...attributes} {...listeners}>
          <DragIcon />
        </ListItemIcon>
      )}
      <ListItemIcon sx={{ minWidth: 36 }}>
        {getIconComponent(submenu.icon)}
      </ListItemIcon>
      <ListItemText
        primary={
          <Typography variant="body2" sx={{ color: submenu.disable ? 'text.disabled' : 'text.primary' }}>
            {submenu.name}
          </Typography>
        }
        secondary={submenu.url || 'Sem URL'}
      />
    </ListItem>
  );
}

export default function MenuManagement() {
  const { user } = useAuth();
  const [menus, setMenus] = useState<MenuData[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [tabIndex, setTabIndex] = useState(0);

  // Reorder mode
  const [reorderMode, setReorderMode] = useState(false);
  const [reorderSubMenuId, setReorderSubMenuId] = useState<number | null>(null);

  // Dialog states
  const [openMenuDialog, setOpenMenuDialog] = useState(false);
  const [openSubmenuDialog, setOpenSubmenuDialog] = useState(false);
  const [editingMenu, setEditingMenu] = useState<MenuData | null>(null);
  const [editingSubmenu, setEditingSubmenu] = useState<SubMenuData | null>(null);
  const [selectedMenuId, setSelectedMenuId] = useState<number | null>(null);

  // Form states
  const [menuForm, setMenuForm] = useState({
    name: '', url: '', icon: '', description: '', order: 0, permission_name: '', is_section: false,
  });
  const [submenuForm, setSubmenuForm] = useState({
    name: '', url: '', icon: '', description: '', order: 0, permission_name: '', menu_id: 0,
  });

  // Hierarchy expand state
  const [expandedMenus, setExpandedMenus] = useState<Set<number>>(new Set());

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
    useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
  );

  useEffect(() => {
    fetchMenus();
  }, []);

  const fetchMenus = async () => {
    try {
      setLoading(true);
      const response = await api.get('/menus/');
      const menusData = (response.data || []).map((menu: MenuData) => ({
        ...menu,
        submenus: Array.isArray(menu.submenus) ? menu.submenus.sort((a: SubMenuData, b: SubMenuData) => a.order - b.order) : [],
      })).sort((a: MenuData, b: MenuData) => a.order - b.order);
      setMenus(menusData);
    } catch {
      setError('Erro ao carregar menus');
    } finally {
      setLoading(false);
    }
  };

  const showSuccess = (msg: string) => {
    setSuccess(msg);
    setTimeout(() => setSuccess(null), 3000);
  };

  // ---- CRUD Menus ----
  const openCreateMenu = () => {
    setEditingMenu(null);
    setMenuForm({ name: '', url: '', icon: '', description: '', order: menus.length, permission_name: '', is_section: false });
    setOpenMenuDialog(true);
  };

  const openEditMenu = (menu: MenuData) => {
    setEditingMenu(menu);
    setMenuForm({
      name: menu.name,
      url: menu.url || '',
      icon: menu.icon || '',
      description: menu.description || '',
      order: menu.order,
      permission_name: menu.permission_name || '',
      is_section: menu.is_section,
    });
    setOpenMenuDialog(true);
  };

  const handleSaveMenu = async () => {
    try {
      const payload = {
        ...menuForm,
        permission_name: menuForm.permission_name || null,
      };

      if (editingMenu) {
        await api.put(`/menus/${editingMenu.id}/`, payload);
        showSuccess('Menu atualizado com sucesso');
      } else {
        await api.post('/menus/', payload);
        showSuccess('Menu criado com sucesso');
      }
      setOpenMenuDialog(false);
      fetchMenus();
    } catch {
      setError(editingMenu ? 'Erro ao atualizar menu' : 'Erro ao criar menu');
    }
  };

  const handleDeleteMenu = async (menuId: number) => {
    if (!window.confirm('Tem certeza que deseja excluir este menu e todos seus submenus?')) return;
    try {
      await api.delete(`/menus/${menuId}/`);
      showSuccess('Menu excluído com sucesso');
      fetchMenus();
    } catch {
      setError('Erro ao excluir menu');
    }
  };

  const handleToggleMenu = async (menuId: number, disable: boolean) => {
    try {
      await api.put(`/menus/${menuId}/`, { disable });
      setMenus(prev => prev.map(m => m.id === menuId ? { ...m, disable } : m));
    } catch {
      setError('Erro ao atualizar status');
    }
  };

  // ---- CRUD Submenus ----
  const openCreateSubmenu = (menuId: number) => {
    const menu = menus.find(m => m.id === menuId);
    setEditingSubmenu(null);
    setSelectedMenuId(menuId);
    setSubmenuForm({
      name: '', url: '', icon: '', description: '', order: menu?.submenus.length || 0,
      permission_name: '', menu_id: menuId,
    });
    setOpenSubmenuDialog(true);
  };

  const openEditSubmenu = (sub: SubMenuData) => {
    setEditingSubmenu(sub);
    setSelectedMenuId(sub.menu_id!);
    setSubmenuForm({
      name: sub.name,
      url: sub.url || '',
      icon: sub.icon || '',
      description: sub.description || '',
      order: sub.order,
      permission_name: sub.permission_name || '',
      menu_id: sub.menu_id!,
    });
    setOpenSubmenuDialog(true);
  };

  const handleSaveSubmenu = async () => {
    const menuId = selectedMenuId || submenuForm.menu_id;
    if (!menuId) return;
    try {
      const payload = {
        ...submenuForm,
        permission_name: submenuForm.permission_name || null,
      };
      delete (payload as any).menu_id;

      if (editingSubmenu) {
        await api.put(`/menus/submenus/${editingSubmenu.id}/`, payload);
        showSuccess('Submenu atualizado com sucesso');
      } else {
        await api.post(`/menus/${menuId}/submenus/`, payload);
        showSuccess('Submenu criado com sucesso');
      }
      setOpenSubmenuDialog(false);
      fetchMenus();
    } catch {
      setError(editingSubmenu ? 'Erro ao atualizar submenu' : 'Erro ao criar submenu');
    }
  };

  const handleDeleteSubmenu = async (menuId: number, submenuId: number) => {
    if (!window.confirm('Tem certeza que deseja excluir este submenu?')) return;
    try {
      await api.delete(`/menus/submenus/${submenuId}/`);
      showSuccess('Submenu excluído');
      fetchMenus();
    } catch {
      setError('Erro ao excluir submenu');
    }
  };

  const handleToggleSubmenu = async (submenuId: number, disable: boolean) => {
    try {
      await api.put(`/menus/submenus/${submenuId}/`, { disable });
      setMenus(prev => prev.map(m => ({
        ...m,
        submenus: m.submenus.map(s => s.id === submenuId ? { ...s, disable } : s),
      })));
    } catch {
      setError('Erro ao atualizar status');
    }
  };

  // ---- Drag & Drop ----
  const handleMenuDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    const oldIndex = menus.findIndex(m => m.id === active.id);
    const newIndex = menus.findIndex(m => m.id === over.id);
    const newMenus = arrayMove(menus, oldIndex, newIndex).map((m, i) => ({ ...m, order: i }));
    setMenus(newMenus);

    try {
      await api.put('/menus/reorder/', newMenus.map(m => ({ id: m.id, order: m.order })));
      showSuccess('Ordem dos menus atualizada');
    } catch {
      setError('Erro ao salvar ordem');
      fetchMenus();
    }
  };

  const handleSubmenuDragEnd = async (menuId: number, event: DragEndEvent) => {
    const { active, over } = event;
    if (!over || active.id === over.id) return;

    setMenus(prev => prev.map(m => {
      if (m.id !== menuId) return m;
      const oldIdx = m.submenus.findIndex(s => s.id === active.id);
      const newIdx = m.submenus.findIndex(s => s.id === over.id);
      const newSubs = arrayMove(m.submenus, oldIdx, newIdx).map((s, i) => ({ ...s, order: i }));

      // Fire and forget the API call
      api.put(`/menus/${menuId}/submenus/reorder/`, newSubs.map(s => ({ id: s.id, order: s.order })))
        .then(() => showSuccess('Ordem dos submenus atualizada'))
        .catch(() => setError('Erro ao salvar ordem dos submenus'));

      return { ...m, submenus: newSubs };
    }));
  };

  const toggleExpand = (menuId: number) => {
    setExpandedMenus(prev => {
      const next = new Set(prev);
      if (next.has(menuId)) next.delete(menuId);
      else next.add(menuId);
      return next;
    });
  };

  const hasPermission = (permissionName: string) => {
    return user?.permissions?.includes(permissionName) || user?.is_superuser;
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
      <Box>
        <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
          <div>
            <Typography variant="h5" component="h1" gutterBottom>
              Gerenciamento de Menus
            </Typography>
            <Typography variant="body2" color="text.secondary">
              Configure menus, submenus e a ordem de exibição na barra lateral. Arraste para reordenar.
            </Typography>
          </div>
        </Box>

        {error && <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>{error}</Alert>}
        {success && <Alert severity="success" sx={{ mb: 2 }} onClose={() => setSuccess(null)}>{success}</Alert>}

        <Paper sx={{ mb: 3 }}>
          <Tabs value={tabIndex} onChange={(_, v) => setTabIndex(v)} variant="fullWidth">
            <Tab label="Menus Principais" />
            <Tab label="Sub-Menus" />
            <Tab label="Estrutura Hierárquica" />
          </Tabs>
        </Paper>

        {/* =============== TAB 0: Menus Principais =============== */}
        {tabIndex === 0 && (
          <Card>
            <CardContent>
              <Box display="flex" justifyContent="space-between" alignItems="center" mb={2}>
                <Typography variant="h6">Menus Principais</Typography>
                <Box sx={{ display: 'flex', gap: 1 }}>
                  <Button
                    variant={reorderMode ? 'contained' : 'outlined'}
                    startIcon={reorderMode ? <SaveIcon /> : <ReorderIcon />}
                    color={reorderMode ? 'success' : 'primary'}
                    onClick={() => setReorderMode(!reorderMode)}
                  >
                    {reorderMode ? 'Modo Reordenação Ativo' : 'Reordenar'}
                  </Button>
                  <Button variant="contained" startIcon={<AddIcon />} onClick={openCreateMenu}>
                    Novo Menu
                  </Button>
                </Box>
              </Box>

              {reorderMode && (
                <Alert severity="info" sx={{ mb: 2 }}>
                  Arraste os itens para reordenar. A ordem é salva automaticamente.
                </Alert>
              )}

              <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleMenuDragEnd}>
                <SortableContext items={menus.map(m => m.id)} strategy={verticalListSortingStrategy}>
                  <List>
                    {menus.map(menu => (
                      <SortableMenuItem
                        key={menu.id}
                        menu={menu}
                        onEdit={openEditMenu}
                        onDelete={handleDeleteMenu}
                        onToggle={handleToggleMenu}
                        reorderMode={reorderMode}
                      />
                    ))}
                  </List>
                </SortableContext>
              </DndContext>
            </CardContent>
          </Card>
        )}

        {/* =============== TAB 1: Sub-Menus =============== */}
        {tabIndex === 1 && (
          <Box>
            {menus.filter(m => m.submenus.length > 0 || m.is_section).map(menu => (
              <Card key={menu.id} sx={{ mb: 2 }}>
                <CardContent>
                  <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      {getIconComponent(menu.icon)}
                      <Typography variant="h6">{menu.name}</Typography>
                      <Chip label={`${menu.submenus.length} submenus`} size="small" />
                    </Box>
                    <Box sx={{ display: 'flex', gap: 1 }}>
                      <Button
                        size="small"
                        variant={reorderSubMenuId === menu.id ? 'contained' : 'outlined'}
                        startIcon={reorderSubMenuId === menu.id ? <SaveIcon /> : <ReorderIcon />}
                        color={reorderSubMenuId === menu.id ? 'success' : 'primary'}
                        onClick={() => setReorderSubMenuId(reorderSubMenuId === menu.id ? null : menu.id)}
                        disabled={menu.submenus.length < 2}
                      >
                        {reorderSubMenuId === menu.id ? 'Ativo' : 'Reordenar'}
                      </Button>
                      <Button size="small" variant="outlined" startIcon={<AddIcon />} onClick={() => openCreateSubmenu(menu.id)}>
                        Adicionar
                      </Button>
                    </Box>
                  </Box>

                  {menu.submenus.length > 0 ? (
                    <DndContext
                      sensors={sensors}
                      collisionDetection={closestCenter}
                      onDragEnd={(e) => handleSubmenuDragEnd(menu.id, e)}
                    >
                      <SortableContext items={menu.submenus.map(s => s.id)} strategy={verticalListSortingStrategy}>
                        <List dense>
                          {menu.submenus.map(sub => (
                            <SortableSubMenuItem
                              key={sub.id}
                              submenu={{ ...sub, menu_id: menu.id }}
                              onEdit={openEditSubmenu}
                              onDelete={handleDeleteSubmenu}
                              onToggle={handleToggleSubmenu}
                              reorderMode={reorderSubMenuId === menu.id}
                            />
                          ))}
                        </List>
                      </SortableContext>
                    </DndContext>
                  ) : (
                    <Typography variant="body2" color="text.secondary" sx={{ py: 2, textAlign: 'center' }}>
                      Nenhum submenu cadastrado
                    </Typography>
                  )}
                </CardContent>
              </Card>
            ))}

            {menus.filter(m => m.submenus.length > 0 || m.is_section).length === 0 && (
              <Alert severity="info">Nenhum menu com submenus encontrado.</Alert>
            )}
          </Box>
        )}

        {/* =============== TAB 2: Estrutura Hierárquica =============== */}
        {tabIndex === 2 && (
          <Box>
            {menus.map(menu => (
              <Card
                key={menu.id}
                sx={{
                  mb: 1,
                  opacity: menu.disable ? 0.5 : 1,
                  borderLeft: menu.is_section ? '4px solid' : 'none',
                  borderLeftColor: 'primary.main',
                }}
              >
                <CardContent sx={{ py: 1.5, '&:last-child': { pb: 1.5 } }}>
                  <Box
                    display="flex"
                    justifyContent="space-between"
                    alignItems="center"
                    sx={{ cursor: menu.submenus.length > 0 ? 'pointer' : 'default' }}
                    onClick={() => menu.submenus.length > 0 && toggleExpand(menu.id)}
                  >
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      {getIconComponent(menu.icon)}
                      <Typography variant="subtitle1" fontWeight={menu.is_section ? 700 : 400}>
                        {menu.name}
                      </Typography>
                      {menu.is_section && <Chip label="Seção" size="small" color="primary" />}
                      {menu.disable && <Chip label="Desativado" size="small" color="error" />}
                      {menu.permission_name && (
                        <Chip label={menu.permission_name} size="small" variant="outlined" color="secondary" />
                      )}
                      <Typography variant="caption" color="text.secondary">
                        {menu.url || ''}
                      </Typography>
                    </Box>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      {menu.submenus.length > 0 && (
                        <Chip label={`${menu.submenus.length} sub`} size="small" />
                      )}
                      <Tooltip title="Editar">
                        <IconButton size="small" onClick={(e) => { e.stopPropagation(); openEditMenu(menu); }}>
                          <EditIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="Adicionar submenu">
                        <IconButton size="small" onClick={(e) => { e.stopPropagation(); openCreateSubmenu(menu.id); }}>
                          <AddIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                      {menu.submenus.length > 0 && (
                        expandedMenus.has(menu.id) ? <ExpandLess /> : <ExpandMore />
                      )}
                    </Box>
                  </Box>

                  <Collapse in={expandedMenus.has(menu.id)}>
                    <List dense sx={{ pl: 4, mt: 1 }}>
                      {menu.submenus.map(sub => (
                        <ListItem
                          key={sub.id}
                          sx={{
                            bgcolor: sub.disable ? 'action.disabledBackground' : 'grey.50',
                            mb: 0.5,
                            borderRadius: 1,
                            border: '1px solid',
                            borderColor: 'divider',
                          }}
                          secondaryAction={
                            <Box sx={{ display: 'flex', gap: 0.5 }}>
                              {sub.permission_name && (
                                <Chip label={sub.permission_name} size="small" variant="outlined" />
                              )}
                              {sub.disable && <Chip label="Desativado" size="small" color="error" />}
                              <Tooltip title="Editar">
                                <IconButton size="small" onClick={() => openEditSubmenu({ ...sub, menu_id: menu.id })}>
                                  <EditIcon fontSize="small" />
                                </IconButton>
                              </Tooltip>
                            </Box>
                          }
                        >
                          <ListItemIcon sx={{ minWidth: 32 }}>
                            {getIconComponent(sub.icon)}
                          </ListItemIcon>
                          <ListItemText
                            primary={sub.name}
                            secondary={sub.url || 'Sem URL'}
                          />
                        </ListItem>
                      ))}
                    </List>
                  </Collapse>
                </CardContent>
              </Card>
            ))}
          </Box>
        )}

        {/* =============== Dialog: Menu Principal =============== */}
        <Dialog open={openMenuDialog} onClose={() => setOpenMenuDialog(false)} maxWidth="sm" fullWidth>
          <DialogTitle>{editingMenu ? 'Editar Menu' : 'Novo Menu'}</DialogTitle>
          <DialogContent>
            <TextField
              autoFocus
              margin="dense"
              label="Nome do Menu"
              fullWidth
              variant="outlined"
              value={menuForm.name}
              onChange={(e) => setMenuForm(prev => ({ ...prev, name: e.target.value }))}
              sx={{ mb: 2 }}
            />
            <TextField
              margin="dense"
              label="URL (ex: /consulta-integrada)"
              fullWidth
              variant="outlined"
              value={menuForm.url}
              onChange={(e) => setMenuForm(prev => ({ ...prev, url: e.target.value }))}
              sx={{ mb: 2 }}
            />
            <FormControl fullWidth sx={{ mb: 2 }}>
              <InputLabel>Ícone</InputLabel>
              <Select
                value={menuForm.icon}
                label="Ícone"
                onChange={(e) => setMenuForm(prev => ({ ...prev, icon: e.target.value }))}
              >
                <MenuItem value=""><em>Nenhum</em></MenuItem>
                {ICON_OPTIONS.map(icon => (
                  <MenuItem key={icon} value={icon}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      {getIconComponent(icon)}
                      <span>{icon}</span>
                    </Box>
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
            <TextField
              margin="dense"
              label="Permissão necessária (ex: view_consulta_integrada)"
              fullWidth
              variant="outlined"
              value={menuForm.permission_name}
              onChange={(e) => setMenuForm(prev => ({ ...prev, permission_name: e.target.value }))}
              helperText="Deixe vazio para visível a todos os usuários autenticados"
              sx={{ mb: 2 }}
            />
            <TextField
              margin="dense"
              label="Descrição"
              fullWidth
              variant="outlined"
              multiline
              rows={2}
              value={menuForm.description}
              onChange={(e) => setMenuForm(prev => ({ ...prev, description: e.target.value }))}
              sx={{ mb: 2 }}
            />
            <FormControlLabel
              control={
                <Switch
                  checked={menuForm.is_section}
                  onChange={(e) => setMenuForm(prev => ({ ...prev, is_section: e.target.checked }))}
                />
              }
              label="É separador de seção (header)"
            />
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setOpenMenuDialog(false)}>Cancelar</Button>
            <Button onClick={handleSaveMenu} variant="contained" disabled={!menuForm.name}>
              {editingMenu ? 'Atualizar' : 'Criar'}
            </Button>
          </DialogActions>
        </Dialog>

        {/* =============== Dialog: Submenu =============== */}
        <Dialog open={openSubmenuDialog} onClose={() => setOpenSubmenuDialog(false)} maxWidth="sm" fullWidth>
          <DialogTitle>{editingSubmenu ? 'Editar Submenu' : 'Novo Submenu'}</DialogTitle>
          <DialogContent>
            {!editingSubmenu && (
              <FormControl fullWidth sx={{ mb: 2, mt: 1 }}>
                <InputLabel>Menu Pai</InputLabel>
                <Select
                  value={selectedMenuId || ''}
                  label="Menu Pai"
                  onChange={(e) => setSelectedMenuId(Number(e.target.value))}
                >
                  {menus.map(m => (
                    <MenuItem key={m.id} value={m.id}>{m.name}</MenuItem>
                  ))}
                </Select>
              </FormControl>
            )}
            <TextField
              autoFocus
              margin="dense"
              label="Nome do Submenu"
              fullWidth
              variant="outlined"
              value={submenuForm.name}
              onChange={(e) => setSubmenuForm(prev => ({ ...prev, name: e.target.value }))}
              sx={{ mb: 2 }}
            />
            <TextField
              margin="dense"
              label="URL (ex: /users)"
              fullWidth
              variant="outlined"
              value={submenuForm.url}
              onChange={(e) => setSubmenuForm(prev => ({ ...prev, url: e.target.value }))}
              sx={{ mb: 2 }}
            />
            <FormControl fullWidth sx={{ mb: 2 }}>
              <InputLabel>Ícone</InputLabel>
              <Select
                value={submenuForm.icon}
                label="Ícone"
                onChange={(e) => setSubmenuForm(prev => ({ ...prev, icon: e.target.value }))}
              >
                <MenuItem value=""><em>Nenhum</em></MenuItem>
                {ICON_OPTIONS.map(icon => (
                  <MenuItem key={icon} value={icon}>
                    <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                      {getIconComponent(icon)}
                      <span>{icon}</span>
                    </Box>
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
            <TextField
              margin="dense"
              label="Permissão necessária (ex: view_users)"
              fullWidth
              variant="outlined"
              value={submenuForm.permission_name}
              onChange={(e) => setSubmenuForm(prev => ({ ...prev, permission_name: e.target.value }))}
              helperText="Deixe vazio para visível a todos"
              sx={{ mb: 2 }}
            />
            <TextField
              margin="dense"
              label="Descrição"
              fullWidth
              variant="outlined"
              multiline
              rows={2}
              value={submenuForm.description}
              onChange={(e) => setSubmenuForm(prev => ({ ...prev, description: e.target.value }))}
            />
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setOpenSubmenuDialog(false)}>Cancelar</Button>
            <Button onClick={handleSaveSubmenu} variant="contained" disabled={!submenuForm.name || !selectedMenuId}>
              {editingSubmenu ? 'Atualizar' : 'Criar'}
            </Button>
          </DialogActions>
        </Dialog>
      </Box>
    </PageContainer>
  );
}
