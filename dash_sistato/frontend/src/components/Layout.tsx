import React, { useState } from 'react';
import Sidebar from './Sidebar';
import { useAuth } from '../contexts/AuthContext';
import { useNavigate } from 'react-router-dom';
import { useTheme } from '@mui/material/styles';
import { useMediaQuery } from '@mui/material';
import Header from './Header';

interface LayoutProps {
  children: React.ReactNode;
}

const SIDEBAR_WIDTH = 280;
const SIDEBAR_COLLAPSED_WIDTH = 72;

const Layout: React.FC<LayoutProps> = ({ children }) => {
  const [sidebarOpen, setSidebarOpen] = useState(false); // for mobile
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false); // for desktop
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  // Mock state for new components
  const [notificationCount] = useState(3);
  const [isDarkMode, setIsDarkMode] = useState(false);

  const handleDrawerToggle = () => {
    setSidebarOpen(!sidebarOpen);
  };
  
  const handleSidebarCollapse = () => {
    if (!isMobile) {
      setSidebarCollapsed((prev) => !prev);
    }
  };

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const handleEditProfile = () => {
    navigate('/edit-profile');
  };

  const handleThemeToggle = () => {
    setIsDarkMode(!isDarkMode);
    // Add theme switching logic here if needed
  };

  // Main content margin calculation
  const mainMarginLeft = isMobile ? '0' : (sidebarCollapsed ? `${SIDEBAR_COLLAPSED_WIDTH}px` : `${SIDEBAR_WIDTH}px`);

  const userName = user ? user.full_name : '';
  const userEmail = user ? user.email : '';

  React.useEffect(() => {
    console.log('AuthContext user object:', user);
  }, [user]);

  return (
    <div className="flex min-h-screen w-full bg-system-gray">
      <Sidebar
        open={sidebarOpen}
        onClose={handleDrawerToggle}
        collapsed={sidebarCollapsed}
        onToggleCollapse={handleSidebarCollapse}
      />

      <div style={{ marginLeft: mainMarginLeft, flexGrow: 1, display: 'flex', flexDirection: 'column', minHeight: '100vh', transition: 'margin-left 0.3s' }}>
        <Header
          onMenuClick={isMobile ? handleDrawerToggle : handleSidebarCollapse}
          userName={userName}
          userEmail={userEmail}
          onLogout={handleLogout}
          onEditProfile={handleEditProfile}
        />
        {/* Page Content */}
        <main className="flex-grow p-3 sm:p-4 md:p-6 w-full box-sizing-border-box">
          {children}
        </main>
      </div>
    </div>
  );
};

export default Layout; 