interface Props {
  errors: Record<string, string>;
}

export function ValidationErrors({ errors }: Props) {
  const messages = Object.values(errors);
  if (messages.length === 0) return null;

  return (
    <div className="alert alert-danger">
      <ul className="mb-0 ps-3">
        {messages.map((msg, i) => (
          <li key={i}>{msg}</li>
        ))}
      </ul>
    </div>
  );
}
