// Frontend-only date formatting utilities
// formatDateTime: returns 'dd/mm/yyyy HH:mm' (date-only inputs default to 23:59)
// formatDate: returns 'YYYY-MM-DD' suitable for <input type="date">

function pad(n) { return n < 10 ? '0' + n : '' + n; }

export function formatDateTime(value) {
  if (!value) return '-';
  if (typeof value !== 'string') return value;

  // Exact ISO-like with time: YYYY-MM-DDTHH:MM or YYYY-MM-DD HH:MM or YYYY-MM-DDTHH:MM:SS
  const exact = value.match(/^(\d{4})-(\d{2})-(\d{2})[T\s](\d{2}):(\d{2})/);
  if (exact) {
    const Y = exact[1], M = exact[2], D = exact[3], H = exact[4], Min = exact[5];
    return `${D}/${M}/${Y} ${H}:${Min}`;
  }

  // Only date YYYY-MM-DD -> default to 23:59
  const onlyDate = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (onlyDate) {
    const Y = onlyDate[1], M = onlyDate[2], D = onlyDate[3];
    return `${D}/${M}/${Y} 23:59`;
  }

  // Try to extract date/time from other common formats (e.g., 'YYYY-MM-DD HH:MM:SS')
  const other = value.match(/(\d{4}-\d{2}-\d{2}).*(\d{2}):(\d{2})/);
  if (other) {
    const parts = other[1].split('-');
    const Y = parts[0], M = parts[1], D = parts[2];
    return `${D}/${M}/${Y} ${other[2]}:${other[3]}`;
  }

  // Fallback: parse with Date and display local components
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  const Y = d.getFullYear();
  const M = pad(d.getMonth() + 1);
  const D = pad(d.getDate());
  const H = pad(d.getHours());
  const Min = pad(d.getMinutes());
  return `${D}/${M}/${Y} ${H}:${Min}`;
}

export function formatDate(value) {
  if (!value) return null;
  if (typeof value !== 'string') return null;
  // If already YYYY-MM-DD
  const onlyDate = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (onlyDate) return onlyDate[0];
  // Try to extract leading date if present
  const m = value.match(/(\d{4}-\d{2}-\d{2})/);
  if (m) return m[1];
  // Fallback parse
  const d = new Date(value);
  if (isNaN(d.getTime())) return null;
  return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
}
