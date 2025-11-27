// Data model for initial rows - will be replaced with real data
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

// Get course ID from URL parameters
function getCourseId() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('course_id') || 1; // Default to 1 if not provided
}

// Fetch real student data from API
async function loadRealStudentData() {
  try {
    const courseId = getCourseId();
    console.log('Loading student data for course ID:', courseId);
    
    // First, get student data
    const formData = new FormData();
    formData.append('action', 'get_students');
    formData.append('course_id', courseId);
    
    const response = await fetch('api.php', {
      method: 'POST',
      body: formData
    });
    console.log('Response status:', response.status);
    
    const data = await response.json();
    console.log('Response data:', data);
    
    if (data.success) {
      console.log('Loaded real student data:', data.students);
      // Set the course name in the HTML
      if (data.students.length > 0 && data.students[0].course) {
        const courseNameElement = document.getElementById('course-name');
        if (courseNameElement) {
          courseNameElement.textContent = data.students[0].course;
        }
      }
      return data.students;
    } else {
      console.error('Failed to load student data:', data.message);
      return initialStudents; // Fallback to initial data
    }
  } catch (error) {
    console.error('Error loading student data:', error);
    return initialStudents; // Fallback to initial data
  }
}

// New function to load attendance and participation data from all sessions
async function loadAllSessionsData(courseId) {
  try {
    // Use GET request with query parameters
    const url = `../backend/api/attendance.php?action=get_all_sessions_attendance&course_id=${courseId}`;
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      return data;
    } else {
      console.error('Failed to load sessions data:', data.message);
      return null;
    }
  } catch (error) {
    console.error('Error loading sessions data:', error);
    return null;
  }
}

// Modified function to render table rows with saved data
async function renderTableRowsWithSavedData() {
  const courseId = getCourseId();
  
  // Load student data
  const students = await loadRealStudentData();
  
  // Load attendance and participation data
  const sessionsData = await loadAllSessionsData(courseId);
  
  if (sessionsData && sessionsData.students && sessionsData.sessions) {
    // Create lookup for student data by student_id
    const studentLookup = {};
    sessionsData.students.forEach(student => {
      studentLookup[student.student_id] = student;
    });
    
    // Update student objects with actual attendance/participation data
    const updatedStudents = students.map(student => {
      const studentData = studentLookup[student.id];
      if (studentData) {
        // Initialize arrays
        const sessionsArray = new Array(6).fill(false);
        const partsArray = new Array(6).fill(false);
        
        // Fill with actual data
        studentData.sessions.forEach(session => {
          const sessionIndex = session.session_number - 1; // Convert to 0-based index
          if (sessionIndex >= 0 && sessionIndex < 6) {
            // Set attendance (true if present or late)
            sessionsArray[sessionIndex] = (session.status === 'present' || session.status === 'late');
            
            // Set participation (true if any participation records)
            partsArray[sessionIndex] = session.participations && session.participations.length > 0;
          }
        });
        
        return {
          ...student,
          sessions: sessionsArray,
          parts: partsArray
        };
      }
      return student;
    });
    
    renderTableRows(updatedStudents);
  } else {
    // Fallback to original rendering if no saved data
    renderTableRows(students);
  }
}

// Add student to database
async function addStudentToDatabase(studentData) {
  try {
    const courseId = getCourseId();
    const formData = new FormData();
    formData.append('action', 'add_student');
    formData.append('course_id', courseId);
    formData.append('student_id', studentData.id);
    formData.append('first_name', studentData.firstName);
    formData.append('last_name', studentData.lastName);
    formData.append('email', studentData.email);
    
    const response = await fetch('api.php', {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      return { success: true, message: data.message };
    } else {
      return { success: false, message: data.message };
    }
  } catch (error) {
    console.error('Error adding student:', error);
    return { success: false, message: 'Error adding student: ' + error.message };
  }
}

const QS = (sel, root = document) => root.querySelector(sel);
const QSA = (sel, root = document) => Array.from(root.querySelectorAll(sel));

// Track current sort mode
let currentSortMode = null;

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

  // Sessions S1..S6
  if (stu.sessions && Array.isArray(stu.sessions)) {
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
  } else {
    // Fallback if sessions data is missing
    for (let i = 0; i < 6; i++) {
      const td = document.createElement('td');
      const cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.checked = false;
      cb.ariaLabel = `Session ${i + 1}`;
      cb.addEventListener('change', () => applyRowStatus(tr));
      td.appendChild(cb);
      tr.appendChild(td);
    }
  }

  // Participation P1..P6
  if (stu.parts && Array.isArray(stu.parts)) {
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
  } else {
    // Fallback if parts data is missing
    for (let i = 0; i < 6; i++) {
      const td = document.createElement('td');
      const cb = document.createElement('input');
      cb.type = 'checkbox';
      cb.checked = false;
      cb.ariaLabel = `Participation ${i + 1}`;
      cb.addEventListener('change', () => applyRowStatus(tr));
      td.appendChild(cb);
      tr.appendChild(td);
    }
  }

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
      backgroundColor: ['#0e79f4ff','rgba(13, 247, 98, 1)','#fb0d0dff']
    }]
  };

  if (chartInstance) chartInstance.destroy();
  chartInstance = new Chart(ctx, { type: 'bar', data, options: { responsive: true, plugins: { legend: { display: false } } } });
}

