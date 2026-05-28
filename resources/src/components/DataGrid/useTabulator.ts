import { useRef, useEffect, useCallback } from 'react';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import type { CrudSchema, ColumnDef } from '../../api/types';
import type { ColumnDefinition } from 'tabulator-tables';

interface UseTabulator {
  endpoint:  string;
  schema:    CrudSchema;
  onEdit?:   (id: number | string) => void;
  onRead?:   (id: number | string) => void;
  onDelete?: (id: number | string) => Promise<void>;
}

export function useTabulator({ endpoint, schema, onEdit, onRead, onDelete }: UseTabulator) {
  const containerRef = useRef<HTMLDivElement>(null);
  const tableRef     = useRef<Tabulator | null>(null);

  const buildColumns = useCallback((cols: ColumnDef[]): ColumnDefinition[] => {
    const pk = schema.primaryKey;

    const dataCols: ColumnDefinition[] = cols.map(col => ({
      title:        col.title,
      field:        col.field,
      sorter:       col.sortable ? 'string' : undefined,
      headerFilter: col.searchable ? 'input' : undefined,
      width:        col.width ?? undefined,
    }));

    const actionCol: ColumnDefinition = {
      title:      '작업',
      field:      '_actions',
      headerSort: false,
      width:      120,
      formatter:  () => {
        const perms = schema.permissions;
        const edit  = perms.edit   ? `<button class="btn btn-xs btn-warning btn-sm py-0 me-1" data-action="edit">수정</button>`   : '';
        const read  = perms.read   ? `<button class="btn btn-xs btn-info btn-sm py-0 me-1" data-action="read">조회</button>`     : '';
        const del   = perms.delete ? `<button class="btn btn-xs btn-danger btn-sm py-0" data-action="delete">삭제</button>` : '';
        return `<div class="ci4crud-actions">${edit}${read}${del}</div>`;
      },
      cellClick: (_e, cell) => {
        const row  = cell.getData() as Record<string, unknown>;
        const id   = row[pk] as number | string;
        const btn  = (_e.target as HTMLElement).closest<HTMLButtonElement>('button');
        if (!btn) return;

        if (btn.dataset['action'] === 'edit')   onEdit?.(id);
        if (btn.dataset['action'] === 'read')   onRead?.(id);
        if (btn.dataset['action'] === 'delete') void onDelete?.(id);
      },
    };

    return [
      { title: '', formatter: 'rowSelection', titleFormatter: 'rowSelection', headerSort: false, width: 40 },
      ...dataCols,
      actionCol,
    ];
  }, [schema, onEdit, onRead, onDelete]);

  useEffect(() => {
    if (!containerRef.current) return;

    tableRef.current = new Tabulator(containerRef.current, {
      ajaxURL:    `${endpoint}?action=list`,
      ajaxConfig: { headers: { Accept: 'application/json' } },

      pagination:     true,
      paginationMode: 'remote',
      sortMode:       'remote',
      filterMode:     'remote',
      paginationSize: schema.perPage,

      layout:           'fitColumns',
      responsiveLayout: 'collapse',
      height:           '600px',
      selectableRows:   true,

      columns: buildColumns(schema.columns),
    });

    return () => {
      tableRef.current?.destroy();
      tableRef.current = null;
    };
  }, [endpoint, schema, buildColumns]);

  const refresh = useCallback(() => { tableRef.current?.replaceData(); }, []);

  const getSelectedIds = useCallback((): (number | string)[] => {
    const rows = tableRef.current?.getSelectedData() as Record<string, unknown>[] ?? [];
    return rows.map(r => r[schema.primaryKey] as number | string);
  }, [schema.primaryKey]);

  return { containerRef, tableRef, refresh, getSelectedIds };
}
