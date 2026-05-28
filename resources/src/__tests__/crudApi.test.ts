import { describe, it, expect, vi, beforeEach } from 'vitest';
import { crudApi } from '../api/crudApi';

describe('crudApi', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('exportUrl — csv 기본값', () => {
    expect(crudApi.exportUrl('/crud/users')).toBe('/crud/users?action=export&type=csv');
  });

  it('exportUrl — excel 타입', () => {
    expect(crudApi.exportUrl('/crud/users', 'excel')).toBe('/crud/users?action=export&type=excel');
  });

  it('schema fetch URL이 올바르다', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ subject: '사용자', primaryKey: 'id', perPage: 20,
        permissions: {}, columns: [], formFields: {}, defaultOrder: {} }),
    });
    vi.stubGlobal('fetch', mockFetch);

    await crudApi.schema('/crud/users');

    expect(mockFetch).toHaveBeenCalledWith(
      '/crud/users?action=schema',
      expect.objectContaining({ headers: expect.any(Headers) }),
    );
  });

  it('insert — POST 메서드 사용', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    });
    vi.stubGlobal('fetch', mockFetch);

    await crudApi.insert('/crud/users', { username: '홍길동' });

    expect(mockFetch).toHaveBeenCalledWith(
      '/crud/users?action=insert',
      expect.objectContaining({ method: 'POST' }),
    );
  });

  it('delete — POST 메서드 사용', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    });
    vi.stubGlobal('fetch', mockFetch);

    await crudApi.delete('/crud/users', 1);

    expect(mockFetch).toHaveBeenCalledWith(
      '/crud/users?action=delete&id=1',
      expect.objectContaining({ method: 'POST' }),
    );
  });

  it('relation — field와 q 파라미터 포함', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve([]),
    });
    vi.stubGlobal('fetch', mockFetch);

    await crudApi.relation('/crud/users', 'dept_id', '개발');

    const calledUrl = (mockFetch.mock.calls[0] as [string])[0];
    expect(calledUrl).toContain('action=relation');
    expect(calledUrl).toContain('field=dept_id');
    expect(calledUrl).toContain('q=');
  });

  it('deleteMultiple — ids[] 배열 전송', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ success: true }),
    });
    vi.stubGlobal('fetch', mockFetch);

    await crudApi.deleteMultiple('/crud/users', [1, 2, 3]);

    expect(mockFetch).toHaveBeenCalledWith(
      '/crud/users?action=delete_multiple',
      expect.objectContaining({ method: 'POST' }),
    );
  });

  it('HTTP 오류 시 throw 발생', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: false,
      status: 500,
      statusText: 'Internal Server Error',
    });
    vi.stubGlobal('fetch', mockFetch);

    await expect(crudApi.schema('/crud/users')).rejects.toThrow('HTTP 500');
  });
});
