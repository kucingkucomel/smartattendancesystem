<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Attendance System</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { max-width: 650px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .hidden { display: none; }
        input, button, select { width: 100%; padding: 12px; margin: 10px 0; box-sizing: border-box; border-radius: 5px; border: 1px solid #ccc;}
        button { background: #004d99; color: white; border: none; cursor: pointer; font-weight: bold; font-size: 16px; }
        button:hover { background: #003366; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #f0f0f0; }
        .alert { padding: 10px; background: #ffcccc; color: red; margin-bottom: 15px; text-align: center; font-weight: bold; border-radius: 5px; }
        .success { background: #ccffcc; color: green; }
        .key-box { background: #eee; border: 2px dashed #333; padding: 15px; font-size: 24px; font-family: monospace; letter-spacing: 2px; font-weight: bold; color: #b30000; margin: 15px 0; }
        
        .clock-hdr { font-size: 24px; font-weight: bold; text-align: center; color: #004d99; margin-bottom: 20px; padding: 10px; background: #e6f2ff; border: 2px solid #004d99; border-radius: 5px;}
        .timetable th { background: #004d99; color: white; }
        .subject-active { background-color: #e6ffe6 !important; cursor: pointer; border: 2px solid green; font-weight: bold; }
        .subject-active:hover { background-color: #ccffcc !important; }
        .subject-inactive { background-color: #f9f9f9; color: #888; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-align: center;">Secure Attendance System</h2>
    <div id="live-clock" class="clock-hdr">Loading System Time...</div>
    <div id="debug-time-panel" class="hidden" style="background: #e6f2ff; padding: 10px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px dashed #004d99;">
        <b>Testing Mode - Override Time:</b> 
        <select id="mock-day" style="width:auto; padding:5px;">
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
        </select>
        <input type="time" id="mock-time" value="10:00" style="width: auto; padding: 5px; margin: 0;">
        <button onclick="setMockTime()" style="width: auto; padding: 5px 10px; font-size: 14px;">Apply Test Time</button>
    </div>
    <div id="message" class="alert hidden"></div>

    <!-- 1. LOGIN UI -->
    <div id="login-view">
        <p style="text-align: center;">Enter your credentials to continue.</p>
        <input type="text" id="username" placeholder="Student ID (e.g. BSW01084686) or LEC123">
        <input type="password" id="password" placeholder="Password (password)">
        <button onclick="login()">Login</button>
    </div>

    <!-- 2. MFA SETUP UI -->
    <div id="mfa-setup-view" class="hidden" style="text-align: center;">
        <h3 style="color: #004d99;">Set Up Google Authenticator</h3>
        <p>1. Open the <b>Google Authenticator</b> app on your phone.<br>2. Choose <b>"Scan a QR Code"</b> or enter the setup key manually:</p>
        <img id="qr-image" src="" alt="QR Code" style="margin: 10px 0; border: 5px solid white; box-shadow: 0 0 5px grey; width: 170px; height: 170px;">
        <div class="key-box" id="secret-text" style="font-size: 16px; padding: 10px; margin-top: 5px;">LOADING KEY...</div>
        <hr>
        <p>3. Enter the 6-digit code from the app to finish setup:</p>
        <input type="text" id="setup-pin" placeholder="Enter 6-digit code (e.g. 123456)">
        <button onclick="verifyMfa('setup-pin')" style="background: green;">Verify & Save</button>
    </div>

    <!-- 3. NORMAL MFA LOGIN UI -->
    <div id="mfa-view" class="hidden" style="text-align: center;">
        <h3 style="color: #004d99;">Google Authenticator</h3>
        <p>Enter the 6-digit code from your authenticator app.</p>
        <input type="text" id="login-pin" placeholder="Enter 6-digit code">
        <button onclick="verifyMfa('login-pin')">Login</button>
    </div>

    <!-- 4. STUDENT DASHBOARD -->
    <div id="student-view" class="hidden">
        <h3>Student Dashboard</h3>
        <hr>
        <h4>My Timetable</h4>
        <p style="font-size:12px; color:#555;">Click on an active subject highlighting in green to mark your attendance.</p>
        <div id="student-timetable-container"></div>
        
        <div id="student-subject-controls" class="hidden">
            <hr>
            <h4>Mark Attendance <span id="student-active-subject" style="color:green;"></span></h4>
            <input type="text" id="attendance-token" placeholder="Enter Token provided by Lecturer">
            <button onclick="markAttendance()">Submit Attendance</button>
        </div>
        <br><br>
        <h4>View Attendance History</h4>
        <button onclick="viewHistory()" style="background:#555;">Load My History</button>
        <div id="history-data"></div>
        <br><br>
        <button onclick="logout()" style="background: #cc0000;">Logout</button>
    </div>

    <!-- 5. LECTURER DASHBOARD -->
    <div id="lecturer-view" class="hidden">
        <h3>Lecturer Dashboard</h3>
        <hr>
        <h4>My Timetable</h4>
        <p style="font-size:12px; color:#555;">Click on an active subject highlighting in green to generate tokens and manage the session.</p>
        <div id="lecturer-timetable-container"></div>
        
        <input type="hidden" id="lecturer-subject">
        
        <div id="subject-controls" class="hidden">
            <hr>
            <h4 style="color:green; border-bottom: 2px solid green; padding-bottom:5px;">Managing Subject: <span id="lecturer-active-subject"></span></h4>
            
            <h4>Generate Secure Token</h4>
            <div style="background: #e6ffe6; padding: 15px; text-align: center; font-size: 20px; font-family: monospace; border: 1px dashed green; word-break: break-all;" id="qr-display">
                Click 'Generate' to start session.
            </div>
            <p style="text-align:center; color:red; font-weight: bold;" id="timer-display"></p>
            <button onclick="startTokenGeneration()">Generate Token (Refreshes every 45s)</button>
            <br><br>

            <h4>Class Attendance List</h4>
            <div id="search-results"></div>
        </div>

        <hr>
        <h4>Global Student Enrollment Management</h4>
        <button onclick="loadEnrollmentManagement()" style="background:#006699;">Open Central Student Registry</button>
        <div id="enrollment-management" class="hidden" style="margin-top:15px; overflow-x:auto;">
             <div id="enrollment-results"></div>
        </div>

        <br><br>
        <button onclick="logout()" style="background: #cc0000;">Logout</button>
    </div>
</div>

<script>
    let currentServerDay = "";
    let serverTimeDateObj = new Date();

    function updateLiveClock() {
        if(currentServerDay == '') return;
        serverTimeDateObj.setSeconds(serverTimeDateObj.getSeconds() + 1);
        let timeStr = serverTimeDateObj.toTimeString().split(' ')[0];
        document.getElementById('live-clock').innerText = "System Time: " + currentServerDay + " | " + timeStr + " (Testing Mode)";
    }
    setInterval(updateLiveClock, 1000);

    function isTimeActive(day, startStr, endStr) {
        if(day !== currentServerDay) return false;
        let timeStr = serverTimeDateObj.toTimeString().split(' ')[0];
        return (timeStr >= startStr && timeStr <= endStr);
    }

    function showMsg(msg, type='alert') {
        let el = document.getElementById('message');
        el.className = type === 'success' ? 'alert success' : 'alert';
        el.innerText = msg;
        el.classList.remove('hidden');
        window.scrollTo(0, 0);
        setTimeout(() => el.classList.add('hidden'), 5000);
    }

    function setMockTime() {
    let fd = new FormData();
    fd.append('action', 'set_global_demo_time');
    fd.append('is_demo_mode', '1');
    fd.append('mock_day', document.getElementById('mock-day').value);

    let tVal = document.getElementById('mock-time').value;
    if (tVal.length === 5) tVal += ':00';
    fd.append('mock_time', tVal);

    fetch('api.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            showMsg("Test Time Applied! Timetable re-evaluated.", "success");
            let role = document.getElementById('student-view').classList.contains('hidden') ? 'lecturer' : 'student';
            loadTimetable(role);
            document.getElementById('subject-controls').classList.add('hidden');
            document.getElementById('student-subject-controls').classList.add('hidden');
        } else {
            showMsg(data.message || "Failed to apply test time.");
        }
    });
}

    function login() {
        let fd = new FormData();
        fd.append('action', 'login');
        fd.append('username', document.getElementById('username').value);
        fd.append('password', document.getElementById('password').value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'setup_mfa') {
                document.getElementById('login-view').classList.add('hidden');
                document.getElementById('mfa-setup-view').classList.remove('hidden');
                document.getElementById('secret-text').innerText = data.secret;
                
                let otpUrl = `otpauth://totp/SecureAttendance:${data.username}?secret=${data.secret}&issuer=UniversityApp`;
                document.getElementById('qr-image').src = `https://quickchart.io/qr?text=${encodeURIComponent(otpUrl)}&size=200`;
            } else if (data.status === 'mfa_required') {
                document.getElementById('login-view').classList.add('hidden');
                document.getElementById('mfa-view').classList.remove('hidden');
            } else {
                showMsg(data.message);
            }
        });
    }

    function verifyMfa(inputId) {
        let fd = new FormData();
        fd.append('action', 'verify_mfa');
        fd.append('pin', document.getElementById(inputId).value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById('mfa-setup-view').classList.add('hidden');
                document.getElementById('mfa-view').classList.add('hidden');
                
                if(data.role === 'student') {
                    document.getElementById('student-view').classList.remove('hidden');
                    document.getElementById('debug-time-panel').classList.remove('hidden');
                    loadTimetable('student');
                }
                if(data.role === 'lecturer') {
                    document.getElementById('lecturer-view').classList.remove('hidden');
                    document.getElementById('debug-time-panel').classList.remove('hidden');
                    loadTimetable('lecturer');
                }
                showMsg("Logged in successfully.", "success");
            } else {
                showMsg(data.message);
            }
        });
    }

    function loadTimetable(role) {
        fetch('api.php?action=get_timetable')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                currentServerDay = data.server_day;
                let tParts = data.server_time.split(':');
                serverTimeDateObj = new Date();
                serverTimeDateObj.setHours(tParts[0], tParts[1], tParts[2]);

                let html = '<table class="timetable"><tr><th>Subject</th><th>Day</th><th>Time</th><th>Status</th></tr>';
                data.data.forEach(sub => {
                    let active = isTimeActive(sub.day_of_week, sub.start_time, sub.end_time);
                    let rowCls = active ? 'subject-active' : 'subject-inactive';
                    let actionText = active ? 'ACTIVE' : 'INACTIVE';
                    let clickFunc = active ? `onclick="openSubject('${role}', '${sub.id}', '${sub.name}')"` : `onclick="showMsg('Class is not active right now.')"`;
                    
                    html += `<tr class="${rowCls}" ${clickFunc}>
                        <td><b>${sub.id}</b> - ${sub.name}</td>
                        <td>${sub.day_of_week}</td><td>${sub.start_time.substring(0,5)} - ${sub.end_time.substring(0,5)}</td>
                        <td><b>${actionText}</b></td>
                    </tr>`;
                });
                html += '</table>';
                
                if (role === 'student') document.getElementById('student-timetable-container').innerHTML = html;
                if (role === 'lecturer') document.getElementById('lecturer-timetable-container').innerHTML = html;
            }
        });
    }

    function openSubject(role, sid, sname) {
        if(role === 'student') {
            document.getElementById('student-subject-controls').classList.remove('hidden');
            document.getElementById('student-active-subject').innerText = '(' + sid + ' : ' + sname + ')';
            document.getElementById('attendance-token').focus();
        }
        if(role === 'lecturer') {
            document.getElementById('lecturer-subject').value = sid;
            document.getElementById('lecturer-active-subject').innerText = sname + ' (' + sid + ')';
            document.getElementById('subject-controls').classList.remove('hidden');
            if(tokenInterval) clearInterval(tokenInterval);
            document.getElementById('qr-display').innerText = "Click 'Generate' to start session.";
            document.getElementById('search-results').innerHTML = '';
            document.getElementById('timer-display').innerText = '';
            loadAttendance();
        }
    }

    // Student Functions
    function markAttendance() { 
        let fd = new FormData();
        fd.append('action', 'mark_attendance');
        fd.append('token', document.getElementById('attendance-token').value);
        fetch('api.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.status === 'success') showMsg(data.message, 'success'); else showMsg(data.message);
        });
    }

    function viewHistory() { 
        fetch('api.php?action=get_history')
        .then(res => res.json())
        .then(data => {
            let html = '<table><tr><th>Date</th><th>Class</th><th>Status</th></tr>';
            data.data.forEach(row => { html += `<tr><td>${row.recorded_at}</td><td>${row.class_name}</td><td>${row.status}</td></tr>`; });
            html += '</table>';
            document.getElementById('history-data').innerHTML = html;
        });
    }

    // Lecturer Functions
    let tokenInterval;
    let countdownInterval;

    function startTokenGeneration() { 
        let sid = document.getElementById('lecturer-subject').value;
        if(!sid) return showMsg("Select subject first");
        
        fetchToken();
        
        if(tokenInterval) clearInterval(tokenInterval);
        tokenInterval = setInterval(fetchToken, 45000); 
        
        if(countdownInterval) clearInterval(countdownInterval);
        let timeLeft = 45;
        document.getElementById('timer-display').innerText = `Token expires in: ${timeLeft}s`;
        countdownInterval = setInterval(() => {
            timeLeft = timeLeft <= 0 ? 45 : timeLeft - 1;
            document.getElementById('timer-display').innerText = `Token expires in: ${timeLeft}s`;
        }, 1000);
    }

    function fetchToken() {
        let fd = new FormData();
        fd.append('action', 'generate_token');
        fd.append('subject_id', document.getElementById('lecturer-subject').value);
        fetch('api.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                document.getElementById('qr-display').innerText = data.token;
            } else {
                showMsg(data.message);
                if(tokenInterval) clearInterval(tokenInterval);
            }
        });
    }

    // Loads Attendance for Opened Subject
    function loadAttendance() { 
        let sid = document.getElementById('lecturer-subject').value;
        if(!sid) return;
        fetch(`api.php?action=search_student&subject_id=${sid}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'error') return showMsg(data.message);
            let html = '<table><tr><th>Name / ID</th><th>Status</th><th>Action</th></tr>';
            data.data.forEach(row => {
                if(row.att_id == null) {
                     html += `<tr><td>${row.name} (<b>${row.student_id}</b>)</td><td colspan="2">No Session Record</td></tr>`;
                } else {
                    let selP = row.status === 'Present' ? 'selected' : '';
                    let selA = row.status === 'Absent' ? 'selected' : '';
                    let selAR = row.status === 'Absent with reason' ? 'selected' : '';
                    html += `<tr>
                        <td>${row.name} (<b>${row.student_id}</b>)</td>
                        <td>${row.status === 'Absent with reason' ? '<span style="color:orange;">Absent (R)</span>' : row.status}</td>
                        <td>
                            <select id="status_${row.att_id}" style="width: auto; padding: 5px;">
                                <option value="Present" ${selP}>Present</option>
                                <option value="Absent" ${selA}>Absent</option>
                                <option value="Absent with reason" ${selAR}>Absent (Reason)</option>
                            </select>
                            <button onclick="updateAttendance(${row.att_id})" style="width: auto; padding: 5px 10px;">Save</button>
                        </td>
                    </tr>`;
                }
            });
            html += '</table>';
            document.getElementById('search-results').innerHTML = html;
        });
    }

    function updateAttendance(att_id) { 
        let fd = new FormData();
        fd.append('action', 'update_attendance');
        fd.append('att_id', att_id);
        fd.append('status', document.getElementById(`status_${att_id}`).value);
        fetch('api.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            showMsg(data.message, 'success'); loadAttendance(); 
        });
    }

    // Global Enrollment Management
    function loadEnrollmentManagement() {
        document.getElementById('enrollment-management').classList.remove('hidden');
        fetch('api.php?action=get_all_students_enrollment')
        .then(res => res.json())
        .then(data => {
            if(data.status !== 'success') return showMsg("Failed to load enrollment data.");
            
            // Build Subject Options
            let dropdownHtml = '';
            data.lecturer_subjects.forEach(ls => {
                dropdownHtml += `<option value="${ls.id}">${ls.name} (${ls.id})</option>`;
            });

            let html = '<table><tr><th>Student</th><th>Current Enrollment Status</th><th>Target Class</th><th>Actions</th></tr>';
            
            data.students.forEach(student => {
                // Find relationships
                let myClasses = [];
                let otherSection = [];

                data.enrollments.forEach(enr => {
                    if (enr.student_id === student.id) {
                        let isMine = false;
                        data.lecturer_subjects.forEach(ls => {
                            if (ls.id === enr.subject_id) { isMine = true; myClasses.push(ls.id); }
                        });
                        if (!isMine) {
                            // Check if it's the exact same subject name
                            let sameNameAsMine = false;
                            data.lecturer_subjects.forEach(ls => {
                                if (ls.name === enr.subject_name) sameNameAsMine = true;
                            });
                            if (sameNameAsMine) {
                                otherSection.push(`${enr.subject_name} (${enr.subject_id})`);
                            }
                        }
                    }
                });

                let statusBadge = "Not Registered";
                if (myClasses.length > 0) statusBadge = `<span style="color:green;font-weight:bold;">In My Class: ${myClasses.join(', ')}</span>`;
                else if (otherSection.length > 0) statusBadge = `<span style="color:orange;">In Another Section: ${otherSection.join(', ')}</span>`;

                html += `<tr>
                    <td>${student.name}<br><small>${student.id}</small></td>
                    <td>${statusBadge}</td>
                    <td><select id="target_class_${student.id}" style="width: auto; padding: 5px;">${dropdownHtml}</select></td>
                    <td>
                        <button onclick="manageEnrollment('add', '${student.id}')" style="width:auto; padding:5px; background:green;">Add</button>
                        <button onclick="manageEnrollment('remove', '${student.id}')" style="width:auto; padding:5px; background:red;">Drop</button>
                    </td>
                </tr>`;
            });
            html += '</table>';
            document.getElementById('enrollment-results').innerHTML = html;
        });
    }

    function manageEnrollment(action, studentId) {
        let sid = document.getElementById(`target_class_${studentId}`).value;
        if(!sid) return showMsg("Please select a target class.");
        
        let fd = new FormData();
        fd.append('action', 'manage_enrollment');
        fd.append('action_type', action);
        fd.append('student_id', studentId);
        fd.append('subject_id', sid);
        
        fetch('api.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
                showMsg(data.message, 'success');
                loadEnrollmentManagement(); // refresh the global view
                
                // If the currently open class list belongs to the subject we just modified, refresh it immediately!
                if(document.getElementById('lecturer-subject').value === sid || document.getElementById('lecturer-subject').value !== '') {
                    loadAttendance(); 
                }
            } else {
                showMsg(data.message);
            }
        });
    }

    function logout() { fetch('api.php?action=logout').then(() => location.reload()); }
</script>
</body>
</html>
