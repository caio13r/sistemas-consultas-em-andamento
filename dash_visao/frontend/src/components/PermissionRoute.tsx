import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import AuthLoadingScreen from './AuthLoadingScreen';

interface PermissionRouteProps {
  children: React.ReactNode;
  requiredPermission?: string;
  requiredPermissions?: string[];
}

const PermissionRoute: React.FC<PermissionRouteProps> = ({
  children,
  requiredPermission,
  requiredPermissions,
}) => {
  const { isAuthenticated, acceptedTerms, hasPermission, hasAnyPermission } = useAuth();

  return (
    <AuthLoadingScreen>
      {!isAuthenticated ? (
        <Navigate to="/login" replace />
      ) : !acceptedTerms ? (
        <Navigate to="/termos-de-uso" replace />
      ) : requiredPermission && !hasPermission(requiredPermission) ? (
        <Navigate to="/" replace />
      ) : requiredPermissions && requiredPermissions.length > 0 && !hasAnyPermission(requiredPermissions) ? (
        <Navigate to="/" replace />
      ) : (
        children
      )}
    </AuthLoadingScreen>
  );
};

export default PermissionRoute;
