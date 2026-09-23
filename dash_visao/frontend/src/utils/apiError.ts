import { isAxiosError } from 'axios';

/** Extrai mensagem legível de erros da API (FastAPI detail). */
export function getApiErrorMessage(error: unknown, fallback = 'Erro na operação'): string {
  if (isAxiosError(error)) {
    const detail = error.response?.data?.detail;
    if (typeof detail === 'string') return detail;
    if (Array.isArray(detail)) {
      return detail
        .map((item) => (typeof item === 'object' && item?.msg ? item.msg : String(item)))
        .join('; ');
    }
    if (error.code === 'ERR_NETWORK' || error.message === 'Network Error') {
      return 'Falha de conexão com o servidor. Verifique a rede e tente novamente.';
    }
    if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
      return 'O servidor demorou para responder (timeout).';
    }
    if (error.message) return error.message;
  }
  if (error instanceof Error) return error.message;
  return fallback;
}

/** Tenta extrair mensagem de erro quando a resposta veio como Blob (ex.: responseType blob + HTTP 4xx/5xx). */
export async function getBlobErrorMessage(data: Blob, fallback: string): Promise<string> {
  try {
    const text = await data.text();
    const parsed = JSON.parse(text);
    if (typeof parsed.detail === 'string') return parsed.detail;
    if (Array.isArray(parsed.detail)) {
      return parsed.detail.map((item: { msg?: string }) => item?.msg || String(item)).join('; ');
    }
  } catch {
    /* não é JSON */
  }
  return fallback;
}

/** Verifica se o blob é um arquivo xlsx válido (ZIP/PK). */
export async function ensureXlsxBlob(data: Blob): Promise<Blob> {
  if (!data || data.size === 0) {
    throw new Error('O servidor retornou um arquivo vazio.');
  }
  const head = new Uint8Array(await data.slice(0, 4).arrayBuffer());
  if (head[0] === 0x50 && head[1] === 0x4b) return data;

  const msg = await getBlobErrorMessage(data, 'O servidor não retornou um arquivo Excel válido.');
  throw new Error(msg);
}
