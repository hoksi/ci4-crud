import type { CrudSchema, FormMode } from '../../api/types';
import { useTabulator } from './useTabulator';
import { BulkActionBar } from './BulkActionBar';
import { crudApi } from '../../api/crudApi';

interface Props {
  endpoint:    string;
  schema:      CrudSchema;
  onOpen:      (mode: FormMode, id?: number | string) => void;
  gridRefresh: number;
}

export function DataGrid({ endpoint, schema, onOpen }: Props) {
  const { containerRef, refresh, getSelectedIds } = useTabulator({
    endpoint,
    schema,
    onEdit:   id => onOpen('edit', id),
    onRead:   schema.permissions.read ? id => onOpen('read', id) : undefined,
    onDelete: async id => {
      if (!confirm('정말 삭제하시겠습니까?')) return;
      await crudApi.delete(endpoint, id);
      refresh();
    },
  });

  const handleDeleteSelected = async () => {
    const ids = getSelectedIds();
    if (!ids.length || !confirm(`${ids.length}건을 삭제하시겠습니까?`)) return;
    await crudApi.deleteMultiple(endpoint, ids);
    refresh();
  };

  return (
    <div className="ci4crud-datagrid">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <h5 className="mb-0">{schema.subject} 목록</h5>
        <div className="d-flex gap-2">
          {schema.permissions.export && (
            <a href={crudApi.exportUrl(endpoint, 'csv')} className="btn btn-sm btn-outline-secondary">
              CSV
            </a>
          )}
          {schema.permissions.add && (
            <button type="button" className="btn btn-sm btn-primary" onClick={() => onOpen('add')}>
              {schema.subject} 추가
            </button>
          )}
        </div>
      </div>

      {schema.permissions.deleteMultiple && (
        <BulkActionBar selectedCount={0} onDeleteSelected={handleDeleteSelected} />
      )}

      <div ref={containerRef} />
    </div>
  );
}
