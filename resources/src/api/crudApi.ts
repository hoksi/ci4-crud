import type { ApiResponse, CrudFormData, CrudSchema, ListResponse, RelationOption } from './types';

function getCsrfToken(): string {
  return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
}

async function request<T>(url: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');
  headers.set('X-CSRF-TOKEN', getCsrfToken());

  const response = await fetch(url, { ...options, headers });

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }

  return response.json() as Promise<T>;
}

export const crudApi = {
  schema(endpoint: string): Promise<CrudSchema> {
    return request<CrudSchema>(`${endpoint}?action=schema`);
  },

  list(endpoint: string, params: Record<string, string>): Promise<ListResponse> {
    const qs = new URLSearchParams({ action: 'list', ...params }).toString();
    return request<ListResponse>(`${endpoint}?${qs}`);
  },

  read(endpoint: string, id: number | string): Promise<ApiResponse> {
    return request<ApiResponse>(`${endpoint}?action=read&id=${id}`);
  },

  insert(endpoint: string, data: CrudFormData): Promise<ApiResponse> {
    const body = new window.FormData();
    Object.entries(data).forEach(([k, v]) => {
      if (v instanceof File) body.append(k, v);
      else if (Array.isArray(v)) v.forEach(item => body.append(`${k}[]`, String(item)));
      else if (v !== null && v !== undefined) body.append(k, String(v));
    });

    return request<ApiResponse>(`${endpoint}?action=insert`, { method: 'POST', body });
  },

  update(endpoint: string, id: number | string, data: CrudFormData): Promise<ApiResponse> {
    const body = new window.FormData();
    Object.entries(data).forEach(([k, v]) => {
      if (v instanceof File) body.append(k, v);
      else if (Array.isArray(v)) v.forEach(item => body.append(`${k}[]`, String(item)));
      else if (v !== null && v !== undefined) body.append(k, String(v));
    });

    return request<ApiResponse>(`${endpoint}?action=update&id=${id}`, { method: 'POST', body });
  },

  delete(endpoint: string, id: number | string): Promise<ApiResponse> {
    return request<ApiResponse>(`${endpoint}?action=delete&id=${id}`, { method: 'POST' });
  },

  deleteMultiple(endpoint: string, ids: (number | string)[]): Promise<ApiResponse> {
    const body = new FormData();
    ids.forEach(id => body.append('ids[]', String(id)));
    return request<ApiResponse>(`${endpoint}?action=delete_multiple`, { method: 'POST', body });
  },

  relation(
    endpoint: string,
    field: string,
    query: string,
    parentValue?: string,
  ): Promise<RelationOption[]> {
    const params = new URLSearchParams({ action: 'relation', field, q: query });
    if (parentValue) params.set('parent_value', parentValue);
    return request<RelationOption[]>(`${endpoint}?${params.toString()}`);
  },

  exportUrl(endpoint: string, type: 'csv' | 'excel' = 'csv'): string {
    return `${endpoint}?action=export&type=${type}`;
  },
};
