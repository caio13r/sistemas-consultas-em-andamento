import React, { useEffect, useState } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  Grid,
  IconButton,
  TextField,
  Typography,
  MenuItem,
  FormControl,
  InputLabel,
  Select,
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon } from '@mui/icons-material';
import { Audit, Theme, getAudits, createAudit, getThemes } from '../services/auditService';
import { useSnackbar } from 'notistack';
import { DateTimePicker } from '@mui/x-date-pickers/DateTimePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { ptBR } from 'date-fns/locale';

const AuditList: React.FC = () => {
  const [audits, setAudits] = useState<Audit[]>([]);
  const [themes, setThemes] = useState<Theme[]>([]);
  const [open, setOpen] = useState(false);
  const [editingAudit, setEditingAudit] = useState<Audit | null>(null);
  const [formData, setFormData] = useState({
    theme_id: '',
    title: '',
    description: '',
    deadline: new Date(),
    status: 'draft',
  });
  const { enqueueSnackbar } = useSnackbar();

  const loadData = async () => {
    try {
      const [auditsData, themesData] = await Promise.all([
        getAudits(),
        getThemes(),
      ]);
      setAudits(auditsData);
      setThemes(themesData);
    } catch (error) {
      enqueueSnackbar('Erro ao carregar dados', { variant: 'error' });
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleOpen = (audit?: Audit) => {
    if (audit) {
      setEditingAudit(audit);
      setFormData({
        theme_id: audit.theme_id.toString(),
        title: audit.title,
        description: audit.description,
        deadline: new Date(audit.deadline),
        status: audit.status,
      });
    } else {
      setEditingAudit(null);
      setFormData({
        theme_id: '',
        title: '',
        description: '',
        deadline: new Date(),
        status: 'draft',
      });
    }
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setEditingAudit(null);
    setFormData({
      theme_id: '',
      title: '',
      description: '',
      deadline: new Date(),
      status: 'draft',
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingAudit) {
        // TODO: Implement update audit
        enqueueSnackbar('Funcionalidade de edição em desenvolvimento', { variant: 'info' });
      } else {
        await createAudit({
          ...formData,
          theme_id: parseInt(formData.theme_id),
          deadline: formData.deadline.toISOString(),
        });
        enqueueSnackbar('Auditoria criada com sucesso', { variant: 'success' });
        loadData();
      }
      handleClose();
    } catch (error) {
      enqueueSnackbar('Erro ao salvar auditoria', { variant: 'error' });
    }
  };

  const handleDelete = async (audit: Audit) => {
    // TODO: Implement delete audit
    enqueueSnackbar('Funcionalidade de exclusão em desenvolvimento', { variant: 'info' });
  };

  return (
    <Box sx={{ p: 3 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
        <Typography variant="h4">Auditorias</Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => handleOpen()}
        >
          Nova Auditoria
        </Button>
      </Box>

      <Grid container spacing={3}>
        {audits.map((audit) => (
          <Grid item xs={12} sm={6} md={4} key={audit.id}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <Typography variant="h6" gutterBottom>
                    {audit.title}
                  </Typography>
                  <Box>
                    <IconButton size="small" onClick={() => handleOpen(audit)}>
                      <EditIcon />
                    </IconButton>
                    <IconButton size="small" onClick={() => handleDelete(audit)}>
                      <DeleteIcon />
                    </IconButton>
                  </Box>
                </Box>
                <Typography color="textSecondary" gutterBottom>
                  Tema: {audit.theme.name}
                </Typography>
                <Typography variant="body2">{audit.description}</Typography>
                <Typography variant="caption" sx={{ display: 'block', mt: 1 }}>
                  Prazo: {new Date(audit.deadline).toLocaleString()}
                </Typography>
                <Typography
                  variant="caption"
                  color={audit.status === 'draft' ? 'warning.main' : 'success.main'}
                  sx={{ display: 'block', mt: 1 }}
                >
                  Status: {audit.status}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
        <form onSubmit={handleSubmit}>
          <DialogTitle>
            {editingAudit ? 'Editar Auditoria' : 'Nova Auditoria'}
          </DialogTitle>
          <DialogContent>
            <FormControl fullWidth margin="dense">
              <InputLabel>Tema</InputLabel>
              <Select
                value={formData.theme_id}
                label="Tema"
                onChange={(e) => setFormData({ ...formData, theme_id: e.target.value })}
                required
              >
                {themes.map((theme) => (
                  <MenuItem key={theme.id} value={theme.id}>
                    {theme.name}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>
            <TextField
              margin="dense"
              label="Título"
              fullWidth
              value={formData.title}
              onChange={(e) => setFormData({ ...formData, title: e.target.value })}
              required
            />
            <TextField
              margin="dense"
              label="Descrição"
              fullWidth
              multiline
              rows={4}
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              required
            />
            <LocalizationProvider dateAdapter={AdapterDateFns} adapterLocale={ptBR}>
              <DateTimePicker
                label="Prazo"
                value={formData.deadline}
                onChange={(newValue: Date | null) => setFormData({ ...formData, deadline: newValue || new Date() })}
                sx={{ width: '100%', mt: 2 }}
              />
            </LocalizationProvider>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Cancelar</Button>
            <Button type="submit" variant="contained">
              {editingAudit ? 'Salvar' : 'Criar'}
            </Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
};

export default AuditList; 