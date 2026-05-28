import { useState, useCallback } from 'react';
import type { CrudFormData, FormMode } from '../api/types';
import { useCrudSchema } from '../hooks/useCrudSchema';
import { crudApi } from '../api/crudApi';
import { DataGrid } from './DataGrid/DataGrid';
import { CrudModal } from './Form/CrudModal';
import { LoadingSpinner } from './shared/LoadingSpinner';

interface Props {
  endpoint:    string;
  theme?:      string;
  locale?:     string;
  height?:     string;
}

interface ModalState {
  show:         boolean;
  mode:         FormMode;
  recordId?:    number | string;
  initialData?: CrudFormData;
  loading:      boolean;
}

const INITIAL_MODAL: ModalState = { show: false, mode: 'add', loading: false };

export function CrudApp({ endpoint, theme = 'bootstrap5', locale = 'ko' }: Props) {
  const { schema, loading, error } = useCrudSchema(endpoint);
  const [modal, setModal]          = useState<ModalState>(INITIAL_MODAL);
  const [gridRefresh, setGridRefresh] = useState(0);

  const openModal = useCallback(async (mode: FormMode, id?: number | string) => {
    if (mode === 'add') {
      setModal({ show: true, mode, loading: false });
      return;
    }
    setModal({ show: true, mode, loading: true });
    try {
      const res = await crudApi.read(endpoint, id!);
      setModal({ show: true, mode, recordId: id, initialData: (res.data ?? {}) as CrudFormData, loading: false });
    } catch {
      setModal(INITIAL_MODAL);
    }
  }, [endpoint]);

  const closeModal   = useCallback(() => setModal(INITIAL_MODAL), []);
  const handleSuccess = useCallback(() => setGridRefresh(n => n + 1), []);

  if (loading) return <LoadingSpinner />;
  if (error)   return <div className="alert alert-danger">스키마 로딩 실패: {error}</div>;
  if (!schema) return null;

  return (
    <div className={`ci4crud-app theme-${theme} locale-${locale}`}>
      <DataGrid
        endpoint={endpoint}
        schema={schema}
        onOpen={openModal}
        gridRefresh={gridRefresh}
      />
      <CrudModal
        show={modal.show}
        endpoint={endpoint}
        schema={schema}
        mode={modal.mode}
        initialData={modal.initialData}
        recordId={modal.recordId}
        loading={modal.loading}
        onSuccess={handleSuccess}
        onClose={closeModal}
      />
    </div>
  );
}
