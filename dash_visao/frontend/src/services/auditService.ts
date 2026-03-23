import api from './api';

export interface Theme {
  id: number;
  name: string;
  description: string;
  category: string;
  status: string;
  created_at: string;
  updated_at?: string;
}

export interface Audit {
  id: number;
  theme_id: number;
  title: string;
  description: string;
  deadline: string;
  status: string;
  created_by: number;
  created_at: string;
  updated_at?: string;
  theme: Theme;
}

export interface Request {
  id: number;
  audit_id: number;
  cro_id: number;
  status: 'pending' | 'sent' | 'in_analysis' | 'approved' | 'rejected';
  message?: string;
  created_at: string;
  updated_at?: string;
  audit: Audit;
}

export interface Document {
  id: number;
  audit_id: number;
  request_id?: number;
  filename: string;
  file_path: string;
  file_type: string;
  file_size: number;
  is_validated: boolean;
  validation_date?: string;
  created_at: string;
  updated_at?: string;
  audit: Audit;
  request?: Request;
}

export interface Communication {
  id: number;
  audit_id: number;
  sender_id: number;
  receiver_id: number;
  message: string;
  is_read: boolean;
  created_at: string;
  audit: Audit;
  sender: any;
  receiver: any;
}

// Theme services
export const getThemes = async (): Promise<Theme[]> => {
  try {
    const response = await api.get<Theme[]>('/audit/themes');
    return response.data;
  } catch (error) {
    console.error('Error fetching themes:', error);
    throw error;
  }
};

export const createTheme = async (theme: Omit<Theme, 'id' | 'created_at' | 'updated_at'>): Promise<Theme> => {
  try {
    const response = await api.post<Theme>('/audit/themes', theme);
    return response.data;
  } catch (error) {
    console.error('Error creating theme:', error);
    throw error;
  }
};

// Audit services
export const getAudits = async (): Promise<Audit[]> => {
  try {
    const response = await api.get<Audit[]>('/audit/audits');
    return response.data;
  } catch (error) {
    console.error('Error fetching audits:', error);
    throw error;
  }
};

export const createAudit = async (audit: Omit<Audit, 'id' | 'created_by' | 'created_at' | 'updated_at' | 'theme'>): Promise<Audit> => {
  try {
    const response = await api.post<Audit>('/audit/audits', audit);
    return response.data;
  } catch (error) {
    console.error('Error creating audit:', error);
    throw error;
  }
};

// Request services
export const getRequests = async (): Promise<Request[]> => {
  try {
    const response = await api.get<Request[]>('/audit/requests');
    return response.data;
  } catch (error) {
    console.error('Error fetching requests:', error);
    throw error;
  }
};

export const createRequest = async (request: Omit<Request, 'id' | 'created_at' | 'updated_at' | 'audit'>): Promise<Request> => {
  try {
    const response = await api.post<Request>('/audit/requests', request);
    return response.data;
  } catch (error) {
    console.error('Error creating request:', error);
    throw error;
  }
};

export const updateRequestStatus = async (requestId: number, status: Request['status']): Promise<Request> => {
  try {
    const response = await api.put<Request>(`/audit/requests/${requestId}/status`, { status });
    return response.data;
  } catch (error) {
    console.error('Error updating request status:', error);
    throw error;
  }
};

// Document services
export const getDocuments = async (): Promise<Document[]> => {
  try {
    const response = await api.get<Document[]>('/audit/documents');
    return response.data;
  } catch (error) {
    console.error('Error fetching documents:', error);
    throw error;
  }
};

export const uploadDocument = async (file: File, auditId?: number, requestId?: number): Promise<Document> => {
  try {
    const formData = new FormData();
    formData.append('file', file);
    if (auditId) formData.append('audit_id', auditId.toString());
    if (requestId) formData.append('request_id', requestId.toString());

    const response = await api.post<Document>('/audit/documents', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  } catch (error) {
    console.error('Error uploading document:', error);
    throw error;
  }
};

// Communication services
export const getCommunications = async (): Promise<Communication[]> => {
  try {
    const response = await api.get<Communication[]>('/audit/communications');
    return response.data;
  } catch (error) {
    console.error('Error fetching communications:', error);
    throw error;
  }
};

export const createCommunication = async (communication: Omit<Communication, 'id' | 'sender_id' | 'created_at' | 'audit' | 'sender' | 'receiver'>): Promise<Communication> => {
  try {
    const response = await api.post<Communication>('/audit/communications', communication);
    return response.data;
  } catch (error) {
    console.error('Error creating communication:', error);
    throw error;
  }
};

export const markCommunicationAsRead = async (communicationId: number): Promise<void> => {
  try {
    await api.put(`/audit/communications/${communicationId}/read`);
  } catch (error) {
    console.error('Error marking communication as read:', error);
    throw error;
  }
}; 