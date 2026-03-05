import axios from 'axios';
import { authService } from './authService';

// Em dev: usa proxy do Vite (mesma origem). Em prod: usa URL completa.
const API_URL = import.meta.env.DEV ? '' : (import.meta.env.VITE_API_URL?.replace(/\/api\/?$/, '') || 'http://localhost:8002');

// Configuração do axios
const api = axios.create({
  baseURL: API_URL,
  timeout: 5000, // 5 segundos de timeout
  headers: {
    'Content-Type': 'application/json',
  }
});

// Interceptor para logging
api.interceptors.request.use(request => {
  console.log('Iniciando requisição:', {
    url: request.url,
    method: request.method,
    headers: request.headers,
    data: request.data
  });
  
  // Adicionar token JWT se existir
  const token = authService.getToken();
  if (token && request.headers) {
    request.headers.Authorization = `Bearer ${token}`;
    console.log('Token JWT adicionado à requisição');
  } else {
    console.log('Nenhum token JWT encontrado');
  }
  
  return request;
});

api.interceptors.response.use(
  response => {
    console.log('Resposta recebida:', {
      status: response.status,
      data: response.data,
      headers: response.headers
    });
    return response;
  },
  error => {
    const fullUrl = error.config?.baseURL && error.config?.url ? `${error.config.baseURL}${error.config.url}` : error.config?.url;
    console.error('Erro na requisição:', {
      url: fullUrl,
      message: error.message,
      status: error.response?.status,
      detail: error.response?.data?.detail,
      code: error.code
    });
    return Promise.reject(error);
  }
);

export interface User {
  id?: number;
  username: string;
  email: string;
  full_name: string;
  password?: string;
  is_active: boolean;
  is_superuser: boolean;
  permissions?: string[];
  roles?: string[];
  created_at?: string;
  last_activity?: string;
}

export const userService = {
  async createUser(userData: User): Promise<User> {
    try {
      const response = await api.post<User>('/users/', userData);
      return response.data;
    } catch (error) {
      if (error instanceof Error) {
        throw new Error(error.message);
      }
      throw new Error('Erro ao criar usuário');
    }
  },

  async getUsers() {
    try {
      console.log('Verificando autenticação antes de buscar usuários');
      if (!authService.isAuthenticated()) {
        console.error('Usuário não autenticado');
        throw new Error('Usuário não autenticado. Por favor, faça login.');
      }

      console.log('Buscando usuários...');
      const response = await api.get('/users');
      console.log('Usuários recebidos:', response.data);
      return response.data;
    } catch (error: any) {
      console.error('Erro detalhado ao buscar usuários:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status,
        config: error.config
      });

      if (error.code === 'ECONNABORTED') {
        throw new Error('O servidor demorou muito para responder. Por favor, tente novamente.');
      }
      if (!error.response) {
        throw new Error('Não foi possível conectar ao servidor. Verifique se o backend está rodando.');
      }
      if (error.response.status === 401) {
        console.log('Token inválido ou expirado, fazendo logout');
        authService.logout();
        throw new Error('Sessão expirada. Por favor, faça login novamente.');
      }
      throw new Error(error.response?.data?.detail || error.message || 'Erro ao buscar usuários');
    }
  },

  async updateUser(id: number, userData: Partial<User>) {
    try {
      console.log('Verificando autenticação antes de atualizar usuário');
      if (!authService.isAuthenticated()) {
        console.error('Usuário não autenticado');
        throw new Error('Usuário não autenticado. Por favor, faça login.');
      }

      console.log('Atualizando usuário:', { id, userData });
      const response = await api.put(`/users/${id}`, userData);
      console.log('Resposta do servidor:', response.data);
      return response.data;
    } catch (error: any) {
      console.error('Erro detalhado ao atualizar usuário:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status,
        config: error.config
      });

      if (error.code === 'ECONNABORTED') {
        throw new Error('O servidor demorou muito para responder. Por favor, tente novamente.');
      }
      if (!error.response) {
        throw new Error('Não foi possível conectar ao servidor. Verifique se o backend está rodando.');
      }
      if (error.response.status === 401) {
        console.log('Token inválido ou expirado, fazendo logout');
        authService.logout();
        throw new Error('Sessão expirada. Por favor, faça login novamente.');
      }
      throw new Error(error.response?.data?.detail || error.message || 'Erro ao atualizar usuário');
    }
  },

  async updateMe(userData: Partial<User>) {
    try {
      if (!authService.isAuthenticated()) {
        throw new Error('Usuário não autenticado. Por favor, faça login.');
      }
      const response = await api.patch('/users/me', userData);
      return response.data;
    } catch (error: any) {
      if (error.response?.status === 401) {
        authService.logout();
        throw new Error('Sessão expirada. Por favor, faça login novamente.');
      }
      throw new Error(error.response?.data?.detail || 'Erro ao atualizar perfil');
    }
  },

  async deleteUser(id: number) {
    try {
      console.log('Verificando autenticação antes de excluir usuário');
      if (!authService.isAuthenticated()) {
        console.error('Usuário não autenticado');
        throw new Error('Usuário não autenticado. Por favor, faça login.');
      }

      console.log('Excluindo usuário:', id);
      const response = await api.delete(`/users/${id}`);
      console.log('Resposta do servidor:', response.data);
      return response.data;
    } catch (error: any) {
      console.error('Erro detalhado ao excluir usuário:', {
        message: error.message,
        response: error.response?.data,
        status: error.response?.status,
        config: error.config
      });

      if (error.code === 'ECONNABORTED') {
        throw new Error('O servidor demorou muito para responder. Por favor, tente novamente.');
      }
      if (!error.response) {
        throw new Error('Não foi possível conectar ao servidor. Verifique se o backend está rodando.');
      }
      if (error.response.status === 401) {
        console.log('Token inválido ou expirado, fazendo logout');
        authService.logout();
        throw new Error('Sessão expirada. Por favor, faça login novamente.');
      }
      throw new Error(error.response?.data?.detail || error.message || 'Erro ao excluir usuário');
    }
  },

  async createFirstAdmin(userData: Omit<User, 'id' | 'created_at' | 'last_activity'>) {
    try {
      console.log('Criando primeiro usuário admin:', userData);
      const response = await api.post('/users/first-admin', userData);
      console.log('Resposta do servidor:', response.data);
      return response.data;
    } catch (error: any) {
      console.error('Erro ao criar primeiro admin:', error);
      if (error.code === 'ECONNABORTED') {
        throw new Error('O servidor demorou muito para responder. Por favor, tente novamente.');
      }
      if (!error.response) {
        throw new Error('Não foi possível conectar ao servidor. Verifique se o backend está rodando.');
      }
      throw new Error(error.response?.data?.detail || error.message || 'Erro ao criar primeiro admin');
    }
  },

  async getCurrentUser() {
    try {
      const response = await api.get('/users/me');
      return response.data;
    } catch (error: any) {
      const status = error.response?.status;
      const detail = error.response?.data?.detail;
      if (!error.response) {
        const baseUrl = API_URL || window.location.origin;
        throw new Error('Não foi possível conectar ao servidor. Verifique se o backend está rodando em ' + baseUrl);
      }
      if (status === 401) {
        throw new Error('Sessão expirada. Por favor, faça login novamente.');
      }
      throw new Error(detail || `Erro ao buscar usuário atual (${status || error.message})`);
    }
  },

  async getUserById(id: number) {
    try {
      const response = await api.get(`/users/${id}`);
      return response.data;
    } catch (error: any) {
      throw new Error('Erro ao buscar usuário');
    }
  }
}; 