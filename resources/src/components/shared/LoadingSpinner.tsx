interface Props {
  size?: 'sm' | 'md' | 'lg';
}

export function LoadingSpinner({ size = 'md' }: Props) {
  const sizeClass = size === 'sm' ? 'spinner-border-sm' : '';
  return (
    <div className="d-flex justify-content-center py-4">
      <div className={`spinner-border ${sizeClass} text-primary`} role="status">
        <span className="visually-hidden">로딩 중...</span>
      </div>
    </div>
  );
}
