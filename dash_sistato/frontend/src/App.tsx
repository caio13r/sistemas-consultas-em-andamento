import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import { ThemeProvider, createTheme } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import { SnackbarProvider } from 'notistack';
import { AuthProvider } from './contexts/AuthContext';
import Layout from './components/Layout';
import Login from './pages/Login';
import Home from './pages/Home';
import UserList from './pages/UserList';
import Permissions from './pages/Permissions';
import MenuManagement from './pages/MenuManagement';
import Temas from './pages/Temas';
import AddUser from './pages/AddUser';
import EditProfile from './pages/EditProfile';
import PrivateRoute from './components/PrivateRoute';
import PermissionRoute from './components/PermissionRoute';
import { ErrorBoundary } from './components/ErrorBoundary';
import Roles from './pages/Roles';
import ConsultaIntegrada from './pages/ConsultaIntegrada';
import Servicos from './pages/Servicos';
import ConsultaIdentidade from './pages/ConsultaIdentidade';
import TabelasCentralizadas from './pages/TabelasCentralizadas';
import ConsultaRFB from './pages/ConsultaRFB';
import ConsultaFiscalizacao from './pages/ConsultaFiscalizacao';
import ConsultaAuditorias from './pages/ConsultaAuditorias';
import ConsultaEstatistica from './pages/ConsultaEstatistica';
import ConsultaPrescricao from './pages/ConsultaPrescricao';
import ConsultaSIGESP from './pages/ConsultaSIGESP';
import DadosAbertos from './pages/DadosAbertos';
import RelatoriosDiversos from './pages/RelatoriosDiversos';
import EleicoesRegionais from './pages/EleicoesRegionais';
import SolicitarUsuario from './pages/SolicitarUsuario';
import StatusSolicitacao from './pages/StatusSolicitacao';
import AdminSolicitacoes from './pages/AdminSolicitacoes';
import ComingSoon from './pages/ComingSoon';
import ForgotPassword from './pages/ForgotPassword';
import ResetPassword from './pages/ResetPassword';

const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#1976d2',
    },
    secondary: {
      main: '#dc004e',
    },
  },
});

function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
        <SnackbarProvider maxSnack={3}>
          <Router>
            <AuthProvider>
              <ErrorBoundary>
            <Routes>
              <Route path="/login" element={<Login />} />
              <Route path="/forgot-password" element={<ForgotPassword />} />
              <Route path="/reset-password" element={<ResetPassword />} />
              <Route path="/solicitar-usuario" element={<SolicitarUsuario />} />
              <Route path="/status-solicitacao" element={<StatusSolicitacao />} />
              <Route
                path="/"
                element={
                  <PrivateRoute>
                    <Layout>
                      <Home />
                    </Layout>
                  </PrivateRoute>
                }
              />
              <Route
                path="/users"
                element={
                  <PermissionRoute requiredPermission="view_users">
                    <Layout>
                      <UserList />
                    </Layout>
                  </PermissionRoute>
                }
              />
              <Route
                path="/add-user"
                element={
                  <PermissionRoute requiredPermission="create_users">
                    <Layout>
                      <AddUser />
                    </Layout>
                  </PermissionRoute>
                }
              />
              <Route
                path="/edit-profile"
                element={
                  <PrivateRoute>
                    <Layout>
                      <EditProfile />
                    </Layout>
                  </PrivateRoute>
                }
              />
              <Route
                path="/edit-profile/:id"
                element={
                  <PrivateRoute>
                    <Layout>
                      <EditProfile />
                    </Layout>
                  </PrivateRoute>
                }
              />
              <Route
                path="/permissions"
                element={
                  <PermissionRoute requiredPermission="manage_permissions">
                    <Layout>
                      <Permissions />
                    </Layout>
                  </PermissionRoute>
                }
              />
              <Route
                path="/menus"
                element={
                  <PermissionRoute requiredPermission="manage_menus">
                    <Layout>
                      <MenuManagement />
                    </Layout>
                  </PermissionRoute>
                }
              />
              <Route
                path="/temas"
                element={
                  <PrivateRoute>
                    <Layout>
                      <Temas />
                    </Layout>
                  </PrivateRoute>
                }
              />
              <Route
                path="/roles"
                element={
                  <PermissionRoute requiredPermission="manage_roles">
                    <Layout>
                      <Roles />
                    </Layout>
                  </PermissionRoute>
                }
              />
              {/* Catálogo de serviços */}
              <Route path="/servicos" element={<PrivateRoute><Layout><Servicos /></Layout></PrivateRoute>} />

              {/* Serviços com permissão */}
              <Route path="/consulta-integrada" element={<PermissionRoute requiredPermission="view_consulta_integrada"><Layout><ConsultaIntegrada /></Layout></PermissionRoute>} />
              <Route path="/consulta-identidade" element={<PermissionRoute requiredPermission="view_consulta_identidade"><Layout><ConsultaIdentidade /></Layout></PermissionRoute>} />
              <Route path="/tabelas-centralizadas" element={<PermissionRoute requiredPermission="view_tabelas_centralizadas"><Layout><TabelasCentralizadas /></Layout></PermissionRoute>} />
              <Route path="/consulta-rfb" element={<PermissionRoute requiredPermission="view_consulta_rfb"><Layout><ConsultaRFB /></Layout></PermissionRoute>} />
              <Route path="/consulta-fiscalizacao" element={<PermissionRoute requiredPermission="view_consulta_fiscalizacao"><Layout><ConsultaFiscalizacao /></Layout></PermissionRoute>} />
              <Route path="/consulta-auditorias" element={<PermissionRoute requiredPermission="view_consulta_auditoria"><Layout><ConsultaAuditorias /></Layout></PermissionRoute>} />
              <Route path="/consulta-estatistica" element={<PermissionRoute requiredPermission="view_consulta_estatistica"><Layout><ConsultaEstatistica /></Layout></PermissionRoute>} />
              <Route path="/consulta-prescricao" element={<PermissionRoute requiredPermission="view_consulta_prescricao"><Layout><ConsultaPrescricao /></Layout></PermissionRoute>} />
              <Route path="/consulta-sigesp" element={<PermissionRoute requiredPermission="view_consulta_sigesp"><Layout><ConsultaSIGESP /></Layout></PermissionRoute>} />
              <Route path="/dados-abertos" element={<PermissionRoute requiredPermission="view_dados_abertos"><Layout><DadosAbertos /></Layout></PermissionRoute>} />
              <Route path="/relatorios-diversos" element={<PermissionRoute requiredPermission="view_relatorios_diversos"><Layout><RelatoriosDiversos /></Layout></PermissionRoute>} />
              <Route path="/eleicoes-regionais" element={<PermissionRoute requiredPermission="view_eleicoes_regionais"><Layout><EleicoesRegionais /></Layout></PermissionRoute>} />

              {/* Admin - Solicitações */}
              <Route path="/admin/solicitacoes" element={<PermissionRoute requiredPermission="manage_user_requests"><Layout><AdminSolicitacoes /></Layout></PermissionRoute>} />

              {/* Páginas em construção - rotas do menu sem componente */}
              <Route path="/documentos" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/nova-auditoria" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/lista-auditorias" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/aprovacao" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/consulta-auditoria" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/relatorio-adimplencia" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/relatorio-auditoria" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/relatorio-financeiro" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />

              {/* Catch-all: qualquer rota não mapeada */}
              <Route path="*" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
            </Routes>
              </ErrorBoundary>
            </AuthProvider>
          </Router>
      </SnackbarProvider>
    </ThemeProvider>
  );
}

export default App;
