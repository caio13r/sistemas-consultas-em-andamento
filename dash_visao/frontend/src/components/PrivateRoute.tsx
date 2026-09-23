import React from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import AuthLoadingScreen from './AuthLoadingScreen';

interface PrivateRouteProps {
  children: React.ReactNode;
}

const PrivateRoute: React.FC<PrivateRouteProps> = ({ children }) => {
  const { isAuthenticated, acceptedTerms } = useAuth();

  return (
    <AuthLoadingScreen>
      {!isAuthenticated ? (
        <Navigate to="/login" replace />
      ) : !acceptedTerms ? (
        <Navigate to="/termos-de-uso" replace />
      ) : (
        children
      )}
    </AuthLoadingScreen>
  );
};

export default PrivateRoute; 