import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import AuthLoadingScreen from './AuthLoadingScreen';

const AdminRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { isAuthenticated, isAdmin, acceptedTerms } = useAuth();

  return (
    <AuthLoadingScreen>
      {!isAuthenticated ? (
        <Navigate to="/login" replace />
      ) : !acceptedTerms ? (
        <Navigate to="/termos-de-uso" replace />
      ) : !isAdmin ? (
        <Navigate to="/" replace />
      ) : (
        children
      )}
    </AuthLoadingScreen>
  );
};

export default AdminRoute;
