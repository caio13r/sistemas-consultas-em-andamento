import api from './api';

export interface LoginResponse {
  access_token: string;
  token_type: string;
  permissions: string[];
  roles: string[];
  user: {
    id: number;
    username: string;
    email: string;
    full_name: string;
    is_active: boolean;
    is_superuser: boolean;
  };
}

export interface LoginData {
  username: string;
  password: string;
}

class AuthService {
  private token: string | null = null;

  constructor() {
    // Recuperar token do localStorage ao inicializar
    this.token = localStorage.getItem('token');
    console.log('AuthService inicializado. Token:', this.token ? 'Presente' : 'Ausente');
  }

  async login(username: string, password: string): Promise<LoginResponse> {
    try {
      console.log('Tentando fazer login com:', { username });
      const params = new URLSearchParams();
      params.append('username', username);
      params.append('password', password);

      const response = await api.post<LoginResponse>('/token', params, {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        timeout: 15000,
      });

      console.log('Resposta do login:', response.data);
      const { access_token } = response.data;
      this.setToken(access_token);
      return response.data;
    } catch (error: any) {
      console.error('Erro no login:', error.message, error.response?.data || error.code);
      if (error.code === 'ECONNABORTED' || !error.response) {
        throw new Error('Servidor não respondeu. Verifique se o backend está rodando.');
      }
      throw new Error(error.response?.data?.detail || 'Erro ao fazer login');
    }
  }

  logout() {
    console.log('Fazendo logout');
    this.token = null;
    localStorage.removeItem('token');
  }

  getToken(): string | null {
    if (!this.token) {
      this.token = localStorage.getItem('token');
    }
    return this.token;
  }

  private setToken(token: string) {
    console.log('Token definido');
    this.token = token;
    localStorage.setItem('token', token);
  }

  isAuthenticated(): boolean {
    const isAuth = !!this.token;
    console.log('Verificando autenticação:', isAuth);
    return isAuth;
  }
}

// Exportar uma única instância do serviço
export const authService = new AuthService(); 