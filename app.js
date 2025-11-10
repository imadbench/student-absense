// Data model for initial rows
const initialStudents = [
  {
    id: '20250001', lastName: 'Ahmed', firstName: 'Sara', course: 'AWP',
    sessions: [true, true, true, false, true, false],
    parts:    [true, false, true, false, true, false]
  },
  {
    id: '20250002', lastName: 'Ali', firstName: 'Yacine', course: 'AWP',
    sessions: [true, true, true, true, true, true],
    parts:    [true, true, true, true, true, true]
  },
  {
    id: '20250003', lastName: 'Houcine', firstName: 'Rania', course: 'AWP',
    sessions: [true, false, false, false, false, false],
    parts:    [false, false, false, false, false, false]
  }
];

const QS = (sel, root = document) => root.querySelector(sel);
const QSA = (sel, root = document) => Array.from(root.querySelectorAll(sel));

function renderTableRows(students) {
  const tbody = QS('#attendance-table tbody');
  tbody.innerHTML = '';
  students.forEach(stu => tbody.appendChild(buildRow(stu)));
  // Apply statuses after render
  QSA('#attendance-table tbody tr').forEach(tr => applyRowStatus(tr));
}

function buildRow(stu) {
  const tr = document.createElement('tr');
  tr.dataset.id = stu.id;
  tr.dataset.firstName = stu.firstName;
  tr.dataset.lastName = stu.lastName;

  const mkTd = (html) => { const td = document.createElement('td'); td.innerHTML = html; return td; };

  tr.appendChild(mkTd(`<span class="mono">${stu.id}</span>`));
  tr.appendChild(mkTd(stu.lastName));
  tr.appendChild(mkTd(stu.firstName));
  tr.appendChild(mkTd(stu.course || 'AWP'));

  // Sessions S1..S6
  stu.sessions.forEach((val, idx) => {
    const td = document.createElement('td');
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = !!val;
    cb.ariaLabel = `Session ${idx + 1}`;
    cb.addEventListener('change', () => applyRowStatus(tr));
    td.appendChild(cb);
    tr.appendChild(td);
  });

  // Participation P1..P6
  stu.parts.forEach((val, idx) => {
    const td = document.createElement('td');
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.checked = !!val;
    cb.ariaLabel = `Participation ${idx + 1}`;
    cb.addEventListener('change', () => applyRowStatus(tr));
    td.appendChild(cb);
    tr.appendChild(td);
  });

  // Absences, Participations, Message
  tr.appendChild(mkTd('<span class="absences">0</span>'));
  tr.appendChild(mkTd('<span class="parts">0</span>'));
  tr.appendChild(mkTd('<span class="message"></span>'));

  return tr;
}

function applyRowStatus(tr) {
  const cells = QSA('td', tr);
  const sessionCbs = cells.slice(4, 10).map(td => td.querySelector('input')); // S1..S6
  const partCbs = cells.slice(10, 16).map(td => td.querySelector('input'));   // P1..P6

  const attended = sessionCbs.filter(cb => cb && cb.checked).length;
  const absences = 6 - attended;
  const parts = partCbs.filter(cb => cb && cb.checked).length;

  QS('.absences', tr).textContent = String(absences);
  QS('.parts', tr).textContent = String(parts);

  tr.classList.remove('status-green', 'status-yellow', 'status-red');
  if (absences >= 5) tr.classList.add('status-red');
  else if (absences >= 3) tr.classList.add('status-yellow');
  else tr.classList.add('status-green');

  const msgEl = QS('.message', tr);
  let message = '';
  if (absences >= 5) message = 'Excluded – too many absences – You need to participate more';
  else if (absences >= 3) message = parts >= 3 ? 'Warning – attendance low – Good participation' : 'Warning – attendance low – You need to participate more';
  else message = parts >= 4 ? 'Good attendance – Excellent participation' : 'Good attendance – You need to participate more';
  msgEl.textContent = message;
}

function addStudentToTable({ id, lastName, firstName, course = 'AWP' }) {
  const emptyBooleans = Array(6).fill(false);
  const tr = buildRow({ id, lastName, firstName, course, sessions: emptyBooleans, parts: emptyBooleans });
  QS('#attendance-table tbody').appendChild(tr);
  applyRowStatus(tr);
}

