

export function formatDateTime(value) {
  if (!value) return '-';
  if (typeof value !== 'string') return value;
  const onlyDate = value.match(/^(\d{4}-\d{2}-\d{2})$/);
  if (onlyDate) return `${onlyDate[1].split('-').reverse().join('/')} 23:59`;
  const m = value.match(/^(\d{4}-\d{2}-\d{2})[T\s]?(\d{2}:\d{2})?/);
  if (m) return `${m[1].split('-').reverse().join('/')} ${m[2] || '00:00'}`;
  return String(value);
}

export function formatDate(value) {
  if (!value) return null;
  if (typeof value !== 'string') return null;
  const m = value.match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : null;
}
