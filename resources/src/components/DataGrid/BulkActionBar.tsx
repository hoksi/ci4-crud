interface Props {
  selectedCount:  number;
  onDeleteSelected: () => void;
}

export function BulkActionBar({ selectedCount, onDeleteSelected }: Props) {
  if (selectedCount === 0) return null;

  return (
    <div className="alert alert-warning d-flex align-items-center gap-3 py-2">
      <span className="fw-semibold">{selectedCount}건 선택됨</span>
      <button
        type="button"
        className="btn btn-sm btn-danger"
        onClick={onDeleteSelected}
      >
        선택 삭제
      </button>
    </div>
  );
}
