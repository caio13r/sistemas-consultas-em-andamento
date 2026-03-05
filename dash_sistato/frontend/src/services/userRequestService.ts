import axios from 'axios';
import api from './api';

const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8002/api';

// Client sem auth para endpoints públicos
const publicApi = axios.create({
  baseURL: BASE_URL,
});

// ============================================================
// Interfaces
// ============================================================

export interface ServicoPublic {
  id: number;
  nome: string;
  slug: string;
  descricao: string | null;
  icone: string | null;
  permissao_nome: string;
  scope_type: string;
}

export interface UserRequestItemCreate {
  servico_id: number;
  permission_name: string;
}

export interface UserRequestCreate {
  nome_completo: string;
  email: string;
  telefone?: string;
  origem_tipo: string;
  organizacao: string;
  departamento?: string;
  justificativa: string;
  outro?: string;
  sugestao_desenvolvimento?: string;
  items: UserRequestItemCreate[];
}

export interface UserRequestItem {
  id: number;
  servico_id: number | null;
  permission_name: string | null;
  approved: boolean;
  servico_nome: string | null;
}

export interface UserRequest {
  id: number;
  nome_completo: string;
  email: string;
  telefone: string | null;
  origem_tipo: string;
  organizacao: string;
  departamento: string | null;
  justificativa: string;
  outro: string | null;
  sugestao_desenvolvimento: string | null;
  status: string;
  admin_notes: string | null;
  reject_reason: string | null;
  clarification_message: string | null;
  created_at: string;
  updated_at: string | null;
  resolved_at: string | null;
  items: UserRequestItem[];
}

export interface UserRequestStats {
  pendente: number;
  em_analise: number;
  aprovado: number;
  rejeitado: number;
  esclarecimento: number;
  total: number;
}

// ============================================================
// Public endpoints
// ============================================================

export const getPublicServicos = async (origemTipo: string): Promise<ServicoPublic[]> => {
  const response = await publicApi.get('/user-requests/public/servicos', {
    params: { origem_tipo: origemTipo },
  });
  return response.data;
};

export const createUserRequest = async (data: UserRequestCreate): Promise<UserRequest> => {
  const response = await publicApi.post('/user-requests/', data);
  return response.data;
};

export const checkRequestStatus = async (email: string): Promise<{ email: string; requests: UserRequest[] }> => {
  const response = await publicApi.post('/user-requests/status-check', { email });
  return response.data;
};

// ============================================================
// Admin endpoints (autenticado)
// ============================================================

export const getAdminStats = async (): Promise<UserRequestStats> => {
  const response = await api.get('/user-requests/admin/stats');
  return response.data;
};

export const getAdminRequests = async (params?: {
  status_filter?: string;
  origem_tipo?: string;
  search?: string;
  skip?: number;
  limit?: number;
}): Promise<{ requests: UserRequest[]; total: number }> => {
  const response = await api.get('/user-requests/admin', { params });
  return response.data;
};

export const getAdminRequest = async (id: number): Promise<UserRequest> => {
  const response = await api.get(`/user-requests/admin/${id}`);
  return response.data;
};

export const analyzeRequest = async (id: number): Promise<UserRequest> => {
  const response = await api.post(`/user-requests/admin/${id}/analyze`);
  return response.data;
};

export const approveRequest = async (
  id: number,
  data: { username: string; password: string; role_ids: number[]; admin_notes?: string }
): Promise<UserRequest> => {
  const response = await api.post(`/user-requests/admin/${id}/approve`, data);
  return response.data;
};

export const modifyRequest = async (
  id: number,
  data: { items: { item_id: number; approved: boolean }[]; admin_notes?: string }
): Promise<UserRequest> => {
  const response = await api.post(`/user-requests/admin/${id}/modify`, data);
  return response.data;
};

export const rejectRequest = async (
  id: number,
  data: { reject_reason: string; admin_notes?: string }
): Promise<UserRequest> => {
  const response = await api.post(`/user-requests/admin/${id}/reject`, data);
  return response.data;
};

export const clarifyRequest = async (
  id: number,
  data: { clarification_message: string; admin_notes?: string }
): Promise<UserRequest> => {
  const response = await api.post(`/user-requests/admin/${id}/clarify`, data);
  return response.data;
};
