import api from './api';

interface ExportColumn {
  key: string;
  label: string;
}

interface GenericExportParams {
  data: Record<string, any>[];
  columns?: ExportColumn[];
  title?: string;
  filename?: string;
}

/**
 * Servico de exportacao - Faz chamadas ao backend para gerar Excel/PDF
 */
export const exportService = {
  /**
   * Exporta dados genericos para Excel via POST.
   * O frontend envia os dados e o backend gera o arquivo.
   */
  async exportGenericExcel(params: GenericExportParams): Promise<void> {
    const response = await api.post('/export/generic/excel', params, {
      responseType: 'blob',
    });

    const filename = params.filename || 'exportacao';
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
    downloadBlob(response.data, `${filename}_${timestamp}.xlsx`);
  },

  /**
   * Exporta Consulta Integrada PF/PJ para Excel via GET (server-side query).
   */
  async exportConsultaIntegradaExcel(params: Record<string, string>): Promise<void> {
    const queryString = new URLSearchParams(params).toString();
    const response = await api.get(`/export/consulta-integrada/excel?${queryString}`, {
      responseType: 'blob',
    });

    const tipo = params.tipo || 'pf';
    downloadBlob(response.data, `consulta_integrada_${tipo}.xlsx`);
  },

  /**
   * Exporta dados genericos para PDF via POST.
   */
  async exportGenericPdf(params: GenericExportParams): Promise<void> {
    const response = await api.post('/export/generic/pdf', params, {
      responseType: 'blob',
    });

    const filename = params.filename || 'exportacao';
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
    downloadBlob(response.data, `${filename}_${timestamp}.pdf`);
  },
};

/**
 * Helper para fazer download de um Blob como arquivo
 */
function downloadBlob(blob: Blob, filename: string) {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
}

export default exportService;
