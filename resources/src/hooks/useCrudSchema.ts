import { useState, useEffect } from 'react';
import { crudApi } from '../api/crudApi';
import type { CrudSchema } from '../api/types';

interface UseCrudSchemaResult {
  schema: CrudSchema | null;
  loading: boolean;
  error: string | null;
  refresh: () => void;
}

export function useCrudSchema(endpoint: string): UseCrudSchemaResult {
  const [schema, setSchema]   = useState<CrudSchema | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState<string | null>(null);
  const [tick, setTick]       = useState(0);

  useEffect(() => {
    let cancelled = false;

    setLoading(true);
    setError(null);

    crudApi.schema(endpoint)
      .then(data => {
        if (!cancelled) {
          setSchema(data);
          setLoading(false);
        }
      })
      .catch(err => {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : '스키마 로딩 실패');
          setLoading(false);
        }
      });

    return () => { cancelled = true; };
  }, [endpoint, tick]);

  const refresh = () => setTick(t => t + 1);

  return { schema, loading, error, refresh };
}
