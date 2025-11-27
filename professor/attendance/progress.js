// Update attendance progress bar
function updateAttendanceProgress() {
  const rows = QSA('#attendance-table tbody tr');
  const totalCells = rows.length * 12; // 6 sessions + 6 participation per student
  
  let checkedCells = 0;
  rows.forEach(tr => {
    const cells = QSA('td', tr);
    const sessionCbs = cells.slice(4, 10).map(td => td.querySelector('input'));
    const partCbs = cells.slice(10, 16).map(td => td.querySelector('input'));
    
    checkedCells += sessionCbs.filter(cb => cb && cb.checked).length;
    checkedCells += partCbs.filter(cb => cb && cb.checked).length;
  });
  
  const percentage = totalCells > 0 ? Math.round((checkedCells / totalCells) * 100) : 0;
  const progressBar = QS('#attendance-progress');
  const progressText = QS('#progress-text');
  
  if (progressBar) progressBar.value = percentage;
  if (progressText) progressText.textContent = percentage + '%';
}
