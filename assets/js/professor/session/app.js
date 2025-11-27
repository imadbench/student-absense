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

// Track current sort mode
let currentSortMode = null;

// Current session ID
let currentSessionId = null;

// Function to get current sessions data (passed from PHP or updated dynamically)
function getCurrentSessions() {
    console.log('getCurrentSessions called');
    console.log('window.SESSIONS_DATA:', typeof window !== 'undefined' ? window.SESSIONS_DATA : 'undefined');
    console.log('window.currentSessions:', typeof window !== 'undefined' ? window.currentSessions : 'undefined');
    console.log('SESSIONS_DATA:', typeof SESSIONS_DATA !== 'undefined' ? SESSIONS_DATA : 'undefined');
    
    // Check window.SESSIONS_DATA first as it's the initial data from PHP
    if (typeof window !== 'undefined' && window.SESSIONS_DATA && window.SESSIONS_DATA.length > 0) {
        console.log('Returning window.SESSIONS_DATA');
        return window.SESSIONS_DATA;
    }
    // Then check window.currentSessions for updated data
    if (typeof window !== 'undefined' && window.currentSessions && window.currentSessions.length > 0) {
        console.log('Returning window.currentSessions');
        return window.currentSessions;
    }
    // Finally check the global SESSIONS_DATA variable
    if (typeof SESSIONS_DATA !== 'undefined') {
        console.log('Returning SESSIONS_DATA');
        return SESSIONS_DATA;
    }
    // Return empty array as fallback
    console.log('Returning empty array');
    return [];
}

// Function to refresh session data
function refreshSessionData() {
    console.log('refreshSessionData called');
    currentSessions = getCurrentSessions();
    console.log('Updated currentSessions:', currentSessions);
    
    // Also update the global window.currentSessions and window.SESSIONS_DATA to ensure consistency
    if (typeof window !== 'undefined') {
        window.currentSessions = currentSessions;
        window.SESSIONS_DATA = currentSessions;
        console.log('Updated global variables');
    }
}

// Periodically check for session data updates
// Only start checking after DOM is ready
let periodicCheckStarted = false;

function startPeriodicCheck() {
    if (periodicCheckStarted) return;
    periodicCheckStarted = true;
    
    setInterval(() => {
        const newSessions = getCurrentSessions();
        console.log('Periodic check - currentSessions:', currentSessions);
        console.log('Periodic check - newSessions:', newSessions);
        if (JSON.stringify(newSessions) !== JSON.stringify(currentSessions)) {
            console.log('Session data updated, refreshing...');
            currentSessions = newSessions;
            
            // Also update the global window.currentSessions and window.SESSIONS_DATA to ensure consistency
            if (typeof window !== 'undefined') {
                window.currentSessions = newSessions;
                window.SESSIONS_DATA = newSessions;
                console.log('Updated global variables in periodic check');
            }
        }
    }, 1000);
}

// Start periodic check when DOM is ready
window.addEventListener('DOMContentLoaded', () => {
    startPeriodicCheck();
});

// Current sessions data
// Initialize with empty array and update when DOM is ready
let currentSessions = [];

