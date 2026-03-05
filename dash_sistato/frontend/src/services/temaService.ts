import api from './api';

export interface Tema {
  id: number;
  nome: string;
  descricao: string;
  categoria: string;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface TemaCreate {
  nome: string;
  descricao: string;
  categoria: string;
  status: string;
}

export interface TemaUpdate {
  nome?: string;
  descricao?: string;
  categoria?: string;
  status?: string;
}

export const temaService = {
  async getTemas() {
    try {
      const response = await api.get<Tema[]>('/temas');
      return response.data;
    } catch (error) {
      throw new Error('Erro ao buscar temas');
    }
  },

  async getTema(id: number) {
    try {
      const response = await api.get<Tema>(`/temas/${id}`);
      return response.data;
    } catch (error) {
      throw new Error('Erro ao buscar tema');
    }
  },

  async createTema(tema: TemaCreate) {
    try {
      const response = await api.post<Tema>('/temas', tema);
      return response.data;
    } catch (error) {
      throw new Error('Erro ao criar tema');
    }
  },

  async updateTema(id: number, tema: TemaUpdate) {
    try {
      const response = await api.put<Tema>(`/temas/${id}`, tema);
      return response.data;
    } catch (error) {
      throw new Error('Erro ao atualizar tema');
    }
  },

  async deleteTema(id: number) {
    try {
      await api.delete(`/temas/${id}`);
    } catch (error) {
      throw new Error('Erro ao excluir tema');
    }
  },
}; 