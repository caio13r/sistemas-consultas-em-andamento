import api from './api';
import { authService } from './authService';

export interface User {
  id?: number;
  username: string;
  email: string;
  full_name: string;
  password?: string;
  is_active: boolean;
  is_superuser: boolean;
  role_ids?: number[];
  permissions?: string[];
  roles?: string[];
  created_at?: string;
  last_activity?: string;
}

export interface RoleOption {
  id: number;
  name: string;
  description?: string;
}

export interface UserCreatePayload {
  username: string;
  email: string;
  full_name: string;
  password: string;
  is_active: boolean;
  is_superuser: boolean;
  role_ids: number[];
}

export const userService = {
  async getRoles(): Promise<RoleOption[]> {
    const response = await api.get<RoleOption[]>('/users/roles');
    return response.data;
  },

  async createUser(userData: UserCreatePayload): Promise<User> {
    const response = await api.post<User>('/users', userData);
    return response.data;
  },

  async getUsers() {
    const response = await api.get('/users');
    return response.data;
  },

  async updateUser(id: number, userData: Partial<User>) {
    const response = await api.put(`/users/${id}`, userData);
    return response.data;
  },

  async updateMe(userData: Partial<User>) {
    const response = await api.patch('/users/me', userData);
    return response.data;
  },

  async deleteUser(id: number) {
    const response = await api.delete(`/users/${id}`);
    return response.data;
  },

  async createFirstAdmin(userData: Omit<User, 'id' | 'created_at' | 'last_activity'>) {
    const response = await api.post('/users/first-admin', userData);
    return response.data;
  },

  async getCurrentUser() {
    const response = await api.get('/users/me');
    return response.data;
  },

  async getUserById(id: number) {
    const response = await api.get(`/users/${id}`);
    return response.data;
  }
};