// Dark Mode Manager
const DarkModeManager = {
  init() {
    // Check localStorage for saved preference
    const savedMode = localStorage.getItem('darkMode');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedMode === 'true' || (savedMode === null && prefersDark)) {
      this.enable();
    }
    
    // Attach listener
    QS('#btn-dark-mode').addEventListener('click', () => this.toggle());
  },
  
  enable() {
    document.body.classList.add('dark-mode');
    localStorage.setItem('darkMode', 'true');
    QS('#btn-dark-mode').textContent = '☀️';
  },
  
  disable() {
    document.body.classList.remove('dark-mode');
    localStorage.setItem('darkMode', 'false');
    QS('#btn-dark-mode').textContent = '🌙';
  },
  
  toggle() {
    if (document.body.classList.contains('dark-mode')) {
      this.disable();
    } else {
      this.enable();
    }
  }
};

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

  // Sessions based on currentSessions
  const sessionCount = (getCurrentSessions() && getCurrentSessions().length) || 6;
  for (let i = 0; i < sessionCount; i++) {
    const td = document.createElement('td');
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    // Use the session data if available, otherwise use the default
    cb.checked = stu.sessions && i < stu.sessions.length ? !!stu.sessions[i] : false;
    cb.ariaLabel = `Session ${i + 1}`;
    cb.addEventListener('change', () => applyRowStatus(tr));
    td.appendChild(cb);
    tr.appendChild(td);
  }

  // Participation based on currentSessions
  for (let i = 0; i < sessionCount; i++) {
    const td = document.createElement('td');
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    // Use the participation data if available, otherwise use the default
    cb.checked = stu.parts && i < stu.parts.length ? !!stu.parts[i] : false;
    cb.ariaLabel = `Participation ${i + 1}`;
    cb.addEventListener('change', () => applyRowStatus(tr));
    td.appendChild(cb);
    tr.appendChild(td);
  }

  // Absences, Participations, Message
  tr.appendChild(mkTd('<span class="absences">0</span>'));
  tr.appendChild(mkTd('<span class="parts">0</span>'));
  tr.appendChild(mkTd('<span class="message"></span>'));

  return tr;
}

function applyRowStatus(tr) {
  const cells = QSA('td', tr);
  const sessionCount = (getCurrentSessions() && getCurrentSessions().length) || 6;
  const sessionCbs = cells.slice(4, 4 + sessionCount).map(td => td.querySelector('input')); // Sessions
  const partCbs = cells.slice(4 + sessionCount, 4 + sessionCount * 2).map(td => td.querySelector('input'));   // Participation

  const attended = sessionCbs.filter(cb => cb && cb.checked).length;
  const absences = sessionCount - attended;
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
  const sessionCount = (getCurrentSessions() && getCurrentSessions().length) || 6;
  const emptyBooleans = Array(sessionCount).fill(false);
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
  const sessionCount = (getCurrentSessions() && getCurrentSessions().length) || 6;
  const total = rows.length;
  let present = 0, participated = 0;
  rows.forEach(tr => {
    const abs = parseInt(QS('.absences', tr).textContent || '0', 10);
    const parts = parseInt(QS('.parts', tr).textContent || '0', 10);
    if (abs < sessionCount) present += 1; // attended at least one session
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
      backgroundColor: ['#0e79f4ff','rgba(13, 247, 98, 1)','#fb0d0dff']
    }]
  };

  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(ctx, { type: 'bar', data, options: { responsive: true, plugins: { legend: { display: false } } } });
}

