import axios, { InternalAxiosRequestConfig } from 'axios';

// Em dev: proxy do Vite. Em prod: proxy do nginx. Ambos em /api.
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
});
// Interceptor para adicionar o token de autenticação
api.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = localStorage.getItem('token');
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor para tratamento de erros
api.interceptors.response.use(
  (response: any) => response,
  (error: any) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      const path = window.location.pathname;
      if (!path.startsWith('/login')) {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export interface MenuItem {
  id_label: number;
  nome: string;
  referencial: string;
  grupo: string;
  descricao: string;
  disable: boolean;
  fk_label?: number;
}

export const getMenuItems = async (): Promise<MenuItem[]> => {
  try {
    const response = await api.get<MenuItem[]>('/menu-items/');
    console.log('API Response:', response.data);
    
    // Garantir que a resposta seja um array
    if (!Array.isArray(response.data)) {
      console.error('API response is not an array:', response.data);
      return [];
    }
    
    return response.data;
  } catch (error) {
    console.error('Error fetching menu items:', error);
    return [];
  }
};

export default api; 