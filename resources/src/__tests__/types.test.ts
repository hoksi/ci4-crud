import { describe, it, expect } from 'vitest';
import type { CrudSchema, FormField, ColumnDef, Permissions } from '../api/types';

describe('TypeScript 타입 구조 검증', () => {
  it('CrudSchema 구조가 올바르다', () => {
    const schema: CrudSchema = {
      subject:      '사용자',
      primaryKey:   'id',
      perPage:      20,
      permissions: {
        add: true, edit: true, delete: true,
        read: false, clone: false, export: true, deleteMultiple: true,
      },
      columns:    [],
      formFields: { add: [], edit: [], read: [], clone: [] },
      defaultOrder: { field: 'created_at', dir: 'desc' },
    };

    expect(schema.subject).toBe('사용자');
    expect(schema.primaryKey).toBe('id');
    expect(schema.perPage).toBe(20);
    expect(schema.permissions.add).toBe(true);
    expect(schema.permissions.read).toBe(false);
  });

  it('ColumnDef 구조가 올바르다', () => {
    const col: ColumnDef = {
      field:      'name',
      title:      '이름',
      sortable:   true,
      searchable: true,
      callback:   false,
      width:      null,
    };

    expect(col.field).toBe('name');
    expect(col.sortable).toBe(true);
  });

  it('FormField 구조가 올바르다', () => {
    const field: FormField = {
      field:    'email',
      title:    '이메일',
      type:     'email',
      required: true,
      readonly: false,
    };

    expect(field.field).toBe('email');
    expect(field.type).toBe('email');
    expect(field.required).toBe(true);
  });

  it('FormField with options', () => {
    const field: FormField = {
      field:    'status',
      title:    '상태',
      type:     'dropdown',
      required: false,
      readonly: false,
      options:  { active: '활성', inactive: '비활성' },
    };

    expect(field.options?.active).toBe('활성');
  });

  it('Permissions 모든 필드 검증', () => {
    const perms: Permissions = {
      add: true, edit: true, delete: false,
      read: true, clone: false, export: true, deleteMultiple: false,
    };

    expect(Object.keys(perms)).toHaveLength(7);
    expect(perms.delete).toBe(false);
    expect(perms.export).toBe(true);
  });
});
