import type { FormField } from '../../api/types';

interface Props {
  field:    FormField;
  value:    unknown;
  endpoint: string;
  readonly: boolean;
  onChange: (field: string, value: unknown) => void;
}

export function FieldRenderer({ field, value, endpoint, readonly, onChange }: Props) {
  const { field: name, type, required, options } = field;
  const str    = value !== null && value !== undefined ? String(value) : '';
  const roAttr = readonly || field.readonly;
  const change = (val: unknown) => onChange(name, val);
  const req    = required ? true : undefined;
  const cls    = 'form-control';

  switch (type) {
    case 'textarea':
      return (
        <textarea name={name} className={cls} rows={4} required={req}
          readOnly={roAttr} value={str} onChange={e => change(e.target.value)} />
      );
    case 'numeric':
      return (
        <input type="number" name={name} className={cls} value={str}
          required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
    case 'float':
      return (
        <input type="number" step="0.01" name={name} className={cls}
          value={str} required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
    case 'boolean':
      return (
        <div className="form-check">
          <input type="checkbox" name={name} className="form-check-input"
            checked={Boolean(value)} disabled={roAttr}
            onChange={e => change(e.target.checked ? 1 : 0)} />
        </div>
      );
    case 'date':
    case 'native_date':
      return (
        <input type="date" name={name} className={cls} value={str}
          required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
    case 'datetime':
      return (
        <input type="datetime-local" name={name} className={cls}
          value={str.replace(' ', 'T')} required={req} readOnly={roAttr}
          onChange={e => change(e.target.value.replace('T', ' '))} />
      );
    case 'native_time':
      return (
        <input type="time" name={name} className={cls} value={str}
          required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
    case 'password':
    case 'password_toggle':
      return (
        <input type="password" name={name} className={cls}
          required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
    case 'email':
      return (
        <input type="email" name={name} className={cls} value={str}
          required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
    case 'color':
      return (
        <input type="color" name={name} className="form-control form-control-color"
          value={str || '#000000'} disabled={roAttr}
          onChange={e => change(e.target.value)} />
      );
    case 'dropdown':
    case 'enum':
      return (
        <select name={name} className="form-select" value={str}
          required={req} disabled={roAttr} onChange={e => change(e.target.value)}>
          <option value="">-- 선택 --</option>
          {Object.entries(options ?? {}).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
      );
    case 'multiselect_native':
    case 'multiselect_searchable':
      return (
        <select name={`${name}[]`} className="form-select" multiple disabled={roAttr}
          onChange={e => {
            const selected = Array.from(e.target.selectedOptions).map(o => o.value);
            change(selected);
          }}>
          {Object.entries(options ?? {}).map(([k, v]) => (
            <option key={k} value={k}>{v}</option>
          ))}
        </select>
      );
    case 'relation':
    case 'relation_nton':
    case 'dependent':
      return (
        <select
          name={type === 'relation_nton' ? `${name}[]` : name}
          className="form-select"
          multiple={type === 'relation_nton'}
          required={req}
          disabled={roAttr}
          data-endpoint={`${endpoint}?action=relation&field=${name}`}
          onChange={e => change(e.target.value)}>
          <option value="">-- 선택 --</option>
        </select>
      );
    case 'upload_file':
      return (
        <input type="file" name={name} className={cls} disabled={roAttr}
          onChange={e => change(e.target.files?.[0])} />
      );
    case 'wysiwyg':
      return (
        <textarea name={name} className={cls} rows={8} required={req}
          readOnly={roAttr} value={str} onChange={e => change(e.target.value)} />
      );
    case 'readonly':
      return <p className="form-control-plaintext">{str}</p>;
    case 'hidden':
      return <input type="hidden" name={name} value={str} />;
    case 'invisible':
    case 'virtual':
      return null;
    default:
      return (
        <input type="text" name={name} className={cls} value={str}
          required={req} readOnly={roAttr} onChange={e => change(e.target.value)} />
      );
  }
}
