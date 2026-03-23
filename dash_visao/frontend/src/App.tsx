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
import AdminRoute from './components/AdminRoute';
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
import Documentos from './pages/Documentos';
import DocumentoUpload from './pages/DocumentoUpload';
import DocumentoValidacao from './pages/DocumentoValidacao';
import ForgotPassword from './pages/ForgotPassword';
import ResetPassword from './pages/ResetPassword';
import LogAlteracoes from './pages/LogAlteracoes';
import LogAtividades from './pages/LogAtividades';
import Cracha from './pages/Cracha';
import FormularioLAI from './pages/FormularioLAI';
import RelatorioAdimplencia from './pages/RelatorioAdimplencia';
import RelatorioAuditoria from './pages/RelatorioAuditoria';
import RelatorioFinanceiro from './pages/RelatorioFinanceiro';

const theme = createTheme({
  palette: {
    mode: 'light',
    primary: {
      main: '#8D0F12',
      light: '#B71C1F',
      dark: '#6B0A0D',
      contrastText: '#fff',
    },
    secondary: {
      main: '#635962',
      light: '#8A7F89',
      dark: '#453E44',
      contrastText: '#fff',
    },
    background: {
      default: '#F4F5F7',
      paper: '#FFFFFF',
    },
    text: {
      primary: '#1C2024',
      secondary: '#5A6169',
    },
    divider: 'rgba(0,0,0,0.08)',
    success: { main: '#2E7D32' },
    warning: { main: '#ED6C02' },
    error: { main: '#C62828' },
  },
  typography: {
    fontFamily: '"Inter", "Segoe UI", "Roboto", "Helvetica Neue", Arial, sans-serif',
    h5: { fontWeight: 700, letterSpacing: '-0.01em' },
    h6: { fontWeight: 600, letterSpacing: '-0.005em' },
    subtitle1: { fontWeight: 600 },
    body2: { lineHeight: 1.6 },
    button: { textTransform: 'none', fontWeight: 600 },
  },
  shape: { borderRadius: 10 },
  components: {
    MuiButton: {
      styleOverrides: {
        root: {
          borderRadius: 8,
          padding: '8px 20px',
          boxShadow: 'none',
          '&:hover': { boxShadow: '0 2px 8px rgba(141,15,18,0.15)' },
        },
        containedPrimary: {
          background: 'linear-gradient(135deg, #8D0F12 0%, #B71C1F 100%)',
          '&:hover': { background: 'linear-gradient(135deg, #6B0A0D 0%, #8D0F12 100%)' },
        },
      },
    },
    MuiCard: {
      styleOverrides: {
        root: {
          borderRadius: 12,
          border: '1px solid rgba(0,0,0,0.06)',
          boxShadow: '0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06)',
          transition: 'all 0.2s ease-in-out',
          '&:hover': {
            boxShadow: '0 8px 25px rgba(141,15,18,0.08), 0 4px 10px rgba(0,0,0,0.05)',
            transform: 'translateY(-2px)',
          },
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: { borderRadius: 12 },
        elevation1: {
          boxShadow: '0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.06)',
        },
      },
    },
    MuiTableHead: {
      styleOverrides: {
        root: {
          '& .MuiTableCell-head': {
            backgroundColor: '#F8F4F4',
            color: '#5A6169',
            fontWeight: 700,
            fontSize: '0.75rem',
            textTransform: 'uppercase',
            letterSpacing: '0.05em',
            borderBottom: '2px solid rgba(141,15,18,0.12)',
          },
        },
      },
    },
    MuiTableRow: {
      styleOverrides: {
        root: {
          '&:hover': { backgroundColor: 'rgba(141,15,18,0.02)' },
          '&:last-child td': { borderBottom: 0 },
        },
      },
    },
    MuiTableCell: {
      styleOverrides: {
        root: {
          borderBottom: '1px solid rgba(0,0,0,0.05)',
          padding: '10px 16px',
          fontSize: '0.8125rem',
        },
      },
    },
    MuiChip: {
      styleOverrides: {
        root: { borderRadius: 8, fontWeight: 600 },
      },
    },
    MuiDivider: {
      styleOverrides: {
        root: { borderColor: 'rgba(0,0,0,0.06)' },
      },
    },
    MuiAlert: {
      styleOverrides: {
        root: { borderRadius: 10 },
      },
    },
    MuiTextField: {
      styleOverrides: {
        root: {
          '& .MuiOutlinedInput-root': {
            borderRadius: 8,
          },
        },
      },
    },
    MuiSelect: {
      styleOverrides: {
        root: { borderRadius: 8 },
      },
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
                  <AdminRoute>
                    <Layout>
                      <UserList />
                    </Layout>
                  </AdminRoute>
                }
              />
              <Route
                path="/add-user"
                element={
                  <AdminRoute>
                    <Layout>
                      <AddUser />
                    </Layout>
                  </AdminRoute>
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
                  <AdminRoute>
                    <Layout>
                      <EditProfile />
                    </Layout>
                  </AdminRoute>
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

              {/* Gestão de Documentos */}
              <Route path="/documentos" element={<PermissionRoute requiredPermission="view_documentos"><Layout><Documentos /></Layout></PermissionRoute>} />
              <Route path="/documentos/upload" element={<PermissionRoute requiredPermission="view_documentos"><Layout><DocumentoUpload /></Layout></PermissionRoute>} />
              <Route path="/documentos/validacao" element={<PermissionRoute requiredPermission="manage_documentos"><Layout><DocumentoValidacao /></Layout></PermissionRoute>} />
              <Route path="/nova-auditoria" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/lista-auditorias" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/aprovacao" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/consulta-auditoria" element={<PrivateRoute><Layout><ComingSoon /></Layout></PrivateRoute>} />
              <Route path="/relatorio-adimplencia" element={<PermissionRoute requiredPermission="view_relatorio_adimplencia"><Layout><RelatorioAdimplencia /></Layout></PermissionRoute>} />
              <Route path="/relatorio-auditoria" element={<PermissionRoute requiredPermission="view_relatorio_auditoria"><Layout><RelatorioAuditoria /></Layout></PermissionRoute>} />
              <Route path="/relatorio-financeiro" element={<PermissionRoute requiredPermission="view_relatorio_financeiro"><Layout><RelatorioFinanceiro /></Layout></PermissionRoute>} />

              {/* Logs de auditoria - somente admin */}
              <Route path="/log-alteracoes" element={<AdminRoute><Layout><LogAlteracoes /></Layout></AdminRoute>} />
              <Route path="/log-atividades" element={<AdminRoute><Layout><LogAtividades /></Layout></AdminRoute>} />

              {/* Crachá e LAI */}
              <Route path="/cracha" element={<PrivateRoute><Layout><Cracha /></Layout></PrivateRoute>} />
              <Route path="/formulario-lai" element={<PrivateRoute><Layout><FormularioLAI /></Layout></PrivateRoute>} />

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
