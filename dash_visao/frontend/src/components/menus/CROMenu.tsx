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
  ExpandLess,
  ExpandMore,
} from '@mui/icons-material';
import { useNavigate } from 'react-router-dom';

interface CROMenuProps {
  isSupervisor: boolean;
}

export default function CROMenu({ isSupervisor }: CROMenuProps) {
  const navigate = useNavigate();
  const [openAuditorias, setOpenAuditorias] = React.useState(false);
  const [openDocumentos, setOpenDocumentos] = React.useState(false);

  const handleAuditoriasClick = () => {
    setOpenAuditorias(!openAuditorias);
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

      {/* Minhas Auditorias */}
      <ListItem button onClick={handleAuditoriasClick}>
        <ListItemIcon>
          <AssignmentIcon />
        </ListItemIcon>
        <ListItemText primary="Minhas Auditorias" />
        {openAuditorias ? <ExpandLess /> : <ExpandMore />}
      </ListItem>
      <Collapse in={openAuditorias} timeout="auto" unmountOnExit>
        <List component="div" disablePadding>
          <ListItem button onClick={() => navigate('/auditorias/pendentes')} sx={{ pl: 4 }}>
            <ListItemText primary="Pendentes" />
          </ListItem>
          <ListItem button onClick={() => navigate('/auditorias/em-analise')} sx={{ pl: 4 }}>
            <ListItemText primary="Em Análise" />
          </ListItem>
          <ListItem button onClick={() => navigate('/auditorias/concluidas')} sx={{ pl: 4 }}>
            <ListItemText primary="Concluídas" />
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
          <ListItem button onClick={() => navigate('/documentos/historico')} sx={{ pl: 4 }}>
            <ListItemText primary="Histórico" />
          </ListItem>
        </List>
      </Collapse>

      {/* Comunicação com o CFO */}
      <ListItem button onClick={() => navigate('/comunicacao')}>
        <ListItemIcon>
          <MessageIcon />
        </ListItemIcon>
        <ListItemText primary="Comunicação com o CFO" />
      </ListItem>

      {/* Histórico de Interações */}
      <ListItem button onClick={() => navigate('/historico')}>
        <ListItemIcon>
          <HistoryIcon />
        </ListItemIcon>
        <ListItemText primary="Histórico de Interações" />
      </ListItem>

      {/* Relatórios Internos */}
      <ListItem button onClick={() => navigate('/relatorios')}>
        <ListItemIcon>
          <AssessmentIcon />
        </ListItemIcon>
        <ListItemText primary="Relatórios Internos" />
      </ListItem>

      {/* Visão Geral (apenas para Supervisor) */}
      {isSupervisor && (
        <ListItem button onClick={() => navigate('/visao-geral')}>
          <ListItemIcon>
            <DashboardIcon />
          </ListItemIcon>
          <ListItemText primary="Visão Geral do CRO" />
        </ListItem>
      )}
    </List>
  );
} 