// Function to save attendance and participation data
function saveAttendanceAndParticipation() {
  console.log('Save function called');
  
  // Check if a session is selected
  if (!window.currentSessionId) {
    alert('Please select a session first.');
    return;
  }
  
  console.log('Current session ID:', window.currentSessionId);

  // Collect attendance and participation data for the current session only
  const attendanceData = {};
  const participationData = {};

  const rows = QSA('#attendance-table tbody tr');
  console.log('Number of rows:', rows.length);
  
  // Get session count from currentSessions or SESSIONS_DATA
  const sessionCount = (getCurrentSessions() && getCurrentSessions().length) || 
                      (typeof window !== 'undefined' && window.SESSIONS_DATA && window.SESSIONS_DATA.length) || 6;
  
  console.log('Session count:', sessionCount);
  console.log('Current sessions:', getCurrentSessions());
  console.log('SESSIONS_DATA:', typeof window !== 'undefined' ? window.SESSIONS_DATA : SESSIONS_DATA);
  
  // Find the index of the current session
  let currentSessionIndex = -1;
  if (getCurrentSessions() && getCurrentSessions().length) {
    currentSessionIndex = getCurrentSessions().findIndex(s => parseInt(s.session_id) === parseInt(window.currentSessionId));
  }
  
  if (currentSessionIndex === -1 && typeof window !== 'undefined' && window.SESSIONS_DATA && window.SESSIONS_DATA.length) {
    currentSessionIndex = window.SESSIONS_DATA.findIndex(s => parseInt(s.session_id) === parseInt(window.currentSessionId));
  }
  
  if (currentSessionIndex === -1) {
    console.error('Current session not found in sessions data');
    alert('Error: Current session not found');
    return;
  }
  
  console.log('Current session index:', currentSessionIndex);
  
  rows.forEach(row => {
    const studentId = row.dataset.id;
    console.log('Processing student ID:', studentId);
    
    if (!studentId) return;

    // Get all cells for this row
    const cells = Array.from(row.querySelectorAll('td'));
    
    // Get the checkbox for the current session ONLY
    const sessionCell = cells[4 + currentSessionIndex]; // 4 for id, last_name, first_name, course
    const sessionCheckbox = sessionCell ? sessionCell.querySelector('input') : null;
    
    // Determine attendance status based on the current session's checkbox ONLY
    let status = 'absent';
    if (sessionCheckbox && sessionCheckbox.checked) {
      status = 'present';
    }
    
    console.log('Student', studentId, 'status for CURRENT session', window.currentSessionId, '(index:', currentSessionIndex, '):', status);
    attendanceData[studentId] = status;

    // Get participation checkbox for the CURRENT session ONLY
    const participationCell = cells[4 + sessionCount + currentSessionIndex]; // After sessions
    const participationCheckbox = participationCell ? participationCell.querySelector('input') : null;
    
    // Collect participation data for the CURRENT session ONLY
    const participations = [];
    if (participationCheckbox && participationCheckbox.checked) {
      // Use a fixed participation type for all sessions
      participations.push('participation');
    }
    
    console.log('Student', studentId, 'participations for CURRENT session', window.currentSessionId, ':', participations);
    participationData[studentId] = participations;
  });
  
  console.log('Attendance data to send:', attendanceData);
  console.log('Participation data to send:', participationData);
  console.log('Sending data to server...');

  // Send data to server
  fetch('../backend/api/attendance.php?_t=' + Date.now(), {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action: 'save_attendance_participation',
      session_id: window.currentSessionId,
      attendance: JSON.stringify(attendanceData),
      participation: JSON.stringify(participationData)
    })
  })
  .then(response => response.json())
  .then(data => {
    console.log('Save response:', data);
    if (data.success) {
      // Reload the sessions and attendance data to reflect the changes
      console.log('Checking for global loadSessions function...');
      console.log('window.loadSessions type:', typeof window.loadSessions);
      console.log('loadSessions type:', typeof loadSessions);
      
      if (typeof window.loadSessions === 'function') {
        console.log('Reloading sessions data using global function...');
        window.loadSessions();
            
        // After a short delay to ensure sessions are loaded, reload attendance data
        setTimeout(() => {
          console.log('Refreshing session data...');
          // Refresh the currentSessions variable to use the latest data
          refreshSessionData();
              
          // Also update the global window.currentSessions and window.SESSIONS_DATA to ensure consistency
          if (typeof window !== 'undefined') {
            const updatedSessions = getCurrentSessions();
            window.currentSessions = updatedSessions;
            window.SESSIONS_DATA = updatedSessions;
            console.log('Updated global session data:', updatedSessions);
          }
              
          // Wait a bit more to ensure the global variables are properly updated
          setTimeout(() => {
            console.log('Checking for global loadAllSessionsAttendance function...');
            console.log('window.loadAllSessionsAttendance type:', typeof window.loadAllSessionsAttendance);
            console.log('loadAllSessionsAttendance type:', typeof loadAllSessionsAttendance);
            
            if (typeof window.loadAllSessionsAttendance === 'function') {
              console.log('Reloading attendance data using global function...');
              window.loadAllSessionsAttendance();
                  
              // Show success message after all data is reloaded
              setTimeout(() => {
                alert('Attendance and participation saved successfully!');
              }, 1000);
            } else if (typeof loadAllSessionsAttendance === 'function') {
              console.log('Reloading attendance data using local function...');
              loadAllSessionsAttendance();
                  
              // Show success message after all data is reloaded
              setTimeout(() => {
                alert('Attendance and participation saved successfully!');
              }, 1000);
            } else {
              console.log('No loadAllSessionsAttendance function available');
              alert('Attendance and participation saved successfully!');
            }
          }, 500);
        }, 1500);
      } else if (typeof loadSessions === 'function') {
        console.log('Global loadSessions function not available, using local function...');
        console.log('Reloading sessions data...');
        loadSessions();
              
        // After a short delay to ensure sessions are loaded, reload attendance data
        setTimeout(() => {
          console.log('Refreshing session data...');
          // Refresh the currentSessions variable to use the latest data
          refreshSessionData();
                
          // Also update the global window.currentSessions and window.SESSIONS_DATA to ensure consistency
          if (typeof window !== 'undefined') {
            const updatedSessions = getCurrentSessions();
            window.currentSessions = updatedSessions;
            window.SESSIONS_DATA = updatedSessions;
            console.log('Updated global session data:', updatedSessions);
          }
                
          // Wait a bit more to ensure the global variables are properly updated
          setTimeout(() => {
            console.log('Checking for loadAllSessionsAttendance function...');
            console.log('loadAllSessionsAttendance type:', typeof loadAllSessionsAttendance);
            
            if (typeof loadAllSessionsAttendance === 'function') {
              console.log('Reloading attendance data...');
              loadAllSessionsAttendance();
                    
              // Show success message after all data is reloaded
              setTimeout(() => {
                alert('Attendance and participation saved successfully!');
              }, 1000);
            } else {
              console.log('No loadAllSessionsAttendance function available');
              alert('Attendance and participation saved successfully!');
            }
          }, 500);
        }, 1500);
      } else {
        console.log('No loadSessions function available');
        // If loadSessions is not available, show success message immediately
        alert('Attendance and participation saved successfully!');
      }
    } else {
      alert('Error saving data: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Save error:', error);
    alert('Error saving attendance and participation data.');
  });
}

