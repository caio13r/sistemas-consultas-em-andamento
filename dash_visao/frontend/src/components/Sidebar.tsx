import React, { useState, useEffect } from 'react';
import './Sidebar.css';
import logoCFO from '../assets/logocfo.png';
import { Link, useLocation } from 'react-router-dom';
import {
  List,
  ListItemIcon,
  ListItemText,
  Collapse,
  ListItemButton,
  Divider,
  Drawer,
  useTheme,
  useMediaQuery,
  Box,
  CircularProgress,
  Typography,
} from '@mui/material';
import {
  Home as HomeIcon,
  People as PeopleIcon,
  PersonAdd as PersonAddIcon,
  Security as SecurityIcon,
  ExpandLess,
  ExpandMore,
  AdminPanelSettings as AdminIcon,
  Assessment as AssessmentIcon,
  Assignment as AssignmentIcon,
  Folder as FolderIcon,
  List as ListIcon,
  Search as SearchIcon,
  Settings as SettingsIcon,
  HowToReg as HowToRegIcon,
  Gavel as GavelIcon,
  Badge as BadgeIcon,
  BarChart as BarChartIcon,
  MedicalServices as MedicalServicesIcon,
  AccountBalance as AccountBalanceIcon,
  Balance as BalanceIcon,
  Public as PublicIcon,
  TableChart as TableChartIcon,
  HowToVote as HowToVoteIcon,
  Policy as PolicyIcon,
  ViewList as ViewListIcon,
  SupervisorAccount as SupervisorAccountIcon,
  Add as AddIcon,
  Storage as StorageIcon,
} from '@mui/icons-material';
import { useAuth } from '../contexts/AuthContext';
import api from '../services/api';

const SIDEBAR_WIDTH = 280;

interface SidebarProps {
  open: boolean;
  onClose: () => void;
  collapsed: boolean;
  onToggleCollapse: () => void;
}

