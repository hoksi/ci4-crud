import { useEffect, useRef } from 'react';
import type { CrudSchema, CrudFormData, FormMode } from '../../api/types';
import { CrudForm } from './CrudForm';
import { LoadingSpinner } from '../shared/LoadingSpinner';

interface Props {
  show:         boolean;
  endpoint:     string;
  schema:       CrudSchema;
  mode:         FormMode;
  initialData?: CrudFormData;
  recordId?:    number | string;
  loading?:     boolean;
  onSuccess:    () => void;
  onClose:      () => void;
}

const TITLE: Record<FormMode, string> = {
  add: '추가', edit: '수정', read: '조회', clone: '복제',
};

export function CrudModal({
  show, endpoint, schema, mode, initialData, recordId,
  loading = false, onSuccess, onClose,
}: Props) {
  const dialogRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handleKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    if (show) document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [show, onClose]);

  if (!show) return null;

  return (
    <div
      className="modal d-block"
      style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
      onClick={e => { if (e.target === e.currentTarget) onClose(); }}
      role="dialog"
      aria-modal="true"
    >
      <div className="modal-dialog modal-lg modal-dialog-scrollable" ref={dialogRef}>
        <div className="modal-content">
          <div className="modal-header">
            <h5 className="modal-title">{schema.subject} {TITLE[mode]}</h5>
            <button type="button" className="btn-close" onClick={onClose} />
          </div>
          <div className="modal-body">
            {loading ? (
              <LoadingSpinner />
            ) : (
              <CrudForm
                endpoint={endpoint}
                schema={schema}
                mode={mode}
                initialData={initialData}
                recordId={recordId}
                onSuccess={() => { onSuccess(); onClose(); }}
                onCancel={onClose}
              />
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