function attachJQueryInteractions() {
  console.log('Attaching jQuery interactions');
  
  // Test if save button exists
  const saveButton = document.getElementById('btn-save-session');
  console.log('Save button found:', saveButton);
  
  // Hover highlight
  $('#attendance-table tbody').on('mouseenter', 'tr', function(){ $(this).addClass('row-hover'); });
  $('#attendance-table tbody').on('mouseleave', 'tr', function(){ $(this).removeClass('row-hover'); });

  // Click row (but not on checkboxes) -> show student information in alert
  $('#attendance-table tbody').on('click', 'tr', function(e){
    // Don't open alert if clicking on checkbox
    if ($(e.target).is('input[type="checkbox"]')) {
      return;
    }

    const id = this.dataset.id;
    const firstName = this.dataset.firstName;
    const lastName = this.dataset.lastName;
    const course = $(this).find('td:eq(3)').text();
    const absences = $(this).find('.absences').text();
    const participation = $(this).find('.parts').text();
    const message = $(this).find('.message').text();

    // Get session count from currentSessions or SESSIONS_DATA
    const sessionCount = (getCurrentSessions() && getCurrentSessions().length) || 
                        (typeof window !== 'undefined' && window.SESSIONS_DATA && window.SESSIONS_DATA.length) || 6;

    const cells = QSA('td', this);
    const sessionCbs = cells.slice(4, 4 + sessionCount).map(td => td.querySelector('input'));
    const partCbs = cells.slice(4 + sessionCount, 4 + sessionCount * 2).map(td => td.querySelector('input'));

    const sessionsAttended = sessionCbs.filter(cb => cb && cb.checked).map((_, idx) => `S${idx + 1}`).join(', ') || 'None';
    const participations = partCbs.filter(cb => cb && cb.checked).map((_, idx) => `P${idx + 1}`).join(', ') || 'None';

    // Modern notification approach - show in a formatted message
    const alertMessage = `
📋 STUDENT INFORMATION
═══════════════════════════════════
👤 Name: ${firstName} ${lastName}
🆔 ID: ${id}
📚 Course: ${course}

📊 ATTENDANCE DETAILS
───────────────────────────────────
❌ Absences: ${absences}
💬 Participation Count: ${participation}
✓ Sessions Attended: ${sessionsAttended}
💭 Participated in: ${participations}

📌 STATUS: ${message}
═══════════════════════════════════`;

    alert(alertMessage);
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

  // Search by Name functionality
  $('#searchStudents').on('keyup', function(){
    const searchTerm = $(this).val().toLowerCase();
    
    if (searchTerm === '') {
      // Show all rows
      $('#attendance-table tbody tr').css('display', '');
    } else {
      // Filter rows based on first name or last name
      $('#attendance-table tbody tr').each(function(){
        const firstName = this.dataset.firstName ? this.dataset.firstName.toLowerCase() : '';
        const lastName = this.dataset.lastName ? this.dataset.lastName.toLowerCase() : '';
        const matches = firstName.includes(searchTerm) || lastName.includes(searchTerm);
        $(this).css('display', matches ? '' : 'none');
      });
    }
  });

  // Sort by Absences (Ascending)
  $('#btn-sort-absences').on('click', function(){
    const rows = $('#attendance-table tbody tr').get();
    rows.sort(function(a, b){
      const absA = parseInt($('.absences', a).text() || '0', 10);
      const absB = parseInt($('.absences', b).text() || '0', 10);
      return absA - absB; // ascending
    });
    
    // Clear the table body and append sorted rows correctly
    const tbody = $('#attendance-table tbody');
    tbody.empty();
    $.each(rows, function(index, row){
      // Clone the row to reset any lingering styles
      const clonedRow = $(row).clone()[0];
      tbody.append(clonedRow);
      // Reapply status styling to ensure consistency
      applyRowStatus(clonedRow);
    });
    
    currentSortMode = 'absences-asc';
    updateSortStatusMessage();
  });

  // Sort by Participation (Descending)
  $('#btn-sort-participation').on('click', function(){
    const rows = $('#attendance-table tbody tr').get();
    rows.sort(function(a, b){
      const partsA = parseInt($('.parts', a).text() || '0', 10);
      const partsB = parseInt($('.parts', b).text() || '0', 10);
      return partsB - partsA; // descending
    });
    
    // Clear the table body and append sorted rows correctly
    const tbody = $('#attendance-table tbody');
    tbody.empty();
    $.each(rows, function(index, row){
      // Clone the row to reset any lingering styles
      const clonedRow = $(row).clone()[0];
      tbody.append(clonedRow);
      // Reapply status styling to ensure consistency
      applyRowStatus(clonedRow);
    });
    
    currentSortMode = 'participation-desc';
    updateSortStatusMessage();
  });

  // Save attendance and participation
  $('#btn-save-session').on('click', function(){
    console.log('Save button clicked');
    saveAttendanceAndParticipation();
  });
}

function updateSortStatusMessage() {
  const messageEl = $('#sortStatusMessage');

  if (currentSortMode === null) {
    messageEl.removeClass('visible').text('');
  } else if (currentSortMode === 'absences-asc') {
    messageEl.addClass('visible').text('Currently sorted by absences (ascending)');
  } else if (currentSortMode === 'participation-desc') {
    messageEl.addClass('visible').text('Currently sorted by participation (descending)');
  }
}

window.addEventListener('DOMContentLoaded', () => {
  // Initialize currentSessions with the actual session data
  currentSessions = getCurrentSessions();
  console.log('Initialized currentSessions in DOM ready:', currentSessions);
  
  // Initialize dark mode
  DarkModeManager.init();
  
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