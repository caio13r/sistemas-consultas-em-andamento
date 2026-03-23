import React, { createContext, useContext, useState, ReactNode, useEffect, useCallback } from 'react';
import { authService } from '../services/authService';
import { userService, User } from '../services/userService';

interface AuthContextType {
  isAuthenticated: boolean;
  user: User | null;
  permissions: string[];
  roles: string[];
  isAdmin: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => void;
  fetchCurrentUser: () => Promise<void>;
  hasPermission: (permission: string) => boolean;
  hasAnyPermission: (permissions: string[]) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
  const [user, setUser] = useState<User | null>(null);
  const [permissions, setPermissions] = useState<string[]>([]);
  const [roles, setRoles] = useState<string[]>([]);

  const fetchCurrentUser = async () => {
    try {
      const currentUser = await userService.getCurrentUser();
      setUser(currentUser);
      setPermissions(currentUser.permissions || []);
      setRoles(currentUser.roles || []);
      setIsAuthenticated(true);
    } catch (error) {
      console.error("Failed to fetch user, logging out.", error);
      setUser(null);
      setPermissions([]);
      setRoles([]);
      setIsAuthenticated(false);
      authService.logout();
    }
  };

  useEffect(() => {
    const token = authService.getToken();
    if (token) {
      fetchCurrentUser();
    }
  }, []);

  const login = async (username: string, password: string) => {
    try {
      await authService.login(username, password);
      await fetchCurrentUser();
    } catch (error) {
      setIsAuthenticated(false);
      setUser(null);
      setPermissions([]);
      setRoles([]);
      throw error;
    }
  };

  const logout = () => {
    authService.logout();
    setIsAuthenticated(false);
    setUser(null);
    setPermissions([]);
    setRoles([]);
  };

  const hasPermission = useCallback((permission: string): boolean => {
    if (user?.is_superuser) return true;
    return permissions.includes(permission);
  }, [user, permissions]);

  const hasAnyPermission = useCallback((perms: string[]): boolean => {
    if (user?.is_superuser) return true;
    return perms.some(p => permissions.includes(p));
  }, [user, permissions]);

  const isAdmin = !!(user?.is_superuser || roles.includes('Administrador'));

  return (
    <AuthContext.Provider value={{
      isAuthenticated,
      user,
      permissions,
      roles,
      isAdmin,
      login,
      logout,
      fetchCurrentUser,
      hasPermission,
      hasAnyPermission,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

const defaultAuth: AuthContextType = {
  isAuthenticated: false,
  user: null,
  permissions: [],
  roles: [],
  isAdmin: false,
  login: async () => { throw new Error('Sistema carregando. Aguarde e tente novamente.'); },
  logout: () => {},
  fetchCurrentUser: async () => {},
  hasPermission: () => false,
  hasAnyPermission: () => false,
};

export function useAuth() {
  const context = useContext(AuthContext);
  return context ?? defaultAuth;
}
