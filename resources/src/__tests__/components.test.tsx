import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { LoadingSpinner } from '../components/shared/LoadingSpinner';
import { ValidationErrors } from '../components/shared/ValidationErrors';
import { BulkActionBar } from '../components/DataGrid/BulkActionBar';
import { FieldRenderer } from '../components/Form/FieldRenderer';
import type { FormField } from '../api/types';

describe('LoadingSpinner', () => {
  it('스피너 엘리먼트를 렌더링한다', () => {
    render(<LoadingSpinner />);
    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('로딩 중 텍스트를 포함한다', () => {
    render(<LoadingSpinner />);
    expect(screen.getByText('로딩 중...')).toBeInTheDocument();
  });
});

describe('ValidationErrors', () => {
  it('에러가 없으면 null을 반환한다', () => {
    const { container } = render(<ValidationErrors errors={{}} />);
    expect(container.firstChild).toBeNull();
  });

  it('에러 메시지를 리스트로 렌더링한다', () => {
    render(
      <ValidationErrors
        errors={{ email: '이메일이 필요합니다.', name: '이름이 필요합니다.' }}
      />
    );
    expect(screen.getByText('이메일이 필요합니다.')).toBeInTheDocument();
    expect(screen.getByText('이름이 필요합니다.')).toBeInTheDocument();
  });

  it('alert-danger 클래스를 사용한다', () => {
    render(<ValidationErrors errors={{ msg: '오류' }} />);
    expect(document.querySelector('.alert-danger')).toBeInTheDocument();
  });
});

describe('BulkActionBar', () => {
  it('선택된 항목이 없으면 null을 반환한다', () => {
    const { container } = render(
      <BulkActionBar selectedCount={0} onDeleteSelected={() => {}} />
    );
    expect(container.firstChild).toBeNull();
  });

  it('선택된 항목 수를 표시한다', () => {
    render(<BulkActionBar selectedCount={3} onDeleteSelected={() => {}} />);
    expect(screen.getByText(/3건 선택됨/)).toBeInTheDocument();
  });

  it('삭제 버튼을 렌더링한다', () => {
    render(<BulkActionBar selectedCount={2} onDeleteSelected={() => {}} />);
    expect(screen.getByText('선택 삭제')).toBeInTheDocument();
  });
});

describe('FieldRenderer', () => {
  const makeField = (type: FormField['type'], extras: Partial<FormField> = {}): FormField => ({
    field: 'test', title: '테스트', type, required: false, readonly: false, ...extras,
  });

  it('string 타입 — text input 렌더링', () => {
    render(
      <FieldRenderer
        field={makeField('string')}
        value="홍길동"
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(screen.getByDisplayValue('홍길동')).toBeInTheDocument();
  });

  it('textarea 타입 렌더링', () => {
    render(
      <FieldRenderer
        field={makeField('textarea')}
        value="본문 내용"
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(screen.getByText('본문 내용')).toBeInTheDocument();
  });

  it('boolean 타입 — checkbox 렌더링', () => {
    render(
      <FieldRenderer
        field={makeField('boolean')}
        value={true}
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    const checkbox = document.querySelector('input[type="checkbox"]');
    expect(checkbox).toBeInTheDocument();
    expect(checkbox).toBeChecked();
  });

  it('dropdown 타입 — select 렌더링', () => {
    render(
      <FieldRenderer
        field={makeField('dropdown', { options: { a: '활성', b: '비활성' } })}
        value="a"
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(screen.getByText('활성')).toBeInTheDocument();
    expect(screen.getByText('비활성')).toBeInTheDocument();
  });

  it('email 타입 — email input 렌더링', () => {
    const { container } = render(
      <FieldRenderer
        field={makeField('email')}
        value="test@example.com"
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(container.querySelector('input[type="email"]')).toBeInTheDocument();
  });

  it('hidden 타입 — hidden input 렌더링', () => {
    const { container } = render(
      <FieldRenderer
        field={makeField('hidden')}
        value="secret"
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(container.querySelector('input[type="hidden"]')).toBeInTheDocument();
  });

  it('invisible 타입 — null 반환', () => {
    const { container } = render(
      <FieldRenderer
        field={makeField('invisible')}
        value=""
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(container.firstChild).toBeNull();
  });

  it('readonly 모드에서 input이 disabled', () => {
    render(
      <FieldRenderer
        field={makeField('string')}
        value="읽기전용"
        endpoint="/crud/test"
        readonly={true}
        onChange={() => {}}
      />
    );
    expect(screen.getByDisplayValue('읽기전용')).toHaveAttribute('readOnly');
  });

  it('upload_file 타입 — file input 렌더링', () => {
    const { container } = render(
      <FieldRenderer
        field={makeField('upload_file')}
        value=""
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(container.querySelector('input[type="file"]')).toBeInTheDocument();
  });

  it('color 타입 — color input 렌더링', () => {
    const { container } = render(
      <FieldRenderer
        field={makeField('color')}
        value=""
        endpoint="/crud/test"
        readonly={false}
        onChange={() => {}}
      />
    );
    expect(container.querySelector('input[type="color"]')).toBeInTheDocument();
  });
});
