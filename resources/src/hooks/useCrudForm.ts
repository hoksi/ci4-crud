import { useState, useCallback } from 'react';
import type { CrudSchema, CrudFormData, FormMode } from '../api/types';
import { useCrudActions } from './useCrudActions';

interface UseCrudFormOptions {
  endpoint:     string;
  mode:         FormMode;
  schema:       CrudSchema;
  initialData?: CrudFormData;
  recordId?:    number | string;
  onSuccess?:   () => void;
}

interface UseCrudFormResult {
  formData:     CrudFormData;
  errors:       Record<string, string>;
  submitting:   boolean;
  setField:     (field: string, value: unknown) => void;
  handleSubmit: () => Promise<void>;
  reset:        () => void;
}

export function useCrudForm({
  endpoint,
  mode,
  schema,
  initialData = {},
  recordId,
  onSuccess,
}: UseCrudFormOptions): UseCrudFormResult {
  const [formData, setFormData]     = useState<CrudFormData>(initialData);
  const [errors, setErrors]         = useState<Record<string, string>>({});
  const [submitting, setSubmitting] = useState(false);

  const { insert, update } = useCrudActions(endpoint);

  const setField = useCallback((field: string, value: unknown) => {
    setFormData(prev => ({ ...prev, [field]: value }));
    setErrors(prev => {
      const next = { ...prev };
      delete next[field];
      return next;
    });
  }, []);

  const handleSubmit = useCallback(async () => {
    setSubmitting(true);
    setErrors({});

    try {
      const result = mode === 'edit' && recordId !== undefined
        ? await update(recordId, formData)
        : await insert(formData);

      if (result.success) {
        onSuccess?.();
      } else {
        setErrors(result.errors ?? { _general: result.message ?? '처리 실패' });
      }
    } catch (err) {
      setErrors({ _general: err instanceof Error ? err.message : '요청 실패' });
    } finally {
      setSubmitting(false);
    }
  }, [mode, recordId, formData, insert, update, onSuccess]);

  const reset = useCallback(() => {
    setFormData(initialData);
    setErrors({});
  }, [initialData]);

  const fields = schema.formFields[mode] ?? [];
  const resolvedData: CrudFormData = {};
  fields.forEach(f => { resolvedData[f.field] = formData[f.field] ?? ''; });

  return {
    formData: { ...resolvedData, ...formData },
    errors, submitting, setField, handleSubmit, reset,
  };
}
