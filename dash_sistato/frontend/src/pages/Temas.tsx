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
} from '@mui/material';
import { Add as AddIcon, Edit as EditIcon, Delete as DeleteIcon } from '@mui/icons-material';
import { Theme, getThemes, createTheme } from '../services/auditService';
import { useSnackbar } from 'notistack';

const Temas: React.FC = () => {
  const [themes, setThemes] = useState<Theme[]>([]);
  const [open, setOpen] = useState(false);
  const [editingTheme, setEditingTheme] = useState<Theme | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    category: '',
    status: 'active',
  });
  const { enqueueSnackbar } = useSnackbar();

  const loadThemes = async () => {
    try {
      const data = await getThemes();
      setThemes(Array.isArray(data) ? data : []);
    } catch (error) {
      enqueueSnackbar('Erro ao carregar temas', { variant: 'error' });
      setThemes([]);
    }
  };

  useEffect(() => {
    loadThemes();
  }, []);

  const handleOpen = (theme?: Theme) => {
    if (theme) {
      setEditingTheme(theme);
      setFormData({
        name: theme.name,
        description: theme.description,
        category: theme.category,
        status: theme.status,
      });
    } else {
      setEditingTheme(null);
      setFormData({
        name: '',
        description: '',
        category: '',
        status: 'active',
      });
    }
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
    setEditingTheme(null);
    setFormData({
      name: '',
      description: '',
      category: '',
      status: 'active',
    });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (editingTheme) {
        // TODO: Implement update theme
        enqueueSnackbar('Funcionalidade de edição em desenvolvimento', { variant: 'info' });
      } else {
        await createTheme(formData);
        enqueueSnackbar('Tema criado com sucesso', { variant: 'success' });
        loadThemes();
      }
      handleClose();
    } catch (error) {
      enqueueSnackbar('Erro ao salvar tema', { variant: 'error' });
    }
  };

  const handleDelete = async (theme: Theme) => {
    // TODO: Implement delete theme
    enqueueSnackbar('Funcionalidade de exclusão em desenvolvimento', { variant: 'info' });
  };

  return (
    <Box sx={{ p: 3 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 3 }}>
        <Typography variant="h4">Temas de Auditoria</Typography>
        <Button
          variant="contained"
          startIcon={<AddIcon />}
          onClick={() => handleOpen()}
        >
          Novo Tema
        </Button>
      </Box>

      <Grid container spacing={3}>
        {Array.isArray(themes) && themes.map((theme) => (
          <Grid item xs={12} sm={6} md={4} key={theme.id}>
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                  <Typography variant="h6" gutterBottom>
                    {theme.name}
                  </Typography>
                  <Box>
                    <IconButton size="small" onClick={() => handleOpen(theme)}>
                      <EditIcon />
                    </IconButton>
                    <IconButton size="small" onClick={() => handleDelete(theme)}>
                      <DeleteIcon />
                    </IconButton>
                  </Box>
                </Box>
                <Typography color="textSecondary" gutterBottom>
                  Categoria: {theme.category}
                </Typography>
                <Typography variant="body2">{theme.description}</Typography>
                <Typography
                  variant="caption"
                  color={theme.status === 'active' ? 'success.main' : 'error.main'}
                  sx={{ display: 'block', mt: 1 }}
                >
                  Status: {theme.status}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      <Dialog open={open} onClose={handleClose} maxWidth="sm" fullWidth>
        <form onSubmit={handleSubmit}>
          <DialogTitle>
            {editingTheme ? 'Editar Tema' : 'Novo Tema'}
          </DialogTitle>
          <DialogContent>
            <TextField
              autoFocus
              margin="dense"
              label="Nome"
              fullWidth
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
            />
            <TextField
              margin="dense"
              label="Categoria"
              fullWidth
              value={formData.category}
              onChange={(e) => setFormData({ ...formData, category: e.target.value })}
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
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Cancelar</Button>
            <Button type="submit" variant="contained">
              {editingTheme ? 'Salvar' : 'Criar'}
            </Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
};

export default Temas; 