interface SubMenuData {
  id: number;
  name: string;
  url?: string;
  icon?: string;
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

const Sidebar: React.FC<SidebarProps> = ({ open, onClose, collapsed, onToggleCollapse }) => {
  const [menus, setMenus] = useState<MenuData[]>([]);
  const [loading, setLoading] = useState(true);
  const [openSections, setOpenSections] = useState<Set<number>>(new Set());
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const location = useLocation();
  const { hasPermission, isAdmin } = useAuth();

  useEffect(() => {
    fetchMenus();
  }, []);

  const fetchMenus = async () => {
    try {
      const response = await api.get('/menus/');
      const data: MenuData[] = (response.data || [])
        .filter((m: MenuData) => !m.disable)
        .sort((a: MenuData, b: MenuData) => a.order - b.order)
        .map((m: MenuData) => ({
          ...m,
          submenus: (m.submenus || [])
            .filter((s: SubMenuData) => !s.disable)
            .sort((a: SubMenuData, b: SubMenuData) => a.order - b.order),
        }));
      setMenus(data);
    } catch (error) {
      console.error('Erro ao carregar menus:', error);
    } finally {
      setLoading(false);
    }
  };

  const getIconComponent = (iconName?: string | null) => {
    const iconMap: { [key: string]: React.ReactElement } = {
      'Home': <HomeIcon />,
      'Search': <SearchIcon />,
      'Assignment': <AssignmentIcon />,
      'Gavel': <GavelIcon />,
      'Badge': <BadgeIcon />,
      'BarChart': <BarChartIcon />,
      'MedicalServices': <MedicalServicesIcon />,
      'AccountBalance': <AccountBalanceIcon />,
      'Balance': <BalanceIcon />,
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
      'Storage': <StorageIcon />,
    };
    return iconMap[iconName || ''] || <ListIcon />;
  };

  const toggleSection = (menuId: number) => {
    setOpenSections(prev => {
      const next = new Set(prev);
      if (next.has(menuId)) next.delete(menuId);
      else next.add(menuId);
      return next;
    });
  };

  // Permissões que exigem perfil Administrador
  const adminOnlyPermissions = ['view_users', 'create_users', 'edit_users', 'delete_users', 'manage_users'];

  const canSeeMenu = (menu: MenuData): boolean => {
    // Se não tem permissão definida, todos veem
    if (!menu.permission_name) return true;
    // Gerenciamento de usuários: somente Administrador
    if (adminOnlyPermissions.includes(menu.permission_name)) return isAdmin;
    return hasPermission(menu.permission_name);
  };

  const canSeeSubmenu = (sub: SubMenuData): boolean => {
    if (!sub.permission_name) return true;
    if (adminOnlyPermissions.includes(sub.permission_name)) return isAdmin;
    return hasPermission(sub.permission_name);
  };

  // Para menus com submenus, verifica se o usuário tem acesso a pelo menos um submenu
  const canSeeMenuWithSubs = (menu: MenuData): boolean => {
    if (menu.submenus.length === 0) return canSeeMenu(menu);
    // Precisa ter acesso ao menu pai OU a pelo menos um submenu
    const hasParentAccess = !menu.permission_name || hasPermission(menu.permission_name);
    const hasSubAccess = menu.submenus.some(sub => canSeeSubmenu(sub));
    return hasParentAccess && hasSubAccess;
  };

  const menuItemSx = (isActive: boolean) => ({
    color: 'white',
    justifyContent: collapsed ? 'center' : 'flex-start',
    px: collapsed ? 2 : 3,
    '&:hover': { backgroundColor: 'rgba(255,255,255,0.1)' },
    backgroundColor: isActive ? 'rgba(255,255,255,0.15)' : 'transparent',
  });

  const subItemSx = (isActive: boolean) => ({
    pl: collapsed ? 2 : 6,
    color: 'white',
    justifyContent: collapsed ? 'center' : 'flex-start',
    '&:hover': { backgroundColor: 'rgba(255,255,255,0.1)' },
    backgroundColor: isActive ? 'rgba(255,255,255,0.15)' : 'transparent',
  });

  // Renderiza submenus collapsible (usado por seções E menus com submenus)
  const renderSubmenus = (menu: MenuData) => {
    const visibleSubmenus = menu.submenus.filter(canSeeSubmenu);
    if (visibleSubmenus.length === 0) return null;

    const isOpen = openSections.has(menu.id);
    const hasActiveSub = visibleSubmenus.some(s => location.pathname === (s.url || ''));

    return (
      <React.Fragment key={`subs-${menu.id}`}>
        <ListItemButton onClick={() => toggleSection(menu.id)} sx={menuItemSx(hasActiveSub && !isOpen)}>
          <ListItemIcon sx={{ color: 'white', minWidth: 0, mr: collapsed ? 0 : 2, justifyContent: 'center' }}>
            {getIconComponent(menu.icon)}
          </ListItemIcon>
          {!collapsed && (
            <ListItemText
              primary={menu.name}
              primaryTypographyProps={{ fontSize: '0.875rem' }}
            />
          )}
          {!collapsed && (isOpen ? <ExpandLess /> : <ExpandMore />)}
        </ListItemButton>
        <Collapse in={isOpen && !collapsed} timeout="auto" unmountOnExit>
          <List component="div" disablePadding>
            {visibleSubmenus.map(sub => {
              const subPath = sub.url || '';
              const isSubActive = location.pathname === subPath;
              return (
                <ListItemButton
                  key={sub.id}
                  component={Link}
                  to={subPath}
                  sx={subItemSx(isSubActive)}
                >
                  <ListItemIcon sx={{ color: 'white', minWidth: 0, mr: collapsed ? 0 : 2, justifyContent: 'center' }}>
                    {getIconComponent(sub.icon)}
                  </ListItemIcon>
                  {!collapsed && (
                    <ListItemText
                      primary={sub.name}
                      primaryTypographyProps={{ fontSize: '0.8rem' }}
                    />
                  )}
                </ListItemButton>
              );
            })}
          </List>
        </Collapse>
      </React.Fragment>
    );
  };

  // Menu simples (link direto, sem submenus)
  const renderMenuItem = (menu: MenuData) => {
    if (!canSeeMenu(menu)) return null;
    const path = menu.url || '';
    const isActive = location.pathname === path;

    return (
      <ListItemButton
        key={menu.id}
        component={Link}
        to={path}
        sx={menuItemSx(isActive)}
      >
        <ListItemIcon sx={{ color: 'white', minWidth: 0, mr: collapsed ? 0 : 2, justifyContent: 'center' }}>
          {getIconComponent(menu.icon)}
        </ListItemIcon>
        {!collapsed && (
          <ListItemText
            primary={menu.name}
            primaryTypographyProps={{ fontSize: '0.875rem' }}
          />
        )}
      </ListItemButton>
    );
  };

  // Seção (separador visual + submenus ou apenas label)
  const renderSection = (menu: MenuData) => {
    const visibleSubmenus = menu.submenus.filter(canSeeSubmenu);

    // Seção sem submenus: apenas mostrar o separador
    if (visibleSubmenus.length === 0) {
      return (
        <React.Fragment key={menu.id}>
          <Divider sx={{ my: 1, backgroundColor: 'rgba(255,255,255,0.1)' }} />
          {!collapsed && (
            <Typography
              variant="caption"
              sx={{
                px: 3, pt: 1, pb: 0.5, opacity: 0.6,
                display: 'block', textTransform: 'uppercase',
                fontSize: '0.65rem', letterSpacing: 1,
              }}
            >
              {menu.name}
            </Typography>
          )}
        </React.Fragment>
      );
    }

    // Seção com submenus: separador + collapsible
    return (
      <React.Fragment key={menu.id}>
        <Divider sx={{ my: 1, backgroundColor: 'rgba(255,255,255,0.1)' }} />
        {!collapsed && (
          <Typography
            variant="caption"
            sx={{
              px: 3, pt: 1, pb: 0.5, opacity: 0.6,
              display: 'block', textTransform: 'uppercase',
              fontSize: '0.65rem', letterSpacing: 1,
            }}
          >
            {menu.name}
          </Typography>
        )}
        {renderSubmenus(menu)}
      </React.Fragment>
    );
  };

  // Renderiza qualquer menu (decide o tipo automaticamente)
  const renderMenu = (menu: MenuData) => {
    // Seção: separador visual
    if (menu.is_section) {
      return renderSection(menu);
    }
    // Menu com submenus: collapsible
    if (menu.submenus.length > 0) {
      if (!canSeeMenuWithSubs(menu)) return null;
      return renderSubmenus(menu);
    }
    // Menu simples: link direto
    return renderMenuItem(menu);
  };

  const sidebarContent = (
    <Box
      sx={{
        width: collapsed ? 72 : SIDEBAR_WIDTH,
        height: '100vh',
        background: 'linear-gradient(180deg, #5C1519 0%, #7A1E26 50%, #9A2832 100%)',
        color: 'white',
        display: 'flex',
        flexDirection: 'column',
        transition: 'width 0.3s',
        position: 'relative',
        overflowY: 'auto',
        overflowX: 'hidden',
        '&::-webkit-scrollbar': { width: 6 },
        '&::-webkit-scrollbar-thumb': { backgroundColor: 'rgba(255,255,255,0.2)', borderRadius: 3 },
      }}
    >
      {/* Logo */}
      <Box
        sx={{
          p: 2,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          borderBottom: '1px solid rgba(255,255,255,0.1)',
        }}
      >
        <img src={logoCFO} alt="CFO Logo" style={{ height: '40px' }} />
      </Box>

      <List component="nav" sx={{ width: '100%', color: 'white', flexGrow: 1 }}>
        {loading ? (
          <Box display="flex" justifyContent="center" alignItems="center" p={3}>
            <CircularProgress color="inherit" />
          </Box>
        ) : (
          <>
            {menus.map(menu => renderMenu(menu))}
          </>
        )}
      </List>

    </Box>
  );

  // Drawer para mobile
  if (isMobile) {
    return (
      <Drawer
        variant="temporary"
        open={open}
        onClose={onClose}
        ModalProps={{ keepMounted: true }}
        sx={{
          '& .MuiDrawer-paper': {
            width: SIDEBAR_WIDTH,
            background: 'linear-gradient(180deg, #5C1519 0%, #7A1E26 50%, #9A2832 100%)',
            color: 'white',
          },
        }}
      >
        {sidebarContent}
      </Drawer>
    );
  }

  // Sidebar fixa para desktop
  return (
    <Box
      sx={{
        width: collapsed ? 72 : SIDEBAR_WIDTH,
        flexShrink: 0,
        height: '100vh',
        background: 'linear-gradient(180deg, #5C1519 0%, #7A1E26 50%, #9A2832 100%)',
        color: 'white',
        position: 'fixed',
        left: 0,
        top: 0,
        zIndex: (theme) => theme.zIndex.drawer,
        transition: 'width 0.3s',
        boxShadow: '2px 0 5px rgba(0,0,0,0.1)',
        display: 'flex',
        flexDirection: 'column',
      }}
    >
      {sidebarContent}
    </Box>
  );
};

export default Sidebar;