function validateForm() {
  const id = QS('#studentId').value.trim();
  const ln = QS('#lastName').value.trim();
  const fn = QS('#firstName').value.trim();
  const em = QS('#email').value.trim();

  let ok = true;

  // Reset errors
  ['studentId','lastName','firstName','email'].forEach(f => QS(`#err-${f}`).textContent = '');

  if (!id) { QS('#err-studentId').textContent = 'Student ID is required.'; ok = false; }
  else if (!/^\d+$/.test(id)) { QS('#err-studentId').textContent = 'Student ID must contain only numbers.'; ok = false; }

  const nameRe = /^[A-Za-z]+$/;
  if (!ln) { QS('#err-lastName').textContent = 'Last Name is required.'; ok = false; }
  else if (!nameRe.test(ln)) { QS('#err-lastName').textContent = 'Last Name must contain only letters.'; ok = false; }

  if (!fn) { QS('#err-firstName').textContent = 'First Name is required.'; ok = false; }
  else if (!nameRe.test(fn)) { QS('#err-firstName').textContent = 'First Name must contain only letters.'; ok = false; }

  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!em) { QS('#err-email').textContent = 'Email is required.'; ok = false; }
  else if (!emailRe.test(em)) { QS('#err-email').textContent = 'Email format is invalid.'; ok = false; }

  return { ok, data: { id, lastName: ln, firstName: fn, email: em } };
}

let chartInstance = null;
function buildReport() {
  const rows = QSA('#attendance-table tbody tr');
  const total = rows.length;
  let present = 0, participated = 0;
  rows.forEach(tr => {
    const abs = parseInt(QS('.absences', tr).textContent || '0', 10);
    const parts = parseInt(QS('.parts', tr).textContent || '0', 10);
    if (abs < 6) present += 1; // attended at least one session
    if (parts > 0) participated += 1;
  });

  QS('#rep-total').textContent = String(total);
  QS('#rep-present').textContent = String(present);
  QS('#rep-participated').textContent = String(participated);

  const ctx = document.getElementById('reportChart');
  const data = {
    labels: ['Total', 'Present (≥1)', 'Participated (≥1)'],
    datasets: [{
      label: 'Students',
      data: [total, present, participated],
      backgroundColor: ['#93c5fd','#86efac','#fca5a5']
    }]
  };

  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(ctx, { type: 'bar', data, options: { responsive: true, plugins: { legend: { display: false } } } });
}

function attachJQueryInteractions() {
  // Hover highlight
  $('#attendance-table tbody').on('mouseenter', 'tr', function(){ $(this).addClass('row-hover'); });
  $('#attendance-table tbody').on('mouseleave', 'tr', function(){ $(this).removeClass('row-hover'); });

  // Click row -> alert full name and absences
  $('#attendance-table tbody').on('click', 'tr', function(){
    const ln = this.dataset.lastName;
    const fn = this.dataset.firstName;
    // Show a French message that includes the student's name
    alert(`Cette page est dédiée à ${fn} ${ln}`);
  });

  // Highlight excellent students (<3 absences)
  $('#btn-highlight-excellent').on('click', function(){
    $('#attendance-table tbody tr').each(function(){
      const abs = parseInt($('.absences', this).text() || '0', 10);
      if (abs < 3) {
        $(this).addClass('excellent').fadeOut(100).fadeIn(100).fadeOut(100).fadeIn(100);
      }
    });
  });

  // Reset colors
  $('#btn-reset-colors').on('click', function(){
    $('#attendance-table tbody tr').removeClass('excellent');
  });
}

window.addEventListener('DOMContentLoaded', () => {
  // Render initial table
  renderTableRows(initialStudents);

  // Form submit
  QS('#add-student-form').addEventListener('submit', (e) => {
    e.preventDefault();
    QS('#add-success').textContent = '';
    const { ok, data } = validateForm();
    if (!ok) return; // prevent submission on validation errors

    addStudentToTable({ id: data.id, lastName: data.lastName, firstName: data.firstName });

    QS('#add-success').textContent = 'Student added successfully to the table!';
    e.target.reset();
  });

  // Show report
  QS('#btn-show-report').addEventListener('click', () => {
    buildReport();
    // Scroll to reports section for convenience with offset to account for sticky navbar
    const target = document.getElementById('reports');
    if (!target) return;

    // Calculate offset (height of navbar) so the section is not hidden under sticky header
    const navbar = document.querySelector('.navbar');
    const navHeight = navbar ? navbar.getBoundingClientRect().height : 0;

    const targetY = window.scrollY + target.getBoundingClientRect().top - navHeight - 12; // extra spacing

    window.scrollTo({ top: targetY, behavior: 'smooth' });

    // After scrolling, move focus to the reports heading for accessibility
    // Use a short timeout to allow the smooth scroll to start; then focus immediately for screen readers
    target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
  });

  // jQuery interactions
  attachJQueryInteractions();
});
