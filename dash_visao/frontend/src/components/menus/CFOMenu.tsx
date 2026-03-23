import React from 'react';
import {
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Collapse,
  Box,
} from '@mui/material';
import {
  Dashboard as DashboardIcon,
  Assignment as AssignmentIcon,
  Description as DescriptionIcon,
  History as HistoryIcon,
  Message as MessageIcon,
  Assessment as AssessmentIcon,
  Settings as SettingsIcon,
  ExpandLess,
  ExpandMore,
  Topic as TopicIcon,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';

interface CFOMenuProps {
  isAdmin: boolean;
}

export default function CFOMenu({ isAdmin }: CFOMenuProps) {
  const navigate = useNavigate();
  const [openGestao, setOpenGestao] = React.useState(false);
  const [openSolicitacoes, setOpenSolicitacoes] = React.useState(false);
  const [openDocumentos, setOpenDocumentos] = React.useState(false);

  const handleGestaoClick = () => {
    setOpenGestao(!openGestao);
  };

  const handleSolicitacoesClick = () => {
    setOpenSolicitacoes(!openSolicitacoes);
  };

  const handleDocumentosClick = () => {
    setOpenDocumentos(!openDocumentos);
  };

  return (
    <List component="nav">
      <ListItem button onClick={() => navigate('/dashboard')}>
        <ListItemIcon>
          <DashboardIcon />
        </ListItemIcon>
        <ListItemText primary="Dashboard" />
      </ListItem>

      {/* Gestão de Temas e Auditorias */}
      <ListItem button onClick={() => navigate('/temas')}>
        <ListItemIcon>
          <TopicIcon />
        </ListItemIcon>
        <ListItemText primary="Gestão de Temas" />
      </ListItem>

      {/* Gestão de Solicitações */}
      <ListItem button onClick={handleSolicitacoesClick}>
        <ListItemIcon>
          <DescriptionIcon />
        </ListItemIcon>
        <ListItemText primary="Gestão de Solicitações" />
        {openSolicitacoes ? <ExpandLess /> : <ExpandMore />}
      </ListItem>
      <Collapse in={openSolicitacoes} timeout="auto" unmountOnExit>
        <List component="div" disablePadding>
          <ListItem button onClick={() => navigate('/solicitacoes/nova')} sx={{ pl: 4 }}>
            <ListItemText primary="Nova Solicitação" />
          </ListItem>
          <ListItem button onClick={() => navigate('/solicitacoes')} sx={{ pl: 4 }}>
            <ListItemText primary="Acompanhamento" />
          </ListItem>
        </List>
      </Collapse>

      {/* Gestão de Documentos */}
      <ListItem button onClick={handleDocumentosClick}>
        <ListItemIcon>
          <DescriptionIcon />
        </ListItemIcon>
        <ListItemText primary="Gestão de Documentos" />
        {openDocumentos ? <ExpandLess /> : <ExpandMore />}
      </ListItem>
      <Collapse in={openDocumentos} timeout="auto" unmountOnExit>
        <List component="div" disablePadding>
          <ListItem button onClick={() => navigate('/documentos/upload')} sx={{ pl: 4 }}>
            <ListItemText primary="Upload/Download" />
          </ListItem>
          <ListItem button onClick={() => navigate('/documentos/validacao')} sx={{ pl: 4 }}>
            <ListItemText primary="Validação" />
          </ListItem>
        </List>
      </Collapse>

      {/* Histórico e Logs */}
      <ListItem button onClick={() => navigate('/historico')}>
        <ListItemIcon>
          <HistoryIcon />
        </ListItemIcon>
        <ListItemText primary="Histórico e Logs" />
      </ListItem>

      {/* Comunicação */}
      <ListItem button onClick={() => navigate('/comunicacao')}>
        <ListItemIcon>
          <MessageIcon />
        </ListItemIcon>
        <ListItemText primary="Comunicação" />
      </ListItem>

      {/* Relatórios */}
      <ListItem button onClick={() => navigate('/relatorios')}>
        <ListItemIcon>
          <AssessmentIcon />
        </ListItemIcon>
        <ListItemText primary="Relatórios" />
      </ListItem>

      {/* Administração do Sistema (apenas para Admin) */}
      {isAdmin && (
        <ListItem button onClick={() => navigate('/admin')}>
          <ListItemIcon>
            <SettingsIcon />
          </ListItemIcon>
          <ListItemText primary="Administração" />
        </ListItem>
      )}
    </List>
  );
} 