import type { CrudSchema, CrudFormData, FormMode } from '../../api/types';
import { useCrudForm } from '../../hooks/useCrudForm';
import { FieldRenderer } from './FieldRenderer';
import { ValidationErrors } from '../shared/ValidationErrors';
import { LoadingSpinner } from '../shared/LoadingSpinner';

interface Props {
  endpoint:     string;
  schema:       CrudSchema;
  mode:         FormMode;
  initialData?: CrudFormData;
  recordId?:    number | string;
  onSuccess:    () => void;
  onCancel:     () => void;
}

export function CrudForm({
  endpoint, schema, mode, initialData = {}, recordId, onSuccess, onCancel,
}: Props) {
  const { formData, errors, submitting, setField, handleSubmit } = useCrudForm({
    endpoint, mode, schema, initialData, recordId, onSuccess,
  });

  const fields  = schema.formFields[mode] ?? [];
  const isRead  = mode === 'read';
  const modeLabel: Record<FormMode, string> = {
    add: '추가', edit: '수정', read: '조회', clone: '복제',
  };

  return (
    <form onSubmit={e => { e.preventDefault(); void handleSubmit(); }}>
      <ValidationErrors errors={errors} />

      {fields.map(field => {
        if (field.type === 'invisible' || field.type === 'virtual') return null;

        if (field.type === 'hidden') {
          return (
            <FieldRenderer
              key={field.field}
              field={field}
              value={formData[field.field]}
              endpoint={endpoint}
              readonly={isRead}
              onChange={setField}
            />
          );
        }

        return (
          <div key={field.field} className="mb-3">
            <label className="form-label fw-semibold">
              {field.title}
              {field.required && <span className="text-danger ms-1">*</span>}
            </label>
            <FieldRenderer
              field={field}
              value={formData[field.field]}
              endpoint={endpoint}
              readonly={isRead}
              onChange={setField}
            />
          </div>
        );
      })}

      <div className="mt-4 d-flex gap-2">
        {!isRead && (
          <button type="submit" className="btn btn-primary" disabled={submitting}>
            {submitting ? <LoadingSpinner size="sm" /> : modeLabel[mode]}
          </button>
        )}
        <button type="button" className="btn btn-secondary" onClick={onCancel} disabled={submitting}>
          {isRead ? '닫기' : '취소'}
        </button>
      </div>
    </form>
  );
}
