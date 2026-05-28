export interface CrudSchema {
  subject: string;
  primaryKey: string;
  perPage: number;
  permissions: Permissions;
  columns: ColumnDef[];
  formFields: Record<FormMode, FormField[]>;
  defaultOrder: { field: string; dir: string };
}

export interface Permissions {
  add: boolean;
  edit: boolean;
  delete: boolean;
  read: boolean;
  clone: boolean;
  export: boolean;
  deleteMultiple: boolean;
}

export interface ColumnDef {
  field: string;
  title: string;
  sortable: boolean;
  searchable: boolean;
  callback: boolean;
  width?: number | null;
}

export interface FormField {
  field: string;
  title: string;
  type: FieldType;
  required: boolean;
  readonly: boolean;
  options?: Record<string, string>;
  relation?: RelationConfig;
  multiple?: boolean;
  dateFormat?: string;
  step?: string;
  toggle?: boolean;
  editor?: string;
}

export interface RelationConfig {
  table: string;
  labelField: string;
  valueField?: string;
  dynamic: boolean;
  parentField?: string;
}

export type FormMode = 'add' | 'edit' | 'read' | 'clone';

export type FieldType =
  | 'string' | 'textarea' | 'numeric' | 'float' | 'boolean'
  | 'date' | 'datetime' | 'native_date' | 'native_time'
  | 'dropdown' | 'dropdown_search' | 'enum'
  | 'multiselect_native' | 'multiselect_searchable'
  | 'relation' | 'relation_nton' | 'dependent'
  | 'password' | 'password_toggle' | 'email' | 'color'
  | 'upload_file' | 'hidden' | 'invisible' | 'virtual' | 'readonly' | 'wysiwyg';

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  message?: string;
  errors?: Record<string, string>;
}

export interface ListResponse {
  last_page: number;
  data: Record<string, unknown>[];
}

export interface RelationOption {
  value: string | number;
  label: string;
}

export type CrudFormData = Record<string, unknown>;
