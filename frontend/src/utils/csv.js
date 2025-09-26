export function downloadCsv(filename, rows, columns) {
  const header = columns.map(c => `"${c.label.replace(/"/g,'""')}"`).join(',') + '\n';
  const lines = rows.map(row => columns.map(c => {
    const v = row[c.key] == null ? '' : String(row[c.key]);
    return `"${v.replace(/"/g,'""')}"`;
  }).join(',')).join('\n');
  const csv = header + lines;
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