function attachJQueryInteractions() {
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
    
      // Get session checkboxes
      const cells = QSA('td', this);
      const sessionCbs = cells.slice(4, 10).map(td => td.querySelector('input'));
      const partCbs = cells.slice(10, 16).map(td => td.querySelector('input'));
    
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
  $('#searchInput').on('keyup', function(){
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

window.addEventListener('DOMContentLoaded', async () => {
  // Initialize dark mode
  DarkModeManager.init();
  
  // Load real student data with saved attendance and participation
  console.log('Starting to load student data with saved attendance...');
  await renderTableRowsWithSavedData();

  // Add Save Button Event Listener
  const saveButton = document.createElement('button');
  saveButton.textContent = '💾 Save Attendance & Participation';
  saveButton.className = 'btn btn-primary';
  saveButton.style.margin = '10px';
  saveButton.addEventListener('click', saveAttendanceData);
  
  // Insert save button after the section header in the attendance section
  const attendanceSection = document.getElementById('attendance');
  const sectionHeader = attendanceSection.querySelector('.section-header');
  sectionHeader.parentNode.insertBefore(saveButton, sectionHeader.nextSibling);

  // Form submit
  QS('#add-student-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    QS('#add-success').textContent = '';
    QS('#err-studentId').textContent = '';
    QS('#err-lastName').textContent = '';
    QS('#err-firstName').textContent = '';
    QS('#err-email').textContent = '';
    
    const { ok, data } = validateForm();
    if (!ok) return; // prevent submission on validation errors

    // Add student to database
    const result = await addStudentToDatabase({
      id: data.id,
      firstName: data.firstName,
      lastName: data.lastName,
      email: data.email
    });
    
    if (result.success) {
      // Instead of just adding the new student, reload all students to ensure consistency
      await renderTableRowsWithSavedData();
      
      QS('#add-success').textContent = result.message;
      e.target.reset();
    } else {
      // Show error message
      QS('#err-studentId').textContent = result.message;
    }
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

// Function to save attendance and participation data
async function saveAttendanceData() {
  try {
    // Collect data from the table
    const rows = QSA('#attendance-table tbody tr');
    const allSessionsData = {};
    
    // Initialize data structure for all 6 sessions
    for (let i = 1; i <= 6; i++) {
      allSessionsData[i] = {
        attendance: {},
        participation: {}
      };
    }
    
    rows.forEach(row => {
      const studentId = row.dataset.id;
      const cells = QSA('td', row);
      
      // Get session checkboxes (S1-S6)
      const sessionCells = cells.slice(4, 10); // Columns 5-10 (0-indexed: 4-9)
      const sessionStatuses = sessionCells.map(cell => {
        const checkbox = cell.querySelector('input[type="checkbox"]');
        return checkbox ? checkbox.checked : false;
      });
      
      // Get participation checkboxes (P1-P6)
      const participationCells = cells.slice(10, 16); // Columns 11-16 (0-indexed: 10-15)
      const participations = participationCells.map(cell => {
        const checkbox = cell.querySelector('input[type="checkbox"]');
        return checkbox ? checkbox.checked : false;
      });
      
      // Store data for each session
      for (let sessionNumber = 1; sessionNumber <= 6; sessionNumber++) {
        // Convert session statuses to database format
        // Using 'present' for checked, 'absent' for unchecked
        allSessionsData[sessionNumber].attendance[studentId] = sessionStatuses[sessionNumber - 1] ? 'present' : 'absent';
        
        // Store participation data
        // Using 'question' for checked, no entry for unchecked
        if (participations[sessionNumber - 1]) {
          allSessionsData[sessionNumber].participation[studentId] = ['question'];
        } else {
          allSessionsData[sessionNumber].participation[studentId] = [];
        }
      }
    });
    
    // Send all data to backend at once
    const courseId = getCourseId();
    
    const formData = new FormData();
    formData.append('action', 'save_all_sessions_data');
    formData.append('course_id', courseId);
    formData.append('sessions_data', JSON.stringify(allSessionsData));
    
    const response = await fetch('../backend/api/attendance.php', {
      method: 'POST',
      body: formData
    });
    
    // Check if response is OK
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    // Get response text first to check for any unexpected output
    const responseText = await response.text();
    console.log('Raw response:', responseText);
    
    // Try to parse JSON
    let result;
    try {
      result = JSON.parse(responseText);
    } catch (parseError) {
      console.error('JSON parsing error:', parseError);
      console.error('Response text that failed to parse:', responseText);
      throw new Error('Invalid JSON response from server. Please check server logs.');
    }
    
    if (result.success) {
      alert(`✅ Attendance and participation data saved successfully! Total records: ${result.saved_records}`);
    } else {
      alert('❌ Error saving data: ' + result.message);
    }
  } catch (error) {
    console.error('Error saving attendance data:', error);
    alert('❌ Error saving attendance data: ' + error.message);
  }
}

