import { useState, useCallback } from 'react';
import { crudApi } from '../api/crudApi';
import type { ApiResponse, CrudFormData } from '../api/types';

interface UseCrudActionsResult {
  loading: boolean;
  insert:  (data: CrudFormData) => Promise<ApiResponse>;
  update:  (id: number | string, data: CrudFormData) => Promise<ApiResponse>;
  remove:  (id: number | string) => Promise<ApiResponse>;
  removeMultiple: (ids: (number | string)[]) => Promise<ApiResponse>;
}

export function useCrudActions(endpoint: string): UseCrudActionsResult {
  const [loading, setLoading] = useState(false);

  const withLoading = useCallback(async <T>(fn: () => Promise<T>): Promise<T> => {
    setLoading(true);
    try {
      return await fn();
    } finally {
      setLoading(false);
    }
  }, []);

  const insert = useCallback(
    (data: CrudFormData) => withLoading(() => crudApi.insert(endpoint, data)),
    [endpoint, withLoading],
  );

  const update = useCallback(
    (id: number | string, data: CrudFormData) =>
      withLoading(() => crudApi.update(endpoint, id, data)),
    [endpoint, withLoading],
  );

  const remove = useCallback(
    (id: number | string) => withLoading(() => crudApi.delete(endpoint, id)),
    [endpoint, withLoading],
  );

  const removeMultiple = useCallback(
    (ids: (number | string)[]) =>
      withLoading(() => crudApi.deleteMultiple(endpoint, ids)),
    [endpoint, withLoading],
  );

  return { loading, insert, update, remove, removeMultiple };
}
